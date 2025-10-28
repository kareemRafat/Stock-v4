<?php

namespace App\Filament\Resources\Suppliers\Pages;

use Filament\Tables;
use Filament\Actions;
use App\Models\Supplier;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Query\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Summarizers\Summarizer;
use App\Filament\Resources\Suppliers\SupplierResource;

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
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'purchase'        => 'فاتورة مشتريات',
                        'payment'         => 'دفعة سداد',
                        'purchase_return' => 'مرتجع مشتريات',
                        'adjustment'      => 'تسوية / رصيد إفتتاحي',
                        default           => $state,
                    }),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->numeric(locale: 'en')
                    ->suffix(' ج.م')
                    ->colors([
                        'success' => fn($record) => $record->type === 'credit',
                        'rose'  => fn($record) => $record->type === 'debit',
                        'warning' => fn($record) => $record->type === 'invoice',
                    ])
                    ->weight('medium')
                    ->summarize([
                        Summarizer::make()
                            ->using(fn(Builder $query) => $query->sum('amount'))
                            ->label('الرصيد الكلي')
                            ->money('egp')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' ج.م')
                            ->formatStateUsing(function ($state) {
                                if ($state < 0) {
                                    $color = 'success';
                                    $displayAmount = abs($state);
                                    $sign = '';
                                } else {
                                    $color = 'rose';
                                    $displayAmount = $state;
                                    $sign = '-';
                                }
                                return view('filament.tables.columns.colored-summary', [
                                    'content' => number_format($displayAmount, 2) . ' ج.م',
                                    'color' => $color,
                                    'sign' => $sign,
                                    'wallet_type' => 'supplier',
                                ]);
                            }),
                    ]),
                TextColumn::make('invoice.invoice_number')
                    ->label('فاتورة المورد')
                    ->searchable()
                    ->default('لا يوجد'),
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
        return 'رصيد المورد';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn() => SupplierResource::getUrl('index')),
        ];
    }
}
