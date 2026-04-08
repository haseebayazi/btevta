<?php

namespace Database\Factories;

use App\Enums\StoryEvidenceType;
use App\Models\SuccessStory;
use App\Models\SuccessStoryEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuccessStoryEvidenceFactory extends Factory
{
    protected $model = SuccessStoryEvidence::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(StoryEvidenceType::cases());

        return [
            'success_story_id' => SuccessStory::factory(),
            'evidence_type'    => $type->value,
            'title'            => $this->faker->sentence(4),
            'description'      => $this->faker->sentence(),
            'file_path'        => 'success-stories/1/evidence/' . $this->faker->uuid() . '.pdf',
            'mime_type'        => 'application/pdf',
            'file_size'        => $this->faker->numberBetween(1024, 10240 * 1024),
            'is_primary'       => false,
            'display_order'    => 0,
            'uploaded_by'      => User::factory(),
        ];
    }
}
