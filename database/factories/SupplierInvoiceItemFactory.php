<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SupplierInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierInvoiceItem>
 */
class SupplierInvoiceItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(5, 100);
        $price    = $this->faker->randomFloat(2, 10, 500);

        return [
            'supplier_invoice_id' => SupplierInvoice::factory(),
            'product_id'          => Product::factory(),
            'quantity'            => $quantity,
            'price'               => $price,
            'subtotal'            => $quantity * $price,
        ];
    }
}
