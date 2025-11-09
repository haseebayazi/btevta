<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'company_name' => fake()->company(),
            'registration_number' => fake()->unique()->numerify('REG-########'),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'contact_person' => fake()->name(),
            'is_active' => true,
        ];
    }
}
