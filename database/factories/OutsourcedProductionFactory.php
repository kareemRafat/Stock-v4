<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OutsourcedProduction>
 */
class OutsourcedProductionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_name' => $this->faker->word(), // اسم المنتج
            'factory_name' => $this->faker->word(), // اسم المصنع
            'quantity' => $this->faker->numberBetween(10, 500), // الكمية
            'size' => $this->faker->randomElement(['10 طن ' , '30 سم']),
            'total_cost' => $this->faker->randomFloat(2, 1000, 100000), // التكلفة
            'start_date' => $this->faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'actual_delivery_date' => $this->faker->optional()->dateTimeBetween('now', '+2 months')?->format('Y-m-d'),
            'status' => $this->faker->randomElement(['in_progress', 'completed', 'canceled']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
