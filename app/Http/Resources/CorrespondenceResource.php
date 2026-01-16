<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorrespondenceResource extends JsonResource
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
            'reference_number' => $this->reference_number,
            'organization_type' => $this->organization_type,
            'type' => $this->type,
            'subject' => $this->subject,
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'date_received' => $this->date_received?->format('Y-m-d'),
            'date_sent' => $this->date_sent?->format('Y-m-d'),
            'content' => $this->content,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'response_date' => $this->response_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'campus' => [
                'id' => $this->campus?->id,
                'name' => $this->campus?->name,
            ],
            'oep' => [
                'id' => $this->oep?->id,
                'name' => $this->oep?->name,
            ],
            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
