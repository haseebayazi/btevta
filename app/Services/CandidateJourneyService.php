<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Support\Collection;

class CandidateJourneyService
{
    /**
     * Get the complete journey/timeline for a candidate
     *
     * @param Candidate $candidate
     * @return array
     */
    public function getJourneyData(Candidate $candidate): array
    {
        return [
            'candidate' => $candidate->load([
                'trade',
                'campus',
                'batch',
                'oep',
                'screenings',
                'trainingCertificates',
                'visaProcess',
                'departure',
            ]),
            'milestones' => $this->getMilestones($candidate),
            'currentStage' => $this->getCurrentStage($candidate),
            'completionPercentage' => $this->getCompletionPercentage($candidate),
        ];
    }

    /**
     * Get the completion percentage for the candidate's journey
     *
     * @param Candidate $candidate
     * @return int
     */
    protected function getCompletionPercentage(Candidate $candidate): int
    {
        $milestones = $this->getMilestones($candidate);
        $completedMilestones = collect($milestones)->where('completed', true)->count();
        $totalMilestones = count($milestones);

        if ($totalMilestones === 0) {
            return 0;
        }

        return (int) round(($completedMilestones / $totalMilestones) * 100);
    }

    /**
     * Get the current stage of the candidate
     *
     * @param Candidate $candidate
     * @return string
     */
    protected function getCurrentStage(Candidate $candidate): string
    {
        return match ($candidate->status) {
            'new', 'listed' => 'Listing',
            'pre_departure_docs' => 'Document Collection',
            'screening', 'screened' => 'Screening',
            'registered' => 'Registered',
            'training' => 'In Training',
            'training_completed' => 'Training Completed',
            'visa_process' => 'Visa Processing',
            'visa_approved' => 'Visa Approved',
            'departure_processing', 'ready_to_depart' => 'Departure Processing',
            'departed' => 'Departed',
            'post_departure' => 'Post Departure',
            'completed' => 'Journey Completed',
            'rejected', 'withdrawn' => 'Journey Ended',
            default => 'Unknown',
        };
    }

    /**
     * Get all journey milestones for the candidate
     *
     * @param Candidate $candidate
     * @return array
     */
    protected function getMilestones(Candidate $candidate): array
    {
        return [
            [
                'name' => 'Listed',
                'date' => $candidate->created_at?->toDateString(),
                'completed' => true, // Always completed if candidate exists
                'icon' => 'fa-user-plus',
                'color' => 'primary',
            ],
            [
                'name' => 'Pre-Departure Documents',
                'date' => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->latest('verified_at')->value('verified_at'),
                'completed' => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->exists(),
                'icon' => 'fa-file-alt',
                'color' => 'info',
            ],
            [
                'name' => 'Screened',
                'date' => $candidate->screenings()->where('screening_status', 'screened')->value('reviewed_at'),
                'completed' => in_array($candidate->status, [
                    'screened', 'registered', 'training', 'training_completed',
                    'visa_process', 'visa_approved', 'departure_processing',
                    'ready_to_depart', 'departed', 'post_departure', 'completed'
                ]),
                'icon' => 'fa-phone',
                'color' => 'warning',
            ],
            [
                'name' => 'Registered',
                'date' => $candidate->registration_date?->toDateString(),
                'completed' => $candidate->registration_date !== null || in_array($candidate->status, [
                    'registered', 'training', 'training_completed',
                    'visa_process', 'visa_approved', 'departure_processing',
                    'ready_to_depart', 'departed', 'post_departure', 'completed'
                ]),
                'icon' => 'fa-clipboard-check',
                'color' => 'success',
            ],
            [
                'name' => 'Training Complete',
                // FIX: Use 'issue_date' instead of 'issued_at'
                'date' => $candidate->trainingCertificates()->latest('issue_date')->value('issue_date'),
                'completed' => $candidate->trainingCertificates()->exists(),
                'icon' => 'fa-graduation-cap',
                'color' => 'success',
            ],
            [
                'name' => 'Visa Approved',
                'date' => $candidate->visaProcess?->visa_issued_at?->toDateString(),
                'completed' => $candidate->visaProcess?->visa_issued_at !== null,
                'icon' => 'fa-passport',
                'color' => 'primary',
            ],
            [
                'name' => 'Departed',
                'date' => $candidate->departure?->actual_departure_date?->toDateString(),
                'completed' => $candidate->departure?->actual_departure_date !== null,
                'icon' => 'fa-plane-departure',
                'color' => 'info',
            ],
            [
                'name' => 'Employment Confirmed',
                'date' => $candidate->departure?->employment_confirmed_at?->toDateString(),
                'completed' => $candidate->departure?->employment_confirmed_at !== null,
                'icon' => 'fa-briefcase',
                'color' => 'success',
            ],
        ];
    }

    /**
     * Get activities/timeline events for a candidate
     *
     * @param Candidate $candidate
     * @return Collection
     */
    public function getActivities(Candidate $candidate): Collection
    {
        // Get all activity logs for this candidate
        $activities = collect();

        // Add screening activities
        foreach ($candidate->screenings as $screening) {
            $activities->push([
                'type' => 'screening',
                'date' => $screening->reviewed_at ?? $screening->created_at,
                'title' => 'Screening Call',
                'description' => "Screening status: {$screening->screening_status}",
                'icon' => 'fa-phone',
                'color' => $screening->screening_status === 'screened' ? 'success' : 'warning',
            ]);
        }

        // Add training certificate activities
        foreach ($candidate->trainingCertificates as $certificate) {
            $activities->push([
                'type' => 'certificate',
                'date' => $certificate->issue_date, // Use issue_date, not issued_at
                'title' => 'Training Certificate Issued',
                'description' => "Certificate #{$certificate->certificate_number}",
                'icon' => 'fa-certificate',
                'color' => 'success',
            ]);
        }

        // Add visa activities
        if ($candidate->visaProcess) {
            if ($candidate->visaProcess->application_submitted_at) {
                $activities->push([
                    'type' => 'visa',
                    'date' => $candidate->visaProcess->application_submitted_at,
                    'title' => 'Visa Application Submitted',
                    'description' => 'Visa application submitted for processing',
                    'icon' => 'fa-file-export',
                    'color' => 'primary',
                ]);
            }

            if ($candidate->visaProcess->visa_issued_at) {
                $activities->push([
                    'type' => 'visa',
                    'date' => $candidate->visaProcess->visa_issued_at,
                    'title' => 'Visa Approved',
                    'description' => "Visa Number: {$candidate->visaProcess->visa_number}",
                    'icon' => 'fa-check-circle',
                    'color' => 'success',
                ]);
            }
        }

        // Add departure activity
        if ($candidate->departure && $candidate->departure->actual_departure_date) {
            $activities->push([
                'type' => 'departure',
                'date' => $candidate->departure->actual_departure_date,
                'title' => 'Departed',
                'description' => "Flight: {$candidate->departure->flight_number}",
                'icon' => 'fa-plane-departure',
                'color' => 'info',
            ]);
        }

        // Sort by date descending
        return $activities->sortByDesc('date')->values();
    }
}
