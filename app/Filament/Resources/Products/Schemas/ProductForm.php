<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Filament\Forms\Components\ClientDatetimeHidden;
use App\Models\Supplier;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->rules('required')
                    ->label('اسم المنتج')
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),

                TextInput::make('unit')
                    ->label('وحدة القياس')
                    ->rules('required')
                    ->required()
                    ->placeholder('كرتونة - قطعة - كيلو إلخ'),

                TextInput::make('production_price')
                    ->required()
                    ->rules('required')
                    ->label('سعر المصنع')
                    ->numeric()
                    ->suffix('جنيه'),

                TextInput::make('wholesale_price')
                    ->required()
                    ->rules('required')
                    ->label('سعر الجملة')
                    ->numeric()
                    ->suffix('جنيه'),

                TextInput::make('retail_price')
                    ->required()
                    ->rules('required')
                    ->label('سعر القطاعي')
                    ->numeric()
                    ->suffix('جنيه'),

                TextInput::make('discount')
                    ->required()
                    ->rules('required')
                    ->numeric()
                    ->label('الخصم')
                    ->suffix(' %')
                    ->default(0),

                TextInput::make('stock_quantity')
                    ->label('الكيمة المتاحة بالمخزن')
                    ->required()
                    ->rules('required')
                    ->numeric()
                    ->default(0),

                Select::make('supplier_id')
                    ->label('المورد')
                    ->helperText('يمكن عدم إختيار مورد فى حالة عدم وجود مورد')
                    ->searchable()
                    ->options(function () {
                        // get 10 when open
                        return Supplier::limit(15)->pluck('name', 'id')->toArray();
                    })
                    ->getOptionLabelUsing(fn($value) => Supplier::find($value)?->name)
                    ->getSearchResultsUsing(function ($search) {
                        return Supplier::where('name', 'like', "%{$search}%")
                            ->pluck('name', 'id')
                            ->toArray();
                    }),

                Textarea::make('description')
                    ->columnSpanFull()
                    ->label('وصف المنتج'),

                ClientDatetimeHidden::make('created_at')
            ]);
    }
}
