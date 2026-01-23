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
            'equipment_code' => $this->faker->unique()->bothify('EQ-####-????'),
            'category' => $this->faker->randomElement(['computer', 'machinery', 'furniture', 'tools', 'vehicle']),
            'description' => $this->faker->optional()->sentence(),
            'brand' => $this->faker->optional()->company(),
            'model' => $this->faker->optional()->bothify('Model-###??'),
            'serial_number' => $this->faker->optional()->bothify('SN-####-????'),
            'purchase_date' => $this->faker->optional()->date(),
            'purchase_cost' => $this->faker->optional()->randomFloat(2, 100, 50000),
            'current_value' => $this->faker->optional()->randomFloat(2, 50, 40000),
            'condition' => $this->faker->randomElement(['excellent', 'good', 'fair', 'poor', 'unusable']),
            'status' => $this->faker->randomElement(['available', 'in_use', 'maintenance', 'retired']),
            'quantity' => $this->faker->numberBetween(1, 10),
            'last_maintenance_date' => $this->faker->optional()->date(),
            'next_maintenance_date' => $this->faker->optional()->date(),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
