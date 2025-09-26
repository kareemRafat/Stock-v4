<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use Filament\Actions\Action;
use App\Models\ProductPurchase;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;

class CreateSupplierInvoice extends CreateRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        foreach ($this->record->items as $item) {

            // Add ProductPurchase
            ProductPurchase::create([
                'product_id' => $item->product_id,
                'supplier_id' => $this->record->supplier_id,
                'quantity' => $item->quantity,
                'purchase_price' => $item->price, // سعر الشراء من الفاتورة
                'total_cost' => $item->subtotal, // $item->quantity * $item->price
                'purchase_date' => $this->record->invoice_date,
                'supplier_invoice_number' => $this->record->invoice_number,
                'notes' => "فاتورة رقم: {$this->record->invoice_number} - مورد: {$this->record->supplier->name}",
            ]);
        }

        //  update Average Cost
        $productIds = $this->record->items->pluck('product_id')->unique();
        foreach ($productIds as $productId) {
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $product->updateAverageCost(); // Product model function
            }
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
