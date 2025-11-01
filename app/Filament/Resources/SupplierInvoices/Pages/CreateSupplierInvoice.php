<?php

namespace App\Filament\Resources\SupplierInvoices\Pages;

use App\Enums\MovementType;
use App\Filament\Resources\SupplierInvoices\SupplierInvoiceResource;
use App\Models\SupplierWallet;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierInvoice extends CreateRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        // send this ['refresh' => 1] to listSupplierInvoice mount()
        return $this->getResource()::getUrl('index', ['refresh' => 1]);
    }

    protected function afterCreate(): void
    {

        $invoice = $this->record;
        $supplierId = $invoice->supplier_id;

        // paid amount
        $paidAmount = (float) ($this->data['paid_amount'] ?? 0);

        // total invoice amount
        $invoiceTotal = $invoice->items->sum('subtotal');

        // نموذج دفتر الأستاذ (Ledger Model)
        // بيحصل تسجيل عمليتين .. الاولي بيبقى المبلغ الاجمالي للفاتورة كعملية شراء
        // الثاني بيقى المبلغ المدفوع فعليا والفرق اللى بينهم بعد كدة بيحدد مديونية ولا ليك فلوس
        if ($invoiceTotal > 0) {
            SupplierWallet::create([
                'supplier_id' => $supplierId,
                'type' => 'purchase', // نوع الحركة: شراء
                'amount' => $invoiceTotal,
                'supplier_invoice_id' => $invoice->id,
                'note' => 'فاتورة مشتريات ',
                'created_at' => $invoice->created_at,
            ]);
        }

        if ($paidAmount > 0) {
            SupplierWallet::create([
                'supplier_id' => $supplierId,
                'type' => 'payment', // نوع الحركة: دفع
                'amount' => $paidAmount,
                'supplier_invoice_id' => $invoice->id,
                'note' => 'دفعة سداد من فاتورة المشتريات ',
                'created_at' => $invoice->created_at,
            ]);
        }

        // to call StockService service - afterCreate can`t use dependancy injection
        $stockService = app(StockService::class);
        //  update products prices
        foreach ($this->record->items as $item) {
            $product = $item->product;
            if ($product) {
                $product->update([
                    'cost_price' => $item->cost_price,
                    'wholesale_price' => $item->wholesale_price,
                    'retail_price' => $item->retail_price,
                ]);
            }

            $stockService->recordMovement(
                product: $product,
                movementType: MovementType::PURCHASE,
                quantity: $item->quantity,
                costPrice: $item->cost_price,
                wholeSalePrice: $item->wholesale_price,
                discount: $product->discount,
                retailPrice: $item->retail_price,
                referenceId: $this->record->id,
                referenceTable: 'supplier_invoices',
                createdAt: $this->record->created_at
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

    public function canCreateAnother(): bool
    {
        return false;
    }
}
