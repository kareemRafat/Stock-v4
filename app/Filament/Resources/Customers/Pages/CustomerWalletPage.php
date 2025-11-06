<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Panel;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class CustomerWalletPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = CustomerResource::class;

    protected string $view = 'filament.pages.customers.customer-wallet-page';

    public Customer $customer;

    public $balance;

    public function mount(int $record): void
    {
        $this->customer = Customer::findOrFail($record);
        $this->balance = $this->customer->balance;
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
                        // مدين
                        'danger' => 'sale',

                        // دائن
                        'success' => fn(string $state): bool => in_array($state, ['payment']),
                        'teal' => fn(string $state): bool => in_array($state, ['sale_return']),

                        // تسويات
                        'warning' => fn(string $state): bool => in_array($state, ['adjustment', 'credit_use']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'sale' => 'فاتورة مبيعات',
                        'payment' => 'دفعة سداد',
                        'sale_return' => 'مرتجع مبيعات',
                        'credit_use' => 'خصم من الرصيد',
                        'adjustment' => 'تسوية يدوية',
                        default => $state,
                    })
                    ->weight('medium'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(locale: 'en')
                    ->suffix(' ج.م')
                    ->weight('medium')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2, '.', ','))
                    ->color(fn($record) => match ($record->type) {
                        'sale' => 'danger',
                        'payment', 'sale_return', 'credit_use', 'adjustment' => 'success',
                        default => 'warning',
                    }),

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
                    ->weight('medium'),

                TextColumn::make('createdDate')
                    ->label('تاريخ الإضافة')
                    ->weight('medium'),

                TextColumn::make('createdTime')
                    ->label('وقت الإضافة')
                    ->weight('medium'),
            ])
            ->filters([], layout: FiltersLayout::AboveContent)
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
