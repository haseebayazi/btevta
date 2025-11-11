<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\User;

class RemittanceFactory extends Factory
{
    public function definition(): array
    {
        $transferDate = fake()->dateTimeBetween('-1 year', 'now');
        $date = \Carbon\Carbon::parse($transferDate);

        return [
            'candidate_id' => Candidate::factory(),
            'departure_id' => Departure::factory(),
            'recorded_by' => User::factory(),
            'transaction_reference' => 'TXN' . fake()->unique()->numerify('##########'),
            'amount' => fake()->randomFloat(2, 10000, 150000),
            'currency' => 'PKR',
            'amount_foreign' => fake()->randomFloat(2, 50, 500),
            'foreign_currency' => fake()->randomElement(['USD', 'SAR', 'AED', 'QAR', 'KWD']),
            'exchange_rate' => fake()->randomFloat(4, 200, 350),
            'transfer_date' => $transferDate,
            'transfer_method' => fake()->randomElement(['bank_transfer', 'money_exchange', 'online_transfer', 'cash_deposit']),
            'sender_name' => fake()->name(),
            'sender_location' => fake()->randomElement(['Riyadh, Saudi Arabia', 'Dubai, UAE', 'Doha, Qatar', 'Kuwait City, Kuwait']),
            'receiver_name' => fake()->name(),
            'receiver_account' => fake()->numerify('PK####################'),
            'bank_name' => fake()->randomElement(['HBL', 'UBL', 'MCB', 'Allied Bank', 'Meezan Bank', 'Faysal Bank']),
            'primary_purpose' => fake()->randomElement(['family_support', 'education', 'healthcare', 'debt_repayment', 'savings', 'investment', 'other']),
            'purpose_description' => fake()->optional()->sentence(),
            'has_proof' => fake()->boolean(85),
            'proof_verified_date' => fake()->optional(0.7)->dateTimeBetween($transferDate, 'now'),
            'verified_by' => fake()->optional(0.7)->randomElement([1, 2, 3]),
            'status' => fake()->randomElement(['pending', 'verified', 'flagged']),
            'notes' => fake()->optional()->paragraph(),
            'alert_message' => null,
            'is_first_remittance' => fake()->boolean(20),
            'month_number' => fake()->numberBetween(1, 24),
            'year' => $date->year,
            'month' => $date->month,
            'quarter' => $date->quarter,
        ];
    }

    /**
     * Indicate that the remittance is verified.
     */
    public function verified()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'verified',
            'has_proof' => true,
            'proof_verified_date' => now(),
            'verified_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the remittance is pending.
     */
    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'proof_verified_date' => null,
            'verified_by' => null,
        ]);
    }

    /**
     * Indicate that the remittance has no proof.
     */
    public function withoutProof()
    {
        return $this->state(fn (array $attributes) => [
            'has_proof' => false,
            'proof_verified_date' => null,
        ]);
    }

    /**
     * Indicate that this is the first remittance.
     */
    public function firstRemittance()
    {
        return $this->state(fn (array $attributes) => [
            'is_first_remittance' => true,
            'month_number' => 1,
        ]);
    }
}
