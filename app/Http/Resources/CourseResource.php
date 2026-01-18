<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'duration_days' => $this->duration_days,
            'training_type' => $this->training_type,
            'training_type_label' => $this->trainingType()?->label(),
            'is_active' => $this->is_active,
            'assignments_count' => $this->when(
                $this->relationLoaded('candidateCourses'),
                $this->candidate_courses_count ?? $this->candidateCourses->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
