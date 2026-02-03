<?php

namespace Database\Factories;

use App\Models\CampusEquipment;
use App\Models\User;
use App\Models\EquipmentUsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentUsageLogFactory extends Factory
{
    protected $model = EquipmentUsageLog::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('-1 month', 'now');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(1, 8) . ' hours');
        $hoursUsed = (strtotime($endTime->format('Y-m-d H:i:s')) - strtotime($startTime->format('Y-m-d H:i:s'))) / 3600;

        return [
            'equipment_id' => CampusEquipment::factory(),
            'user_id' => User::factory(),
            'usage_type' => $this->faker->randomElement(['training', 'maintenance', 'idle', 'repair']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'hours_used' => round($hoursUsed, 2),
            'status' => $this->faker->randomElement(['in_use', 'completed', 'cancelled']),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
