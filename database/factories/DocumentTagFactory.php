<?php

namespace Database\Factories;

use App\Models\DocumentTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTagFactory extends Factory
{
    protected $model = DocumentTag::class;

    public function definition(): array
    {
        $colors = [
            '#ef4444', // red
            '#f59e0b', // orange
            '#eab308', // yellow
            '#22c55e', // green
            '#3b82f6', // blue
            '#6366f1', // indigo
            '#8b5cf6', // purple
            '#ec4899', // pink
        ];

        $tagNames = [
            'Urgent',
            'Verified',
            'Pending Review',
            'Expired',
            'Personal',
            'Medical',
            'Training',
            'Visa',
            'Confidential',
            'Archived',
            'Important',
            'Completed',
            'In Progress',
            'Cancelled',
        ];

        $name = $this->faker->words(2, true) . ' ' . $this->faker->unique()->numberBetween(1, 999999);
        $slug = \Str::slug($name);

        return [
            'name' => $name,
            'slug' => $slug,
            'color' => $this->faker->randomElement($colors),
            'description' => $this->faker->optional()->sentence(),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the tag was created by a specific user
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Create an urgent tag
     */
    public function urgent(): static
    {
        $unique = fake()->unique()->numberBetween(1, 999999);
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgent ' . $unique,
            'slug' => 'urgent-' . $unique,
            'color' => '#ef4444',
        ]);
    }

    /**
     * Create a verified tag
     */
    public function verified(): static
    {
        $unique = fake()->unique()->numberBetween(1, 999999);
        return $this->state(fn (array $attributes) => [
            'name' => 'Verified ' . $unique,
            'slug' => 'verified-' . $unique,
            'color' => '#22c55e',
        ]);
    }

    /**
     * Create a confidential tag
     */
    public function confidential(): static
    {
        $unique = fake()->unique()->numberBetween(1, 999999);
        return $this->state(fn (array $attributes) => [
            'name' => 'Confidential ' . $unique,
            'slug' => 'confidential-' . $unique,
            'color' => '#ef4444',
        ]);
    }
}
