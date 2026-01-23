<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\TrainingSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingScheduleFactory extends Factory
{
    protected $model = TrainingSchedule::class;

    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'start_time' => $this->faker->time('H:i'),
            'end_time' => $this->faker->time('H:i'),
            'session_type' => $this->faker->randomElement(['theory', 'practical', 'assessment']),
            'status' => $this->faker->randomElement(['scheduled', 'completed', 'cancelled']),
        ];
    }
}
