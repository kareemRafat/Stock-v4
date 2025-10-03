<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use App\Models\Product;
use Filament\Actions\Action;
use App\Models\ProductPurchase;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;
use App\Services\StockService;

class CreateSupplierInvoice extends CreateRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // to call StockService service - afterCreate can`t use dependancy injection
        $stockService = app(StockService::class);

        //  update products prices
        foreach ($this->record->items as $item) {
            $product = $item->product;
            if ($product) {
                // تحديث سعر الجملة والقطاعي لكل منتج حسب الفاتورة
                $product->update([
                    'cost_price' => $item->cost_price,
                    'wholesale_price' => $item->wholesale_price,
                    'retail_price'    => $item->retail_price,
                ]);
            }

            $stockService->recordMovement(
                product: $product,
                movementType: 'purchase',
                quantity: $item->quantity,
                costPrice: $item->cost_price,
                wholeSalePrice: $item->wholesale_price,
                retailPrice: $item->retail_price,
                referenceId: $this->record->id,
                referenceTable: 'supplier_invoices',
                createdAt : $this->record->created_at
            );
        }

        \Filament\Notifications\Notification::make()
            ->title('تم حفظ الفاتورة بنجاح')
            ->body("تم إضافة {$this->record->items->count()} منتج وتحديث المخزن")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(url()->previous() ?? SupplierInvoiceResource::getUrl('index')),
            // if no "previous", fallback to index
        ];
    }

    // to remove add and add more
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(), // Add button
            $this->getCancelFormAction(), // cancel button
        ];
    }
}
