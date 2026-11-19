<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Jasa '.fake()->word(),
            'price' => fake()->numberBetween(25, 500) * 1000,
            'est_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
        ];
    }
}
