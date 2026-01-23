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

        return [
            'equipment_id' => CampusEquipment::factory(),
            'user_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
