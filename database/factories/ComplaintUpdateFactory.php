<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\User;
use App\Models\ComplaintUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintUpdateFactory extends Factory
{
    protected $model = ComplaintUpdate::class;

    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'user_id' => User::factory(),
            'message' => $this->faker->paragraph(),
            'status_changed_from' => $this->faker->optional()->randomElement(['open', 'assigned', 'in_progress']),
            'status_changed_to' => $this->faker->optional()->randomElement(['assigned', 'in_progress', 'resolved']),
            'is_internal' => $this->faker->boolean(30),
        ];
    }
}
