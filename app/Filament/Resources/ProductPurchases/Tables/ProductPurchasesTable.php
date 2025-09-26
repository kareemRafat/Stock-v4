<?php

namespace App\Filament\Resources\ProductPurchases\Tables;

use App\Models\Product;
use App\Models\Supplier;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Schemas\Components\Grid;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;

class ProductPurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn($query) =>
                $query->with(['product', 'supplier'])
            )
            ->defaultSort('purchase_date', 'desc')
            ->recordUrl(null) // This disables row clicking
            ->recordAction(null) // prevent clickable row
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->state(
                        fn($rowLoop, $livewire) => ($livewire->getTableRecordsPerPage() * ($livewire->getTablePage() - 1))
                            + $rowLoop->iteration
                    )
                    ->sortable(false)
                    ->weight('semibold'),
                TextColumn::make('product.name')
                    ->label('المنتج')
                    ->searchable()
                    ->weight('semibold')
                    ->color('violet'),

                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->suffix(' ج.م ')
                    ->sortable(),

                TextColumn::make('total_cost')
                    ->label('التكلفة الإجمالية')
                    ->suffix(' ج.م ')
                    ->sortable(),
                // ->summarize([
                //     Summarizers\Sum::make()
                //         ->money('EGP'),
                // ]),

                TextColumn::make('purchase_date')
                    ->label('تاريخ الشراء')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('supplier_invoice_number')
                    ->label('رقم فاتورة المورد')
                    ->toggleable(true),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->searchable()
                    ->options(function () {
                        // show only 15
                        return Supplier::query()
                            ->latest()
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [$supplier->id => $supplier->name];
                            });
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        // results when search
                        return Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [$supplier->id => $supplier->name];
                            });
                    })
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        Supplier::find($value)?->name
                    )
                    ->placeholder('كل الموردين'),

                SelectFilter::make('product_id')
                    ->label('المنتج')
                    ->options(
                        fn() => Product::query()
                            ->latest()
                            ->limit(20)
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->getSearchResultsUsing(
                        fn(string $search) => Product::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(
                        fn($value): ?string => Product::find($value)?->name
                    )
                    ->native(false)
                    ->placeholder('اختر منتج'),

                Filter::make('purchase_date')
                    ->schema([
                        Grid::make(2) // Use grid with 2 columns
                            ->schema([
                                DatePicker::make('from')
                                    ->label('من تاريخ')
                                    ->native(false)
                                    ->placeholder('اختار تاريخ بدء الفلتر'),
                                DatePicker::make('until')
                                    ->label('إلى تاريخ')
                                    ->native(false)
                                    ->placeholder('اختار تاريخ نهاية الفلتر'),
                            ]),
                    ])
                    ->columnSpanfull()
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('purchase_date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('purchase_date', '<=', $data['until']));
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
