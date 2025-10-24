<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\MovementType;
use Livewire\Attributes\On;
use Filament\Actions\Action;
use App\Services\StockService;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Actions\ProductActions\AddStockAction;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;

        dd($record);

        if ($record->wasChanged('stock_quantity')) {
            $originalQty = $record->getOriginal('stock_quantity');
            $newQty = $record->stock_quantity;
            $diff = $newQty - $originalQty;

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
                    createdAt: now(),
                );
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            AddStockAction::make(),
            Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ProductResource::getUrl('index')),
            // Products Can`t be Delete because of the invoices relation
        ];
    }

    #[On('refresh-product-page')]
    public function refreshPage()
    {
        //  $livewire->dispatch('refresh-product-page'); in the action AddStockAction
        $this->mount($this->record->getKey()); // إعادة تحميل نفس المنتج
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
