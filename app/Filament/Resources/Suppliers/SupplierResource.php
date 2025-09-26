<?php

namespace App\Filament\Resources\Suppliers;

use UnitEnum;
use BackedEnum;
use App\Models\Supplier;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use App\Filament\Resources\SupplierResource\Pages\SupplierWalletPage;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Truck;

    protected static string|UnitEnum|null $navigationGroup = 'الموردين';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'الموردين';

    protected static ?string $pluralModelLabel = 'الموردين';

    protected static ?string $modelLabel = 'مورد';

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
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
            'index' => ListSuppliers::route('/'),
            'edit' => EditSupplier::route('/{record}/edit'),
            'wallet' => SupplierWalletPage::route('/{record}/wallet'),
        ];
    }
}
