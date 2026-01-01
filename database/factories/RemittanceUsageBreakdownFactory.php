<?php

namespace Database\Factories;

use App\Models\Remittance;
use App\Models\RemittanceUsageBreakdown;
use Illuminate\Database\Eloquent\Factories\Factory;

class RemittanceUsageBreakdownFactory extends Factory
{
    protected $model = RemittanceUsageBreakdown::class;

    public function definition(): array
    {
        return [
            'remittance_id' => Remittance::factory(),
            'category' => $this->faker->randomElement(['family_support', 'education', 'healthcare', 'savings', 'business', 'other']),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'description' => $this->faker->sentence(),
        ];
    }
}
