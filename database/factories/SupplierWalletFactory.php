<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupplierWallet>
 */
class SupplierWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type   = $this->faker->randomElement(['debit', 'invoice', 'credit']);
        $amount = $this->faker->randomFloat(2, 100, 2000);

        return [
            'supplier_id'         => Supplier::factory(),
            'type'                => $type,
            'amount'              => $amount,
            'supplier_invoice_id' => SupplierInvoice::factory(),
            'note'                => $this->faker->sentence(),
        ];
    }
}
