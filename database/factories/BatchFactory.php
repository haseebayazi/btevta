<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Batch-' . fake()->numerify('####'),
            'description' => fake()->sentence(),
            'start_date' => fake()->dateTimeBetween('-3 months'),
            'end_date' => fake()->dateTimeBetween('+3 months'),
            'capacity' => fake()->randomElement([20, 30, 40, 50]),
            'status' => fake()->randomElement(['planned', 'active', 'completed']),
        ];
    }
}
