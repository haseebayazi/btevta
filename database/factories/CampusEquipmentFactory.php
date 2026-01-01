<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\CampusEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampusEquipmentFactory extends Factory
{
    protected $model = CampusEquipment::class;

    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['computer', 'projector', 'printer', 'machinery', 'tool']),
            'serial_number' => $this->faker->unique()->bothify('EQ-####-????'),
            'status' => $this->faker->randomElement(['available', 'in_use', 'maintenance', 'retired']),
            'purchase_date' => $this->faker->date(),
            'purchase_price' => $this->faker->randomFloat(2, 100, 50000),
            'condition' => $this->faker->randomElement(['new', 'good', 'fair', 'poor']),
        ];
    }
}
