<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerWallet>
 */
class CustomerWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['debit', 'invoice', 'credit']);
        $amount = $this->faker->randomFloat(2, 50, 1000);

        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(),
            'type' => $type,
            'amount' => $amount,
            'invoice_id' => $type === 'invoice' ? Invoice::inRandomOrder()->first()?->id : null,
        ];
    }
}
