<?php

namespace App\Filament\Resources\Customers;

use UnitEnum;
use BackedEnum;
use App\Models\Customer;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Tables\CustomersTable;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Newspaper;

    protected static string|UnitEnum|null $navigationGroup = 'العملاء والمنتجات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';



    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
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
            'index' => ListCustomers::route('/'),
            'wallet' => Pages\CustomerWalletPage::route('/{record}/wallet'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    // Eager Loading Balance for the customer
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->addSelect([
                'balance_sum' => DB::table('customer_wallets')
                    ->selectRaw('COALESCE(SUM(CASE
                    WHEN type = "sale" THEN amount
                    WHEN type IN ("payment", "sale_return", "adjustment") THEN -amount
                    ELSE 0 END), 0)')
                    ->whereColumn('customer_wallets.customer_id', 'customers.id'),
            ]);
    }
}
