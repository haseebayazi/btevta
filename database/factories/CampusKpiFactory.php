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
            'year' => $this->faker->numberBetween(2023, 2026),
            'month' => $this->faker->numberBetween(1, 12),
            'candidates_registered' => $this->faker->numberBetween(10, 100),
            'candidates_trained' => $this->faker->numberBetween(5, 80),
            'candidates_departed' => $this->faker->numberBetween(0, 50),
            'candidates_rejected' => $this->faker->numberBetween(0, 10),
            'training_completion_rate' => $this->faker->randomFloat(2, 50, 100),
            'assessment_pass_rate' => $this->faker->randomFloat(2, 60, 100),
            'attendance_rate' => $this->faker->randomFloat(2, 70, 100),
        ];
    }
}
