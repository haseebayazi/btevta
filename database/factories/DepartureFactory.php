<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Candidate;

class DepartureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'departure_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'flight_number' => fake()->bothify('??###'),
            'destination' => fake()->randomElement(['Saudi Arabia', 'UAE', 'Qatar', 'Kuwait', 'Bahrain', 'Oman']),
            'pre_departure_briefing' => fake()->boolean(80),
            'briefing_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'iqama_number' => fake()->numerify('##########'),
            'iqama_issue_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'post_arrival_medical_path' => null,
            'absher_registered' => fake()->boolean(70),
            'absher_registration_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'qiwa_id' => fake()->numerify('##########'),
            'qiwa_activated' => fake()->boolean(60),
            'salary_amount' => fake()->randomFloat(2, 1000, 5000),
            'first_salary_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'ninety_day_report_submitted' => fake()->boolean(50),
            'salary_confirmed' => fake()->boolean(60),
            'accommodation_status' => fake()->randomElement(['pending', 'verified', 'issue']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
