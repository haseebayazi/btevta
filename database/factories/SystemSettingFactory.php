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
            'setting_key' => $this->faker->unique()->word(),
            'setting_value' => $this->faker->word(),
        ];
    }
}
