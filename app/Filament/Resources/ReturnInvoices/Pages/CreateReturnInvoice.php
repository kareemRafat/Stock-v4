<?php

namespace App\Filament\Resources\ReturnInvoices\Pages;

use App\Models\Invoice;
use App\Models\Product;
use App\Enums\MovementType;
use App\Models\InvoiceItem;
use App\Models\CustomerWallet;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ReturnInvoices\ReturnInvoiceResource;

class CreateReturnInvoice extends CreateRecord
{
    protected static string $resource = ReturnInvoiceResource::class;

    public ?int $originalInvoiceId = null;

    public function mount(): void
    {
        parent::mount();

        $this->originalInvoiceId = request()->query('original_invoice');

        // Generate return number once
        $time = (int)(microtime(true) * 1000000);
        $sixDigits = $time % 1000000;
        $returnNumber = 'RET-' . str_pad($sixDigits, 6, '0', STR_PAD_LEFT);

        if ($this->originalInvoiceId) {
            $originalInvoice = Invoice::with('items.product')->find($this->originalInvoiceId);

            if ($originalInvoice) {
                // هات كل المرتجعات المرتبطة بالفاتورة الأصلية مرة واحدة
                $returns = \App\Models\ReturnInvoiceItem::whereHas('returnInvoice', function ($q) use ($originalInvoice) {
                    $q->where('original_invoice_id', $originalInvoice->id);
                })
                    ->selectRaw('product_id, SUM(quantity_returned) as total_returned')
                    ->groupBy('product_id')
                    ->pluck('total_returned', 'product_id');
                // هترجع مصفوفة [product_id => total_returned]

                // جهّز بيانات الأصناف مع استبعاد اللي خلصت (remaining <= 0)
                $itemsData = $originalInvoice->items
                    ->map(function ($item) use ($returns) {
                        $totalReturned = $returns[$item->product_id] ?? 0;
                        $remaining = $item->quantity - $totalReturned;

                        if ($remaining <= 0) {
                            return null; // تجاهل الصنف اللي خلص
                        }

                        return [
                            'product_id' => $item->product_id,
                            'quantity' => $remaining,
                            'quantity_returned' => 0,
                        ];
                    })
                    ->filter()   // شيل الـ null
                    ->values()   // رتب العناصر من الأول
                    ->toArray();

                // املأ الفورم
                $this->form->fill([
                    'customer_id' => $originalInvoice->customer_id,
                    'original_invoice_number' => $originalInvoice->invoice_number,
                    'original_invoice_id' => $originalInvoice->id,
                    'return_invoice_number' => $returnNumber,
                    'notes' => $originalInvoice->notes,
                    'items' => $itemsData,
                ]);
            }
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Extract items data
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Validate and filter items
        $validItems = $this->getValidReturnItems($items);

        if (empty($validItems)) {
            $this->showNoItemsNotification();
            $this->halt();
        }

        // Calculate total amount
        $totalAmount = $this->calculateTotalAmount($validItems);
        $data['total_amount'] = $totalAmount;

        // Create return invoice with items
        return $this->createReturnInvoiceWithItems($data, $validItems);
    }

    /**
     * Filter out items that shouldn't be returned
     */
    private function getValidReturnItems(array $items): array
    {
        return array_filter($items, function ($item) {
            return (!empty($item['return_all']) && $item['return_all']) ||
                (!empty($item['quantity_returned']) && $item['quantity_returned'] > 0);
        });
    }

    /**
     * Show notification when no items are selected for return
     */
    private function showNoItemsNotification(): void
    {
        Notification::make()
            ->title('لم يتم اختيار اي سلعة للإرجاع')
            ->body('يجب اختيار منتجات او كمية للإرجاع')
            ->danger()
            ->send();
    }

    /**
     * Calculate total amount for valid return items
     */
    private function calculateTotalAmount(array $validItems): float
    {
        $totalAmount = 0;

        foreach ($validItems as $item) {
            $quantityReturned = $this->getQuantityReturned($item);
            $price = $this->getProductPrice($item['product_id']);
            $totalAmount += $quantityReturned * $price;
        }

        return $totalAmount;
    }

    /**
     * Get the quantity to be returned for an item
     */
    private function getQuantityReturned(array $item): int
    {
        if (!empty($item['return_all']) && $item['return_all']) {
            return $item['quantity'];
        }

        return $item['quantity_returned'] ?? 0;
    }

    /**
     * Get product price by ID
     */
    private function getProductPrice($productId): float
    {
        $product = Product::find($productId);
        return $product->cost_price ?? 0;
    }

    /**
     * Create return invoice and its items in a transaction
     */
    private function createReturnInvoiceWithItems(array $data, array $validItems): Model
    {
        return DB::transaction(function () use ($data, $validItems) {
            // Create the return invoice
            $returnInvoice = static::getModel()::create($data);

            // use stock service class
            $stockService = app(StockService::class);

            //حساب القيمة الفعلية للمرتجع
            $totalReturnAmount = 0;
            $originalInvoice = Invoice::find($returnInvoice->original_invoice_id);
            $invoiceType = $originalInvoice?->price_type; // 'wholesale' أو 'retail'

            foreach ($validItems as $item) {
                $this->createReturnInvoiceItem($returnInvoice, $item, $stockService);

                // حساب قيمة هذا الصنف المرتجع
                $quantityReturned = $this->getQuantityReturned($item);
                $product = Product::find($item['product_id']);

                if ($product && $quantityReturned > 0) {
                    if ($invoiceType === 'wholesale') {
                        // سعر الجملة مع الخصم
                        $price = $product->wholesale_price;
                        $discount = $product->discount ?? 0;
                        $priceAfterDiscount = $price - ($price * $discount / 100);
                        $totalReturnAmount += $quantityReturned * $priceAfterDiscount;
                    } else {
                        // سعر القطاعي بدون خصم
                        $totalReturnAmount += $quantityReturned * $product->retail_price;
                    }
                }
            }

            //إضافة حركة المرتجع في محفظة العميل
            if ($totalReturnAmount > 0 && $returnInvoice->customer_id) {
                \App\Models\CustomerWallet::create([
                    'customer_id' => $returnInvoice->customer_id,
                    'type' => 'sale_return',
                    'amount' => $totalReturnAmount, // القيمة الفعلية حسب النوع والخصم
                    'invoice_id' => $returnInvoice->original_invoice_id,
                    'return_invoice_id' => $returnInvoice->id,
                    'notes' => 'فاتورة مرتجع ' . $returnInvoice->return_invoice_number ,
                    'created_at' => $returnInvoice->created_at ?? now(),
                ]);
            }

            return $returnInvoice;
        });
    }

    /**
     * Create a single return invoice item and update inventory
     */
    private function createReturnInvoiceItem(Model $returnInvoice, array $item, StockService $stockService): void
    {
        $quantityReturned = $this->getQuantityReturned($item);

        if ($quantityReturned <= 0) {
            return;
        }

        $product = Product::find($item['product_id']);
        if (! $product) {
            return;
        }

        // جلب بيانات الفاتورة الأصلية والبند الأصلي
        $originalInvoice = $returnInvoice->invoice ?? Invoice::find($returnInvoice->original_invoice_id);
        $originalItem = \App\Models\InvoiceItem::where('invoice_id', $returnInvoice->original_invoice_id)
            ->where('product_id', $product->id)
            ->first();

        // الأسعار الفعلية المستخدمة (مع fallback لجدول المنتجات)
        $costPrice      = $originalItem->cost_price ?? $product->cost_price;
        $wholesalePrice = $originalItem->wholesale_price ?? $product->wholesale_price;
        $retailPrice    = $originalItem->retail_price ?? $product->retail_price;

        // تحديد السعر بناءً على نوع الفاتورة
        $invoiceType = $originalInvoice?->price_type; // 'wholesale' أو 'retail'

        $price = match ($invoiceType) {
            'wholesale' => $wholesalePrice,
            'retail'    => $retailPrice,
            default     => $retailPrice, // احتياطًا لو النوع غير محدد
        };

        $subtotal = $quantityReturned * $price;

        // إنشاء بند المرتجع
        $returnInvoice->items()->create([
            'product_id'        => $item['product_id'],
            'quantity_returned' => $quantityReturned,
            'price'             => $price,
            'subtotal'          => $subtotal,
        ]);

        // تسجيل حركة المخزون
        $stockService->recordMovement(
            product: $product,
            movementType: MovementType::SALE_RETURN,
            quantity: $quantityReturned,
            costPrice: $costPrice,
            wholeSalePrice: $invoiceType === 'wholesale' ? $wholesalePrice : null,
            retailPrice: $invoiceType === 'retail' ? $retailPrice : null,
            referenceId: $returnInvoice->id,
            referenceTable: 'return_invoices'
        );
    }



    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }

    public function canCreateAnother(): bool
    {
        return false;
    }
}
