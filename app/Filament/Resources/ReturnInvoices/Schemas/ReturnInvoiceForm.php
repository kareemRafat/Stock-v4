<?php

namespace App\Filament\Resources\ReturnInvoices\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use App\Filament\Forms\Components\ClientDatetimeHidden;

class ReturnInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->columnSpan(2),

                Hidden::make('original_invoice_id'),

                TextInput::make('original_invoice_number')
                    ->label('رقم الفاتورة')
                    ->readOnly(),

                TextInput::make('return_invoice_number')
                    ->label('رقم فاتورة الإرجاع')
                    ->readOnly(),

                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),

                // get the javascript Date
                ClientDatetimeHidden::make('created_at'),

                Repeater::make('items')
                    ->label('الأصناف المرتجعة')
                    ->schema([
                        Select::make('product_id')
                            ->label('المنتج')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Product::where('name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('name', 'id');
                            })
                            ->getOptionLabelUsing(function ($value) {
                                return \App\Models\Product::find($value)?->name;
                            })
                            ->required(),

                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->readOnly(),

                        TextInput::make('quantity_returned')
                            ->label('الكمية المرتجعة')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->rules([
                                'required',
                                'numeric',
                                'min:0',
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $quantity = $get('quantity') ?? 0;
                                        if ($value > $quantity) {
                                            $fail("الكمية المرتجعة ($value) لا يمكن أن تكون أكبر من الكمية الأصلية ($quantity).");
                                        }
                                    };
                                },
                            ]),

                        Checkbox::make('return_all')
                            ->label('إرجاع السلعة بالكامل')
                            ->helperText('في حالة الاختيار يتم ارجاع السلعة بالكامل'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->addable(false)
            ]);
    }
}
