<?php

namespace Database\Factories;

use App\Enums\ComplaintPriority;
use App\Models\ComplaintTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintTemplateFactory extends Factory
{
    protected $model = ComplaintTemplate::class;

    public function definition(): array
    {
        $categories = ['salary', 'conduct', 'training', 'facility', 'document', 'accommodation'];

        return [
            'name'                   => $this->faker->words(3, true),
            'category'               => $this->faker->randomElement($categories),
            'description_template'   => $this->faker->paragraph(),
            'required_evidence_types' => ['supporting_document'],
            'suggested_actions'      => ['Review', 'Contact supervisor'],
            'default_priority'       => $this->faker->randomElement(ComplaintPriority::cases())->value,
            'suggested_sla_hours'    => $this->faker->randomElement([24, 48, 72, 120]),
            'is_active'              => true,
        ];
    }
}
