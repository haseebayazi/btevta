<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\RegistrationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegistrationDocumentFactory extends Factory
{
    protected $model = RegistrationDocument::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'document_type' => $this->faker->randomElement(['cnic', 'passport', 'photo', 'education_certificate', 'domicile']),
            'document_number' => $this->faker->bothify('DOC-####-????'),
            'file_path' => 'documents/' . $this->faker->uuid() . '.pdf',
            'is_verified' => $this->faker->boolean(),
        ];
    }
}
