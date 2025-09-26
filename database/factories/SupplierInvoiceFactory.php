<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierInvoice>
 */
class SupplierInvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id'   => Supplier::factory(),
            'invoice_date'  => $this->faker->dateTimeBetween('-6 months', 'now'),
            'total_amount'  => $this->faker->randomFloat(2, 500, 10000),
            'invoice_number'=> 'SUP-' . $this->faker->unique()->numberBetween(1000, 9999),
            'notes'         => $this->faker->sentence(),
        ];
    }
}
