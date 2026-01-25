<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\User;
use App\Models\TrainingAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingAssessmentFactory extends Factory
{
    protected $model = TrainingAssessment::class;

    public function definition(): array
    {
        $totalScore = $this->faker->numberBetween(0, 100);
        $passScore = 60;
        $score = $this->faker->numberBetween(0, 100);

        return [
            'candidate_id' => Candidate::factory(),
            'batch_id' => Batch::factory(),
            'trainer_id' => User::factory(),
            'assessment_type' => $this->faker->randomElement(['initial', 'midterm', 'practical', 'final']),
            'assessment_date' => $this->faker->date(),
            'score' => $score,
            'theoretical_score' => $this->faker->numberBetween(0, 100),
            'practical_score' => $this->faker->numberBetween(0, 100),
            'total_score' => $totalScore,
            'max_score' => 100,
            'pass_score' => $passScore,
            'result' => $totalScore >= $passScore ? 'pass' : 'fail',
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }
}
