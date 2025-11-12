<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\MovementType;
use App\Filament\Actions\ProductActions\AddStockAction;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\StockService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProductsTable
{
    private static ?float $newStockQuantity = null;

    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null) // disable row clicking
            ->recordAction(null)
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn ($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
                            + $rowLoop->iteration
                    )
                    ->sortable(false)
                    ->weight('semibold'),

                TextColumn::make('name')
                    ->searchable()
                    ->color('purple')
                    ->label('الاسم')
                    ->weight(FontWeight::Medium),

                TextColumn::make('unit')
                    ->label('الوحدة')
                    ->weight(FontWeight::Medium),

                TextColumn::make('stock_quantity')
                    ->numeric()
                    ->label('الكمية المتوفرة')
                    ->formatStateUsing(fn ($state) => $state == 0 ? 'لاتوجد' : $state)
                    ->color(fn ($state) => $state == 0 ? 'danger' : ($state < 20 ? 'orange' : null))
                    ->weight(FontWeight::Medium),

                TextColumn::make('cost_price')
                    ->label('سعر المصنع')
                    ->suffix(' جنيه ')
                    ->weight(FontWeight::Medium)
                    ->hidden(fn () => ! Auth::user()->isAdmin()),

                TextColumn::make('wholesale_price')
                    ->label('سعر الجملة')
                    ->suffix(' جنيه ')
                    ->weight(FontWeight::Medium)
                    ->color('indigo'),

                TextColumn::make('retail_price')
                    ->label('سعر القطاعي')
                    ->suffix(' جنيه ')
                    ->weight(FontWeight::Medium)
                    ->color('orange'),

                TextColumn::make('discount')
                    ->numeric(locale: 'en')
                    ->label('الخصم')
                    ->suffix(' %')
                    ->weight(FontWeight::Medium),
            ])
            ->deferColumnManager(false)
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->searchable()
                    ->options(function () {
                        // static cache
                        static $suppliers = null;

                        if ($suppliers === null) {
                            $suppliers = Supplier::latest()->limit(20)->pluck('name', 'id');
                        }

                        return $suppliers;
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        // results when search
                        return Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->getOptionLabelUsing(function ($value) {
                        $supplier = Supplier::find($value);

                        return $supplier ? $supplier->name : '';
                    })
                    ->placeholder('كل الموردين')
                    ->columnSpan(2),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ActionGroup::make([
                    AddStockAction::make(),
                    EditAction::make()
                        ->before(function (EditAction $action) {
                            // احفظ القيمة الجديدة قبل الحفظ
                            self::$newStockQuantity = $action->getData()['new_stock'] ?? null;
                        })
                        ->after(function (EditAction $action) {
                            $record = $action->getRecord()->refresh();
                            $newQty = self::$newStockQuantity;

                            self::recordStockMovement($record, $newQty);
                        }),
                ])
                    ->label('المزيد')
                    ->button()
                    ->color('gray')
                    ->size('xs'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn (): bool => ! Auth::user()->isAdmin()),
                ]),
            ]);
    }

    private static function recordStockMovement(Product $record, ?float $newQty): void
    {
        if (! $newQty) {
            return;
        }

        $originalQty = $record->getOriginal('stock_quantity');
        $diff = $newQty - $originalQty;

        if ($diff != 0) {
            $movementType = $diff > 0
                ? MovementType::ADJUSTMENT_IN
                : MovementType::ADJUSTMENT_OUT;

            app(StockService::class)->recordMovement(
                product: $record,
                movementType: $movementType,
                quantity: abs($diff),
                costPrice: $record->cost_price,
                wholeSalePrice: $record->wholesale_price,
                discount: $record->discount,
                retailPrice: $record->retail_price,
                referenceId: $record->id,
                referenceTable: 'products',
                createdAt: $record->created_at
            );
        }
    }
}
