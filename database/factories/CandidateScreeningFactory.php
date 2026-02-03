<?php

namespace Database\Factories;

use App\Models\CandidateScreening;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateScreeningFactory extends Factory
{
    protected $model = CandidateScreening::class;

    public function definition(): array
    {
        $screeningTypes = [
            CandidateScreening::TYPE_DESK,
            CandidateScreening::TYPE_CALL,
            CandidateScreening::TYPE_PHYSICAL,
            CandidateScreening::TYPE_DOCUMENT,
            CandidateScreening::TYPE_MEDICAL,
        ];

        $statuses = [
            CandidateScreening::STATUS_PENDING,
            CandidateScreening::STATUS_IN_PROGRESS,
            CandidateScreening::STATUS_PASSED,
            CandidateScreening::STATUS_FAILED,
            CandidateScreening::STATUS_DEFERRED,
        ];

        return [
            'candidate_id' => Candidate::factory(),
            'screening_type' => fake()->randomElement($screeningTypes),
            'screening_stage' => fake()->numberBetween(1, 3),
            'status' => fake()->randomElement($statuses),
            'remarks' => fake()->optional()->sentence(),
            'screened_by' => fake()->optional()->passthrough(User::factory()),
            'screened_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'evidence_path' => fake()->optional()->filePath(),
            'call_count' => fake()->numberBetween(0, 3),
            'call_duration' => fake()->optional()->numberBetween(60, 900),
            'next_call_date' => fake()->optional()->dateTimeBetween('now', '+1 week'),
            'verification_status' => fake()->optional()->randomElement(['pending', 'verified', 'failed']),
            'verification_remarks' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => fake()->optional()->passthrough(User::factory()),
            // Module 2: Initial Screening fields
            'consent_for_work' => fake()->boolean(),
            'placement_interest' => fake()->randomElement(['local', 'international']),
            'target_country_id' => null,
            'screening_status' => fake()->randomElement(['pending', 'screened', 'deferred']),
            'reviewer_id' => fake()->optional()->passthrough(User::factory()),
            'reviewed_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the screening is a desk screening
     */
    public function desk(): static
    {
        return $this->state(fn (array $attributes) => [
            'screening_type' => CandidateScreening::TYPE_DESK,
            'call_count' => 0,
            'call_duration' => null,
            'next_call_date' => null,
        ]);
    }

    /**
     * Indicate that the screening is a call screening
     */
    public function call(): static
    {
        return $this->state(fn (array $attributes) => [
            'screening_type' => CandidateScreening::TYPE_CALL,
            'call_count' => fake()->numberBetween(1, 3),
            'call_duration' => fake()->numberBetween(120, 600),
        ]);
    }

    /**
     * Indicate that the screening is a physical screening
     */
    public function physical(): static
    {
        return $this->state(fn (array $attributes) => [
            'screening_type' => CandidateScreening::TYPE_PHYSICAL,
            'call_count' => 0,
            'call_duration' => null,
            'next_call_date' => null,
        ]);
    }

    /**
     * Indicate that the screening has passed
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CandidateScreening::STATUS_PASSED,
            'screened_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'screened_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the screening has failed
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CandidateScreening::STATUS_FAILED,
            'screened_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'screened_by' => User::factory(),
            'remarks' => 'Failed screening criteria',
        ]);
    }

    /**
     * Indicate that the screening is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CandidateScreening::STATUS_PENDING,
            'screened_at' => null,
            'screened_by' => null,
        ]);
    }

    /**
     * Indicate that the screening is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CandidateScreening::STATUS_IN_PROGRESS,
        ]);
    }
}
