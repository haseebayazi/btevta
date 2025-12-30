<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\VisaStage;

class VisaProcessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentStage = VisaStage::tryFrom($this->overall_status ?? $this->current_stage);

        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,

            // Current stage with metadata
            'current_stage' => [
                'value' => $this->overall_status ?? $this->current_stage,
                'label' => $currentStage?->label() ?? 'Unknown',
                'color' => $currentStage?->color() ?? 'secondary',
                'order' => $currentStage?->order() ?? 0,
            ],
            'progress_percentage' => $currentStage?->progressPercentage() ?? 0,

            // Interview Stage
            'interview' => [
                'date' => $this->interview_date?->format('Y-m-d'),
                'status' => $this->interview_status,
                'completed' => (bool) $this->interview_completed,
                'remarks' => $this->interview_remarks,
            ],

            // Trade Test Stage
            'trade_test' => [
                'date' => $this->trade_test_date?->format('Y-m-d'),
                'status' => $this->trade_test_status,
                'completed' => (bool) $this->trade_test_completed,
                'remarks' => $this->trade_test_remarks,
            ],

            // Takamol Stage
            'takamol' => [
                'date' => $this->takamol_date?->format('Y-m-d'),
                'booking_date' => $this->takamol_booking_date?->format('Y-m-d'),
                'status' => $this->takamol_status,
                'score' => $this->takamol_score,
                'has_result' => !empty($this->takamol_result_path),
                'remarks' => $this->takamol_remarks,
            ],

            // Medical/GAMCA Stage
            'medical' => [
                'date' => $this->medical_date?->format('Y-m-d'),
                'booking_date' => $this->gamca_booking_date?->format('Y-m-d'),
                'status' => $this->medical_status,
                'completed' => (bool) $this->medical_completed,
                'barcode' => $this->when($this->canViewSensitive($request), $this->gamca_barcode),
                'expiry_date' => $this->gamca_expiry_date?->format('Y-m-d'),
                'has_result' => !empty($this->gamca_result_path),
                'remarks' => $this->medical_remarks,
            ],

            // E-Number Stage
            'enumber' => [
                'number' => $this->when($this->canViewSensitive($request), $this->enumber),
                'date' => $this->enumber_date?->format('Y-m-d'),
                'status' => $this->enumber_status,
            ],

            // Biometrics/Etimad Stage
            'biometrics' => [
                'date' => $this->biometric_date?->format('Y-m-d'),
                'appointment_id' => $this->etimad_appointment_id,
                'center' => $this->etimad_center,
                'status' => $this->biometric_status,
                'completed' => (bool) $this->biometric_completed,
                'remarks' => $this->biometric_remarks,
            ],

            // Visa Submission Stage
            'visa_submission' => [
                'date' => $this->visa_submission_date?->format('Y-m-d'),
                'application_number' => $this->when($this->canViewSensitive($request), $this->visa_application_number),
                'embassy' => $this->embassy,
            ],

            // Visa Issuance Stage
            'visa' => [
                'date' => $this->visa_date?->format('Y-m-d'),
                'number' => $this->when($this->canViewSensitive($request), $this->visa_number),
                'status' => $this->visa_status,
                'issued' => (bool) $this->visa_issued,
                'remarks' => $this->visa_remarks,
            ],

            // PTN & Attestation
            'ptn' => [
                'number' => $this->when($this->canViewSensitive($request), $this->ptn_number),
                'issue_date' => $this->ptn_issue_date?->format('Y-m-d'),
                'attestation_date' => $this->attestation_date?->format('Y-m-d'),
            ],

            // Ticket & Travel
            'travel' => [
                'ticket_uploaded' => (bool) $this->ticket_uploaded,
                'ticket_date' => $this->ticket_date?->format('Y-m-d'),
                'ticket_number' => $this->when($this->canViewSensitive($request), $this->ticket_number),
                'flight_number' => $this->flight_number,
                'departure_date' => $this->departure_date?->toIso8601String(),
                'arrival_date' => $this->arrival_date?->toIso8601String(),
                'has_travel_plan' => !empty($this->travel_plan_path),
            ],

            // General
            'remarks' => $this->remarks,

            // Relationships
            'candidate' => $this->whenLoaded('candidate', fn() => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'btevta_id' => $this->candidate->btevta_id,
            ]),

            'visa_partner' => $this->whenLoaded('visaPartner', fn() => [
                'id' => $this->visaPartner->id,
                'name' => $this->visaPartner->name,
            ]),

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

        return $user->hasRole(['admin', 'campus_admin', 'project_director', 'visa_partner']);
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
                'available_stages' => VisaStage::toArray(),
            ],
        ];
    }
}
