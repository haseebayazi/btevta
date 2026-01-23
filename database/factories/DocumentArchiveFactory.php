<?php

namespace Database\Factories;

use App\Models\DocumentArchive;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentArchiveFactory extends Factory
{
    protected $model = DocumentArchive::class;

    public function definition(): array
    {
        $documentTypes = [
            'cnic' => 'CNIC',
            'passport' => 'Passport',
            'certificate' => 'Certificate',
            'photo' => 'Photograph',
            'medical' => 'Medical Report',
            'visa' => 'Visa',
            'ticket' => 'Ticket',
            'contract' => 'Contract',
            'other' => 'Other',
        ];

        $documentCategories = [
            'identity',
            'education',
            'medical',
            'travel',
            'legal',
            'training',
            'other',
        ];

        $fileTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $fileType = fake()->randomElement($fileTypes);

        return [
            'candidate_id' => fake()->optional()->passthrough(Candidate::factory()),
            'campus_id' => fake()->optional()->passthrough(Campus::factory()),
            'oep_id' => fake()->optional()->passthrough(Oep::factory()),
            'document_category' => fake()->randomElement($documentCategories),
            'document_type' => fake()->randomElement(array_keys($documentTypes)),
            'document_name' => fake()->words(3, true) . '.' . $fileType,
            'document_number' => fake()->optional()->numerify('DOC-##########'),
            'file_path' => 'documents/' . fake()->uuid() . '.' . $fileType,
            'file_type' => $fileType,
            'file_size' => fake()->numberBetween(10240, 5242880), // 10KB to 5MB
            'version' => 1,
            'uploaded_by' => User::factory(),
            'uploaded_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'is_current_version' => true,
            'replaces_document_id' => null,
            'issue_date' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
            'expiry_date' => fake()->optional()->dateTimeBetween('now', '+5 years'),
            'description' => fake()->optional()->sentence(),
            'tags' => fake()->optional()->words(3, true),
        ];
    }

    /**
     * Indicate that the document is a CNIC
     */
    public function cnic(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_category' => 'identity',
            'document_type' => 'cnic',
            'document_name' => 'CNIC.pdf',
            'file_type' => 'pdf',
        ]);
    }

    /**
     * Indicate that the document is a passport
     */
    public function passport(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_category' => 'identity',
            'document_type' => 'passport',
            'document_name' => 'Passport.pdf',
            'file_type' => 'pdf',
            'expiry_date' => fake()->dateTimeBetween('+1 year', '+10 years'),
        ]);
    }

    /**
     * Indicate that the document is expired
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the document is expiring soon
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Indicate that the document is an old version
     */
    public function oldVersion(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current_version' => false,
            'version' => fake()->numberBetween(1, 5),
        ]);
    }
}
