<?php

namespace Database\Factories;

use App\Models\ImplementingPartner;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImplementingPartnerFactory extends Factory
{
    protected $model = ImplementingPartner::class;

    public function definition(): array
    {
        $name = fake()->company();
        $code = strtoupper(substr(preg_replace('/[^A-Z]/', '', $name), 0, 3)) . fake()->numerify('##');

        return [
            'name' => $name,
            'code' => $code,
            'contact_person' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => '03' . fake()->numerify('##########'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country_id' => Country::factory(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the implementing partner is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the implementing partner is in Pakistan
     */
    public function inPakistan(): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => fake()->randomElement(['Rawalpindi', 'Islamabad', 'Lahore', 'Karachi', 'Peshawar', 'Multan', 'Faisalabad']),
        ]);
    }
}
