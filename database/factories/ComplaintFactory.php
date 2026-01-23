<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'candidate_id' => Candidate::factory(),
            'complaint_number' => $this->faker->unique()->bothify('CMP-####-????'),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['open', 'assigned', 'in_progress', 'resolved', 'closed']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'category' => $this->faker->randomElement(['training', 'accommodation', 'visa', 'payment', 'other']),
            'complaint_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
