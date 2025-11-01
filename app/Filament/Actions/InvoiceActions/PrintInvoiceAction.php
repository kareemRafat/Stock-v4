<?php

namespace App\Filament\Actions\InvoiceActions;

use Filament\Actions\Action;
use Mpdf\Mpdf;

class PrintInvoiceAction
{
    public static function make(): Action
    {
        return Action::make('print-invoice')
            ->label('طباعة نسخة أولية')
            ->icon('heroicon-o-printer')
            ->color('indigo')
            ->extraAttributes(['class' => 'font-semibold'])
            ->action(function ($livewire) {
                // جلب البيانات من الفورم الحالي
                $invoiceData = $livewire->form->getRawState();

                // تبسيط معالجة البيانات
                $items = collect($invoiceData['items'] ?? [])->map(function ($item) {
                    // جلب بيانات المنتج من قاعدة البيانات
                    $product = \App\Models\Product::find($item['product_id'] ?? null);

                    return [
                        'product_name' => $product->name ?? '---',
                        'product_unit' => $product->unit ?? '',
                        'wholesale_price' => $product->wholesale_price ?? 0,
                        'retail_price' => $product->retail_price ?? 0,
                        'quantity' => $item['quantity'] ?? 0,
                        'discount' => $product->discount ?? 0,
                        'subtotal' => $item['subtotal'] ?? 0,
                    ];
                });

                // إعداد بيانات العرض
                $viewData = [
                    'invoice_number' => $invoiceData['invoice_number'] ?? '---',
                    'customer' => \App\Models\Customer::find($invoiceData['customer_id'] ?? null),
                    'price_type' => $invoiceData['price_type'] ?? 'wholesale',
                    'items' => $items,
                    'notes' => $invoiceData['notes'] ?? '',
                    'special_discount' => $invoiceData['special_discount'] ?? 0,
                    'created_at' => now()->format('Y-m-d H:i'),
                ];

                // إعدادات Mpdf مع DejaVu Sans
                $mpdf = new Mpdf([
                    'debug' => false,
                    'showImageErrors' => false,
                    'default_font' => 'readexpro',
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'fontDir' => [
                        public_path('fonts/'),
                        ...(new \Mpdf\Config\ConfigVariables)->getDefaults()['fontDir'],
                    ],
                    'fontdata' => [
                        'readexpro' => [
                            'R' => 'Cairo-Regular.ttf',
                            'useOTL' => 0xFF,
                        ],
                    ],
                    'default_font_size' => 11,
                    'directionality' => 'rtl',
                    'autoScriptToLang' => true,
                    'autoLangToFont' => true,
                    'autoArabic' => true,
                ]);

                $mpdf->SetDirectionality('rtl');
                $html = view('filament.pages.invoices.print-invoice', $viewData)->render();
                $mpdf->WriteHTML($html);

                return response()->streamDownload(
                    fn () => $mpdf->Output(),
                    "فاتورة-{$viewData['invoice_number']}.pdf"
                );
            });
    }
}
