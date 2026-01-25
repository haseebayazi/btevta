<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Candidate;
use App\Models\Remittance;
use App\Models\User;

class RemittanceAlertFactory extends Factory
{
    public function definition(): array
    {
        $alertType = fake()->randomElement(['missing_remittance', 'missing_proof', 'first_remittance_delay', 'low_frequency', 'unusual_amount']);

        $titles = [
            'missing_remittance' => 'No Recent Remittances',
            'missing_proof' => 'Missing Proof of Remittance',
            'first_remittance_delay' => 'First Remittance Delayed',
            'low_frequency' => 'Low Remittance Frequency',
            'unusual_amount' => 'Unusual Remittance Amount',
        ];

        $severities = [
            'missing_remittance' => 'warning',
            'missing_proof' => 'warning',
            'first_remittance_delay' => 'critical',
            'low_frequency' => 'info',
            'unusual_amount' => 'warning',
        ];

        return [
            'candidate_id' => Candidate::factory(),
            'remittance_id' => fake()->optional(0.6)->randomElement([null, Remittance::factory()]),
            'alert_type' => $alertType,
            'severity' => $severities[$alertType] ?? 'info',
            'title' => $titles[$alertType] ?? 'Alert',
            'message' => fake()->sentence(15),
            'metadata' => [
                'days_since_last_remittance' => fake()->numberBetween(30, 180),
                'expected_count' => fake()->numberBetween(3, 12),
                'actual_count' => fake()->numberBetween(0, 5),
            ],
            'is_read' => fake()->boolean(40),
            'is_resolved' => fake()->boolean(30),
            'resolved_by' => fake()->optional(0.3)->randomElement([null, User::factory()]),
            'resolved_at' => fake()->optional(0.3)->dateTimeBetween('-30 days', 'now'),
            'resolution_notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the alert is unread.
     */
    public function unread()
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    /**
     * Indicate that the alert is unresolved.
     */
    public function unresolved()
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => false,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ]);
    }

    /**
     * Indicate that the alert is critical.
     */
    public function critical()
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'critical',
        ]);
    }

    /**
     * Indicate that the alert is for missing remittance.
     */
    public function missingRemittance()
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'missing_remittance',
            'severity' => 'warning',
            'title' => 'No Recent Remittances',
            'remittance_id' => null,
        ]);
    }

    /**
     * Indicate that the alert is for missing proof.
     */
    public function missingProof()
    {
        return $this->state(fn (array $attributes) => [
            'alert_type' => 'missing_proof',
            'severity' => 'warning',
            'title' => 'Missing Proof of Remittance',
            'remittance_id' => Remittance::factory(),
        ]);
    }
}
