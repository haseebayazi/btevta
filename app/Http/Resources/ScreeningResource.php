<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningResource extends JsonResource
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
            'candidate' => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'btevta_id' => $this->candidate->btevta_id,
                'phone' => $this->candidate->phone,
                'email' => $this->candidate->email,
                'campus' => [
                    'id' => $this->candidate->campus?->id,
                    'name' => $this->candidate->campus?->name,
                ],
                'oep' => [
                    'id' => $this->candidate->oep?->id,
                    'name' => $this->candidate->oep?->name,
                ],
            ],
            'screening_date' => $this->screening_date?->format('Y-m-d'),
            'screener_name' => $this->screener_name,
            'contact_method' => $this->contact_method,
            'status' => $this->status,
            'outcome' => $this->outcome,
            'remarks' => $this->remarks,
            'next_steps' => $this->next_steps,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
