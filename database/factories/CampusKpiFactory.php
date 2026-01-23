<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\CampusKpi;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampusKpiFactory extends Factory
{
    protected $model = CampusKpi::class;

    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'target_value' => $this->faker->numberBetween(70, 100),
            'actual_value' => $this->faker->numberBetween(50, 100),
            'period' => $this->faker->date('Y-m'),
            'status' => $this->faker->randomElement(['on_track', 'at_risk', 'behind']),
        ];
    }
}
