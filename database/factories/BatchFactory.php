<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        $campus = Campus::inRandomOrder()->first();
        $trade = Trade::inRandomOrder()->first();
        $startDate = fake()->dateTimeBetween('-2 months', '+3 months');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');

        return [
            'batch_code' => $this->generateBatchCode(),
            'name' => fake()->randomElement([
                'Basic Training Batch',
                'Advanced Skills Program',
                'Foundation Course',
                'Professional Development Batch',
            ]) . ' ' . fake()->monthName() . ' ' . fake()->year(),
            'campus_id' => $campus?->id ?? Campus::factory(),
            'trade_id' => $trade?->id ?? Trade::factory(),
            'oep_id' => fake()->optional(0.3)->randomElement(Oep::pluck('id')->toArray()),
            'capacity' => fake()->randomElement([20, 25, 30, 35, 40, 50]),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'intake_period' => fake()->randomElement([
                'Q1 2025', 'Q2 2025', 'Q3 2025', 'Q4 2025',
                'January 2025', 'Spring 2025', 'Summer 2025'
            ]),
            'district' => fake()->randomElement([
                'Rawalpindi', 'Islamabad', 'Lahore', 'Karachi',
                'Faisalabad', 'Multan', 'Peshawar', 'Quetta'
            ]),
            'specialization' => fake()->optional(0.6)->randomElement([
                'Residential Wiring', 'Industrial Automation',
                'Automotive Mechanics', 'HVAC Systems',
                'Plumbing & Piping', 'Welding & Fabrication'
            ]),
            'status' => fake()->randomElement([
                Batch::STATUS_PLANNED,
                Batch::STATUS_ACTIVE,
                Batch::STATUS_ACTIVE,  // Higher probability for active
                Batch::STATUS_COMPLETED,
                Batch::STATUS_CANCELLED,
            ]),
            'description' => fake()->optional(0.7)->sentence(12),
            'trainer_id' => fake()->optional(0.5)->randomElement(
                User::where('role', 'trainer')->pluck('id')->toArray()
            ),
            'coordinator_id' => fake()->optional(0.4)->randomElement(
                User::whereIn('role', ['campus_admin', 'admin'])->pluck('id')->toArray()
            ),
        ];
    }

    /**
     * Generate a unique batch code
     */
    protected function generateBatchCode(): string
    {
        $year = date('Y');
        $month = date('m');
        $random = fake()->unique()->numberBetween(1, 9999);

        return sprintf('BATCH-%s%s-%04d', $year, $month, $random);
    }

    /**
     * Create a planned batch
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Batch::STATUS_PLANNED,
            'start_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }

    /**
     * Create an active batch
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Batch::STATUS_ACTIVE,
            'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ]);
    }

    /**
     * Create a completed batch
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Batch::STATUS_COMPLETED,
            'start_date' => fake()->dateTimeBetween('-6 months', '-3 months'),
            'end_date' => fake()->dateTimeBetween('-2 months', '-1 week'),
        ]);
    }

    /**
     * Create a cancelled batch
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Batch::STATUS_CANCELLED,
        ]);
    }

    /**
     * Create a batch with full capacity
     */
    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => 20,  // Will be filled with candidates in seeder
        ]);
    }

    /**
     * Create a batch with specific campus
     */
    public function forCampus(int|Campus $campus): static
    {
        $campusId = $campus instanceof Campus ? $campus->id : $campus;

        return $this->state(fn (array $attributes) => [
            'campus_id' => $campusId,
        ]);
    }

    /**
     * Create a batch with specific trade
     */
    public function forTrade(int|Trade $trade): static
    {
        $tradeId = $trade instanceof Trade ? $trade->id : $trade;

        return $this->state(fn (array $attributes) => [
            'trade_id' => $tradeId,
        ]);
    }
}
