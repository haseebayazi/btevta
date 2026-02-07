<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Training;
use App\Enums\TrainingProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'batch_id' => Batch::factory(),
            'status' => 'not_started',
            'technical_training_status' => TrainingProgress::NOT_STARTED,
            'soft_skills_status' => TrainingProgress::NOT_STARTED,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn() => [
            'status' => 'in_progress',
            'technical_training_status' => TrainingProgress::IN_PROGRESS,
            'soft_skills_status' => TrainingProgress::IN_PROGRESS,
        ]);
    }

    public function technicalComplete(): static
    {
        return $this->state(fn() => [
            'status' => 'in_progress',
            'technical_training_status' => TrainingProgress::COMPLETED,
            'technical_completed_at' => now(),
            'soft_skills_status' => TrainingProgress::IN_PROGRESS,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn() => [
            'status' => 'completed',
            'technical_training_status' => TrainingProgress::COMPLETED,
            'soft_skills_status' => TrainingProgress::COMPLETED,
            'technical_completed_at' => now(),
            'soft_skills_completed_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
