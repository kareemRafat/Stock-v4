<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\MovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Actions\ProductActions\AddProductsAction;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false)
                ->slideOver()
                ->using(function (array $data, StockService $stockService) {
                    // store stock_quantity in to reuse
                    $openingStock = $data['stock_quantity'] ?? 0;

                    // exclude stock_quantity before product create
                    unset($data['stock_quantity']);

                    // create product without stock_quantity
                    $product = Product::create($data);

                    // استخدام StockService لتسجيل opening stock وتحديث products.stock_quantity
                    if ($openingStock > 0) {
                        $stockService->recordMovement(
                            product: $product,
                            movementType: MovementType::OPENING_STOCK,
                            quantity: $openingStock,
                            costPrice: $product->cost_price,
                            wholeSalePrice: $product->wholesale_price,
                            discount : $product->discount ,
                            retailPrice: $product->retail_price,
                            referenceId: $product->id,
                            referenceTable: 'products'
                        );
                    }

                    return $product;
                }),

            AddProductsAction::make('addProducts'),
        ];
    }
}
