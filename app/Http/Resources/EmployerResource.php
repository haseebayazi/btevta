<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'permission_number' => $this->permission_number,
            'visa_issuing_company' => $this->visa_issuing_company,
            'country' => new CountryResource($this->whenLoaded('country')),
            'country_id' => $this->country_id,
            'sector' => $this->sector,
            'trade' => $this->trade,
            'employment_package' => [
                'basic_salary' => $this->basic_salary,
                'salary_currency' => $this->salary_currency,
                'food_by_company' => $this->food_by_company,
                'transport_by_company' => $this->transport_by_company,
                'accommodation_by_company' => $this->accommodation_by_company,
                'other_conditions' => $this->other_conditions,
            ],
            'has_evidence' => !empty($this->evidence_path),
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'candidates_count' => $this->when(
                $this->relationLoaded('candidates'),
                $this->candidates_count ?? $this->candidates->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
