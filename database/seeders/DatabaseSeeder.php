<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerWallet;
use App\Models\Invoice;
use App\Models\InvoiceItem;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\OutsourcedProduction;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'كريم',
            'email' => 'admin@admin.com',
            'username' => 'kareem',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'ايمان',
            'username' => 'eman',
            'email' => 'eman@admin.com',
            'password' => bcrypt('12345678'),
            'role' => 'employee',
        ]);

        // supplier seeders
        $this->call([
            // SupplierSeeder::class,
        ]);

        // Product::factory(50)->create();
        Customer::factory(10)->create();
        // Invoice::factory(20)->create();
        // InvoiceItem::factory(20)->create();
        // CustomerWallet::factory(20)->create();
        Supplier::factory(10)->create();

        // OutsourcedProduction::factory(20)->create();
    }
}
