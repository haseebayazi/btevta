<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->getRoleLabel(),
            'campus_id' => $this->campus_id,
            'campus' => new CampusResource($this->whenLoaded('campus')),
            'oep_id' => $this->oep_id,
            'visa_partner_id' => $this->visa_partner_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get role label
     */
    protected function getRoleLabel(): string
    {
        return match($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'project_director' => 'Project Director',
            'campus_admin' => 'Campus Admin',
            'trainer' => 'Trainer',
            'oep' => 'OEP',
            'visa_partner' => 'Visa Partner',
            'viewer' => 'Viewer',
            'staff' => 'Staff',
            default => ucfirst($this->role),
        };
    }
}
