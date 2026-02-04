<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Bank Account', 'EasyPaisa', 'JazzCash', 'UPaisa']),
            'code' => $this->faker->unique()->lexify('???'),
            'icon' => 'bank',
            'requires_account_number' => true,
            'requires_bank_name' => false,
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Bank Account',
            'code' => 'bank',
            'requires_bank_name' => true,
        ]);
    }

    public function easypaisa(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'EasyPaisa',
            'code' => 'easypaisa',
            'requires_bank_name' => false,
        ]);
    }

    public function jazzcash(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'JazzCash',
            'code' => 'jazzcash',
            'requires_bank_name' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
