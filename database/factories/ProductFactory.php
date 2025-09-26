<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'زيت عباد الشمس',
                'مكرونة إيطالي',
                'أرز مصري',
                'لبن كامل الدسم',
                'شاي أخضر',
                'سكر ناعم',
                'علبة تونة',
                'جبنة رومي',
                'بيض بلدي',
                'عصير مانجو',
            ]),
            'supplier_id' => 1 ,
            'description' => $this->faker->optional()->randomElement([
                'منتج عالي الجودة ومناسب للاستخدام اليومي.',
                'أفضل اختيار من حيث السعر والجودة.',
                'مناسب لجميع الأعمار ويُستخدم على نطاق واسع.',
                'تم تعبئته بعناية للحفاظ على النكهة الأصلية.',
                'مصدر غني بالعناصر الغذائية المفيدة.',
                'مصنوع وفقًا لأعلى معايير الجودة.',
                'اختيار مثالي للمطبخ العصري.',
                'تم اختباره معمليًا لضمان السلامة والجودة.',
                'يوفر لك تجربة طهي مميزة ومذاق رائع.',
                'متوفر الآن بسعر مميز لفترة محدودة.',
            ]),
            'production_price' => $this->faker->randomFloat(2, 10, 1000), // من 10 إلى 1000 جنيه
            'price' => $this->faker->randomFloat(2, 10, 1000), // من 10 إلى 1000 جنيه
            'discount' => $this->faker->numberBetween(0, 30), // خصم بين 0% و 30%
            'stock_quantity' => $this->faker->numberBetween(0, 500),
            'unit' => $this->faker->randomElement([
                'قطعة',
                'كرتونة',
                'كيلو',
                'لتر',
                'علبة',
            ]),
            'created_at' => now()->subDays(rand(0, 30)),
        ];
    }
}
