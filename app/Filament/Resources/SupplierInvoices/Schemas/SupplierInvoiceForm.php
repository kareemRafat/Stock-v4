<?php

namespace App\Filament\Resources\SupplierInvoices\Schemas;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use App\Models\Product;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

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
                            ->pluck('name', 'id');
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        // results when search
                        return Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->getOptionLabelUsing(fn ($value) => Supplier::find($value)?->name ?? 'محذوف'),
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
                            ->required()
                            ->options(fn () => Product::query()
                                ->orderBy('name')
                                ->limit(20)
                                ->pluck('name', 'id'))
                            ->getSearchResultsUsing(fn (string $search) => Product::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50)
                                ->pluck('name', 'id'))
                            ->getOptionLabelUsing(function ($value) {
                                static $cache = [];

                                if (! isset($cache[$value])) {
                                    $cache[$value] = Product::query()
                                        ->where('id', $value)
                                        ->value('name') ?? '';
                                }

                                return $cache[$value];
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $product = Product::find($state);
                                if ($product) {
                                    $set('wholesale_price', $product->wholesale_price ?? 0);
                                    $set('retail_price', $product->retail_price ?? 0);
                                }
                            })
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
                            ->skipRenderAfterStateUpdated()
                            ->columnSpan(2),

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
                            ->skipRenderAfterStateUpdated(),

                        TextInput::make('subtotal')
                            ->label('الإجمالي')
                            ->numeric()
                            ->required()
                            ->dehydrated(true)
                            ->columnSpan(2),

                        TextInput::make('wholesale_price')
                            ->label('سعر الجملة الجديد قبل الخصم')
                            ->numeric()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('retail_price')
                            ->label('سعر القطاعي الجديد')
                            ->numeric()
                            ->required(),

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
                            ->sum(fn ($item) => (int) ($item['subtotal'] ?? 0)).' جنيه';
                    })
                    ->extraAttributes([
                        'class' => 'bg-primary-600 text-white border rounded-lg shadow-sm p-3',
                    ])
                    ->columnSpanFull(),

                TextInput::make('paid_amount')
                    ->label('المبلغ المدفوع')
                    ->numeric()
                    ->prefix('جنيه')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
