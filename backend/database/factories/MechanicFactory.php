<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MechanicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name('male'),
            'phone' => fake()->numerify('08##########'),
            'is_active' => true,
        ];
    }
}
