<?php

namespace App\Filament\Resources\Invoices\Pages;

use Filament\Actions\Action;
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

    //! for testin
    // public function create(bool $another = false): void
    // {
    //     $data = $this->form->getRawState();

    //     dd($data);

    //     parent::create($another);
    // }


    protected function afterCreate(): void
    {
        $total = 0;

        foreach ($this->record->items as $item) {
            if ($item->product) {
                // السعر × الكمية
                $lineTotal = $item->price * $item->quantity;

                // لو فيه خصم على المنتج
                $discountAmount = $item->product->discount > 0
                    ? ($lineTotal * $item->product->discount) / 100
                    : 0;

                // الإجمالي بعد الخصم
                $finalSubtotal = $lineTotal - $discountAmount;

                // حدّث السطر
                $item->update([
                    'subtotal' => round($finalSubtotal, 2),
                ]);

                // اجمع على التوتال
                $total += $finalSubtotal;

                // خصم الكمية من المخزن
                $item->product->decrement('stock_quantity', $item->quantity);
            }
        }

        // حدّث إجمالي الفاتورة
        $this->record->update([
            'total_amount' => round($total, 2),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('view', ['record' => $this->record]);
    }
}
