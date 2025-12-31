<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\User;
use App\Models\ComplaintEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintEvidenceFactory extends Factory
{
    protected $model = ComplaintEvidence::class;

    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'uploaded_by' => User::factory(),
            'file_path' => 'evidence/' . $this->faker->uuid() . '.pdf',
            'file_name' => $this->faker->word() . '.pdf',
            'file_type' => 'application/pdf',
            'description' => $this->faker->sentence(),
        ];
    }
}
