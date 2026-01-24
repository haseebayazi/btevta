<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\User;
use App\Models\TrainingAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingAttendanceFactory extends Factory
{
    protected $model = TrainingAttendance::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'batch_id' => Batch::factory(),
            'trainer_id' => User::factory(),
            'date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'leave']),
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }
}
