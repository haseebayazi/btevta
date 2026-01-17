<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'batch_code' => $this->batch_code,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'intake_period' => $this->intake_period,
            'district' => $this->district,
            'specialization' => $this->specialization,
            'description' => $this->description,

            // Status information
            'status' => [
                'value' => $this->status,
                'label' => ucfirst($this->status),
                'badge_class' => $this->status_badge_class,
            ],

            // Computed attributes
            'enrollment_count' => $this->enrollment_count,
            'available_slots' => $this->available_slots,
            'is_full' => $this->is_full,
            'is_active' => $this->is_active,
            'duration_in_days' => $this->duration_in_days,
            'progress_percentage' => $this->progress_percentage,
            'enrollment_progress_percentage' => $this->getEnrollmentProgressPercentage(),

            // Relationships (conditionally loaded)
            'campus' => $this->whenLoaded('campus', fn() => [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
                'code' => $this->campus->code,
                'district' => $this->campus->district,
            ]),

            'trade' => $this->whenLoaded('trade', fn() => [
                'id' => $this->trade->id,
                'name' => $this->trade->name,
                'code' => $this->trade->code,
            ]),

            'oep' => $this->whenLoaded('oep', fn() => [
                'id' => $this->oep->id,
                'name' => $this->oep->name,
            ]),

            'trainer' => $this->whenLoaded('trainer', fn() => [
                'id' => $this->trainer->id,
                'name' => $this->trainer->name,
                'email' => $this->trainer->email,
                'role' => $this->trainer->role,
            ]),

            'coordinator' => $this->whenLoaded('coordinator', fn() => [
                'id' => $this->coordinator->id,
                'name' => $this->coordinator->name,
                'email' => $this->coordinator->email,
                'role' => $this->coordinator->role,
            ]),

            // Counts
            'candidates_count' => $this->whenCounted('candidates'),
            'training_schedules_count' => $this->whenCounted('trainingSchedules'),

            // Statistics (conditionally included)
            'statistics' => $this->when($request->input('include_statistics'), function () {
                return $this->getStatistics();
            }),

            // Audit fields
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'available_statuses' => [
                    'planned' => 'Planned',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ],
            ],
        ];
    }
}
