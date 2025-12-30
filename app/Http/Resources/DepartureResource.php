<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartureResource extends JsonResource
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
            'candidate_id' => $this->candidate_id,

            // Pre-Departure
            'briefing' => [
                'date' => $this->briefing_date?->format('Y-m-d'),
                'completed' => (bool) $this->briefing_completed,
                'remarks' => $this->briefing_remarks,
            ],

            // Departure Information
            'departure' => [
                'date' => $this->departure_date?->format('Y-m-d'),
                'flight_number' => $this->flight_number,
                'airport' => $this->airport,
                'destination' => $this->destination,
                'status' => $this->departure_status,
            ],

            // Arrival Information
            'arrival' => [
                'date' => $this->arrival_date?->format('Y-m-d'),
                'confirmed' => (bool) $this->arrival_confirmed,
            ],

            // Saudi Documentation
            'iqama' => [
                'number' => $this->when($this->canViewSensitive($request), $this->iqama_number),
                'date' => $this->iqama_date?->format('Y-m-d'),
                'expiry' => $this->iqama_expiry?->format('Y-m-d'),
            ],

            'absher' => [
                'registered' => (bool) $this->absher_registered,
                'date' => $this->absher_date?->format('Y-m-d'),
            ],

            // Employment Status
            'employment' => [
                'wps_registered' => (bool) $this->wps_registered,
                'wps_date' => $this->wps_date?->format('Y-m-d'),
                'qiwa_registered' => (bool) $this->qiwa_registered,
                'qiwa_date' => $this->qiwa_date?->format('Y-m-d'),
                'employer_name' => $this->employer_name,
                'employer_contact' => $this->when($this->canViewSensitive($request), $this->employer_contact),
                'job_title' => $this->job_title,
                'salary' => $this->when($this->canViewSensitive($request), $this->salary),
            ],

            // First Salary
            'first_salary' => [
                'received' => (bool) $this->first_salary_received,
                'date' => $this->first_salary_date?->format('Y-m-d'),
                'amount' => $this->when($this->canViewSensitive($request), $this->first_salary_amount),
            ],

            // 90-Day Compliance
            'compliance_90_day' => [
                'due_date' => $this->compliance_due_date?->format('Y-m-d'),
                'completed' => (bool) $this->compliance_completed,
                'completion_date' => $this->compliance_completion_date?->format('Y-m-d'),
                'is_compliant' => (bool) $this->is_compliant,
                'remarks' => $this->compliance_remarks,
            ],

            // Issues
            'issues' => $this->whenLoaded('issues', fn() =>
                $this->issues->map(fn($issue) => [
                    'id' => $issue['id'] ?? null,
                    'type' => $issue['type'] ?? null,
                    'description' => $issue['description'] ?? null,
                    'status' => $issue['status'] ?? null,
                    'reported_date' => $issue['reported_date'] ?? null,
                    'resolved_date' => $issue['resolved_date'] ?? null,
                ])
            ),

            // Return Information
            'returned' => [
                'is_returned' => (bool) $this->is_returned,
                'date' => $this->return_date?->format('Y-m-d'),
                'reason' => $this->return_reason,
            ],

            // Overall Status
            'status' => $this->status,
            'remarks' => $this->remarks,

            // Relationships
            'candidate' => $this->whenLoaded('candidate', fn() => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'btevta_id' => $this->candidate->btevta_id,
            ]),

            // Summary Statistics
            'summary' => [
                'days_since_departure' => $this->departure_date ? now()->diffInDays($this->departure_date) : null,
                'is_compliant' => $this->isCompliant(),
                'has_active_issues' => $this->hasActiveIssues(),
            ],

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

        return $user->hasRole(['admin', 'campus_admin', 'project_director', 'oep']);
    }

    /**
     * Check if departure is compliant
     */
    protected function isCompliant(): bool
    {
        return $this->is_compliant ?? false;
    }

    /**
     * Check if there are active issues
     */
    protected function hasActiveIssues(): bool
    {
        if (!$this->relationLoaded('issues')) {
            return false;
        }

        return $this->issues->where('status', '!=', 'resolved')->isNotEmpty();
    }
}
