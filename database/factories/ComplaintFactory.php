<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Complaint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'candidate_id' => Candidate::factory(),
            'complaint_number' => $this->faker->unique()->bothify('CMP-####-????'),
            'subject' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['open', 'assigned', 'in_progress', 'resolved', 'closed']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent']),
            'complaint_category' => $this->faker->randomElement(['training', 'accommodation', 'visa', 'payment', 'other']),
            'complaint_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Override the make() method to handle 'category' parameter.
     * Maps 'category' to 'complaint_category' before model instantiation.
     */
    public function make($attributes = [], ?Model $parent = null)
    {
        $attributes = $this->mapCategoryAttribute($attributes);
        return parent::make($attributes, $parent);
    }

    /**
     * Override the create() method to handle 'category' parameter.
     * Maps 'category' to 'complaint_category' before model creation.
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $attributes = $this->mapCategoryAttribute($attributes);
        return parent::create($attributes, $parent);
    }

    /**
     * Override the raw() method to handle 'category' parameter.
     * Maps 'category' to 'complaint_category' in raw attributes.
     */
    public function raw($attributes = [], ?Model $parent = null)
    {
        $attributes = $this->mapCategoryAttribute($attributes);
        return parent::raw($attributes, $parent);
    }

    /**
     * Map 'category' attribute to 'complaint_category'.
     * Helper method to ensure consistent mapping across all factory methods.
     */
    protected function mapCategoryAttribute(array $attributes): array
    {
        if (isset($attributes['category'])) {
            $attributes['complaint_category'] = $attributes['category'];
            unset($attributes['category']);
        }

        return $attributes;
    }
}
