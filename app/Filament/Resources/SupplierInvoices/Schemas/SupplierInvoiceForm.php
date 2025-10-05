<?php

namespace App\Filament\Resources\SupplierInvoices\Schemas;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use App\Models\Supplier;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;

class SupplierInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label('المورد')
                    ->loadingMessage('تحميل الموردين ...')
                    ->searchable()
                    ->preload()
                    ->placeholder('اختر المورد...')
                    ->required()
                    ->options(function () {
                        // show only 20
                        return Supplier::query()
                            ->latest()
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [$supplier->id => $supplier->name];
                            });
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        // results when search
                        return Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [$supplier->id => $supplier->name];
                            });
                    }),
                TextInput::make('invoice_number')
                    ->label('رقم الفاتورة')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                TextInput::make('total_amount')
                    ->label('إجمالي الفاتورة')
                    ->numeric()
                    ->prefix('جنيه')
                    ->required(),

                DatePicker::make('invoice_date')
                    ->label('تاريخ الفاتورة')
                    ->required()
                    ->native(false)
                    ->placeholder('اختر تاريخ الفاتورة'),

                ClientDatetimeHidden::make('created_at'),

                Repeater::make('items')
                    ->label('الأصناف')
                    ->columnSpanFull()
                    ->relationship('items') // SupplierInvoice hasMany SupplierInvoiceItem
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
                                return $product ? $product->name : '';
                            })
                            ->live()
                            ->columnSpan(2),

                        TextInput::make('cost_price')
                            ->label('سعر المورد للوحدة')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdatedJs(<<<'JS'
                                const subtotal = ($state ?? 0) * ($get('quantity') ?? 0);
                                $set('subtotal', subtotal);
                            JS)
                            ->skipRenderAfterStateUpdated(),

                        TextInput::make('quantity')
                            ->label('الكمية')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->afterStateUpdatedJs(<<<'JS'
                                const subtotal = ($state ?? 0) * ($get('cost_price') ?? 0);
                                $set('subtotal', subtotal);

                                // recalc total from all rows
                                let total = 0;
                                for (const item of $get('items') ?? []) {
                                    total += parseInt(item.subtotal ?? 0);
                                }
                                $set('total_placeholder', total + ' جنيه');
                            JS)
                            ->skipRenderAfterStateUpdated()
                            ->columnSpan(2),

                        TextInput::make('subtotal')
                            ->label('الإجمالي')
                            ->numeric()
                            ->required()
                            ->dehydrated(true)
                            ->columnSpan(2),

                        TextInput::make('wholesale_price')
                            ->label('سعر الجملة الجديد')
                            ->numeric()
                            ->required(),
                        TextInput::make('retail_price')
                            ->label('سعر القطاعي الجديد')
                            ->numeric()
                            ->required()
                            ->columnSpan(2)
                    ])
                    ->columns(5)
                    ->addActionLabel('إضافة صنف جديد')
                    ->defaultItems(1),
                TextEntry::make('total_placeholder')
                    ->label('إجمالي الفاتورة')
                    ->belowContent('هذا الحقل لمراجعة الاجمالي مع الفاتورة الأصلية')
                    ->live() // allow frontend updates
                    ->state(function ($get) {
                        return collect($get('items') ?? [])
                            ->sum(fn($item) => (int) ($item['subtotal'] ?? 0)) . ' جنيه';
                    })
                    ->extraAttributes([
                        'class' => 'bg-primary-600 text-white border rounded-lg shadow-sm p-3'
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
