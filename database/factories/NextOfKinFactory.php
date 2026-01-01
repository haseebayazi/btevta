<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\NextOfKin;
use Illuminate\Database\Eloquent\Factories\Factory;

class NextOfKinFactory extends Factory
{
    protected $model = NextOfKin::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'name' => $this->faker->name(),
            'relationship' => $this->faker->randomElement(['father', 'mother', 'spouse', 'brother', 'sister', 'guardian']),
            'cnic' => $this->faker->numerify('#####-#######-#'),
            'phone' => $this->faker->numerify('03#########'),
            'address' => $this->faker->address(),
            'is_emergency_contact' => $this->faker->boolean(),
        ];
    }
}
