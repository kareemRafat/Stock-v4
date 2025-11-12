<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\MovementType;
use App\Filament\Resources\Products\ProductResource;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected ?float $oldStockQuantity = null;

    protected function beforeSave(): void
    {
        $this->oldStockQuantity = $this->record->stock_quantity;
    }

    protected function afterSave(): void
    {
        // when update only
        $record = $this->record;

        $originalQty = $this->oldStockQuantity;
        $newQty = $this->data['new_stock'];
        $diff = $newQty - $originalQty;
        // dd($diff);
        if ($newQty) {
            if ($diff != 0) {
                $movementType = $diff > 0
                    ? MovementType::ADJUSTMENT_IN
                    : MovementType::ADJUSTMENT_OUT;

                $stockService = app(StockService::class);

                $stockService->recordMovement(
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

    protected function getHeaderActions(): array
    {
        return [

            Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ProductResource::getUrl('index')),
            // Products Can`t be Delete because of the invoices relation
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
