<?php

namespace Database\Factories;

use App\Models\VisaProcess;
use App\Models\Candidate;
use App\Models\Oep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisaProcessFactory extends Factory
{
    protected $model = VisaProcess::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'visa_partner_id' => fake()->optional()->passthrough(Oep::factory()),

            // Interview & Trade Test
            'interview_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'interview_status' => fake()->optional()->randomElement(['pending', 'scheduled', 'completed', 'passed', 'failed']),
            'interview_completed' => fake()->boolean(30),
            'interview_remarks' => fake()->optional()->sentence(),

            'trade_test_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'trade_test_status' => fake()->optional()->randomElement(['pending', 'scheduled', 'completed', 'passed', 'failed']),
            'trade_test_completed' => fake()->boolean(30),
            'trade_test_remarks' => fake()->optional()->sentence(),

            // Takamol Test
            'takamol_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'takamol_booking_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'takamol_status' => fake()->optional()->randomElement(['pending', 'booked', 'completed', 'passed', 'failed']),
            'takamol_remarks' => fake()->optional()->sentence(),
            'takamol_result_path' => fake()->optional()->filePath(),
            'takamol_score' => fake()->optional()->numberBetween(50, 100),

            // Medical/GAMCA
            'medical_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'gamca_booking_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'medical_status' => fake()->optional()->randomElement(['pending', 'booked', 'completed', 'fit', 'unfit']),
            'medical_completed' => fake()->boolean(30),
            'medical_remarks' => fake()->optional()->sentence(),
            'gamca_result_path' => fake()->optional()->filePath(),
            'gamca_barcode' => fake()->optional()->numerify('GAMCA########'),
            'gamca_expiry_date' => fake()->optional()->dateTimeBetween('+1 year', '+2 years'),

            // E-Number
            'enumber' => fake()->optional()->numerify('E########'),
            'enumber_date' => fake()->optional()->dateTimeBetween('-2 months', 'now'),
            'enumber_status' => fake()->optional()->randomElement(['pending', 'applied', 'issued']),

            // Biometrics/Etimad
            'biometric_date' => fake()->optional()->dateTimeBetween('-2 months', '+1 month'),
            'etimad_appointment_id' => fake()->optional()->numerify('ETIMAD######'),
            'etimad_center' => fake()->optional()->randomElement(['Islamabad', 'Karachi', 'Lahore', 'Peshawar']),
            'biometric_status' => fake()->optional()->randomElement(['pending', 'scheduled', 'completed']),
            'biometric_completed' => fake()->boolean(20),
            'biometric_remarks' => fake()->optional()->sentence(),

            // Visa Documents Submission
            'visa_submission_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'visa_application_number' => fake()->optional()->numerify('VISA########'),
            'embassy' => fake()->optional()->randomElement(['Saudi Embassy Islamabad', 'Saudi Consulate Karachi', 'Saudi Consulate Lahore']),

            // Visa & PTN
            'visa_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'visa_number' => fake()->optional()->numerify('V########'),
            'visa_status' => fake()->optional()->randomElement(['pending', 'applied', 'issued', 'rejected']),
            'visa_issued' => fake()->boolean(20),
            'visa_remarks' => fake()->optional()->sentence(),
            'ptn_number' => fake()->optional()->numerify('PTN########'),
            'ptn_issue_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'attestation_date' => fake()->optional()->dateTimeBetween('-1 month', 'now'),

            // Ticket & Travel
            'ticket_uploaded' => fake()->boolean(15),
            'ticket_date' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'ticket_path' => fake()->optional()->filePath(),
            'ticket_number' => fake()->optional()->numerify('TKT########'),
            'flight_number' => fake()->optional()->bothify('??-####'),
            'departure_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'arrival_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'travel_plan_path' => fake()->optional()->filePath(),

            // General
            'overall_status' => fake()->randomElement(['initiated', 'in_progress', 'pending_documents', 'completed', 'rejected']),
            'current_stage' => fake()->randomElement(['initiated', 'interview', 'trade_test', 'takamol', 'medical', 'enumber', 'biometrics', 'visa_submission', 'visa_issued', 'ticket', 'completed']),
            'remarks' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
            'updated_by' => fake()->optional()->passthrough(User::factory()),
        ];
    }

    /**
     * Indicate that the visa process is at interview stage
     */
    public function atInterviewStage(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'interview',
            'overall_status' => 'in_progress',
            'interview_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'interview_status' => 'scheduled',
        ]);
    }

    /**
     * Indicate that the visa has been issued
     */
    public function visaIssued(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'visa_issued',
            'overall_status' => 'completed',
            'visa_issued' => true,
            'visa_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'visa_number' => fake()->numerify('V########'),
            'visa_status' => 'issued',
            'ptn_number' => fake()->numerify('PTN########'),
            'ptn_issue_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the visa process is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'completed',
            'overall_status' => 'completed',
            'interview_completed' => true,
            'trade_test_completed' => true,
            'medical_completed' => true,
            'biometric_completed' => true,
            'visa_issued' => true,
            'ticket_uploaded' => true,
        ]);
    }

    /**
     * Indicate that the visa process is at medical stage
     */
    public function atMedicalStage(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'medical',
            'overall_status' => 'in_progress',
            'interview_completed' => true,
            'trade_test_completed' => true,
            'medical_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'medical_status' => 'booked',
        ]);
    }

    /**
     * Indicate that the visa process is pending documents
     */
    public function pendingDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_status' => 'pending_documents',
            'remarks' => 'Awaiting required documents from candidate',
        ]);
    }

    /**
     * Indicate that the visa was rejected
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'overall_status' => 'rejected',
            'visa_status' => 'rejected',
            'visa_remarks' => 'Visa application rejected',
        ]);
    }
}
