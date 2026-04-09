<?php

namespace Database\Factories;

use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreDepartureDocumentFactory extends Factory
{
    protected $model = PreDepartureDocument::class;

    public function definition(): array
    {
        return [
            'candidate_id'          => null, // must be provided by caller
            'document_checklist_id' => DocumentChecklist::factory(),
            'file_path'             => 'private/test/' . fake()->uuid() . '.pdf',
            'original_filename'     => fake()->word() . '.pdf',
            'mime_type'             => 'application/pdf',
            'file_size'             => fake()->numberBetween(10000, 5000000),
            'notes'                 => null,
            'uploaded_at'           => now(),
            'uploaded_by'           => User::factory(),
            'verified_at'           => null,
            'verified_by'           => null,
            'expiry_date'           => null,
        ];
    }
}
