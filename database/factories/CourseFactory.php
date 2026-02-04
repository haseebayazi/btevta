<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true) . ' Training',
            'code' => strtoupper($this->faker->unique()->lexify('???-???')),
            'description' => $this->faker->sentence(),
            'duration_days' => $this->faker->numberBetween(7, 90),
            'training_type' => $this->faker->randomElement(['technical', 'soft_skills', 'both']),
            'program_id' => null,
            'is_active' => true,
        ];
    }

    public function withProgram(): static
    {
        return $this->state(fn (array $attributes) => [
            'program_id' => Program::factory(),
        ]);
    }

    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'training_type' => 'technical',
        ]);
    }

    public function softSkills(): static
    {
        return $this->state(fn (array $attributes) => [
            'training_type' => 'soft_skills',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
