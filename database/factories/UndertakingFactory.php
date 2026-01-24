<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Undertaking;
use Illuminate\Database\Eloquent\Factories\Factory;

class UndertakingFactory extends Factory
{
    protected $model = Undertaking::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'undertaking_type' => $this->faker->randomElement(['employment', 'financial', 'behavior', 'other']),
            'content' => $this->faker->paragraph(3),
            'signature_path' => $this->faker->optional()->filePath(),
            'signed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'is_completed' => $this->faker->boolean(70),
            'witness_name' => $this->faker->optional()->name(),
            'witness_cnic' => $this->faker->optional()->numerify('#############'),
        ];
    }
}
