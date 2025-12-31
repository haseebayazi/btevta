<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\TrainingCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingCertificateFactory extends Factory
{
    protected $model = TrainingCertificate::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'certificate_number' => $this->faker->unique()->bothify('CERT-####-????'),
            'issue_date' => $this->faker->date(),
            'certificate_type' => $this->faker->randomElement(['completion', 'participation', 'excellence']),
            'status' => $this->faker->randomElement(['issued', 'revoked']),
        ];
    }
}
