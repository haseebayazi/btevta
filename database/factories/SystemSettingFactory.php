<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        $settingKeys = [
            'app_name' => 'BTEVTA System',
            'app_logo' => '/images/logo.png',
            'batch_size' => '25',
            'max_batch_size' => '30',
            'attendance_threshold' => '80',
            'passing_score' => '60',
            'email_notifications' => 'true',
            'sms_notifications' => 'false',
            'maintenance_mode' => 'false',
            'registration_open' => 'true',
        ];

        $key = $this->faker->unique()->randomElement(array_keys($settingKeys));

        return [
            'setting_key' => $key,
            'setting_value' => $settingKeys[$key],
        ];
    }

    /**
     * Create a maintenance mode setting
     */
    public function maintenanceMode(bool $enabled = true): static
    {
        return $this->state(fn (array $attributes) => [
            'setting_key' => 'maintenance_mode',
            'setting_value' => $enabled ? 'true' : 'false',
        ]);
    }

    /**
     * Create a batch size setting
     */
    public function batchSize(int $size = 25): static
    {
        return $this->state(fn (array $attributes) => [
            'setting_key' => 'batch_size',
            'setting_value' => (string) $size,
        ]);
    }
}
