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
                        'success' => 'credit',
                        'danger' => 'debit',
                        'warning' => 'invoice',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'credit' => 'إيداع',
                        'debit' => 'سحب',
                        'invoice' => 'فاتورة',
                        default => $state,
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
                            ->using(
                                fn(Builder $query) => $query->clone()->selectRaw("
                                    SUM(
                                        CASE
                                            WHEN type = 'debit' THEN -amount
                                            WHEN type = 'invoice' THEN -amount
                                            WHEN type = 'credit' THEN amount
                                            ELSE 0
                                        END
                                    ) as balance
                                ")->value('balance') ?? 0
                            )
                            ->label('الرصيد الكلي')
                            ->money('egp')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' ج.م'),
                    ]),
                TextColumn::make('invoice.invoice_number')
                    ->label('فاتورة')
                    ->default('لا يوجد'),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->default('لايوجد')
                    ->limit(40),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date('d-m-Y'),
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
