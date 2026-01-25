<?php

namespace Database\Factories;

use App\Models\Remittance;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RemittanceFactory extends Factory
{
    protected $model = Remittance::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 1000, 50000);
        $exchangeRate = $this->faker->randomFloat(4, 0.5, 300);

        $transactionDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $year = (int) date('Y', $transactionDate->getTimestamp());
        $month = (int) date('n', $transactionDate->getTimestamp());

        return [
            'candidate_id' => Candidate::factory(),
            'departure_id' => Departure::factory(),
            'campus_id' => Campus::factory(),
            'transaction_reference' => 'RMT-' . now()->format('Ymd') . '-' . strtoupper($this->faker->bothify('??####')),
            'transaction_type' => $this->faker->randomElement(['salary', 'bonus', 'allowance', 'reimbursement']),
            'transaction_date' => $transactionDate,
            'transfer_date' => $transactionDate, // Legacy 2025 field - same as transaction_date
            'amount' => $amount,
            'currency' => $this->faker->randomElement(['SAR', 'PKR', 'USD']),
            'exchange_rate' => $exchangeRate,
            'amount_in_pkr' => $amount * $exchangeRate,
            'transfer_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'mobile_wallet']),
            'bank_name' => $this->faker->randomElement(['Meezan Bank', 'HBL', 'UBL', 'Allied Bank', 'MCB']),
            'account_number' => $this->faker->numerify('####-#######-###'),
            'sender_name' => $this->faker->name(),
            'receiver_name' => $this->faker->name(),
            'purpose' => $this->faker->randomElement(['Monthly salary', 'Overtime payment', 'End of service benefit', 'Bonus payment']),
            'description' => $this->faker->optional()->sentence(),
            'month' => $month,
            'year' => $year,
            'month_year' => $this->faker->date('Y-m'),
            'verification_status' => $this->faker->randomElement(['pending', 'verified', 'rejected', 'under_review']),
            'status' => $this->faker->randomElement(['initiated', 'processing', 'completed', 'failed']),
            'recorded_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the remittance is verified
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'verified_by' => User::factory(),
            'verified_at' => now(),
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the remittance is pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'pending',
            'verified_by' => null,
            'verified_at' => null,
            'status' => 'initiated',
        ]);
    }

    /**
     * Indicate that the remittance is rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'rejected',
            'verified_by' => User::factory(),
            'verified_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the remittance has proof document
     */
    public function withProof(): static
    {
        return $this->state(fn (array $attributes) => [
            'proof_document_path' => 'remittances/proofs/test_proof.pdf',
            'proof_document_type' => 'pdf',
            'proof_document_size' => $this->faker->numberBetween(100000, 5000000),
        ]);
    }
}
