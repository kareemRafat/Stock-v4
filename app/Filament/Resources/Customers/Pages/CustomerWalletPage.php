<?php

namespace App\Filament\Resources\Customers\Pages;

use Filament\Panel;
use Filament\Tables;
use Filament\Actions;
use App\Models\Customer;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Summarizers\Summarizer;
use App\Filament\Resources\Customers\CustomerResource;

class CustomerWalletPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected string $view = 'filament.pages.customers.customer-wallet-page';

    public Customer $customer;

    public function mount(int $record): void
    {
        $this->customer = Customer::findOrFail($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->customer->wallet()->getQuery())
            ->emptyStateHeading('لا توجد حركات رصيد للعميل')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
                            + $rowLoop->iteration
                    )
                    ->sortable(false)
                    ->searchable(false)
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->colors([
                        // مدين (على العميل) - أحمر
                        'danger'  => 'sale',

                        // دائن (من العميل) - أخضر
                        'success' => fn(string $state): bool => in_array($state, ['payment', 'sale_return']),

                        // تسويات - برتقالي
                        'warning' => fn(string $state): bool => in_array($state, ['adjustment', 'credit_use']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sale'          => 'فاتورة مبيعات',
                        'payment'       => 'دفعة سداد',
                        'sale_return'   => 'مرتجع مبيعات',
                        'credit_use'    => 'خصم من الرصيد',
                        'adjustment'    => 'تسوية يدوية',
                        default         => $state,
                    })
                    ->weight('medium'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(locale: 'en')
                    ->suffix(' ج.م')
                    ->weight('medium')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ','))
                    ->color(function ($record) {
                        // مدين (فواتير) - أحمر
                        if ($record->type === 'sale') {
                            return 'danger';
                        }
                        // دائن (مدفوعات ومرتجعات) - أخضر
                        if (in_array($record->type, ['payment', 'sale_return', 'credit_use'])) {
                            return 'success';
                        }
                        // تسويات - برتقالي
                        return 'warning';
                    })
                    ->summarize([
                        Summarizer::make()
                            ->label('الرصيد النهائي')
                            ->visible(function (\Filament\Tables\Table $table): bool {
                                // إخفاء الملخص عند البحث
                                $livewire = $table->getLivewire();
                                $search = $livewire->search ?? $livewire->tableSearch ?? null;
                                return blank($search);
                            })
                            ->using(fn() => $this->customer->calculateBalance()) // ⬅️ استخدم دالة الموديل مباشرة
                            ->formatStateUsing(function ($state) {
                                if ($state > 0) {
                                    // مديونية على العميل (أحمر)
                                    $color = 'rose';
                                    $displayAmount = $state;
                                    $sign = '+';
                                    $label = 'مديونية على العميل';
                                } elseif ($state < 0) {
                                    // رصيد دائن للعميل (أخضر)
                                    $color = 'success';
                                    $displayAmount = abs($state);
                                    $sign = '-';
                                    $label = 'رصيد دائن للعميل';
                                } else {
                                    // متوازن (رمادي)
                                    $color = 'gray';
                                    $displayAmount = 0;
                                    $sign = '';
                                    $label = 'متوازن';
                                }

                                return view('filament.tables.columns.colored-summary', [
                                    'content' => number_format($displayAmount, 2, '.', ',') . ' ج.م',
                                    'color' => $color,
                                    'sign' => $sign,
                                    'label' => $label,
                                    'wallet_type' => 'customer',
                                ]);
                            }),
                    ]),

                TextColumn::make('invoice.invoice_number')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->default('لا يوجد')
                    ->weight('medium')
                    ->url(
                        fn($record) => $record->invoice_id
                            ? route('filament.admin.resources.invoices.view', ['record' => $record->invoice_id])
                            : null
                    )
                    ->color(fn($record) => $record->invoice_id ? 'primary' : 'gray'),

                TextColumn::make('notes')
                    ->label('ملاحظات الحركة')
                    ->default('لا يوجد')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->notes)
                    ->weight('medium'),

                TextColumn::make('createdDate')
                    ->label('تاريخ الإضافة')
                    ->weight('medium'),

                TextColumn::make('createdTime')
                    ->label('وقت الإضافة')
                    ->weight('medium'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع الحركة')
                    ->options([
                        'sale'          => 'فاتورة مبيعات',
                        'payment'       => 'دفعة سداد',
                        'sale_return'   => 'مرتجع مبيعات',
                        'credit_use'    => 'خصم من الرصيد',
                        'adjustment'    => 'تسوية يدوية',
                    ])
                    ->multiple(),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return static::generateRouteName('wallet', $panel);
    }


    public function getBreadcrumb(): string
    {
        return 'حركات الرصيد';
    }

    public function getTitle(): string
    {
        return 'رصيد العميل: ' . $this->customer->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(CustomerResource::getUrl('index')),
        ];
    }
}
