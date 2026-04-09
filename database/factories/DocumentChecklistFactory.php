<?php

namespace Database\Factories;

use App\Models\DocumentChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentChecklistFactory extends Factory
{
    protected $model = DocumentChecklist::class;

    public function definition(): array
    {
        return [
            'name'                   => fake()->unique()->words(3, true),
            'code'                   => 'CL-' . fake()->unique()->numerify('###'),
            'description'            => fake()->sentence(),
            'category'               => fake()->randomElement(['mandatory', 'optional']),
            'is_mandatory'           => true,
            'supports_multiple_pages' => false,
            'max_pages'              => 1,
            'display_order'          => fake()->numberBetween(1, 100),
            'is_active'              => true,
        ];
    }
}
