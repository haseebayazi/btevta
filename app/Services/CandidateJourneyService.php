<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\PostDepartureDetail;
use App\Enums\CandidateStatus;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CandidateJourneyService
{
    /**
     * Define all stages in the candidate lifecycle
     */
    protected array $stages = [
        'listing' => [
            'name' => 'Listing',
            'icon' => 'fas fa-user-plus',
            'color' => 'gray',
            'status' => 'listed',
            'module' => 1,
            'order' => 1,
        ],
        'pre_departure_docs' => [
            'name' => 'Pre-Departure Documents',
            'icon' => 'fas fa-file-alt',
            'color' => 'blue',
            'status' => 'pre_departure_docs',
            'module' => 1,
            'order' => 2,
        ],
        'screening' => [
            'name' => 'Initial Screening',
            'icon' => 'fas fa-clipboard-check',
            'color' => 'indigo',
            'status' => 'screening',
            'module' => 2,
            'order' => 3,
        ],
        'screened' => [
            'name' => 'Screened',
            'icon' => 'fas fa-check-circle',
            'color' => 'indigo',
            'status' => 'screened',
            'module' => 2,
            'order' => 4,
        ],
        'registered' => [
            'name' => 'Registration',
            'icon' => 'fas fa-user-check',
            'color' => 'green',
            'status' => 'registered',
            'module' => 3,
            'order' => 5,
        ],
        'training' => [
            'name' => 'Training',
            'icon' => 'fas fa-graduation-cap',
            'color' => 'yellow',
            'status' => 'training',
            'module' => 4,
            'order' => 6,
        ],
        'training_completed' => [
            'name' => 'Training Completed',
            'icon' => 'fas fa-certificate',
            'color' => 'green',
            'status' => 'training_completed',
            'module' => 4,
            'order' => 7,
        ],
        'visa_process' => [
            'name' => 'Visa Processing',
            'icon' => 'fas fa-passport',
            'color' => 'purple',
            'status' => 'visa_process',
            'module' => 5,
            'order' => 8,
        ],
        'visa_approved' => [
            'name' => 'Visa Approved',
            'icon' => 'fas fa-stamp',
            'color' => 'green',
            'status' => 'visa_approved',
            'module' => 5,
            'order' => 9,
        ],
        'departure_processing' => [
            'name' => 'Departure Processing',
            'icon' => 'fas fa-plane-departure',
            'color' => 'blue',
            'status' => 'departure_processing',
            'module' => 6,
            'order' => 10,
        ],
        'ready_to_depart' => [
            'name' => 'Ready to Depart',
            'icon' => 'fas fa-suitcase',
            'color' => 'blue',
            'status' => 'ready_to_depart',
            'module' => 6,
            'order' => 11,
        ],
        'departed' => [
            'name' => 'Departed',
            'icon' => 'fas fa-plane',
            'color' => 'green',
            'status' => 'departed',
            'module' => 6,
            'order' => 12,
        ],
        'post_departure' => [
            'name' => 'Post Departure',
            'icon' => 'fas fa-globe',
            'color' => 'teal',
            'status' => 'post_departure',
            'module' => 7,
            'order' => 13,
        ],
        'completed' => [
            'name' => 'Completed',
            'icon' => 'fas fa-flag-checkered',
            'color' => 'green',
            'status' => 'completed',
            'module' => 7,
            'order' => 14,
        ],
    ];

    /**
     * Get complete journey data for a candidate
     */
    public function getCompleteJourney(Candidate $candidate): array
    {
        $currentStatus = $candidate->status;
        $currentOrder = $this->getStatusOrder($currentStatus);

        return collect($this->stages)->map(function ($stage, $key) use ($candidate, $currentOrder) {
            $stageOrder = $stage['order'];
            $status = 'pending';
            $completedAt = null;
            $details = [];

            if ($stageOrder < $currentOrder) {
                $status = 'completed';
                $completedAt = $this->getStageCompletedAt($candidate, $key);
                $details = $this->getStageDetails($candidate, $key);
            } elseif ($stageOrder === $currentOrder) {
                $status = 'in_progress';
                $details = $this->getStageDetails($candidate, $key);
            }

            return [
                'key' => $key,
                'name' => $stage['name'],
                'icon' => $stage['icon'],
                'color' => $stage['color'],
                'module' => $stage['module'],
                'order' => $stageOrder,
                'status' => $status,
                'completed_at' => $completedAt,
                'details' => $details,
            ];
        })->values()->toArray();
    }

    /**
     * Get stage order by status
     */
    protected function getStatusOrder(string $status): int
    {
        foreach ($this->stages as $stage) {
            if ($stage['status'] === $status) {
                return $stage['order'];
            }
        }

        // Handle terminal statuses
        return match ($status) {
            'deferred', 'rejected', 'withdrawn' => 0,
            default => 1,
        };
    }

    /**
     * Get when a stage was completed
     */
    protected function getStageCompletedAt(Candidate $candidate, string $stageKey): ?string
    {
        return match ($stageKey) {
            'listing' => $candidate->created_at?->toDateTimeString(),
            'pre_departure_docs' => $candidate->preDepartureDocuments()
                ->latest('verified_at')
                ->value('verified_at'),
            'screening', 'screened' => $candidate->screenings()
                ->where('screening_status', 'screened')
                ->value('reviewed_at'),
            'registered' => $candidate->registered_at?->toDateTimeString(),
            'training' => $candidate->batch?->start_date?->toDateTimeString(),
            'training_completed' => $candidate->trainingCertificates()
                ->latest('issued_at')
                ->value('issued_at'),
            'visa_process', 'visa_approved' => $candidate->visaProcess?->visa_issued_at?->toDateTimeString(),
            'departure_processing', 'ready_to_depart' => $candidate->departure?->created_at?->toDateTimeString(),
            'departed' => $candidate->departure?->actual_departure_date?->toDateTimeString(),
            'post_departure' => $candidate->departure?->arrival_confirmation_date?->toDateTimeString(),
            'completed' => $candidate->departure?->employment_confirmed_at?->toDateTimeString(),
            default => null,
        };
    }

    /**
     * Get stage-specific details
     */
    protected function getStageDetails(Candidate $candidate, string $stageKey): array
    {
        return match ($stageKey) {
            'listing' => [
                'campus' => $candidate->campus?->name,
                'trade' => $candidate->trade?->name,
                'oep' => $candidate->oep?->name,
            ],
            'pre_departure_docs' => [
                'documents_uploaded' => $candidate->preDepartureDocuments()->count(),
                'documents_verified' => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->count(),
            ],
            'screening', 'screened' => $this->getScreeningDetails($candidate),
            'registered' => [
                'batch' => $candidate->batch?->name,
                'allocated_number' => $candidate->allocated_number,
            ],
            'training', 'training_completed' => $this->getTrainingDetails($candidate),
            'visa_process', 'visa_approved' => $this->getVisaDetails($candidate),
            'departure_processing', 'ready_to_depart', 'departed' => $this->getDepartureDetails($candidate),
            'post_departure', 'completed' => $this->getPostDepartureDetails($candidate),
            default => [],
        };
    }

    /**
     * Get screening details
     */
    protected function getScreeningDetails(Candidate $candidate): array
    {
        $screening = $candidate->screenings()->latest()->first();

        if (!$screening) {
            return [];
        }

        return [
            'outcome' => $screening->screening_status ?? $screening->status,
            'placement_interest' => $screening->placement_interest,
            'target_country' => $screening->targetCountry?->name,
            'consent_for_work' => $screening->consent_for_work,
            'reviewed_at' => $screening->reviewed_at?->toDateTimeString(),
            'reviewer' => $screening->reviewer?->name,
        ];
    }

    /**
     * Get training details
     */
    protected function getTrainingDetails(Candidate $candidate): array
    {
        $batch = $candidate->batch;

        if (!$batch) {
            return [];
        }

        $attendanceRate = TrainingAttendance::where('candidate_id', $candidate->id)
            ->whereNotNull('attended_at')
            ->count();

        $totalScheduled = TrainingAttendance::where('candidate_id', $candidate->id)->count();

        $assessments = TrainingAssessment::where('candidate_id', $candidate->id)
            ->get(['assessment_type', 'score', 'grade', 'passed'])
            ->toArray();

        return [
            'batch_name' => $batch->name,
            'start_date' => $batch->start_date?->toDateString(),
            'end_date' => $batch->end_date?->toDateString(),
            'attendance_rate' => $totalScheduled > 0 ? round(($attendanceRate / $totalScheduled) * 100, 1) : 0,
            'assessments' => $assessments,
            'certificate_issued' => $candidate->trainingCertificates()->exists(),
        ];
    }

    /**
     * Get visa processing details
     */
    protected function getVisaDetails(Candidate $candidate): array
    {
        $visaProcess = $candidate->visaProcess;

        if (!$visaProcess) {
            return [];
        }

        return [
            'current_stage' => $visaProcess->current_stage,
            'e_number' => $visaProcess->e_number,
            'visa_number' => $visaProcess->visa_number,
            'visa_issued_at' => $visaProcess->visa_issued_at?->toDateString(),
            'ptn_number' => $visaProcess->ptn_number,
        ];
    }

    /**
     * Get departure details
     */
    protected function getDepartureDetails(Candidate $candidate): array
    {
        $departure = $candidate->departure;

        if (!$departure) {
            return [];
        }

        return [
            'destination' => $departure->destination,
            'flight_number' => $departure->flight_number,
            'airline' => $departure->airline,
            'departure_date' => $departure->actual_departure_date?->toDateString() ?? $departure->scheduled_departure?->toDateString(),
            'employer' => $departure->employer?->name,
        ];
    }

    /**
     * Get post-departure details
     */
    protected function getPostDepartureDetails(Candidate $candidate): array
    {
        $postDeparture = $candidate->departure?->postDepartureDetails;

        if (!$postDeparture) {
            return [];
        }

        return [
            'residency_number' => $postDeparture->residency_number,
            'residency_expiry' => $postDeparture->residency_expiry?->toDateString(),
            'employment_status' => $postDeparture->employment_status,
            'job_title' => $postDeparture->job_title,
            'monthly_salary' => $postDeparture->monthly_salary,
        ];
    }

    /**
     * Get key milestones
     */
    public function getMilestones(Candidate $candidate): array
    {
        return [
            [
                'name' => 'Listed',
                'date' => $candidate->created_at?->toDateString(),
                'completed' => true,
            ],
            [
                'name' => 'Documents Verified',
                'date' => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->latest('verified_at')->value('verified_at'),
                'completed' => $candidate->preDepartureDocuments()->whereNotNull('verified_at')->exists(),
            ],
            [
                'name' => 'Screened',
                'date' => $candidate->screenings()->where('screening_status', 'screened')->value('reviewed_at'),
                'completed' => in_array($candidate->status, ['screened', 'registered', 'training', 'training_completed', 'visa_process', 'visa_approved', 'departure_processing', 'ready_to_depart', 'departed', 'post_departure', 'completed']),
            ],
            [
                'name' => 'Registered',
                'date' => $candidate->registered_at?->toDateString(),
                'completed' => $candidate->registered_at !== null,
            ],
            [
                'name' => 'Training Complete',
                'date' => $candidate->trainingCertificates()->latest('issued_at')->value('issued_at'),
                'completed' => $candidate->trainingCertificates()->exists(),
            ],
            [
                'name' => 'Visa Approved',
                'date' => $candidate->visaProcess?->visa_issued_at?->toDateString(),
                'completed' => $candidate->visaProcess?->visa_issued_at !== null,
            ],
            [
                'name' => 'Departed',
                'date' => $candidate->departure?->actual_departure_date?->toDateString(),
                'completed' => $candidate->departure?->actual_departure_date !== null,
            ],
            [
                'name' => 'Employment Confirmed',
                'date' => $candidate->departure?->employment_confirmed_at?->toDateString(),
                'completed' => $candidate->departure?->employment_confirmed_at !== null,
            ],
        ];
    }

    /**
     * Get current stage information
     */
    public function getCurrentStage(Candidate $candidate): array
    {
        $status = $candidate->status;

        foreach ($this->stages as $key => $stage) {
            if ($stage['status'] === $status) {
                return [
                    'key' => $key,
                    'name' => $stage['name'],
                    'icon' => $stage['icon'],
                    'color' => $stage['color'],
                    'module' => $stage['module'],
                ];
            }
        }

        // Handle terminal statuses
        return [
            'key' => $status,
            'name' => ucfirst(str_replace('_', ' ', $status)),
            'icon' => 'fas fa-times-circle',
            'color' => 'red',
            'module' => null,
        ];
    }

    /**
     * Get next required actions for candidate
     */
    public function getNextRequiredActions(Candidate $candidate): array
    {
        $actions = [];

        switch ($candidate->status) {
            case 'listed':
                $actions[] = [
                    'action' => 'Upload pre-departure documents',
                    'route' => 'candidates.pre-departure-documents.index',
                    'params' => ['candidate' => $candidate->id],
                ];
                break;

            case 'pre_departure_docs':
                $pendingDocs = $candidate->getMissingMandatoryDocuments();
                if (count($pendingDocs) > 0) {
                    $actions[] = [
                        'action' => 'Complete missing documents: ' . implode(', ', array_slice($pendingDocs, 0, 3)),
                        'route' => 'candidates.pre-departure-documents.index',
                        'params' => ['candidate' => $candidate->id],
                    ];
                } else {
                    $actions[] = [
                        'action' => 'Proceed to Initial Screening',
                        'route' => 'candidates.initial-screening',
                        'params' => ['candidate' => $candidate->id],
                    ];
                }
                break;

            case 'screening':
                $actions[] = [
                    'action' => 'Complete initial screening',
                    'route' => 'candidates.initial-screening',
                    'params' => ['candidate' => $candidate->id],
                ];
                break;

            case 'screened':
                $actions[] = [
                    'action' => 'Complete registration',
                    'route' => 'registration.show',
                    'params' => ['candidate' => $candidate->id],
                ];
                break;

            case 'registered':
                if (!$candidate->batch_id) {
                    $actions[] = [
                        'action' => 'Assign to training batch',
                        'route' => 'admin.batches.index',
                        'params' => [],
                    ];
                } else {
                    $actions[] = [
                        'action' => 'Begin training',
                        'route' => 'training.batch.show',
                        'params' => ['batch' => $candidate->batch_id],
                    ];
                }
                break;

            case 'training':
                $actions[] = [
                    'action' => 'Record attendance and assessments',
                    'route' => 'training.attendance.index',
                    'params' => [],
                ];
                break;

            case 'training_completed':
                $actions[] = [
                    'action' => 'Start visa processing',
                    'route' => 'visa-processing.create',
                    'params' => ['candidate' => $candidate->id],
                ];
                break;

            case 'visa_process':
                $actions[] = [
                    'action' => 'Update visa processing status',
                    'route' => 'visa-processing.index',
                    'params' => [],
                ];
                break;

            case 'visa_approved':
                $actions[] = [
                    'action' => 'Process departure',
                    'route' => 'departure.create',
                    'params' => [],
                ];
                break;

            case 'departure_processing':
            case 'ready_to_depart':
                $actions[] = [
                    'action' => 'Complete departure details',
                    'route' => 'departure.index',
                    'params' => [],
                ];
                break;

            case 'departed':
                $actions[] = [
                    'action' => 'Record post-departure details',
                    'route' => 'admin.post-departure.index',
                    'params' => [],
                ];
                break;
        }

        return $actions;
    }

    /**
     * Get blockers preventing candidate from progressing
     */
    public function getBlockers(Candidate $candidate): array
    {
        $blockers = [];

        switch ($candidate->status) {
            case 'listed':
            case 'pre_departure_docs':
                $missingDocs = $candidate->getMissingMandatoryDocuments();
                if (count($missingDocs) > 0) {
                    $blockers[] = [
                        'type' => 'documents',
                        'message' => 'Missing mandatory documents: ' . implode(', ', $missingDocs),
                        'severity' => 'high',
                    ];
                }

                $unverifiedDocs = $candidate->preDepartureDocuments()
                    ->whereNull('verified_at')
                    ->count();
                if ($unverifiedDocs > 0) {
                    $blockers[] = [
                        'type' => 'verification',
                        'message' => "{$unverifiedDocs} documents pending verification",
                        'severity' => 'medium',
                    ];
                }
                break;

            case 'screening':
                $screening = $candidate->screenings()->latest()->first();
                if (!$screening || !$screening->consent_for_work) {
                    $blockers[] = [
                        'type' => 'consent',
                        'message' => 'Consent for work not obtained',
                        'severity' => 'high',
                    ];
                }
                break;

            case 'training':
                $attendanceRate = $this->calculateAttendanceRate($candidate);
                if ($attendanceRate < 80) {
                    $blockers[] = [
                        'type' => 'attendance',
                        'message' => "Attendance rate below 80% (currently {$attendanceRate}%)",
                        'severity' => 'high',
                    ];
                }
                break;

            case 'visa_process':
                $visaProcess = $candidate->visaProcess;
                if ($visaProcess && $visaProcess->current_stage === 'interview' && $visaProcess->interview_result === 'failed') {
                    $blockers[] = [
                        'type' => 'visa',
                        'message' => 'Interview failed - visa cannot proceed',
                        'severity' => 'critical',
                    ];
                }
                break;
        }

        return $blockers;
    }

    /**
     * Calculate attendance rate
     */
    protected function calculateAttendanceRate(Candidate $candidate): float
    {
        $total = TrainingAttendance::where('candidate_id', $candidate->id)->count();
        $attended = TrainingAttendance::where('candidate_id', $candidate->id)
            ->whereNotNull('attended_at')
            ->count();

        return $total > 0 ? round(($attended / $total) * 100, 1) : 0;
    }

    /**
     * Estimate completion date based on average processing times
     */
    public function estimateCompletionDate(Candidate $candidate): ?string
    {
        $currentOrder = $this->getStatusOrder($candidate->status);
        $completedOrder = $this->stages['completed']['order'];
        $remainingStages = $completedOrder - $currentOrder;

        if ($remainingStages <= 0) {
            return null;
        }

        // Average days per stage (rough estimates)
        $daysPerStage = [
            'pre_departure_docs' => 14,
            'screening' => 7,
            'screened' => 3,
            'registered' => 7,
            'training' => 60,
            'training_completed' => 7,
            'visa_process' => 45,
            'visa_approved' => 7,
            'departure_processing' => 14,
            'ready_to_depart' => 7,
            'departed' => 1,
            'post_departure' => 30,
            'completed' => 0,
        ];

        $totalDays = 0;
        $counting = false;

        foreach ($this->stages as $key => $stage) {
            if ($stage['status'] === $candidate->status) {
                $counting = true;
                continue;
            }
            if ($counting && isset($daysPerStage[$key])) {
                $totalDays += $daysPerStage[$key];
            }
        }

        return Carbon::now()->addDays($totalDays)->toDateString();
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(Candidate $candidate): int
    {
        $currentOrder = $this->getStatusOrder($candidate->status);
        $totalStages = count($this->stages);

        return (int) round(($currentOrder / $totalStages) * 100);
    }
}
