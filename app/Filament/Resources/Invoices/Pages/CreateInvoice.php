<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\MovementType;
use App\Filament\Actions\InvoiceActions\PrintInvoiceAction;
use Filament\Actions\Action;
use App\Services\StockService;
use Filament\Schemas\Components\Section;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Resources\Pages\Concerns\HasWizard;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;

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
                ->label('اصناف الفاتورة')
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

    //! for testing
    /*
    public function create(bool $another = false): void
    {
        $data = $this->form->getRawState();

        dd($data);

        parent::create($another);
    }
    */

    protected function afterCreate(): void
    {
        // Inject the StockService manually (Filament doesn't do constructor DI here)
        $stockService = app(StockService::class);

        // 'wholesale' or 'retail'
        $invoicePriceType = $this->record->price_type;

        // 1️⃣ Update total amount
        $this->record->update([
            'total_amount' => $this->record->items()->sum('subtotal'),
        ]);

        // 2️⃣ Loop through items and record stock movement for each
        foreach ($this->record->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $isWholesale = $this->record->price_type === 'wholesale';

            // the prices and discount in the time of invoice
            $costPrice      = $product->cost_price ?? 0;
            $discountPercent = $product->discount ?? 0;
            $wholesalePrice = $isWholesale ? ($product->discounted_wholesale_price ?? 0) : null;
            $retailPrice = $isWholesale ? null : ($product->retail_price ?? 0);


            $item->update([
                'cost_price'      => $costPrice,
                'wholesale_price' => $wholesalePrice, // added after discount
                'retail_price'    => $retailPrice,
                'discount' => $discountPercent
            ]);



            // 3️⃣ Decrease stock via StockService
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

        $this->dispatch('$refresh');
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }
}
