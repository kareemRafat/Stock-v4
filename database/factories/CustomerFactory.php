<?php

namespace Database\Factories;
use Faker\Factory as FakerFactory;


use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = FakerFactory::create('ar_SA');

        return [
            'name' => $faker->name(), // اسم عربي حقيقي
            'phone' => $faker->phoneNumber(), // رقم موبايل عربي
            'address' => $faker->address(), // عنوان عربي
            'city' => $faker->city(), // عنوان عربي
            'governorate' => $faker->city(), // محافظة عربية
            'created_at' => now()->subDays(rand(0, 30)),
            'updated_at' => now(),
        ];
    }
}
