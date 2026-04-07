<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Correspondence;
use App\Models\Oep;
use Illuminate\Database\Eloquent\Factories\Factory;

class CorrespondenceFactory extends Factory
{
    protected $model = Correspondence::class;

    public function definition(): array
    {
        $sentAt  = fake()->dateTimeBetween('-6 months', 'now');
        $replied = fake()->boolean(40);
        $type    = fake()->randomElement([Correspondence::TYPE_INCOMING, Correspondence::TYPE_OUTGOING]);

        return [
            // Relations
            'campus_id'    => null,
            'oep_id'       => null,
            'candidate_id' => null,
            'assigned_to'  => null,
            'created_by'   => null,
            'updated_by'   => null,

            // Direction & reference
            'type'                  => $type,
            'file_reference_number' => Correspondence::generateFileReferenceNumber(),
            'organization_type'     => fake()->randomElement([
                Correspondence::ORG_BTEVTA,
                Correspondence::ORG_OEP,
                Correspondence::ORG_EMBASSY,
                Correspondence::ORG_CAMPUS,
                Correspondence::ORG_GOVERNMENT,
                Correspondence::ORG_OTHER,
            ]),

            // Parties
            'sender'    => fake()->company(),
            'recipient' => fake()->company(),

            // Content
            'subject'     => fake()->sentence(),
            'message'     => fake()->paragraph(3),
            'description' => fake()->optional()->paragraph(2),
            'notes'       => fake()->optional()->sentence(),

            // Priority & status
            'priority_level' => fake()->randomElement([
                Correspondence::PRIORITY_LOW,
                Correspondence::PRIORITY_NORMAL,
                Correspondence::PRIORITY_HIGH,
            ]),
            'status' => fake()->randomElement([
                Correspondence::STATUS_PENDING,
                Correspondence::STATUS_IN_PROGRESS,
                Correspondence::STATUS_REPLIED,
                Correspondence::STATUS_CLOSED,
            ]),

            // Reply tracking
            'requires_reply' => fake()->boolean(70),
            'replied'        => $replied,
            'sent_at'        => $sentAt,
            'replied_at'     => $replied ? fake()->dateTimeBetween($sentAt, 'now') : null,
            'due_date'       => fake()->optional()->dateTimeBetween('now', '+3 months'),

            // Attachment
            'attachment_path' => fake()->optional()->filePath(),
        ];
    }

    // ─── Named states ─────────────────────────────────────────────────────────

    public function incoming(): static
    {
        return $this->state(['type' => Correspondence::TYPE_INCOMING]);
    }

    public function outgoing(): static
    {
        return $this->state(['type' => Correspondence::TYPE_OUTGOING]);
    }

    public function requiresReply(): static
    {
        return $this->state([
            'requires_reply' => true,
            'replied'        => false,
            'replied_at'     => null,
            'status'         => Correspondence::STATUS_PENDING,
            'due_date'       => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    public function replied(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_reply' => true,
            'replied'        => true,
            'replied_at'     => fake()->dateTimeBetween($attributes['sent_at'] ?? '-1 month', 'now'),
            'status'         => Correspondence::STATUS_REPLIED,
        ]);
    }

    public function overdue(): static
    {
        return $this->state([
            'requires_reply' => true,
            'replied'        => false,
            'replied_at'     => null,
            'status'         => Correspondence::STATUS_PENDING,
            'due_date'       => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(['priority_level' => Correspondence::PRIORITY_URGENT]);
    }

    public function forCampus(): static
    {
        return $this->state([
            'campus_id'    => Campus::factory(),
            'oep_id'       => null,
            'candidate_id' => null,
        ]);
    }

    public function forOep(): static
    {
        return $this->state([
            'campus_id'    => null,
            'oep_id'       => Oep::factory(),
            'candidate_id' => null,
        ]);
    }

    public function forCandidate(): static
    {
        return $this->state([
            'campus_id'    => null,
            'oep_id'       => null,
            'candidate_id' => Candidate::factory(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => Correspondence::STATUS_CLOSED,
            'replied'    => true,
            'replied_at' => fake()->dateTimeBetween($attributes['sent_at'] ?? '-1 month', 'now'),
        ]);
    }
}
