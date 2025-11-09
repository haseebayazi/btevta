<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TradeFactory extends Factory
{
    public function definition(): array
    {
        $trades = ['Electrician', 'Plumber', 'Mason', 'Carpenter', 'Welder', 'Mechanic', 'Painter'];
        
        return [
            'code' => fake()->unique()->lexify('TRADE-???'),
            'name' => fake()->randomElement($trades),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Technical', 'Service', 'Construction']),
            'duration_months' => fake()->randomElement([6, 12, 18, 24]),
            'is_active' => true,
        ];
    }
}