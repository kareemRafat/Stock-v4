<?php

namespace App\Filament\Resources\Suppliers\Pages;

use Filament\Tables;
use Filament\Actions;
use App\Models\Supplier;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use App\Models\SupplierWallet;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Filament\Actions\SupplierActions\PayDebtAction;
use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;

class SupplierWalletPage extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = SupplierResource::class;

    protected string $view = 'filament.pages.suppliers.supplier-wallet-page';

    public Supplier $supplier;

    public function mount(int $record): void
    {
        $this->supplier = Supplier::findOrFail($record);
    }

    public static function getResource(): string
    {
        return SupplierResource::class;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->supplier->wallet()->getQuery())
            ->emptyStateHeading('لا توجد حركات رصيد للمورد')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
                            + $rowLoop->iteration
                    )
                    ->sortable(false)
                    ->weight('semibold'),
                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->colors([
                        'rose' => fn($record) => in_array($record->type, ['purchase', 'adjustment']),
                        'success' => fn($record) => in_array($record->type, ['payment', 'purchase_return']),
                        'warning' => fn($record) => in_array($record->type, ['debt_payment']),
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'purchase' => 'فاتورة مشتريات',
                        'payment' => 'دفعة سداد',
                        'purchase_return' => 'مرتجع مشتريات',
                        'debt_payment' => 'سداد مديونية',
                        'adjustment' => 'تسوية / رصيد إفتتاحي',
                        default => $state,
                    }),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(locale: 'en')
                    ->suffix(' ج.م')
                    ->colors([
                        'success' => fn($record) => $record->type === 'credit',
                        'rose' => fn($record) => $record->type === 'debit',
                        'warning' => fn($record) => $record->type === 'invoice',
                    ])
                    ->weight('medium'),

                TextColumn::make('invoice.invoice_number')
                    ->label('فاتورة المورد')
                    ->searchable()
                    ->default('لا يوجد')
                    ->tooltip('عرض تفاصيل الفاتورة')
                    ->url(
                        fn(SupplierWallet $record): ?string => $record->supplier_invoice_id
                            ? SupplierInvoiceResource::getUrl('view', ['record' => $record->supplier_invoice_id])
                            : null
                    ),

                TextColumn::make('note')
                    ->label('ملاحظات')
                    ->default('لايوجد')
                    ->limit(40),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->sortable()
                    ->date('d-m-Y'),
                TextColumn::make('time_only')
                    ->label('الوقت')
                    ->getStateUsing(fn($record) => $record->created_at->format('h:i a')),
            ])
            ->filters([], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public function getHeading(): string
    {
        return 'حركات رصيد المورد';
    }

    public function getBreadcrumb(): string
    {
        return 'رصيد المورد';
    }

    public function getTitle(): string
    {
        return 'رصيد المورد: ' . $this->supplier->name;
    }

    protected function getHeaderActions(): array
    {
        $fallbackUrl = SupplierResource::getUrl('index');
        $previousUrl = url()->previous();
        $currentUrl = url()->current();

        if ($previousUrl !== $currentUrl && ! str_contains($previousUrl, '/login')) {
            $url = $previousUrl;
        } else {
            $url = $fallbackUrl;
        }

        return [
            PayDebtAction::make()
                ->record($this->supplier), // to use $this-record inside the action
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => $url),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Auth::user() && Auth::user()->isAdmin();
    }

    #[On('refresh-wallet')]
    public function refreshWallet()
    {
        // to reload livewire wallet page component when add pay
        $this->supplier->refresh();
    }
}
