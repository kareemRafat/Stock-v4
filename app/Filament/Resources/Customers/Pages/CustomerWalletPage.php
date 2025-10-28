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
            ->emptyStateHeading('لا توجد حركات رصيد للعملاء')
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
                        // تزيد مديونية العميل
                        'danger'  => 'sale',

                        // تخفض مديونية العميلإيجابية للعميل)
                        'success' => fn(string $state): bool => in_array($state, ['payment', 'sale_return']),

                        // 'warning' (أصفر): للتسويات أو الأرصدة الافتتاحية
                        'warning' => 'adjustment',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sale'          => 'فاتورة مبيعات',
                        'payment'       => 'دفعة سداد',
                        'sale_return'   => 'مرتجع مبيعات',
                        'adjustment'    => 'تسوية رصيد',
                        default         => $state,
                    })
                    ->weight('medium'),

                TextColumn::make('amount')
                    ->label('الكمية')
                    ->numeric(locale: 'en')
                    ->suffix(' ج.م')
                    ->weight('medium')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ','))
                    ->colors([
                        'success' => fn($record) => $record->type === 'credit',
                        'rose'  => fn($record) => $record->type === 'debit',
                        'warning' => fn($record) => $record->type === 'invoice',
                    ])
                    ->summarize([
                        Summarizer::make()
                            ->label('الرصيد الكلي')

                            // 1. التعديل: حساب الرصيد باستخدام SUM المباشر
                            ->using(fn(Builder $query) => $query->sum('amount'))

                            // 2. التعديل: استخدام formatStateUsing للتحقق من الإشارة وعرض Blade View
                            ->formatStateUsing(function ($state) {

                                // الرصيد الموجب (state > 0) يعني مديونية على العميل (دين لك)
                                if ($state > 0) {
                                    $color = 'success'; // أحمر (مديونية على العميل)
                                    $displayAmount = $state;
                                    $sign = '+';

                                    // الرصيد السالب (state < 0) يعني رصيد دائن للعميل (دين عليك)
                                } elseif ($state < 0) {
                                    $color = 'rose'; // أخضر (رصيد دائن للعميل)
                                    $displayAmount = abs($state); // عرض القيمة المطلقة
                                    $sign = '-';

                                    // الرصيد صفر
                                } else {
                                    $color = 'gray';
                                    $displayAmount = 0;
                                    $sign = '';
                                }

                                return view('filament.tables.columns.colored-summary', [
                                    'content' => number_format($displayAmount, 2) . ' ج.م',
                                    'color' => $color,
                                    'sign' => $sign,
                                    'wallet_type' => 'customer',
                                ]);
                            }),
                    ]),

                TextColumn::make('invoice.invoice_number')
                    ->label('سحب بالفاتورة')
                    ->searchable()
                    ->default('لايوجد')
                    ->weight('medium'),

                TextColumn::make('notes')
                    ->label('ملاحظات الحركة')
                    ->default('لايوجد')
                    ->limit(40)
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
                        'credit' => 'إيداع',
                        'debit' => 'سحب',
                        'invoice' => 'فاتورة',
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        return static::generateRouteName('wallet', $panel);
    }

    public function getHeading(): string
    {
        return 'حركات رصيد العميل';
    }

    public function getBreadcrumb(): string
    {
        return 'حركات الرصيد';
    }

    public function getTitle(): string
    {
        return ' رصيد العميل';
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
