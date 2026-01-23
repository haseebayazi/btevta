<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->country(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'code_2' => strtoupper($this->faker->unique()->lexify('??')),
            'currency_code' => strtoupper($this->faker->currencyCode()),
            'phone_code' => '+' . $this->faker->numberBetween(1, 999),
            'is_destination' => $this->faker->boolean(),
            'specific_requirements' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the country is a destination country.
     */
    public function destination(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_destination' => true,
        ]);
    }

    /**
     * Indicate that the country is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
