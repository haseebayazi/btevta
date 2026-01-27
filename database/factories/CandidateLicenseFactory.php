<?php

namespace Database\Factories;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CandidateLicense>
 */
class CandidateLicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['driving', 'professional'];
        $type = $this->faker->randomElement($types);

        $drivingNames = ['Car License', 'Motorcycle License', 'HGV License', 'PSV License'];
        $professionalNames = ['RN Nurse License', 'LPN License', 'Electrician License', 'Plumber License'];

        return [
            'candidate_id' => Candidate::factory(),
            'license_type' => $type,
            'license_name' => $type === 'driving'
                ? $this->faker->randomElement($drivingNames)
                : $this->faker->randomElement($professionalNames),
            'license_number' => strtoupper($this->faker->bothify('??######')),
            'license_category' => $type === 'driving' ? $this->faker->randomElement(['B', 'C', 'D']) : null,
            'issuing_authority' => $this->faker->company(),
            'issue_date' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
            'expiry_date' => $this->faker->dateTimeBetween('+1 year', '+5 years'),
            'file_path' => null,
        ];
    }

    /**
     * Indicate that the license is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }

    /**
     * Indicate that the license is expiring soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => $this->faker->dateTimeBetween('now', '+90 days'),
        ]);
    }
}
