<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\MovementType;
use App\Filament\Actions\InvoiceActions\PrintInvoiceAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;

class CreateInvoice extends CreateRecord
{
    use HasWizard;

    protected static string $resource = InvoiceResource::class;

    public function getSteps(): array
    {
        return [
            Step::make('Order')
                ->schema([
                    Section::make()
                        ->schema(InvoiceForm::getInvoiceInformation()),
                ])
                ->label('الطلب'),
            Step::make('order_items')
                ->schema([
                    Section::make()
                        ->schema(InvoiceForm::getInvoiceItemsInfo()),
                ])
                ->label('اصناف الفاتورة'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            PrintInvoiceAction::make(),
            Action::make('back')
                ->label('رجوع')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(function () {
                    // Get the referrer URL
                    $referrer = request()->header('referer');

                    // Check if coming from customers index with queries
                    if ($referrer && str_contains($referrer, route('filament.admin.resources.customers.index'))) {
                        return $referrer; // Preserve full URL with queries
                    }

                    // return to index with same index queries
                    return InvoiceResource::getUrl('index', ['_query' => request()->query()]);
                }),
        ];
    }

    protected function afterCreate(): void
    {

        // Inject the StockService manually (Filament doesn't do constructor DI here)
        $stockService = app(StockService::class);

        // 'wholesale' or 'retail'
        $invoicePriceType = $this->record->price_type;

        // 1 Update total amount
        $this->record->update([
            'total_amount' => $this->record->items()->sum('subtotal'),
        ]);

        // 2 update previous Debt
        $previousBalance = $this->record->customer->calculateBalance();
        $this->record->update([
            'previous_debt' => max(0, $previousBalance) // مديونية سابقة
        ]);

        // 3 Loop through items and record stock movement for each
        foreach ($this->record->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $isWholesale = $this->record->price_type === 'wholesale';

            // the prices and discount in the time of invoice
            $costPrice = $product->cost_price ?? 0;
            $discountPercent = $product->discount ?? 0;
            $wholesalePrice = $isWholesale ? ($product->discounted_wholesale_price ?? 0) : null;
            $retailPrice = $isWholesale ? null : ($product->retail_price ?? 0);

            $item->update([
                'cost_price' => $costPrice,
                'wholesale_price' => $wholesalePrice, // added after discount
                'retail_price' => $retailPrice,
                'discount' => $discountPercent,
            ]);

            // 4 Decrease stock via StockService
            $stockService->recordMovement(
                product: $product,
                movementType: MovementType::INVOICE_SALE,
                quantity: $item->quantity,
                costPrice: $costPrice,
                wholeSalePrice: $wholesalePrice,
                retailPrice: $retailPrice,
                referenceId: $this->record->id,
                referenceTable: 'invoices',
                createdAt: $this->record->created_at
            );
        }

        // قيمة الفاتورة الكلية لتسجيلها في المحفظة
        $totalAmount = $this->record->total_amount - $this->data['special_discount'];

        // 5 تسجيل حركة المديونية في محفظة العميل (SALE)
        if ($totalAmount > 0) {
            $this->record->customer->wallet()->create([
                'type' => 'sale',
                'amount' => $totalAmount, // قيمة موجبة (+) تعني مديونية على العميل
                'invoice_id' => $this->record->id,
                'invoice_number' => $this->record->invoice_number,
                'notes' => 'فاتورة مبيعات جديدة ',
                'created_at' => $this->record->created_at, // استخدام نفس تاريخ الفاتورة
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }
}
