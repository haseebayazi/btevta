<?php

namespace Database\Factories;

use App\Enums\StoryStatus;
use App\Enums\StoryType;
use App\Models\Candidate;
use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuccessStoryFactory extends Factory
{
    protected $model = SuccessStory::class;

    public function definition(): array
    {
        return [
            'candidate_id'           => Candidate::factory(),
            'story_type'             => $this->faker->randomElement(StoryType::cases())->value,
            'headline'               => $this->faker->sentence(6),
            'written_note'           => $this->faker->paragraphs(3, true),
            'employer_name'          => $this->faker->company(),
            'position_achieved'      => $this->faker->jobTitle(),
            'salary_achieved'        => $this->faker->randomFloat(2, 1000, 10000),
            'salary_currency'        => 'SAR',
            'employment_start_date'  => $this->faker->dateTimeBetween('-1 year', 'now'),
            'time_to_employment_days' => $this->faker->numberBetween(30, 365),
            'is_featured'            => false,
            'views_count'            => 0,
            'status'                 => StoryStatus::DRAFT->value,
            'recorded_by'            => User::factory(),
            'recorded_at'            => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status'       => StoryStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
            'status'      => StoryStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
    }
}
