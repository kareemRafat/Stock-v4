<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;
use App\Filament\Forms\Components\ClientDatetimeHidden;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    Step::make('Order')
                        ->icon(Heroicon::ShoppingBag)
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->label('الطلب')
                        ->schema(self::getInvoiceInformation()),
                    Step::make('order_items')
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->label('اصناف الفاتورة')
                        ->schema(self::getInvoiceItemsInfo()),
                ])
                    ->label('إنشاء فاتورة')
                    ->columnSpanFull()
            ]);
    }


    public static function getInvoiceInformation()
    {
        return [
            TextInput::make('invoice_number')
                ->label('رقم الفاتورة')
                ->default(fn($livewire) => $livewire instanceof CreateRecord ? Invoice::generateUniqueInvoiceNumber() : null)
                ->disabled()
                ->dehydrated() // allow it to be saved
                ->dehydrateStateUsing(fn($state) => $state) // manually pass the state
                ->required()
                ->rules(['required', 'string', 'max:255']),
            Select::make('customer_id')
                ->searchable()
                ->native(false)
                ->required()
                ->preload(true)
                ->label('اسم العميل')
                ->loadingMessage('تحميل العملاء ...')
                ->placeholder('اختر العميل...')
                ->options(function () {
                    // get 10 when open
                    return Customer::where('status', 'enabled')
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(function ($customer) {
                            return [$customer->id => $customer->name];
                        });
                })
                ->getOptionLabelUsing(fn($value) => Customer::find($value)?->name)
                ->getSearchResultsUsing(function ($search) {
                    return Customer::where('name', 'like', "%{$search}%")
                        ->where('status', 'enabled')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(function ($customer) {
                            return [$customer->id => $customer->name];
                        });
                }),


            Textarea::make('notes')
                ->columnSpanFull(),

            // get the javascript Date
            ClientDatetimeHidden::make('created_at')
        ];
    }

    public static function getInvoiceItemsInfo()
    {
        return [
            Repeater::make('items')
                ->relationship('items')
                ->label('اصناف الفاتورة')
                ->schema([
                    Select::make('product_id')
                        ->label('الصنف')
                        ->loadingMessage('تحميل المنتجات ...')
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return \App\Models\Product::query()
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(function ($product) {
                                    return [
                                        $product->id => $product->name . ' - ' . $product->type
                                    ];
                                });
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            return \App\Models\Product::query()
                                ->where(function ($query) use ($search) {
                                    $query->where('name', 'like', "%{$search}%")
                                        ->orWhere('type', 'like', "%{$search}%");
                                })
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($product) {
                                    return [
                                        $product->id => $product->name . ' - ' . $product->type
                                    ];
                                });
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $product = \App\Models\Product::find($value);
                            return $product ? $product->name . ' - ' . $product->type : '';
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $product  = \App\Models\Product::find($state);
                            $price    = $product?->final_price ?? 0;
                            $stock    = $product?->stock_quantity ?? 0;
                            $quantity = $get('quantity') ?? 1;

                            $set('price', $price);
                            $set('stock_quantity', $stock);
                            $set('subtotal', round($price * $quantity, 2));

                            // Recalculate total
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                        }),
                    TextInput::make('stock_quantity')
                        ->label('المتاح بالمخزن')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('quantity')
                        ->label('الكمية')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->live()
                        ->rule(function (callable $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $stock = $get('stock_quantity') ?? 0;
                                if ($value > $stock) {
                                    $fail("الكمية المطلوبة ($value) أكبر من المتاح في المخزن");
                                }
                            };
                        })
                        ->afterStateUpdatedJs('
                            const price = parseFloat($get("price")) || 0;
                            const quantity = parseFloat($state) || 0;
                            const subtotal = Math.round(price * quantity * 100) / 100;

                            $set("subtotal", subtotal);

                            // Recalculate total
                            const items = $get("../../items") || [];
                            const total = items.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
                            $set("../../total_amount", Math.round(total * 100) / 100);
                        ')
                        ->skipRenderAfterStateUpdated(),

                    TextInput::make('price')
                        ->label('السعر')
                        ->numeric()
                        ->required()
                        ->disabled()
                        ->dehydrated(),

                    TextInput::make('subtotal')
                        ->label('الإجمالي')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),
                ])
                ->addActionLabel('إضافة صنف')
                ->columns(5) // was 4, now 5 because we added stock_quantity
                ->minItems(1)
                ->required(),

            //! show blue blade
            ViewField::make('total_amount_display')
                ->view('filament.partials.invoice-total')
                ->live()
                ->viewData(function ($get) {
                    $items = $get('items') ?? [];
                    $total = collect($items)->sum('subtotal');

                    return [
                        'total' => $total,
                    ];
                }),

            // Hidden field for saving total to DB
            Hidden::make('total_amount')
                ->dehydrated()
                ->default(0),
        ];
    }
}
