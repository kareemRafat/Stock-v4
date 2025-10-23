<?php

namespace App\Filament\Resources\ProductPurchases;

use App\Filament\Resources\ProductPurchases\Pages\CreateProductPurchase;
use App\Filament\Resources\ProductPurchases\Pages\EditProductPurchase;
use App\Filament\Resources\ProductPurchases\Pages\ListProductPurchases;
use App\Filament\Resources\ProductPurchases\Schemas\ProductPurchaseForm;
use App\Filament\Resources\ProductPurchases\Tables\ProductPurchasesTable;
use App\Models\SupplierInvoiceItem;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductPurchaseResource extends Resource
{
    protected static ?string $model = SupplierInvoiceItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'إدارة المخزون';

    protected static ?string $modelLabel = 'عملية شراء';

    protected static ?string $pluralModelLabel = 'عمليات الشراء للمخزن';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ProductPurchaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductPurchasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductPurchases::route('/'),
            // 'create' => CreateProductPurchase::route('/create'),
            // 'edit' => EditProductPurchase::route('/{record}/edit'),
        ];
    }
}
