<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Trade;

class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'btevta_id' => 'TLP-' . date('Y') . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT) . '-' . fake()->randomDigit(),
            'application_id' => fake()->unique()->numberBetween(100000, 999999),
            'trade_id' => Trade::inRandomOrder()->first()->id, // 👈 Added line
'cnic' => fake()->unique()->numerify('#############'), // 13 digits
            'name' => fake()->name(),
            'father_name' => fake()->name('male'),
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => '03' . fake()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'district' => fake()->randomElement(['Rawalpindi', 'Islamabad', 'Lahore', 'Karachi', 'Peshawar']),
            'tehsil' => fake()->word(),
            'status' => 'new',
        ];
    }
}
