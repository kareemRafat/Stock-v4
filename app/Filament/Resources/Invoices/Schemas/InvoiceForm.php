<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Radio;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\ToggleButtons;
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
                ->dehydrated()
                ->dehydrateStateUsing(fn($state) => $state)
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
                    return Customer::where('status', 'enabled')
                        ->limit(10)
                        ->get()
                        ->mapWithKeys(fn($c) => [$c->id => $c->name]);
                })
                ->getOptionLabelUsing(fn($value) => Customer::find($value)?->name)
                ->getSearchResultsUsing(function ($search) {
                    return Customer::where('name', 'like', "%{$search}%")
                        ->where('status', 'enabled')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn($c) => [$c->id => $c->name]);
                }),

            // نوع السعر على مستوى الفاتورة
            ToggleButtons::make('price_type')
                ->label('نوع السعر (لكل الفاتورة)')
                ->options([
                    'wholesale' => 'جملة',
                    'retail'    => 'قطاعي',
                ])
                ->default('wholesale')
                ->required()
                ->inline()
                ->extraAttributes(['class' => 'pl-2 py-2'])
                ->colors([
                    'wholesale' => 'teal',
                    'retail'    => 'orange',
                ])
                ->icons([
                    'wholesale' => 'heroicon-o-shopping-bag',
                    'retail'    => 'heroicon-o-shopping-cart',
                ])
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    $items = $get('items') ?? [];

                    foreach ($items as $index => $item) {
                        $product = \App\Models\Product::find($item['product_id'] ?? null);

                        if ($product) {
                            $price = $state === 'retail'
                                ? $product->retail_price
                                : $product->wholesale_price;

                            $quantity = $item['quantity'] ?? 1;
                            $subtotal = round($price * $quantity, 2);

                            $set("items.{$index}.price", round($price, 2));
                            $set("items.{$index}.subtotal", $subtotal);
                        }
                    }

                    // تحديث الإجمالي
                    $total = collect($get('items'))->sum('subtotal');
                    $set('total_amount', round($total, 2));
                })
                ->columnSpanFull(),

            Textarea::make('notes')
                ->columnSpanFull(),

            ClientDatetimeHidden::make('created_at'),
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
                                        $product->id => $product->name
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
                                        $product->id => $product->name
                                    ];
                                });
                        })
                        ->getOptionLabelUsing(function ($value) {
                            $product = \App\Models\Product::find($value);
                            return $product ? $product->name  : '';
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $product = Product::find($state);
                            if (! $product) {
                                return;
                            }

                            // نوع السعر على مستوى الفاتورة (wholesale/retail)
                            $invoicePriceType = $get('../../price_type') ?? 'wholesale';

                            // اختر السعر بناء على النوع
                            $price = $invoicePriceType === 'retail' ? $product->retail_price : $product->wholesale_price;
                            $quantity = (int) ($get('quantity') ?? 1);

                            // خزّن الأسعار والخصم داخل الـ item عشان الـ JS يستخدمهم لاحقًا
                            $set('wholesale_price', round($product->wholesale_price, 2));
                            $set('retail_price', round($product->retail_price, 2));
                            $set('product_discount', $product->discount ?? 0);

                            // احسب الخصم (تحويل 10 -> 0.1 لو كان مُخزّن كنسبة صحيحة)
                            $discountRate = (float) ($product->discount ?? 0);
                            if ($discountRate > 1) {
                                $discountRate = $discountRate / 100;
                            }

                            $lineTotal = $price * $quantity;
                            $discountAmount = $discountRate > 0 ? round($lineTotal * $discountRate, 2) : 0;
                            $subtotal = round($lineTotal - $discountAmount, 2);

                            // حدّث حقول السطر
                            $set('price', round($price, 2));
                            $set('stock_quantity', $product->stock_quantity);
                            $set('subtotal', $subtotal);

                            // حدّث إجمالي الفاتورة من الـ items الحالية
                            $items = $get('../../items') ?? [];
                            $total = collect($items)->sum('subtotal');
                            $set('../../total_amount', round($total, 2));
                        }),
                    Hidden::make('wholesale_price')->default(0),
                    Hidden::make('retail_price')->default(0),
                    Hidden::make('product_discount')->default(0),

                    TextInput::make('stock_quantity')
                        ->label('المتاح بالمخزن')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('price')
                        ->label('السعر')
                        ->numeric()
                        ->required()
                        ->disabled()
                        ->dehydrated(),

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
                        ->afterStateUpdatedJs(
                            <<<'JS'
                            const price = parseFloat($get("price")) || 0;
                            const quantity = parseFloat($state) || 0;

                            // خصم المنتج مخزن في hidden field داخل الـ item
                            let discount = parseFloat($get("product_discount")) || 0;
                            if (discount > 1) discount = discount / 100;

                            let subtotal = price * quantity;
                            if (discount > 0) {
                                const discountAmount = Math.round(price * quantity * discount * 100) / 100;
                                subtotal = subtotal - discountAmount;
                            }

                            subtotal = Math.round(subtotal * 100) / 100;
                            $set("subtotal", subtotal);

                            // Recalculate total across all items
                            const items = $get("../../items") || [];
                            const total = items.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
                            $set("../../total_amount", Math.round(total * 100) / 100);
                            JS
                        )
                        ->skipRenderAfterStateUpdated(),

                    TextInput::make('subtotal')
                        ->label('الإجمالي بعد الخصم')
                        ->numeric()
                        ->disabled()
                        ->dehydrated()
                        ->default(0),
                ])
                ->addActionLabel('إضافة صنف')
                ->columns(5)
                ->minItems(1)
                ->required(),

            ViewField::make('total_amount_display')
                ->view('filament.partials.invoice-total')
                ->live()
                ->viewData(function ($get) {
                    $items = $get('items') ?? [];
                    $total = collect($items)->sum('subtotal');

                    return ['total' => $total];
                }),

            Hidden::make('total_amount')
                ->dehydrated()
                ->default(0),
        ];
    }
}
