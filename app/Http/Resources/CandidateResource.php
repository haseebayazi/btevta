<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\CandidateStatus;

class CandidateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = CandidateStatus::tryFrom($this->status);

        return [
            'id' => $this->id,
            'btevta_id' => $this->btevta_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->when($this->canViewSensitive($request), $this->phone),
            'cnic' => $this->when($this->canViewSensitive($request), $this->cnic),
            'district' => $this->district,
            'tehsil' => $this->tehsil,
            'province' => $this->province,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'gender' => $this->gender,
            'father_name' => $this->father_name,
            'address' => $this->when($this->canViewSensitive($request), $this->address),
            'blood_group' => $this->blood_group,
            'marital_status' => $this->marital_status,
            'qualification' => $this->qualification,
            'experience_years' => $this->experience_years,
            'passport_number' => $this->when($this->canViewSensitive($request), $this->passport_number),
            'passport_expiry' => $this->passport_expiry?->format('Y-m-d'),
            'photo_url' => $this->photo_path ? asset('storage/' . $this->photo_path) : null,
            'remarks' => $this->remarks,

            // Status information with enum metadata
            'status' => [
                'value' => $this->status,
                'label' => $status?->label() ?? $this->status,
                'color' => $status?->color() ?? 'secondary',
                'is_terminal' => $status?->isTerminal() ?? false,
            ],

            // Training information
            'training_status' => $this->training_status,
            'registration_date' => $this->registration_date?->format('Y-m-d'),
            'training_start_date' => $this->training_start_date?->format('Y-m-d'),
            'training_end_date' => $this->training_end_date?->format('Y-m-d'),

            // Relationships (conditionally loaded)
            'campus' => $this->whenLoaded('campus', fn() => [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
                'code' => $this->campus->code,
            ]),

            'trade' => $this->whenLoaded('trade', fn() => [
                'id' => $this->trade->id,
                'name' => $this->trade->name,
            ]),

            'batch' => $this->whenLoaded('batch', fn() => [
                'id' => $this->batch->id,
                'name' => $this->batch->name,
                'code' => $this->batch->code,
            ]),

            'oep' => $this->whenLoaded('oep', fn() => [
                'id' => $this->oep->id,
                'name' => $this->oep->name,
            ]),

            'visa_partner' => $this->whenLoaded('visaPartner', fn() => [
                'id' => $this->visaPartner->id,
                'name' => $this->visaPartner->name,
            ]),

            'visa_process' => new VisaProcessResource($this->whenLoaded('visaProcess')),
            'departure' => new DepartureResource($this->whenLoaded('departure')),
            'documents_count' => $this->whenCounted('documents'),
            'remittances_count' => $this->whenCounted('remittances'),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Check if the authenticated user can view sensitive information
     */
    protected function canViewSensitive(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        // Admin and Campus Admin can view sensitive data
        if ($user->hasRole(['admin', 'campus_admin', 'project_director'])) {
            return true;
        }

        // OEP can view their assigned candidates' data
        if ($user->hasRole('oep') && $this->oep_id === $user->oep_id) {
            return true;
        }

        return false;
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
                'available_statuses' => CandidateStatus::toArray(),
            ],
        ];
    }
}
