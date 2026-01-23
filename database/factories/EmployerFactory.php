<?php

namespace Database\Factories;

use App\Models\Employer;
use App\Models\Country;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployerFactory extends Factory
{
    protected $model = Employer::class;

    public function definition(): array
    {
        $sectors = [
            'Construction',
            'Hospitality',
            'Manufacturing',
            'Healthcare',
            'Retail',
            'Transportation',
            'Agriculture',
            'IT Services',
            'Other',
        ];

        $trades = [
            'Electrician',
            'Plumber',
            'Welder',
            'Carpenter',
            'Mason',
            'Driver',
            'Cook',
            'Waiter',
            'Cleaner',
            'Security Guard',
            'Mechanic',
            'Technician',
        ];

        $currencies = ['SAR', 'AED', 'QAR', 'OMR', 'BHD', 'KWD'];

        return [
            'permission_number' => fake()->numerify('PERM-##########'),
            'visa_issuing_company' => fake()->company(),
            'country_id' => Country::factory(),
            'sector' => fake()->randomElement($sectors),
            'trade' => fake()->randomElement($trades),
            'basic_salary' => fake()->randomFloat(2, 1000, 5000),
            'salary_currency' => fake()->randomElement($currencies),
            'food_by_company' => fake()->boolean(70),
            'transport_by_company' => fake()->boolean(60),
            'accommodation_by_company' => fake()->boolean(80),
            'other_conditions' => fake()->optional()->sentence(),
            'evidence_path' => fake()->optional()->filePath(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the employer is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the employer is in Saudi Arabia
     */
    public function inSaudiArabia(): static
    {
        return $this->state(fn (array $attributes) => [
            'salary_currency' => 'SAR',
        ]);
    }

    /**
     * Indicate that the employer provides all benefits
     */
    public function withAllBenefits(): static
    {
        return $this->state(fn (array $attributes) => [
            'food_by_company' => true,
            'transport_by_company' => true,
            'accommodation_by_company' => true,
        ]);
    }

    /**
     * Indicate that the employer provides no benefits
     */
    public function withNoBenefits(): static
    {
        return $this->state(fn (array $attributes) => [
            'food_by_company' => false,
            'transport_by_company' => false,
            'accommodation_by_company' => false,
        ]);
    }

    /**
     * Indicate that the employer is in construction sector
     */
    public function construction(): static
    {
        return $this->state(fn (array $attributes) => [
            'sector' => 'Construction',
            'trade' => fake()->randomElement(['Mason', 'Electrician', 'Plumber', 'Welder', 'Carpenter']),
        ]);
    }
}
