# Module 10: System Integration & Lifecycle Orchestration - Implementation Prompt for Claude

**Project:** BTEVTA WASL
**Module:** Module 10 - Integration & End-to-End Lifecycle
**Status:** New - Critical for System Completeness
**Date:** February 2026

---

## Executive Summary

This module addresses the **INTEGRATION GAPS** between all other modules to ensure a seamless end-to-end candidate lifecycle. While individual modules are comprehensive, several cross-cutting concerns need implementation for production readiness.

**CRITICAL:** This module connects everything together and ensures data flows correctly through the entire pipeline.

---

## Key Areas Covered

| Area | Priority | Description |
|------|----------|-------------|
| Candidate Journey Dashboard | HIGH | Visual timeline showing complete journey |
| Status Transition Audit | HIGH | Comprehensive logging with reasons |
| Notification Delivery | HIGH | Actually send emails/SMS |
| Document Renewal Workflow | HIGH | Process for expired documents |
| Gate Enforcement | HIGH | Ensure status prerequisites enforced |
| Pipeline Dashboard | HIGH | Master view of all candidates by stage |
| Activity Logging Enhancement | MEDIUM | Systematic model observers |
| E2E Integration Tests | MEDIUM | Complete lifecycle testing |
| Remittance Completion | MEDIUM | Remaining remittance features |
| Scheduled Tasks Verification | MEDIUM | Ensure all cron jobs configured |

---

## Part A: Candidate Journey Dashboard

### A1: Create Journey Controller

```php
// app/Http/Controllers/CandidateJourneyController.php
<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Services\CandidateJourneyService;
use Illuminate\Http\Request;

class CandidateJourneyController extends Controller
{
    public function __construct(protected CandidateJourneyService $journeyService)
    {
    }

    /**
     * Show visual journey timeline for candidate
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $journey = $this->journeyService->getCompleteJourney($candidate);
        $milestones = $this->journeyService->getMilestones($candidate);
        $currentStage = $this->journeyService->getCurrentStage($candidate);
        $nextActions = $this->journeyService->getNextRequiredActions($candidate);
        $blockers = $this->journeyService->getBlockers($candidate);
        $estimatedCompletion = $this->journeyService->estimateCompletionDate($candidate);

        return view('candidates.journey', compact(
            'candidate', 'journey', 'milestones', 'currentStage',
            'nextActions', 'blockers', 'estimatedCompletion'
        ));
    }

    /**
     * Get journey data as JSON for AJAX/API
     */
    public function journeyData(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        return response()->json([
            'journey' => $this->journeyService->getCompleteJourney($candidate),
            'milestones' => $this->journeyService->getMilestones($candidate),
            'current_stage' => $this->journeyService->getCurrentStage($candidate),
            'progress_percentage' => $this->journeyService->getProgressPercentage($candidate),
            'estimated_completion' => $this->journeyService->estimateCompletionDate($candidate),
        ]);
    }

    /**
     * Export journey to PDF
     */
    public function exportPdf(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $journey = $this->journeyService->getCompleteJourney($candidate);

        $pdf = \PDF::loadView('candidates.journey-pdf', compact('candidate', 'journey'));

        return $pdf->download("candidate-journey-{$candidate->theleap_id}.pdf");
    }
}
```

### A2: Create Journey Service

```php
// app/Services/CandidateJourneyService.php
<?php

namespace App\Services;

use App\Models\Candidate;
use App\Enums\CandidateStatus;
use Illuminate\Support\Collection;

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
        ],
        'pre_departure_docs' => [
            'name' => 'Pre-Departure Documents',
            'icon' => 'fas fa-file-alt',
            'color' => 'blue',
            'status' => 'pre_departure_docs',
            'module' => 1,
        ],
        'screening' => [
            'name' => 'Initial Screening',
            'icon' => 'fas fa-clipboard-check',
            'color' => 'indigo',
            'status' => 'screening',
            'module' => 2,
        ],
        'screened' => [
            'name' => 'Screened',
            'icon' => 'fas fa-check-circle',
            'color' => 'indigo',
            'status' => 'screened',
            'module' => 2,
        ],
        'registration' => [
            'name' => 'Registration',
            'icon' => 'fas fa-id-card',
            'color' => 'purple',
            'status' => 'registered',
            'module' => 3,
        ],
        'training' => [
            'name' => 'Training',
            'icon' => 'fas fa-chalkboard-teacher',
            'color' => 'yellow',
            'status' => 'training',
            'module' => 4,
        ],
        'training_completed' => [
            'name' => 'Training Completed',
            'icon' => 'fas fa-graduation-cap',
            'color' => 'yellow',
            'status' => 'training_completed',
            'module' => 4,
        ],
        'visa_process' => [
            'name' => 'Visa Processing',
            'icon' => 'fas fa-passport',
            'color' => 'orange',
            'status' => 'visa_process',
            'module' => 5,
        ],
        'visa_approved' => [
            'name' => 'Visa Approved',
            'icon' => 'fas fa-stamp',
            'color' => 'orange',
            'status' => 'visa_approved',
            'module' => 5,
        ],
        'departure_processing' => [
            'name' => 'Departure Processing',
            'icon' => 'fas fa-plane-departure',
            'color' => 'teal',
            'status' => 'departure_processing',
            'module' => 6,
        ],
        'ready_to_depart' => [
            'name' => 'Ready to Depart',
            'icon' => 'fas fa-suitcase',
            'color' => 'teal',
            'status' => 'ready_to_depart',
            'module' => 6,
        ],
        'departed' => [
            'name' => 'Departed',
            'icon' => 'fas fa-plane',
            'color' => 'green',
            'status' => 'departed',
            'module' => 6,
        ],
        'post_departure' => [
            'name' => 'Post-Departure',
            'icon' => 'fas fa-home',
            'color' => 'green',
            'status' => 'post_departure',
            'module' => 7,
        ],
        'completed' => [
            'name' => 'Completed',
            'icon' => 'fas fa-trophy',
            'color' => 'emerald',
            'status' => 'completed',
            'module' => 7,
        ],
    ];

    /**
     * Get complete journey with all stages and their status
     */
    public function getCompleteJourney(Candidate $candidate): array
    {
        $currentStatusOrder = CandidateStatus::from($candidate->status)->order();
        $journey = [];

        foreach ($this->stages as $key => $stage) {
            $stageStatus = CandidateStatus::tryFrom($stage['status']);
            $stageOrder = $stageStatus?->order() ?? 0;

            $state = 'pending';
            $completedAt = null;
            $data = [];

            if ($stageOrder < $currentStatusOrder) {
                $state = 'completed';
                $completedAt = $this->getStageCompletionDate($candidate, $key);
                $data = $this->getStageData($candidate, $key);
            } elseif ($stageOrder === $currentStatusOrder) {
                $state = 'current';
                $data = $this->getStageData($candidate, $key);
            }

            $journey[$key] = array_merge($stage, [
                'state' => $state,
                'completed_at' => $completedAt,
                'data' => $data,
            ]);
        }

        return $journey;
    }

    /**
     * Get key milestones with dates
     */
    public function getMilestones(Candidate $candidate): array
    {
        return [
            'listed' => [
                'label' => 'Listed',
                'date' => $candidate->created_at,
                'achieved' => true,
            ],
            'screened' => [
                'label' => 'Screened',
                'date' => $candidate->screenings()->latest()->first()?->reviewed_at,
                'achieved' => in_array($candidate->status, ['screened', 'registered', 'training', 'training_completed', 'visa_process', 'visa_approved', 'departure_processing', 'ready_to_depart', 'departed', 'post_departure', 'completed']),
            ],
            'registered' => [
                'label' => 'Registered',
                'date' => $candidate->registrationDocuments()->where('verified', true)->latest()->first()?->verified_at,
                'achieved' => in_array($candidate->status, ['registered', 'training', 'training_completed', 'visa_process', 'visa_approved', 'departure_processing', 'ready_to_depart', 'departed', 'post_departure', 'completed']),
            ],
            'training_completed' => [
                'label' => 'Training Completed',
                'date' => $candidate->training?->completed_at,
                'achieved' => in_array($candidate->status, ['training_completed', 'visa_process', 'visa_approved', 'departure_processing', 'ready_to_depart', 'departed', 'post_departure', 'completed']),
            ],
            'visa_approved' => [
                'label' => 'Visa Approved',
                'date' => $candidate->visaProcess?->visa_approved_at,
                'achieved' => in_array($candidate->status, ['visa_approved', 'departure_processing', 'ready_to_depart', 'departed', 'post_departure', 'completed']),
            ],
            'departed' => [
                'label' => 'Departed',
                'date' => $candidate->departure?->departed_at,
                'achieved' => in_array($candidate->status, ['departed', 'post_departure', 'completed']),
            ],
            'completed' => [
                'label' => '90-Day Compliance',
                'date' => $candidate->postDepartureDetail?->compliance_verified_date,
                'achieved' => $candidate->status === 'completed',
            ],
        ];
    }

    /**
     * Get current stage details
     */
    public function getCurrentStage(Candidate $candidate): array
    {
        $status = $candidate->status;

        foreach ($this->stages as $key => $stage) {
            if ($stage['status'] === $status) {
                return array_merge($stage, [
                    'key' => $key,
                    'data' => $this->getStageData($candidate, $key),
                    'progress' => $this->getStageProgress($candidate, $key),
                ]);
            }
        }

        return [];
    }

    /**
     * Get next required actions
     */
    public function getNextRequiredActions(Candidate $candidate): array
    {
        $actions = [];

        switch ($candidate->status) {
            case 'listed':
                $actions[] = ['action' => 'Upload Pre-Departure Documents', 'url' => route('candidates.pre-departure-documents.index', $candidate)];
                break;

            case 'pre_departure_docs':
                $incomplete = $candidate->preDepartureDocuments()->where('status', '!=', 'verified')->count();
                if ($incomplete > 0) {
                    $actions[] = ['action' => "Complete {$incomplete} document(s)", 'url' => route('candidates.pre-departure-documents.index', $candidate)];
                } else {
                    $actions[] = ['action' => 'Proceed to Initial Screening', 'url' => route('candidates.initial-screening', $candidate)];
                }
                break;

            case 'screening':
                $actions[] = ['action' => 'Complete Initial Screening', 'url' => route('candidates.initial-screening', $candidate)];
                break;

            case 'screened':
                $actions[] = ['action' => 'Complete Registration', 'url' => route('registration.allocation', $candidate)];
                break;

            case 'registered':
                $actions[] = ['action' => 'Assign to Training Batch', 'url' => route('training.assign', $candidate)];
                break;

            case 'training':
                $training = $candidate->training;
                if ($training && !$training->isBothComplete()) {
                    if ($training->technical_training_status->value !== 'completed') {
                        $actions[] = ['action' => 'Complete Technical Training', 'url' => route('training.candidate-progress', $training)];
                    }
                    if ($training->soft_skills_status->value !== 'completed') {
                        $actions[] = ['action' => 'Complete Soft Skills Training', 'url' => route('training.candidate-progress', $training)];
                    }
                }
                break;

            case 'training_completed':
                $actions[] = ['action' => 'Start Visa Processing', 'url' => route('visa.create', $candidate)];
                break;

            case 'visa_process':
                $visaProcess = $candidate->visaProcess;
                if ($visaProcess) {
                    $nextStage = $this->getNextVisaStage($visaProcess);
                    if ($nextStage) {
                        $actions[] = ['action' => "Complete {$nextStage}", 'url' => route('visa.stage-details', [$visaProcess, $nextStage])];
                    }
                }
                break;

            case 'visa_approved':
                $actions[] = ['action' => 'Start Departure Processing', 'url' => route('departure.checklist', $candidate->departure)];
                break;

            case 'departure_processing':
                $departure = $candidate->departure;
                if ($departure) {
                    $checklist = $departure->getDepartureChecklist();
                    foreach ($checklist as $key => $item) {
                        if (!$item['complete']) {
                            $actions[] = ['action' => "Complete: {$item['label']}", 'url' => route('departure.checklist', $departure)];
                        }
                    }
                }
                break;

            case 'ready_to_depart':
                $actions[] = ['action' => 'Record Departure', 'url' => route('departure.record-departure', $candidate->departure)];
                break;

            case 'departed':
                $actions[] = ['action' => 'Complete Post-Departure Setup', 'url' => route('post-departure.show', $candidate)];
                break;

            case 'post_departure':
                $detail = $candidate->postDepartureDetail;
                if ($detail) {
                    $checklist = $detail->getComplianceChecklist();
                    foreach ($checklist as $key => $item) {
                        if (!$item['complete']) {
                            $actions[] = ['action' => "Complete: {$item['label']}", 'url' => route('post-departure.show', $candidate)];
                        }
                    }
                }
                break;
        }

        return $actions;
    }

    /**
     * Get blockers preventing progress
     */
    public function getBlockers(Candidate $candidate): array
    {
        $blockers = [];

        // Check for expired documents
        $expiredDocs = $candidate->preDepartureDocuments()
            ->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereNotNull('expiry_date')->where('expiry_date', '<', now());
            })
            ->get();

        foreach ($expiredDocs as $doc) {
            $blockers[] = [
                'type' => 'document_expired',
                'severity' => 'high',
                'message' => "Document '{$doc->document_type}' has expired",
                'action' => 'Upload new document',
                'url' => route('candidates.pre-departure-documents.index', $candidate),
            ];
        }

        // Check for failed assessments
        if ($candidate->status === 'training') {
            $failedAssessments = $candidate->training?->assessments()
                ->where('score', '<', 50)
                ->get() ?? collect();

            foreach ($failedAssessments as $assessment) {
                $blockers[] = [
                    'type' => 'assessment_failed',
                    'severity' => 'medium',
                    'message' => "Failed {$assessment->assessment_type} assessment",
                    'action' => 'Retake assessment',
                ];
            }
        }

        // Check for visa stage failures
        if ($candidate->status === 'visa_process') {
            $visaProcess = $candidate->visaProcess;
            if ($visaProcess) {
                $stages = $visaProcess->getStagesOverview();
                foreach ($stages as $key => $stage) {
                    if ($stage['details']->getResultEnum()?->isTerminal()) {
                        $blockers[] = [
                            'type' => 'visa_stage_failed',
                            'severity' => 'critical',
                            'message' => "Failed at visa stage: {$stage['name']}",
                            'action' => 'Contact admin for resolution',
                        ];
                    }
                }
            }
        }

        // Check for SLA breached complaints
        $breachedComplaints = $candidate->complaints()
            ->where('sla_breached', true)
            ->where('status', '!=', 'resolved')
            ->count();

        if ($breachedComplaints > 0) {
            $blockers[] = [
                'type' => 'complaint_sla',
                'severity' => 'high',
                'message' => "{$breachedComplaints} complaint(s) with breached SLA",
                'action' => 'Resolve complaints',
                'url' => route('complaints.index', ['candidate_id' => $candidate->id]),
            ];
        }

        return $blockers;
    }

    /**
     * Estimate completion date based on average processing times
     */
    public function estimateCompletionDate(Candidate $candidate): ?string
    {
        $currentStatusOrder = CandidateStatus::from($candidate->status)->order();
        $completedStatusOrder = CandidateStatus::COMPLETED->order();

        if ($currentStatusOrder >= $completedStatusOrder) {
            return null; // Already completed
        }

        // Get average days per stage from historical data
        $averageDaysPerStage = $this->getAverageDaysPerStage();

        $remainingDays = 0;
        foreach ($this->stages as $key => $stage) {
            $stageStatus = CandidateStatus::tryFrom($stage['status']);
            $stageOrder = $stageStatus?->order() ?? 0;

            if ($stageOrder > $currentStatusOrder) {
                $remainingDays += $averageDaysPerStage[$key] ?? 14; // Default 14 days
            }
        }

        return now()->addDays($remainingDays)->format('Y-m-d');
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(Candidate $candidate): int
    {
        $currentStatusOrder = CandidateStatus::from($candidate->status)->order();
        $totalStages = count($this->stages);
        $completedStages = 0;

        foreach ($this->stages as $stage) {
            $stageStatus = CandidateStatus::tryFrom($stage['status']);
            if ($stageStatus && $stageStatus->order() < $currentStatusOrder) {
                $completedStages++;
            }
        }

        return (int) round(($completedStages / $totalStages) * 100);
    }

    /**
     * Get stage-specific data
     */
    protected function getStageData(Candidate $candidate, string $stage): array
    {
        return match($stage) {
            'listing', 'pre_departure_docs' => [
                'documents_completed' => $candidate->preDepartureDocuments()->where('status', 'verified')->count(),
                'documents_total' => $candidate->preDepartureDocuments()->count(),
            ],
            'screening', 'screened' => [
                'screening' => $candidate->screenings()->latest()->first()?->only(['screening_outcome', 'placement_interest', 'reviewed_at']),
            ],
            'registration' => [
                'batch' => $candidate->batch?->batch_number,
                'allocated_number' => $candidate->allocated_number,
            ],
            'training', 'training_completed' => [
                'technical_status' => $candidate->training?->technical_training_status?->value,
                'soft_skills_status' => $candidate->training?->soft_skills_status?->value,
                'attendance_rate' => $candidate->training ? $this->calculateAttendanceRate($candidate->training) : null,
            ],
            'visa_process', 'visa_approved' => [
                'current_stage' => $candidate->visaProcess?->current_stage,
                'e_number' => $candidate->visaProcess?->e_number,
                'ptn_number' => $candidate->visaProcess?->ptn_number,
            ],
            'departure_processing', 'ready_to_depart', 'departed' => [
                'flight_date' => $candidate->departure?->ticket_details_object?->departureDate,
                'airline' => $candidate->departure?->ticket_details_object?->airline,
            ],
            'post_departure', 'completed' => [
                'iqama_status' => $candidate->postDepartureDetail?->iqama_status?->value,
                'compliance_verified' => $candidate->postDepartureDetail?->compliance_verified,
                'employer' => $candidate->postDepartureDetail?->currentEmployment?->company_name,
            ],
            default => [],
        };
    }

    protected function getAverageDaysPerStage(): array
    {
        // These would ideally be calculated from historical data
        return [
            'listing' => 1,
            'pre_departure_docs' => 7,
            'screening' => 3,
            'screened' => 2,
            'registration' => 5,
            'training' => 45,
            'training_completed' => 2,
            'visa_process' => 30,
            'visa_approved' => 7,
            'departure_processing' => 14,
            'ready_to_depart' => 3,
            'departed' => 1,
            'post_departure' => 90,
            'completed' => 0,
        ];
    }
}
```

### A3: Create Journey View

```php
// resources/views/candidates/journey.blade.php
// Visual timeline with:
// - Horizontal progress bar showing all stages
// - Current stage highlighted
// - Completed stages with checkmarks and dates
// - Pending stages grayed out
// - Milestones sidebar
// - Next actions panel
// - Blockers alert panel
// - Estimated completion date
// - Export to PDF button
```

---

## Part B: Pipeline Dashboard (Master Overview)

### B1: Create Pipeline Controller

```php
// app/Http/Controllers/PipelineController.php
<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Enums\CandidateStatus;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewPipeline', Candidate::class);

        $user = auth()->user();
        $campusId = $user->isCampusAdmin() ? $user->campus_id : $request->get('campus_id');

        // Get counts by status
        $pipeline = $this->getPipelineCounts($campusId);

        // Get bottlenecks (stages with high counts relative to average)
        $bottlenecks = $this->identifyBottlenecks($pipeline);

        // Get flow metrics (average time per stage, throughput)
        $flowMetrics = $this->getFlowMetrics($campusId);

        // Get at-risk candidates
        $atRisk = $this->getAtRiskCandidates($campusId);

        return view('pipeline.index', compact('pipeline', 'bottlenecks', 'flowMetrics', 'atRisk'));
    }

    protected function getPipelineCounts(?int $campusId): array
    {
        $query = Candidate::query();

        if ($campusId) {
            $query->where('campus_id', $campusId);
        }

        $counts = $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Organize by module
        return [
            'module_1' => [
                'name' => 'Listing & Pre-Departure',
                'stages' => [
                    'listed' => $counts['listed'] ?? 0,
                    'pre_departure_docs' => $counts['pre_departure_docs'] ?? 0,
                ],
            ],
            'module_2' => [
                'name' => 'Initial Screening',
                'stages' => [
                    'screening' => $counts['screening'] ?? 0,
                    'screened' => $counts['screened'] ?? 0,
                ],
            ],
            'module_3' => [
                'name' => 'Registration',
                'stages' => [
                    'registered' => $counts['registered'] ?? 0,
                ],
            ],
            'module_4' => [
                'name' => 'Training',
                'stages' => [
                    'training' => $counts['training'] ?? 0,
                    'training_completed' => $counts['training_completed'] ?? 0,
                ],
            ],
            'module_5' => [
                'name' => 'Visa Processing',
                'stages' => [
                    'visa_process' => $counts['visa_process'] ?? 0,
                    'visa_approved' => $counts['visa_approved'] ?? 0,
                ],
            ],
            'module_6' => [
                'name' => 'Departure',
                'stages' => [
                    'departure_processing' => $counts['departure_processing'] ?? 0,
                    'ready_to_depart' => $counts['ready_to_depart'] ?? 0,
                    'departed' => $counts['departed'] ?? 0,
                ],
            ],
            'module_7' => [
                'name' => 'Post-Departure',
                'stages' => [
                    'post_departure' => $counts['post_departure'] ?? 0,
                    'completed' => $counts['completed'] ?? 0,
                ],
            ],
            'terminal' => [
                'name' => 'Terminal States',
                'stages' => [
                    'rejected' => $counts['rejected'] ?? 0,
                    'withdrawn' => $counts['withdrawn'] ?? 0,
                    'deferred' => $counts['deferred'] ?? 0,
                ],
            ],
        ];
    }

    protected function identifyBottlenecks(array $pipeline): array
    {
        $bottlenecks = [];
        $threshold = 50; // More than 50 candidates stuck

        foreach ($pipeline as $module) {
            foreach ($module['stages'] as $stage => $count) {
                if ($count > $threshold && !in_array($stage, ['completed', 'rejected', 'withdrawn', 'deferred'])) {
                    $bottlenecks[] = [
                        'stage' => $stage,
                        'count' => $count,
                        'module' => $module['name'],
                    ];
                }
            }
        }

        return $bottlenecks;
    }

    protected function getFlowMetrics(?int $campusId): array
    {
        // Calculate average days at each stage
        // This would query activity logs or status change timestamps

        return [
            'avg_days_to_completion' => 180, // Placeholder
            'throughput_this_month' => Candidate::where('status', 'completed')
                ->whereMonth('updated_at', now()->month)
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->count(),
            'dropout_rate' => $this->calculateDropoutRate($campusId),
        ];
    }

    protected function getAtRiskCandidates(?int $campusId): \Illuminate\Database\Eloquent\Collection
    {
        return Candidate::with(['campus', 'trade'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->where(function ($q) {
                // Stuck at same status for too long
                $q->where('updated_at', '<', now()->subDays(30))
                    ->whereNotIn('status', ['completed', 'rejected', 'withdrawn', 'deferred', 'departed', 'post_departure']);
            })
            ->orWhere(function ($q) {
                // Has expired documents
                $q->whereHas('preDepartureDocuments', function ($q) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($q) {
                            $q->whereNotNull('expiry_date')
                                ->where('expiry_date', '<', now());
                        });
                });
            })
            ->limit(20)
            ->get();
    }
}
```

---

## Part C: Status Transition Audit System

### C1: Create Status Change Log Table

```php
// database/migrations/YYYY_MM_DD_create_candidate_status_logs_table.php
Schema::create('candidate_status_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->string('from_status', 50)->nullable();
    $table->string('to_status', 50);
    $table->string('reason', 500)->nullable();
    $table->text('notes')->nullable();
    $table->json('context')->nullable(); // Additional context data
    $table->foreignId('changed_by')->constrained('users');
    $table->timestamp('changed_at');
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();

    $table->index(['candidate_id', 'changed_at']);
    $table->index('to_status');
});
```

### C2: Create Status Change Observer

```php
// app/Observers/CandidateStatusObserver.php
<?php

namespace App\Observers;

use App\Models\Candidate;
use App\Models\CandidateStatusLog;
use Illuminate\Support\Facades\Request;

class CandidateStatusObserver
{
    public function updating(Candidate $candidate): void
    {
        if ($candidate->isDirty('status')) {
            $fromStatus = $candidate->getOriginal('status');
            $toStatus = $candidate->status;

            CandidateStatusLog::create([
                'candidate_id' => $candidate->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'reason' => session('status_change_reason'),
                'notes' => session('status_change_notes'),
                'context' => session('status_change_context'),
                'changed_by' => auth()->id() ?? 1,
                'changed_at' => now(),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);

            // Clear session data
            session()->forget(['status_change_reason', 'status_change_notes', 'status_change_context']);
        }
    }
}
```

### C3: Register Observer

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Candidate::observe(CandidateStatusObserver::class);
}
```

### C4: Create Helper for Status Changes

```php
// app/Services/StatusTransitionService.php
<?php

namespace App\Services;

use App\Models\Candidate;
use App\Enums\CandidateStatus;

class StatusTransitionService
{
    /**
     * Transition candidate status with reason and validation
     */
    public function transition(
        Candidate $candidate,
        CandidateStatus $toStatus,
        string $reason,
        ?string $notes = null,
        ?array $context = null
    ): bool {
        $fromStatus = CandidateStatus::from($candidate->status);

        // Validate transition
        if (!$fromStatus->canTransitionTo($toStatus)) {
            throw new \Exception(
                "Invalid transition from {$fromStatus->value} to {$toStatus->value}"
            );
        }

        // Check prerequisites
        $this->validatePrerequisites($candidate, $toStatus);

        // Store reason in session for observer
        session([
            'status_change_reason' => $reason,
            'status_change_notes' => $notes,
            'status_change_context' => $context,
        ]);

        // Perform transition
        $candidate->status = $toStatus->value;
        $candidate->save();

        // Fire event
        event(new \App\Events\CandidateStatusChanged($candidate, $fromStatus, $toStatus, $reason));

        return true;
    }

    /**
     * Validate prerequisites for status transition
     */
    protected function validatePrerequisites(Candidate $candidate, CandidateStatus $toStatus): void
    {
        switch ($toStatus) {
            case CandidateStatus::SCREENING:
                // Must have completed pre-departure documents
                $verified = $candidate->preDepartureDocuments()->where('status', 'verified')->count();
                $required = $candidate->preDepartureDocuments()->count();
                if ($verified < $required) {
                    throw new \Exception("All pre-departure documents must be verified before screening.");
                }
                break;

            case CandidateStatus::REGISTERED:
                // Must be screened
                if ($candidate->status !== CandidateStatus::SCREENED->value) {
                    throw new \Exception("Candidate must be screened before registration.");
                }
                break;

            case CandidateStatus::TRAINING:
                // Must be registered with batch
                if (!$candidate->batch_id) {
                    throw new \Exception("Candidate must be assigned to a batch before training.");
                }
                break;

            case CandidateStatus::TRAINING_COMPLETED:
                // Must have completed both training types
                if (!$candidate->training?->isBothComplete()) {
                    throw new \Exception("Both technical and soft skills training must be completed.");
                }
                break;

            case CandidateStatus::VISA_APPROVED:
                // Must have visa process completed
                if ($candidate->visaProcess?->status !== 'completed') {
                    throw new \Exception("Visa process must be completed.");
                }
                break;

            case CandidateStatus::READY_TO_DEPART:
                // Must have all departure checklist items complete
                if (!$candidate->departure?->canMarkReadyToDepart()) {
                    throw new \Exception("All departure checklist items must be complete.");
                }
                break;

            case CandidateStatus::COMPLETED:
                // Must have 90-day compliance verified
                if (!$candidate->postDepartureDetail?->compliance_verified) {
                    throw new \Exception("90-day compliance must be verified.");
                }
                break;
        }
    }

    /**
     * Get status change history for candidate
     */
    public function getHistory(Candidate $candidate): \Illuminate\Database\Eloquent\Collection
    {
        return $candidate->statusLogs()
            ->with('changedBy')
            ->orderBy('changed_at', 'desc')
            ->get();
    }
}
```

---

## Part D: Notification Delivery System

### D1: Configure Mail and SMS

```php
// config/services.php
'sms' => [
    'provider' => env('SMS_PROVIDER', 'twilio'), // or 'nexmo', 'africas_talking'
    'from' => env('SMS_FROM_NUMBER'),
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
    ],
],
```

### D2: Create SMS Channel

```php
// app/Channels/SmsChannel.php
<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $phone = $notifiable->routeNotificationFor('sms', $notification);

        if (!$phone) {
            return;
        }

        $twilio = new Client(
            config('services.sms.twilio.sid'),
            config('services.sms.twilio.token')
        );

        $twilio->messages->create($phone, [
            'from' => config('services.sms.from'),
            'body' => $message,
        ]);
    }
}
```

### D3: Update Notifications to Include SMS

```php
// Example: app/Notifications/CandidateStatusChanged.php
public function via($notifiable): array
{
    $channels = ['database'];

    if ($notifiable->email) {
        $channels[] = 'mail';
    }

    if ($notifiable->phone && $this->isImportantTransition()) {
        $channels[] = SmsChannel::class;
    }

    return $channels;
}

public function toSms($notifiable): string
{
    return "WASL: Your status has been updated to {$this->toStatus->label()}. Log in for details.";
}
```

---

## Part E: Document Renewal Workflow

### E1: Create Document Renewal Request Table

```php
// database/migrations/YYYY_MM_DD_create_document_renewal_requests_table.php
Schema::create('document_renewal_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
    $table->string('document_type', 50);
    $table->morphs('documentable'); // pre_departure_document, registration_document, etc.
    $table->date('current_expiry_date');
    $table->date('requested_date');
    $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
    $table->string('new_document_path', 500)->nullable();
    $table->date('new_expiry_date')->nullable();
    $table->text('notes')->nullable();
    $table->foreignId('requested_by')->constrained('users');
    $table->foreignId('processed_by')->nullable()->constrained('users');
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    $table->index(['candidate_id', 'status']);
    $table->index('document_type');
});
```

### E2: Create Document Renewal Service

```php
// app/Services/DocumentRenewalService.php
<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\DocumentRenewalRequest;
use App\Models\PreDepartureDocument;
use Illuminate\Support\Facades\DB;

class DocumentRenewalService
{
    /**
     * Request document renewal
     */
    public function requestRenewal(
        Candidate $candidate,
        string $documentType,
        $documentable,
        ?string $notes = null
    ): DocumentRenewalRequest {
        return DocumentRenewalRequest::create([
            'candidate_id' => $candidate->id,
            'document_type' => $documentType,
            'documentable_type' => get_class($documentable),
            'documentable_id' => $documentable->id,
            'current_expiry_date' => $documentable->expiry_date,
            'requested_date' => now(),
            'status' => 'pending',
            'notes' => $notes,
            'requested_by' => auth()->id(),
        ]);
    }

    /**
     * Process renewal with new document
     */
    public function processRenewal(
        DocumentRenewalRequest $request,
        $newDocumentFile,
        string $newExpiryDate
    ): void {
        DB::transaction(function () use ($request, $newDocumentFile, $newExpiryDate) {
            // Upload new document
            $path = $newDocumentFile->store(
                "renewals/{$request->candidate_id}",
                'private'
            );

            // Update the original document
            $request->documentable->update([
                'file_path' => $path,
                'expiry_date' => $newExpiryDate,
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

            // Update renewal request
            $request->update([
                'status' => 'completed',
                'new_document_path' => $path,
                'new_expiry_date' => $newExpiryDate,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            activity()
                ->performedOn($request->documentable)
                ->causedBy(auth()->user())
                ->log('Document renewed');
        });
    }

    /**
     * Get pending renewals
     */
    public function getPendingRenewals(?int $campusId = null): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentRenewalRequest::with(['candidate.campus', 'documentable'])
            ->where('status', 'pending')
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($q2) => $q2->where('campus_id', $campusId)))
            ->orderBy('current_expiry_date')
            ->get();
    }

    /**
     * Auto-create renewal requests for expiring documents
     */
    public function createRenewalRequestsForExpiringDocuments(): int
    {
        $expiringDocs = PreDepartureDocument::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->whereDoesntHave('renewalRequests', fn($q) => $q->whereIn('status', ['pending', 'in_progress']))
            ->get();

        $count = 0;
        foreach ($expiringDocs as $doc) {
            $this->requestRenewal(
                $doc->candidate,
                $doc->document_type,
                $doc,
                'Auto-generated: Document expiring within 30 days'
            );
            $count++;
        }

        return $count;
    }
}
```

---

## Part F: Scheduled Tasks Configuration

### F1: Verify Kernel Schedule

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Every 15 minutes
    $schedule->command('complaints:check-sla')->everyFifteenMinutes();

    // Hourly
    $schedule->command('remittance:generate-alerts')->hourly();

    // Daily at 6 AM
    $schedule->command('documents:check-expiry')->dailyAt('06:00');
    $schedule->command('screening:send-reminders')->dailyAt('08:00');
    $schedule->command('salary:send-reminders')->dailyAt('09:00');
    $schedule->command('compliance:check-90-day')->dailyAt('07:00');

    // Daily at 1 AM (cleanup)
    $schedule->command('logs:cleanup')->dailyAt('01:00');
    $schedule->command('data:purge-old')->dailyAt('02:00');

    // Weekly on Monday
    $schedule->command('reports:generate-weekly')->weeklyOn(1, '06:00');

    // Monthly on 1st
    $schedule->command('reports:generate-monthly')->monthlyOn(1, '06:00');

    // NEW: Auto-create renewal requests
    $schedule->command('documents:create-renewal-requests')->dailyAt('07:00');

    // NEW: Send pipeline summary to admins
    $schedule->command('pipeline:send-daily-summary')->dailyAt('08:00');
}
```

### F2: Create Missing Commands

```php
// app/Console/Commands/CreateDocumentRenewalRequests.php
// app/Console/Commands/SendPipelineDailySummary.php
// app/Console/Commands/GenerateWeeklyReport.php
// app/Console/Commands/GenerateMonthlyReport.php
```

---

## Part G: End-to-End Integration Tests

### G1: Complete Lifecycle Test

```php
// tests/Integration/CompleteLifecycleIntegrationTest.php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();

        $this->actingAs($this->admin);
    }

    /** @test */
    public function complete_candidate_lifecycle_from_listing_to_completion(): void
    {
        // STAGE 1: Create candidate (Listed)
        $candidate = $this->createCandidate();
        $this->assertEquals(CandidateStatus::LISTED->value, $candidate->status);

        // STAGE 2: Upload and verify pre-departure documents
        $this->uploadPreDepartureDocuments($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::PRE_DEPARTURE_DOCS->value, $candidate->status);

        // STAGE 3: Complete initial screening
        $this->completeInitialScreening($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::SCREENED->value, $candidate->status);

        // STAGE 4: Complete registration with allocation
        $this->completeRegistration($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::REGISTERED->value, $candidate->status);
        $this->assertNotNull($candidate->batch_id);
        $this->assertNotNull($candidate->allocated_number);

        // STAGE 5: Complete training
        $this->completeTraining($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::TRAINING_COMPLETED->value, $candidate->status);

        // STAGE 6: Complete visa processing
        $this->completeVisaProcessing($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::VISA_APPROVED->value, $candidate->status);

        // STAGE 7: Complete departure processing
        $this->completeDepartureProcessing($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::DEPARTED->value, $candidate->status);

        // STAGE 8: Complete post-departure and 90-day compliance
        $this->completePostDeparture($candidate);
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::COMPLETED->value, $candidate->status);

        // Verify full journey
        $journey = app(\App\Services\CandidateJourneyService::class)->getCompleteJourney($candidate);
        $completedStages = collect($journey)->where('state', 'completed')->count();
        $this->assertEquals(count($journey), $completedStages);
    }

    /** @test */
    public function candidate_cannot_skip_stages(): void
    {
        $candidate = $this->createCandidate();

        // Try to skip to registration without screening
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid transition');

        app(\App\Services\StatusTransitionService::class)->transition(
            $candidate,
            CandidateStatus::REGISTERED,
            'Test skip'
        );
    }

    /** @test */
    public function expired_documents_block_progress(): void
    {
        $candidate = $this->createCandidate();
        $this->uploadPreDepartureDocuments($candidate, expireSoon: true);

        // Fast forward time to expire documents
        $this->travel(31)->days();

        // Try to proceed to screening
        $blockers = app(\App\Services\CandidateJourneyService::class)->getBlockers($candidate);

        $this->assertNotEmpty($blockers);
        $this->assertEquals('document_expired', $blockers[0]['type']);
    }

    // ... helper methods for each stage
}
```

---

## Part H: Activity Logging Enhancement

### H1: Create Model Observers for All Key Models

```php
// app/Observers/ActivityLoggingObserver.php
<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLoggingObserver
{
    public function created(Model $model): void
    {
        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->withProperties(['attributes' => $model->getAttributes()])
            ->log('created');
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => array_intersect_key($model->getOriginal(), $changes),
                'new' => $changes,
            ])
            ->log('updated');
    }

    public function deleted(Model $model): void
    {
        activity()
            ->performedOn($model)
            ->causedBy(auth()->user())
            ->log('deleted');
    }
}

// Register in AppServiceProvider for all key models:
// Candidate, Training, VisaProcess, Departure, PostDepartureDetail, etc.
```

---

## Validation Checklist

### Journey Dashboard
- [ ] CandidateJourneyService created
- [ ] CandidateJourneyController created
- [ ] Journey view created with visual timeline
- [ ] Milestones displayed correctly
- [ ] Next actions calculated correctly
- [ ] Blockers identified correctly
- [ ] PDF export works

### Pipeline Dashboard
- [ ] PipelineController created
- [ ] Pipeline counts by module correct
- [ ] Bottlenecks identified
- [ ] At-risk candidates listed
- [ ] Flow metrics calculated

### Status Audit
- [ ] candidate_status_logs table created
- [ ] CandidateStatusObserver registered
- [ ] StatusTransitionService works
- [ ] All prerequisites validated
- [ ] History retrievable

### Notifications
- [ ] SMS channel configured
- [ ] Key notifications send email AND SMS
- [ ] Notification preferences respected

### Document Renewal
- [ ] Renewal request table created
- [ ] DocumentRenewalService works
- [ ] Auto-creation of renewal requests works
- [ ] Processing renewal updates original document

### Scheduled Tasks
- [ ] All commands scheduled in Kernel
- [ ] Commands tested individually
- [ ] Cron configured on server

### Integration Tests
- [ ] Complete lifecycle test passes
- [ ] Stage skip prevention test passes
- [ ] Document blocker test passes

### Activity Logging
- [ ] Observer registered for all key models
- [ ] Changes logged with before/after
- [ ] Timeline view shows all activities

---

## Files to Create

```
app/Http/Controllers/CandidateJourneyController.php
app/Http/Controllers/PipelineController.php
app/Services/CandidateJourneyService.php
app/Services/StatusTransitionService.php
app/Services/DocumentRenewalService.php
app/Observers/CandidateStatusObserver.php
app/Observers/ActivityLoggingObserver.php
app/Channels/SmsChannel.php
app/Models/CandidateStatusLog.php
app/Models/DocumentRenewalRequest.php
app/Console/Commands/CreateDocumentRenewalRequests.php
app/Console/Commands/SendPipelineDailySummary.php
database/migrations/YYYY_MM_DD_create_candidate_status_logs_table.php
database/migrations/YYYY_MM_DD_create_document_renewal_requests_table.php
resources/views/candidates/journey.blade.php
resources/views/candidates/journey-pdf.blade.php
resources/views/pipeline/index.blade.php
tests/Integration/CompleteLifecycleIntegrationTest.php
docs/MODULE_10_INTEGRATION.md
```

---

## Success Criteria

Module 10 is complete when:

1. Candidate Journey Dashboard shows complete visual timeline
2. Pipeline Dashboard shows all candidates by stage with bottlenecks
3. Status transitions are logged with reasons and prerequisites validated
4. Notifications deliver via email AND SMS for critical updates
5. Document renewal workflow handles expired documents
6. All scheduled tasks are configured and running
7. Activity logging captures all model changes systematically
8. End-to-end integration tests pass for complete lifecycle
9. No candidate can skip stages or proceed with blockers
10. System handles 1000+ concurrent candidates without issues

---

*End of Module 10 Implementation Prompt*
