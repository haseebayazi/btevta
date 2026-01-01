<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->word(),
            'value' => $this->faker->word(),
            'type' => $this->faker->randomElement(['string', 'integer', 'boolean', 'json']),
            'description' => $this->faker->sentence(),
            'is_public' => $this->faker->boolean(),
        ];
    }
}
