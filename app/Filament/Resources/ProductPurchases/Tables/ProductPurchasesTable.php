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
                fn($query) => $query->with([
                    'invoice',
                    'product',
                ])
            )
            ->defaultSort('invoice.created_at', 'desc')
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

                TextColumn::make('invoice.supplier.name')
                    ->label('المورد')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('cost_price')
                    ->label('سعر الشراء')
                    ->suffix(' ج.م ')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('التكلفة الإجمالية')
                    ->suffix(' ج.م ')
                    ->sortable(),
                // ->summarize([
                //     Summarizers\Sum::make()
                //         ->money('EGP'),
                // ]),

                TextColumn::make('invoice.created_at')
                    ->label('تاريخ الشراء')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('invoice.invoice_number')
                    ->label('رقم فاتورة المورد')
                    ->toggleable(true),
            ])
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('المورد')
                    ->searchable()
                    ->options(
                        fn() =>
                        Supplier::query()
                            ->latest()
                            ->limit(20)
                            ->pluck('name', 'id')
                    )
                    ->getSearchResultsUsing(
                        fn(string $search) =>
                        Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        Supplier::find($value)?->name
                    )
                    ->query(function ($query, $data) {
                        // لو المستخدم اختار مورد فقط
                        if (!empty($data['value'])) {
                            $query->whereHas('invoice', function ($q) use ($data) {
                                $q->where('supplier_id', $data['value']);
                            });
                        }
                    })
                    ->placeholder('كل الموردين'),

                SelectFilter::make('product_id')
                    ->label('المنتج')
                    ->searchable()
                    ->options(
                        fn() =>
                        Product::query()
                            ->latest()
                            ->limit(20)
                            ->pluck('name', 'id')
                    )
                    ->getSearchResultsUsing(
                        fn(string $search) =>
                        Product::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                    )
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        Product::where('id', $value)->value('name')
                    )
                    ->query(function ($query, $data) {
                        if (!empty($data['value'])) {
                            $query->where('product_id', $data['value']);
                        }
                    })
                    ->native(false)
                    ->placeholder('كل المنتجات'),

                Filter::make('invoice_date')
                    ->columnSpan(2)
                    ->label('تاريخ الفاتورة')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('from')
                                ->label('من تاريخ')
                                ->native(false)
                                ->placeholder('اختار تاريخ البداية'),
                            DatePicker::make('until')
                                ->label('إلى تاريخ')
                                ->native(false)
                                ->placeholder('اختار تاريخ النهاية'),
                        ]),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'من: ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'إلى: ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return $indicators;
                    })
                    ->query(function ($query, array $data) {
                        return $query->whereHas('invoice', function ($q) use ($data) {
                            $q
                                ->when($data['from'] ?? null, fn($q, $date) => $q->whereDate('invoice_date', '>=', $date))
                                ->when($data['until'] ?? null, fn($q, $date) => $q->whereDate('invoice_date', '<=', $date));
                        });
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(2)
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
