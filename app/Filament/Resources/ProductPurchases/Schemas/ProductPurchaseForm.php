<?php

namespace App\Filament\Resources\ProductPurchases\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductPurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                TextInput::make('purchase_price')
                    ->required()
                    ->numeric(),
                TextInput::make('total_cost')
                    ->required()
                    ->numeric(),
                DatePicker::make('purchase_date')
                    ->required(),
                Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->default(null),
                TextInput::make('supplier_invoice_number')
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
