<?php

namespace Database\Factories;

use App\Models\VisaPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisaPartnerFactory extends Factory
{
    protected $model = VisaPartner::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'country' => $this->faker->country(),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
