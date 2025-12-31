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
            'undertaking_type' => $this->faker->randomElement(['training', 'visa', 'departure', 'general', 'code_of_conduct']),
            'undertaking_date' => $this->faker->date(),
            'file_path' => 'undertakings/' . $this->faker->uuid() . '.pdf',
            'terms_agreed' => true,
        ];
    }
}
