<?php

namespace App\Filament\Resources\ReturnInvoices;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use App\Models\ReturnInvoice;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\ReturnInvoices\Pages\ViewReturnInvoice;
use App\Filament\Resources\ReturnInvoices\Pages\ListReturnInvoices;
use App\Filament\Resources\ReturnInvoices\Pages\CreateReturnInvoice;
use App\Filament\Resources\ReturnInvoices\Schemas\ReturnInvoiceForm;
use App\Filament\Resources\ReturnInvoices\Tables\ReturnInvoicesTable;

class ReturnInvoiceResource extends Resource
{
    protected static ?string $model = ReturnInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ArrowUturnLeft;

    protected static string|UnitEnum|null $navigationGroup = 'العملاء والمنتجات';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'فواتير المرتجعات';

    protected static ?string $pluralModelLabel = 'فواتير المرتجعات';

    protected static ?string $modelLabel = 'فاتورة مرتجع';

    public static function form(Schema $schema): Schema
    {
        return ReturnInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnInvoicesTable::configure($table);
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
            'index' => ListReturnInvoices::route('/'),
            'create' => CreateReturnInvoice::route('/create'),
            'view' => ViewReturnInvoice::route('/{record}'),
            // 'edit' => EditReturnInvoice::route('/{record}/edit'),
        ];
    }
}
