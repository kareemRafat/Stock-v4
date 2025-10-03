<?php

namespace App\Filament\Resources\Products\Pages;

use App\Models\StockMovement;
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
                ->after(function ($record) {
                    // this work cause we insert with modal
                    StockMovement::firstOrCreate(
                        [
                            'product_id'    => $record->id,
                            'movement_type' => 'opening_stock',
                        ],
                        [
                            'qty_in'     => $record->stock_quantity,
                            'qty_out'    => 0,
                            'cost_price' => $record->production_price,
                            'retail_price' => $record->retail_price,
                            'wholesale_price' => $record->wholesale_price,
                            'created_at' => $record->created_at
                        ]
                    );
                }),
            AddProductsAction::make('addProducts'),
        ];
    }
}
