<?php

namespace App\Services;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use Illuminate\Support\Collection;

class CandidateJourneyService
{
    protected array $stages = [
        'listing' => [
            'name'   => 'Listing',
            'icon'   => 'fas fa-user-plus',
            'color'  => 'gray',
            'status' => 'listed',
            'module' => 1,
        ],
        'pre_departure_docs' => [
            'name'   => 'Pre-Departure Documents',
            'icon'   => 'fas fa-file-alt',
            'color'  => 'blue',
            'status' => 'pre_departure_docs',
            'module' => 1,
        ],
        'screening' => [
            'name'   => 'Initial Screening',
            'icon'   => 'fas fa-clipboard-check',
            'color'  => 'indigo',
            'status' => 'screening',
            'module' => 2,
        ],
        'screened' => [
            'name'   => 'Screened',
            'icon'   => 'fas fa-check-circle',
            'color'  => 'indigo',
            'status' => 'screened',
            'module' => 2,
        ],
        'registration' => [
            'name'   => 'Registration',
            'icon'   => 'fas fa-id-card',
            'color'  => 'purple',
            'status' => 'registered',
            'module' => 3,
        ],
        'training' => [
            'name'   => 'Training',
            'icon'   => 'fas fa-chalkboard-teacher',
            'color'  => 'yellow',
            'status' => 'training',
            'module' => 4,
        ],
        'training_completed' => [
            'name'   => 'Training Completed',
            'icon'   => 'fas fa-graduation-cap',
            'color'  => 'yellow',
            'status' => 'training_completed',
            'module' => 4,
        ],
        'visa_process' => [
            'name'   => 'Visa Processing',
            'icon'   => 'fas fa-passport',
            'color'  => 'orange',
            'status' => 'visa_process',
            'module' => 5,
        ],
        'visa_approved' => [
            'name'   => 'Visa Approved',
            'icon'   => 'fas fa-stamp',
            'color'  => 'orange',
            'status' => 'visa_approved',
            'module' => 5,
        ],
        'departure_processing' => [
            'name'   => 'Departure Processing',
            'icon'   => 'fas fa-plane-departure',
            'color'  => 'teal',
            'status' => 'departure_processing',
            'module' => 6,
        ],
        'ready_to_depart' => [
            'name'   => 'Ready to Depart',
            'icon'   => 'fas fa-suitcase',
            'color'  => 'teal',
            'status' => 'ready_to_depart',
            'module' => 6,
        ],
        'departed' => [
            'name'   => 'Departed',
            'icon'   => 'fas fa-plane',
            'color'  => 'green',
            'status' => 'departed',
            'module' => 6,
        ],
        'post_departure' => [
            'name'   => 'Post-Departure',
            'icon'   => 'fas fa-home',
            'color'  => 'green',
            'status' => 'post_departure',
            'module' => 7,
        ],
        'completed' => [
            'name'   => 'Completed',
            'icon'   => 'fas fa-trophy',
            'color'  => 'emerald',
            'status' => 'completed',
            'module' => 7,
        ],
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Full stage-by-stage journey with state flags, completion dates, and data.
     */
    public function getCompleteJourney(Candidate $candidate): array
    {
        $currentOrder = CandidateStatus::tryFrom($candidate->status)?->order() ?? 0;
        $journey      = [];

        foreach ($this->stages as $key => $stage) {
            $stageStatus = CandidateStatus::tryFrom($stage['status']);
            $stageOrder  = $stageStatus?->order() ?? 0;

            if ($stageOrder < $currentOrder) {
                $state       = 'completed';
                $completedAt = $this->getStageCompletionDate($candidate, $key);
                $data        = $this->getStageData($candidate, $key);
            } elseif ($stageOrder === $currentOrder) {
                $state       = 'current';
                $completedAt = null;
                $data        = $this->getStageData($candidate, $key);
            } else {
                $state       = 'pending';
                $completedAt = null;
                $data        = [];
            }

            $journey[$key] = array_merge($stage, [
                'state'        => $state,
                'completed_at' => $completedAt,
                'data'         => $data,
            ]);
        }

        return $journey;
    }

    /**
     * Key milestone dates (for sidebar / summary view).
     */
    public function getMilestones(Candidate $candidate): array
    {
        $statusEnum   = CandidateStatus::tryFrom($candidate->status);
        $currentOrder = $statusEnum?->order() ?? 0;

        return [
            'listed' => [
                'name'      => 'Listed',
                'date'      => $candidate->created_at?->toDateString(),
                'achieved'  => true,
                'completed' => $currentOrder >= CandidateStatus::LISTED->order(),
                'icon'      => 'fa-user-plus',
                'color'     => 'primary',
            ],
            'screened' => [
                'name'      => 'Screened',
                'date'      => $candidate->screenings()->where('screening_status', 'screened')->value('reviewed_at'),
                'achieved'  => $currentOrder >= CandidateStatus::SCREENED->order(),
                'completed' => $currentOrder >= CandidateStatus::SCREENED->order(),
                'icon'      => 'fa-phone',
                'color'     => 'warning',
            ],
            'registered' => [
                'name'      => 'Registered',
                'date'      => $candidate->registration_date?->toDateString(),
                'achieved'  => $currentOrder >= CandidateStatus::REGISTERED->order(),
                'completed' => $currentOrder >= CandidateStatus::REGISTERED->order(),
                'icon'      => 'fa-clipboard-check',
                'color'     => 'success',
            ],
            'training_completed' => [
                'name'      => 'Training Complete',
                'date'      => $candidate->trainingCertificates()->latest('issue_date')->value('issue_date'),
                'achieved'  => $currentOrder >= CandidateStatus::TRAINING_COMPLETED->order(),
                'completed' => $currentOrder >= CandidateStatus::TRAINING_COMPLETED->order(),
                'icon'      => 'fa-graduation-cap',
                'color'     => 'success',
            ],
            'visa_approved' => [
                'name'      => 'Visa Approved',
                'date'      => $candidate->visaProcess?->visa_issued_at?->toDateString(),
                'achieved'  => $currentOrder >= CandidateStatus::VISA_APPROVED->order(),
                'completed' => $currentOrder >= CandidateStatus::VISA_APPROVED->order(),
                'icon'      => 'fa-passport',
                'color'     => 'primary',
            ],
            'departed' => [
                'name'      => 'Departed',
                'date'      => $candidate->departure?->actual_departure_date?->toDateString(),
                'achieved'  => $currentOrder >= CandidateStatus::DEPARTED->order(),
                'completed' => $currentOrder >= CandidateStatus::DEPARTED->order(),
                'icon'      => 'fa-plane-departure',
                'color'     => 'info',
            ],
            'completed' => [
                'name'      => 'Employment Confirmed',
                'date'      => $candidate->postDepartureDetail?->compliance_verified_date?->toDateString(),
                'achieved'  => $candidate->status === CandidateStatus::COMPLETED->value,
                'completed' => $candidate->status === CandidateStatus::COMPLETED->value,
                'icon'      => 'fa-briefcase',
                'color'     => 'success',
            ],
        ];
    }

    /**
     * Current stage details (name, icon, colour, progress within stage).
     */
    public function getCurrentStage(Candidate $candidate): array
    {
        foreach ($this->stages as $key => $stage) {
            if ($stage['status'] === $candidate->status) {
                return array_merge($stage, [
                    'key'      => $key,
                    'data'     => $this->getStageData($candidate, $key),
                    'progress' => $this->getStageProgress($candidate, $key),
                ]);
            }
        }

        // Fallback for terminal states
        return [
            'key'      => $candidate->status,
            'name'     => CandidateStatus::tryFrom($candidate->status)?->label() ?? $candidate->status,
            'icon'     => 'fas fa-flag',
            'color'    => 'red',
            'status'   => $candidate->status,
            'module'   => null,
            'data'     => [],
            'progress' => 100,
        ];
    }

    /**
     * Actionable next steps for the candidate's current stage.
     */
    public function getNextRequiredActions(Candidate $candidate): array
    {
        $actions = [];

        switch ($candidate->status) {
            case 'listed':
                $actions[] = ['action' => 'Upload Pre-Departure Documents', 'url' => route('candidates.pre-departure-documents.index', $candidate)];
                break;

            case 'pre_departure_docs':
                $incomplete = $candidate->preDepartureDocuments()->whereNull('verified_at')->count();
                if ($incomplete > 0) {
                    $actions[] = ['action' => "Verify {$incomplete} document(s)", 'url' => route('candidates.pre-departure-documents.index', $candidate)];
                } else {
                    $actions[] = ['action' => 'Proceed to Initial Screening', 'url' => route('screening.create', $candidate)];
                }
                break;

            case 'screening':
                $actions[] = ['action' => 'Complete Initial Screening', 'url' => route('screening.create', $candidate)];
                break;

            case 'screened':
                $actions[] = ['action' => 'Complete Registration', 'url' => route('registration.create', $candidate)];
                break;

            case 'registered':
                if (!$candidate->batch_id) {
                    $actions[] = ['action' => 'Assign to Training Batch', 'url' => route('candidates.show', $candidate)];
                } else {
                    $actions[] = ['action' => 'Start Training', 'url' => route('training.show', $candidate->training ?? $candidate)];
                }
                break;

            case 'training':
                $actions[] = ['action' => 'Record Attendance & Assessments', 'url' => route('training.show', $candidate->training ?? $candidate)];
                break;

            case 'training_completed':
                $actions[] = ['action' => 'Start Visa Processing', 'url' => route('visa.create', $candidate)];
                break;

            case 'visa_process':
                $actions[] = ['action' => 'Update Visa Stage', 'url' => route('visa.show', $candidate->visaProcess ?? $candidate)];
                break;

            case 'visa_approved':
                $actions[] = ['action' => 'Start Departure Processing', 'url' => route('departure.show', $candidate->departure ?? $candidate)];
                break;

            case 'departure_processing':
            case 'ready_to_depart':
                $actions[] = ['action' => 'Update Departure Details', 'url' => route('departure.show', $candidate->departure ?? $candidate)];
                break;

            case 'departed':
                $actions[] = ['action' => 'Complete Post-Departure Setup', 'url' => route('post-departure.show', $candidate)];
                break;

            case 'post_departure':
                $actions[] = ['action' => 'Verify 90-Day Compliance', 'url' => route('post-departure.show', $candidate)];
                break;
        }

        return $actions;
    }

    /**
     * Blockers preventing the candidate from progressing.
     */
    public function getBlockers(Candidate $candidate): array
    {
        $blockers = [];

        // Expired documents
        $expiredDocs = $candidate->preDepartureDocuments()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->get();

        foreach ($expiredDocs as $doc) {
            $type       = $doc->documentChecklist?->document_type ?? 'Document';
            $blockers[] = [
                'type'     => 'document_expired',
                'severity' => 'high',
                'message'  => "'{$type}' has expired (expired: {$doc->expiry_date->format('d M Y')})",
                'action'   => 'Upload renewed document',
                'url'      => route('candidates.pre-departure-documents.index', $candidate),
            ];
        }

        // Unresolved SLA-breached complaints
        $breachedComplaints = $candidate->complaints()
            ->where('sla_breached', true)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        if ($breachedComplaints > 0) {
            $blockers[] = [
                'type'     => 'complaint_sla',
                'severity' => 'high',
                'message'  => "{$breachedComplaints} complaint(s) with breached SLA",
                'action'   => 'Resolve complaints',
                'url'      => route('complaints.index', ['candidate_id' => $candidate->id]),
            ];
        }

        // Pending document renewal requests
        $pendingRenewals = $candidate->documentRenewalRequests()
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        if ($pendingRenewals > 0) {
            $blockers[] = [
                'type'     => 'renewal_pending',
                'severity' => 'medium',
                'message'  => "{$pendingRenewals} document renewal(s) pending",
                'action'   => 'Process renewal requests',
                'url'      => route('candidates.pre-departure-documents.index', $candidate),
            ];
        }

        return $blockers;
    }

    /**
     * Estimated completion date based on average historical processing times.
     */
    public function estimateCompletionDate(Candidate $candidate): ?string
    {
        $currentOrder   = CandidateStatus::tryFrom($candidate->status)?->order() ?? 0;
        $completedOrder = CandidateStatus::COMPLETED->order();

        if ($currentOrder >= $completedOrder) {
            return null;
        }

        $averageDays   = $this->getAverageDaysPerStage();
        $remainingDays = 0;

        foreach ($this->stages as $key => $stage) {
            $stageOrder = CandidateStatus::tryFrom($stage['status'])?->order() ?? 0;
            if ($stageOrder > $currentOrder) {
                $remainingDays += $averageDays[$key] ?? 14;
            }
        }

        return now()->addDays($remainingDays)->format('Y-m-d');
    }

    /**
     * Overall completion percentage (0-100).
     */
    public function getProgressPercentage(Candidate $candidate): int
    {
        $currentOrder  = CandidateStatus::tryFrom($candidate->status)?->order() ?? 0;
        $totalStages   = count($this->stages);
        $completed     = 0;

        foreach ($this->stages as $stage) {
            $order = CandidateStatus::tryFrom($stage['status'])?->order() ?? 0;
            if ($order < $currentOrder) {
                $completed++;
            }
        }

        return $totalStages > 0 ? (int) round(($completed / $totalStages) * 100) : 0;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Legacy helpers kept for backward compatibility
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @deprecated Use getCompleteJourney() instead.
     */
    public function getJourneyData(Candidate $candidate): array
    {
        return [
            'candidate'           => $candidate->load(['trade', 'campus', 'batch', 'oep', 'screenings', 'trainingCertificates', 'visaProcess', 'departure']),
            'milestones'          => $this->getMilestones($candidate),
            'currentStage'        => $this->getCurrentStage($candidate)['name'] ?? 'Unknown',
            'completionPercentage' => $this->getProgressPercentage($candidate),
        ];
    }

    /**
     * @deprecated Use getCompleteJourney() + filter activities instead.
     */
    public function getActivities(Candidate $candidate): Collection
    {
        $activities = collect();

        foreach ($candidate->screenings as $screening) {
            $activities->push([
                'type'        => 'screening',
                'date'        => $screening->reviewed_at ?? $screening->created_at,
                'title'       => 'Screening Call',
                'description' => "Screening status: {$screening->screening_status}",
                'icon'        => 'fa-phone',
                'color'       => $screening->screening_status === 'screened' ? 'success' : 'warning',
            ]);
        }

        foreach ($candidate->trainingCertificates as $certificate) {
            $activities->push([
                'type'        => 'certificate',
                'date'        => $certificate->issue_date,
                'title'       => 'Training Certificate Issued',
                'description' => "Certificate #{$certificate->certificate_number}",
                'icon'        => 'fa-certificate',
                'color'       => 'success',
            ]);
        }

        if ($candidate->visaProcess) {
            if ($candidate->visaProcess->application_submitted_at) {
                $activities->push([
                    'type'        => 'visa',
                    'date'        => $candidate->visaProcess->application_submitted_at,
                    'title'       => 'Visa Application Submitted',
                    'description' => 'Visa application submitted for processing',
                    'icon'        => 'fa-file-export',
                    'color'       => 'primary',
                ]);
            }
            if ($candidate->visaProcess->visa_issued_at) {
                $activities->push([
                    'type'        => 'visa',
                    'date'        => $candidate->visaProcess->visa_issued_at,
                    'title'       => 'Visa Approved',
                    'description' => "Visa Number: {$candidate->visaProcess->visa_number}",
                    'icon'        => 'fa-check-circle',
                    'color'       => 'success',
                ]);
            }
        }

        if ($candidate->departure?->actual_departure_date) {
            $activities->push([
                'type'        => 'departure',
                'date'        => $candidate->departure->actual_departure_date,
                'title'       => 'Departed',
                'description' => "Flight: {$candidate->departure->flight_number}",
                'icon'        => 'fa-plane-departure',
                'color'       => 'info',
            ]);
        }

        return $activities->sortByDesc('date')->values();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    protected function getStageCompletionDate(Candidate $candidate, string $stageKey): ?string
    {
        return match ($stageKey) {
            'listing'              => $candidate->created_at?->toDateString(),
            'pre_departure_docs'   => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->latest('verified_at')->value('verified_at'),
            'screening', 'screened' => $candidate->screenings()->where('screening_status', 'screened')->value('reviewed_at'),
            'registration'         => $candidate->registration_date?->toDateString(),
            'training'             => null,
            'training_completed'   => $candidate->trainingCertificates()->latest('issue_date')->value('issue_date'),
            'visa_process'         => null,
            'visa_approved'        => $candidate->visaProcess?->visa_issued_at?->toDateString(),
            'departed'             => $candidate->departure?->actual_departure_date?->toDateString(),
            'post_departure', 'completed' => $candidate->postDepartureDetail?->compliance_verified_date?->toDateString(),
            default                => null,
        };
    }

    protected function getStageData(Candidate $candidate, string $stageKey): array
    {
        return match ($stageKey) {
            'listing', 'pre_departure_docs' => [
                'documents_completed' => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->count(),
                'documents_total'     => $candidate->preDepartureDocuments()->count(),
            ],
            'screening', 'screened' => [
                'screening' => $candidate->screenings()->latest()->first()?->only(['screening_outcome', 'placement_interest', 'reviewed_at']),
            ],
            'registration' => [
                'batch'            => $candidate->batch?->name,
                'allocated_number' => $candidate->allocated_number,
            ],
            'training', 'training_completed' => [
                'technical_status'  => $candidate->training?->technical_training_status?->value ?? null,
                'soft_skills_status' => $candidate->training?->soft_skills_status?->value ?? null,
            ],
            'visa_process', 'visa_approved' => [
                'current_stage' => $candidate->visaProcess?->current_stage,
                'e_number'      => $candidate->visaProcess?->e_number,
                'ptn_number'    => $candidate->visaProcess?->ptn_number,
            ],
            'departure_processing', 'ready_to_depart', 'departed' => [
                'flight_date' => $candidate->departure?->actual_departure_date?->toDateString(),
                'airline'     => $candidate->departure?->airline,
            ],
            'post_departure', 'completed' => [
                'iqama_status'        => $candidate->postDepartureDetail?->iqama_status?->value ?? null,
                'compliance_verified' => $candidate->postDepartureDetail?->compliance_verified ?? false,
            ],
            default => [],
        };
    }

    protected function getStageProgress(Candidate $candidate, string $stageKey): int
    {
        return match ($stageKey) {
            'pre_departure_docs' => $this->documentProgress($candidate),
            'training'           => $this->trainingProgress($candidate),
            'visa_process'       => 50,
            default              => 100,
        };
    }

    private function documentProgress(Candidate $candidate): int
    {
        $total    = $candidate->preDepartureDocuments()->count();
        $verified = $candidate->preDepartureDocuments()->whereNotNull('verified_at')->count();
        return $total > 0 ? (int) round(($verified / $total) * 100) : 0;
    }

    private function trainingProgress(Candidate $candidate): int
    {
        $training = $candidate->training;
        if (!$training) {
            return 0;
        }
        $techDone  = ($training->technical_training_status?->value === 'completed') ? 1 : 0;
        $softDone  = ($training->soft_skills_status?->value === 'completed') ? 1 : 0;
        return (int) round((($techDone + $softDone) / 2) * 100);
    }

    protected function getAverageDaysPerStage(): array
    {
        return [
            'listing'              => 1,
            'pre_departure_docs'   => 7,
            'screening'            => 3,
            'screened'             => 2,
            'registration'         => 5,
            'training'             => 45,
            'training_completed'   => 2,
            'visa_process'         => 30,
            'visa_approved'        => 7,
            'departure_processing' => 14,
            'ready_to_depart'      => 3,
            'departed'             => 1,
            'post_departure'       => 90,
            'completed'            => 0,
        ];
    }
}
