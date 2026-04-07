<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorrespondenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,

            // API-compatible aliases (tests and external consumers use these names)
            'reference_number' => $this->file_reference_number,
            'type'             => $this->type,
            'content'          => $this->message,
            'priority'         => $this->priority_level,

            // Canonical field names
            'subject'          => $this->subject,
            'organization_type'=> $this->organization_type,
            'sender'           => $this->sender,
            'recipient'        => $this->recipient,
            'status'           => $this->status,
            'description'      => $this->description,
            'notes'            => $this->notes,
            'due_date'         => $this->due_date?->format('Y-m-d'),

            // Date fields surfaced with both API alias and canonical name
            'date_received'    => $this->type === 'incoming'
                ? $this->sent_at?->format('Y-m-d')
                : null,
            'date_sent'        => $this->type === 'outgoing'
                ? $this->sent_at?->format('Y-m-d')
                : null,
            'response_date'    => $this->replied_at?->format('Y-m-d'),

            // Reply tracking
            'requires_reply'   => $this->requires_reply,
            'replied'          => $this->replied,

            // Nested relations (null-safe)
            'campus'           => $this->campus ? [
                'id'   => $this->campus->id,
                'name' => $this->campus->name,
            ] : null,

            'oep'              => $this->oep ? [
                'id'   => $this->oep->id,
                'name' => $this->oep->name,
            ] : null,

            'creator'          => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,

            'created_at'       => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'       => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
