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
            'campus_id' => null,
            'instructor_id' => null,
            'trade_id' => null,
            'module_name' => $this->faker->words(3, true),
            'module_description' => $this->faker->optional()->sentence(),
            'module_number' => $this->faker->numberBetween(1, 20),
            'duration_hours' => $this->faker->numberBetween(1, 8),
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'start_time' => $this->faker->time('H:i'),
            'end_time' => $this->faker->time('H:i'),
            'room' => $this->faker->optional()->bothify('Room-##'),
            'building' => $this->faker->optional()->randomElement(['A', 'B', 'C']),
        ];
    }
}
