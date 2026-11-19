<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'plate_number' => Str::upper(fake()->bothify('? #### ??')),
            'type' => fake()->randomElement(['motor', 'mobil']),
            'brand' => fake()->randomElement(['Honda', 'Yamaha', 'Toyota', 'Suzuki', 'Daihatsu']),
            'model' => Str::upper(fake()->bothify('MODEL-###')),
            'year' => fake()->numberBetween(2005, 2025),
        ];
    }
}
