<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CampusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word() . ' Campus',
            'code' => fake()->unique()->lexify('CAMP-???'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'contact_person' => fake()->name(),
            'phone' => '051-' . fake()->numerify('########'),
            'email' => fake()->companyEmail(),
            'is_active' => true,
        ];
    }
}
