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

        $name = $this->faker->unique()->randomElement($tagNames);
        $slug = \Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1000, 9999);

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
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgent',
            'slug' => 'urgent',
            'color' => '#ef4444',
        ]);
    }

    /**
     * Create a verified tag
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Verified',
            'slug' => 'verified',
            'color' => '#22c55e',
        ]);
    }

    /**
     * Create a confidential tag
     */
    public function confidential(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Confidential',
            'slug' => 'confidential',
            'color' => '#ef4444',
        ]);
    }
}
