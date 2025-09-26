<?php

namespace App\Filament\Resources\SupplierInvoices;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Models\SupplierInvoice;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\SupplierInvoices\Pages\EditSupplierInvoice;
use App\Filament\Resources\SupplierInvoices\Pages\ListSupplierInvoices;
use App\Filament\Resources\SupplierInvoices\Pages\CreateSupplierInvoice;
use App\Filament\Resources\SupplierInvoices\Schemas\SupplierInvoiceForm;
use App\Filament\Resources\SupplierInvoices\Tables\SupplierInvoicesTable;
use App\Filament\Resources\SupplierInvoiceResource\Pages\ViewSupplierInvoice;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Calculator;

    protected static string|UnitEnum|null $navigationGroup = 'الموردين';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'فاتورة مورد';

    protected static ?string $pluralModelLabel = 'فواتير الموردين';

    public static function form(Schema $schema): Schema
    {
        return SupplierInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierInvoicesTable::configure($table);
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
            'index' => ListSupplierInvoices::route('/'),
            'create' => CreateSupplierInvoice::route('/create'),
            'view' => ViewSupplierInvoice::route('/{record}'),
            // 'edit' => EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
