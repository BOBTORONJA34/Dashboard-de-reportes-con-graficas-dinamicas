<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Sale;
use App\Models\Category;
use App\Models\Region;

class SaleFactory extends Factory
{
    // 👇 si tu Laravel es antiguo y no infiere el modelo, descomenta:
    // protected $model = Sale::class;

    public function definition(): array
    {
        $catId = Category::inRandomOrder()->value('id') ?? Category::factory()->create()->id;
        $regId = Region::inRandomOrder()->value('id') ?? Region::factory()->create()->id;

        return [
            'category_id' => $catId,
            'region_id'   => $regId,
            'sold_at'     => $this->faker->dateTimeBetween('-60 days', 'now'),
            'quantity'    => $this->faker->numberBetween(1, 8),
            'amount'      => $this->faker->randomFloat(2, 100, 5000),
        ];
    }
}
