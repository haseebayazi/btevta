<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Enums\CandidateStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineController extends Controller
{
    /**
     * Display the pipeline dashboard showing candidates by stage.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

        $user = auth()->user();

        // Base query with campus filtering for campus admins
        $baseQuery = Candidate::query();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $baseQuery->where('campus_id', $user->campus_id);
        }

        // Get counts by status for the funnel
        $statusCounts = $this->getStatusCounts($baseQuery);

        // Get pipeline stages with candidate counts
        $stages = $this->buildPipelineStages($statusCounts);

        // Get recent transitions
        $recentTransitions = $this->getRecentTransitions($user);

        // Get bottleneck analysis
        $bottlenecks = $this->analyzeBottlenecks($baseQuery);

        // Get candidates requiring attention
        $requiresAttention = $this->getCandidatesRequiringAttention($baseQuery);

        // Filters for the view
        $campuses = Campus::orderBy('name')->get();
        $trades = Trade::where('is_active', true)->orderBy('name')->get();
        $oeps = Oep::where('is_active', true)->orderBy('name')->get();

        return view('pipeline.index', compact(
            'stages',
            'statusCounts',
            'recentTransitions',
            'bottlenecks',
            'requiresAttention',
            'campuses',
            'trades',
            'oeps'
        ));
    }

    /**
     * Get counts grouped by status.
     */
    protected function getStatusCounts($baseQuery): array
    {
        $counts = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Initialize all statuses with 0
        $allStatuses = [];
        foreach (CandidateStatus::cases() as $status) {
            $allStatuses[$status->value] = $counts[$status->value] ?? 0;
        }

        return $allStatuses;
    }

    /**
     * Build pipeline stages for visualization.
     */
    protected function buildPipelineStages(array $statusCounts): array
    {
        $stageDefinitions = [
            [
                'name' => 'Module 1: Listing',
                'icon' => 'fas fa-user-plus',
                'color' => 'gray',
                'statuses' => ['listed', 'pre_departure_docs'],
                'module' => 1,
            ],
            [
                'name' => 'Module 2: Screening',
                'icon' => 'fas fa-clipboard-check',
                'color' => 'indigo',
                'statuses' => ['screening', 'screened'],
                'module' => 2,
            ],
            [
                'name' => 'Module 3: Registration',
                'icon' => 'fas fa-user-check',
                'color' => 'blue',
                'statuses' => ['registered'],
                'module' => 3,
            ],
            [
                'name' => 'Module 4: Training',
                'icon' => 'fas fa-graduation-cap',
                'color' => 'yellow',
                'statuses' => ['training', 'training_completed'],
                'module' => 4,
            ],
            [
                'name' => 'Module 5: Visa',
                'icon' => 'fas fa-passport',
                'color' => 'purple',
                'statuses' => ['visa_process', 'visa_approved'],
                'module' => 5,
            ],
            [
                'name' => 'Module 6: Departure',
                'icon' => 'fas fa-plane-departure',
                'color' => 'teal',
                'statuses' => ['departure_processing', 'ready_to_depart', 'departed'],
                'module' => 6,
            ],
            [
                'name' => 'Module 7: Post-Departure',
                'icon' => 'fas fa-globe',
                'color' => 'green',
                'statuses' => ['post_departure', 'completed'],
                'module' => 7,
            ],
        ];

        $stages = [];
        foreach ($stageDefinitions as $definition) {
            $count = 0;
            $breakdown = [];
            foreach ($definition['statuses'] as $status) {
                $statusCount = $statusCounts[$status] ?? 0;
                $count += $statusCount;
                $breakdown[$status] = [
                    'count' => $statusCount,
                    'label' => CandidateStatus::tryFrom($status)?->label() ?? ucfirst($status),
                ];
            }

            $stages[] = [
                'name' => $definition['name'],
                'icon' => $definition['icon'],
                'color' => $definition['color'],
                'module' => $definition['module'],
                'count' => $count,
                'breakdown' => $breakdown,
            ];
        }

        return $stages;
    }

    /**
     * Get recent status transitions.
     */
    protected function getRecentTransitions($user): \Illuminate\Support\Collection
    {
        $query = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', Candidate::class)
            ->where('description', 'like', '%status%')
            ->with('causer')
            ->latest()
            ->limit(20);

        if ($user->isCampusAdmin() && $user->campus_id) {
            $candidateIds = Candidate::where('campus_id', $user->campus_id)->pluck('id');
            $query->whereIn('subject_id', $candidateIds);
        }

        return $query->get();
    }

    /**
     * Analyze bottlenecks in the pipeline.
     */
    protected function analyzeBottlenecks($baseQuery): array
    {
        $bottlenecks = [];

        // Candidates stuck in screening for more than 7 days
        $stuckScreening = (clone $baseQuery)
            ->where('status', 'screening')
            ->where('updated_at', '<', now()->subDays(7))
            ->count();

        if ($stuckScreening > 0) {
            $bottlenecks[] = [
                'status' => 'screening',
                'count' => $stuckScreening,
                'message' => "{$stuckScreening} candidates stuck in screening for 7+ days",
                'severity' => $stuckScreening > 10 ? 'high' : 'medium',
            ];
        }

        // Candidates stuck in visa processing for more than 30 days
        $stuckVisa = (clone $baseQuery)
            ->where('status', 'visa_process')
            ->where('updated_at', '<', now()->subDays(30))
            ->count();

        if ($stuckVisa > 0) {
            $bottlenecks[] = [
                'status' => 'visa_process',
                'count' => $stuckVisa,
                'message' => "{$stuckVisa} candidates stuck in visa processing for 30+ days",
                'severity' => $stuckVisa > 5 ? 'high' : 'medium',
            ];
        }

        // Candidates departed but not confirmed in 90+ days
        $pendingConfirmation = (clone $baseQuery)
            ->where('status', 'departed')
            ->where('updated_at', '<', now()->subDays(90))
            ->count();

        if ($pendingConfirmation > 0) {
            $bottlenecks[] = [
                'status' => 'departed',
                'count' => $pendingConfirmation,
                'message' => "{$pendingConfirmation} candidates awaiting post-departure confirmation (90+ days)",
                'severity' => 'high',
            ];
        }

        // Candidates without batch assignment
        $noBatch = (clone $baseQuery)
            ->where('status', 'registered')
            ->whereNull('batch_id')
            ->count();

        if ($noBatch > 0) {
            $bottlenecks[] = [
                'status' => 'registered',
                'count' => $noBatch,
                'message' => "{$noBatch} registered candidates without batch assignment",
                'severity' => 'medium',
            ];
        }

        return $bottlenecks;
    }

    /**
     * Get candidates requiring immediate attention.
     */
    protected function getCandidatesRequiringAttention($baseQuery): \Illuminate\Support\Collection
    {
        return (clone $baseQuery)
            ->with(['campus', 'trade'])
            ->where(function ($query) {
                // Screening overdue
                $query->where(function ($q) {
                    $q->where('status', 'screening')
                        ->where('updated_at', '<', now()->subDays(14));
                })
                // Visa processing overdue
                ->orWhere(function ($q) {
                    $q->where('status', 'visa_process')
                        ->where('updated_at', '<', now()->subDays(45));
                })
                // Post-departure follow-up needed
                ->orWhere(function ($q) {
                    $q->where('status', 'departed')
                        ->where('updated_at', '<', now()->subDays(90));
                });
            })
            ->orderBy('updated_at', 'asc')
            ->limit(10)
            ->get();
    }

    /**
     * Get candidates at a specific status for drill-down.
     */
    public function byStatus(Request $request, string $status)
    {
        $this->authorize('viewAny', Candidate::class);

        $user = auth()->user();

        $query = Candidate::where('status', $status)
            ->with(['campus', 'trade', 'batch', 'oep']);

        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        // Apply filters
        if ($request->campus_id) {
            $query->where('campus_id', $request->campus_id);
        }
        if ($request->trade_id) {
            $query->where('trade_id', $request->trade_id);
        }
        if ($request->oep_id) {
            $query->where('oep_id', $request->oep_id);
        }

        $candidates = $query->latest()->paginate(25);

        $statusLabel = CandidateStatus::tryFrom($status)?->label() ?? ucfirst($status);

        return view('pipeline.by-status', compact('candidates', 'status', 'statusLabel'));
    }

    /**
     * Export pipeline data as CSV.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

        $user = auth()->user();

        $query = Candidate::with(['campus', 'trade', 'batch', 'oep']);

        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $candidates = $query->get();

        $filename = 'pipeline-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($candidates) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'TheLeap ID', 'Name', 'CNIC', 'Status', 'Campus', 'Trade', 'Batch', 'OEP', 'Created', 'Updated'
            ]);

            foreach ($candidates as $candidate) {
                fputcsv($file, [
                    $candidate->btevta_id ?? 'N/A',
                    $candidate->name,
                    $candidate->cnic,
                    CandidateStatus::tryFrom($candidate->status)?->label() ?? $candidate->status,
                    $candidate->campus?->name ?? 'N/A',
                    $candidate->trade?->name ?? 'N/A',
                    $candidate->batch?->name ?? 'N/A',
                    $candidate->oep?->name ?? 'N/A',
                    $candidate->created_at?->format('Y-m-d'),
                    $candidate->updated_at?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        activity()
            ->causedBy(auth()->user())
            ->log('Exported pipeline data');

        return response()->stream($callback, 200, $headers);
    }
}
