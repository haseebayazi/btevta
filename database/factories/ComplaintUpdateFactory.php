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
            'update_type' => $this->faker->randomElement(['status_change', 'comment', 'assignment', 'resolution']),
            'content' => $this->faker->paragraph(),
            'old_status' => 'open',
            'new_status' => 'in_progress',
        ];
    }
}
