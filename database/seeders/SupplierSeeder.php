<?php 
// database/seeders/SupplierSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\SupplierWallet;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::factory(10)->create()->each(function ($supplier) {
            // Create invoices for each supplier
            $invoices = SupplierInvoice::factory(3)->create([
                'supplier_id' => $supplier->id,
            ]);

            $invoices->each(function ($invoice) {
                SupplierInvoiceItem::factory(5)->create([
                    'supplier_invoice_id' => $invoice->id,
                ]);
            });

            // Create wallet transactions
            SupplierWallet::factory(5)->create([
                'supplier_id' => $supplier->id,
            ]);
        });
    }
}
