<?php

namespace Database\Factories;
use Faker\Factory as FakerFactory;


use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::inRandomOrder()->value('id') ?? \App\Models\Customer::factory(), // fallback factory إذا ما في عملاء
            'invoice_number' => $this->faker->unique()->numerify('inv-#####'), // رقم فاتورة فريد
            'total_amount' => $this->faker->randomFloat(2, 100, 5000), // من 100 إلى 5000 جنيه
            'notes' => $this->faker->optional()->sentence(10, true),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
