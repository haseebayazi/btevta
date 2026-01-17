<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
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
            'complaint_reference' => $this->complaint_reference,
            'candidate' => [
                'id' => $this->candidate?->id,
                'name' => $this->candidate?->name,
                'btevta_id' => $this->candidate?->btevta_id,
            ],
            'complainant_name' => $this->complainant_name,
            'complainant_contact' => $this->complainant_contact,
            'complainant_email' => $this->complainant_email,
            'complaint_category' => $this->complaint_category,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'registered_at' => $this->registered_at?->format('Y-m-d H:i:s'),
            'assigned_to' => [
                'id' => $this->assignee?->id,
                'name' => $this->assignee?->name,
            ],
            'assigned_at' => $this->assigned_at?->format('Y-m-d H:i:s'),
            'escalation_level' => $this->escalation_level,
            'escalated_at' => $this->escalated_at?->format('Y-m-d H:i:s'),
            'escalation_reason' => $this->escalation_reason,
            'escalated_to' => [
                'id' => $this->escalatedToUser?->id,
                'name' => $this->escalatedToUser?->name,
            ],
            'sla_days' => $this->sla_days,
            'sla_due_date' => $this->sla_due_date?->format('Y-m-d H:i:s'),
            'sla_breached' => $this->sla_breached,
            'sla_breached_at' => $this->sla_breached_at?->format('Y-m-d H:i:s'),
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'resolution_details' => $this->resolution_details,
            'action_taken' => $this->action_taken,
            'resolution_category' => $this->resolution_category,
            'resolution_time_days' => $this->resolution_time_days,
            'closed_at' => $this->closed_at?->format('Y-m-d H:i:s'),
            'campus' => [
                'id' => $this->campus?->id,
                'name' => $this->campus?->name,
            ],
            'oep' => [
                'id' => $this->oep?->id,
                'name' => $this->oep?->name,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
