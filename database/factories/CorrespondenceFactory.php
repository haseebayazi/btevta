<?php

namespace Database\Factories;

use App\Models\Correspondence;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrespondenceFactory extends Factory
{
    protected $model = Correspondence::class;

    public function definition(): array
    {
        $sentAt = fake()->dateTimeBetween('-6 months', 'now');
        $replied = fake()->boolean(40);

        return [
            'campus_id' => fake()->optional()->passthrough(Campus::factory()),
            'oep_id' => fake()->optional()->passthrough(Oep::factory()),
            'candidate_id' => fake()->optional()->passthrough(Candidate::factory()),
            'subject' => fake()->sentence(),
            'message' => fake()->paragraph(3),
            'requires_reply' => fake()->boolean(70),
            'replied' => $replied,
            'sent_at' => $sentAt,
            'replied_at' => $replied ? fake()->dateTimeBetween($sentAt, 'now') : null,
            'status' => fake()->randomElement([
                Correspondence::STATUS_PENDING,
                Correspondence::STATUS_IN_PROGRESS,
                Correspondence::STATUS_REPLIED,
                Correspondence::STATUS_CLOSED,
            ]),
            'attachment_path' => fake()->optional()->filePath(),
        ];
    }

    /**
     * Indicate that the correspondence requires a reply
     */
    public function requiresReply(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_reply' => true,
            'replied' => false,
            'replied_at' => null,
            'status' => Correspondence::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the correspondence has been replied to
     */
    public function replied(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_reply' => true,
            'replied' => true,
            'replied_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? '-1 month', 'now'),
            'status' => Correspondence::STATUS_REPLIED,
        ]);
    }

    /**
     * Indicate that the correspondence is for a campus
     */
    public function forCampus(): static
    {
        return $this->state(fn (array $attributes) => [
            'campus_id' => Campus::factory(),
            'oep_id' => null,
            'candidate_id' => null,
        ]);
    }

    /**
     * Indicate that the correspondence is for an OEP
     */
    public function forOep(): static
    {
        return $this->state(fn (array $attributes) => [
            'campus_id' => null,
            'oep_id' => Oep::factory(),
            'candidate_id' => null,
        ]);
    }

    /**
     * Indicate that the correspondence is for a candidate
     */
    public function forCandidate(): static
    {
        return $this->state(fn (array $attributes) => [
            'campus_id' => null,
            'oep_id' => null,
            'candidate_id' => Candidate::factory(),
        ]);
    }

    /**
     * Indicate that the correspondence is closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Correspondence::STATUS_CLOSED,
            'replied' => true,
            'replied_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? '-1 month', 'now'),
        ]);
    }
}
