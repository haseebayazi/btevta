<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessStoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'departure_id' => $this->departure_id,
            'written_note' => $this->written_note,
            'evidence_type' => $this->evidence_type,
            'evidence_type_label' => $this->evidenceType()?->label(),
            'has_evidence' => !empty($this->evidence_path),
            'evidence_filename' => $this->evidence_filename,
            'is_featured' => $this->is_featured,
            'recorded_by' => $this->recorded_by,
            'recorder' => new UserResource($this->whenLoaded('recorder')),
            'recorded_at' => $this->recorded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
