<?php

namespace App\Filament\Resources\OutsourcedProductions;

use App\Filament\Resources\OutsourcedProductions\Pages\CreateOutsourcedProduction;
use App\Filament\Resources\OutsourcedProductions\Pages\EditOutsourcedProduction;
use App\Filament\Resources\OutsourcedProductions\Pages\ListOutsourcedProductions;
use App\Filament\Resources\OutsourcedProductions\Schemas\OutsourcedProductionForm;
use App\Filament\Resources\OutsourcedProductions\Tables\OutsourcedProductionsTable;
use App\Models\OutsourcedProduction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OutsourcedProductionResource extends Resource
{
    protected static ?string $model = OutsourcedProduction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::BuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'الموردين';

    // ! remove from navigation
    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'اوردر تصنيع'; // Singular

    protected static ?string $pluralModelLabel = 'التصنيع الخارجي'; // Plural

    public static function form(Schema $schema): Schema
    {
        return OutsourcedProductionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutsourcedProductionsTable::configure($table);
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
            'index' => ListOutsourcedProductions::route('/'),
            // 'create' => CreateOutsourcedProduction::route('/create'),
            // 'edit' => EditOutsourcedProduction::route('/{record}/edit'),
        ];
    }
}
