<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\TrainingCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingCertificateFactory extends Factory
{
    protected $model = TrainingCertificate::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'batch_id' => Batch::factory(),
            'certificate_number' => $this->faker->unique()->bothify('CERT-####-????'),
            'issue_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['issued', 'revoked']),
        ];
    }
}
