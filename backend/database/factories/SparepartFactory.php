<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SparepartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.fake()->unique()->numerify('####'),
            'name' => 'Sparepart '.fake()->word(),
            'price' => fake()->numberBetween(10, 400) * 1000,
            'stock' => fake()->numberBetween(0, 40),
            'min_stock' => 5,
        ];
    }
}
