<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->lexify('???-###')),
            'description' => fake()->sentence(),
            'duration_weeks' => fake()->numberBetween(4, 52),
            'country_id' => Country::factory(),
            'is_active' => fake()->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Indicate that the program is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the program is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
