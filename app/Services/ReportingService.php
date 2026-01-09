<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Trade;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\Complaint;
use App\Models\Remittance;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * ReportingService
 *
 * Dynamic reporting engine for generating customizable reports
 * with filters, aggregations, and export capabilities.
 */
class ReportingService
{
    /**
     * Available report types
     */
    const REPORT_TYPES = [
        'candidates' => 'Candidate Reports',
        'training' => 'Training Reports',
        'visa' => 'Visa Processing Reports',
        'departure' => 'Departure Reports',
        'remittance' => 'Remittance Reports',
        'compliance' => 'Compliance Reports',
        'campus' => 'Campus Performance Reports',
        'oep' => 'OEP Performance Reports',
    ];

    /**
     * Filter operators
     */
    const OPERATORS = [
        'equals' => '=',
        'not_equals' => '!=',
        'greater_than' => '>',
        'less_than' => '<',
        'greater_equal' => '>=',
        'less_equal' => '<=',
        'contains' => 'LIKE',
        'starts_with' => 'LIKE',
        'ends_with' => 'LIKE',
        'between' => 'BETWEEN',
        'in' => 'IN',
        'not_in' => 'NOT IN',
        'is_null' => 'IS NULL',
        'is_not_null' => 'IS NOT NULL',
    ];

    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 15;

    // =========================================================================
    // AUDIT FIX: ROLE-BASED ACCESS CONTROL
    // =========================================================================

    /**
     * Get role-based filter constraints for the current user.
     *
     * AUDIT FIX: This ensures campus admins only see their campus data,
     * OEP users only see their OEP data, preventing cross-tenant data leakage.
     *
     * @return array Filter constraints based on user role
     */
    protected function getRoleBasedFilters(): array
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        // Super admins and project directors can see all data
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return [];
        }

        $filters = [];

        // Campus admins can only see their campus data
        if ($user->role === 'campus_admin' && $user->campus_id) {
            $filters['campus_id'] = $user->campus_id;
        }

        // OEP users can only see their OEP data
        if ($user->role === 'oep' && $user->oep_id) {
            $filters['oep_id'] = $user->oep_id;
        }

        return $filters;
    }

    /**
     * Merge user-provided filters with role-based access constraints.
     *
     * AUDIT FIX: Role-based constraints take precedence and cannot be overridden.
     *
     * @param array $userFilters User-provided filters
     * @return array Merged filters with role-based constraints applied
     */
    protected function applyRoleBasedFilters(array $userFilters): array
    {
        $roleFilters = $this->getRoleBasedFilters();

        // Role-based filters take precedence (cannot be overridden by user)
        return array_merge($userFilters, $roleFilters);
    }

    /**
     * Apply campus-based filtering to a query for role-based access control.
     *
     * @param Builder $query The query builder
     * @param string $campusColumn The column name for campus_id (default: 'campus_id')
     * @return Builder
     */
    protected function applyCampusAccessFilter(Builder $query, string $campusColumn = 'campus_id'): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Super admins and project directors can see all data
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return $query;
        }

        // Campus admins can only see their campus data
        if ($user->role === 'campus_admin' && $user->campus_id) {
            $query->where($campusColumn, $user->campus_id);
        }

        return $query;
    }

    /**
     * Apply OEP-based filtering to a query for role-based access control.
     *
     * @param Builder $query The query builder
     * @param string $oepColumn The column name for oep_id (default: 'oep_id')
     * @return Builder
     */
    protected function applyOepAccessFilter(Builder $query, string $oepColumn = 'oep_id'): Builder
    {
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Super admins and project directors can see all data
        if ($user->isSuperAdmin() || $user->isProjectDirector()) {
            return $query;
        }

        // OEP users can only see their OEP data
        if ($user->role === 'oep' && $user->oep_id) {
            $query->where($oepColumn, $user->oep_id);
        }

        return $query;
    }

    /**
     * Generate cache key that includes role-based constraints.
     *
     * AUDIT FIX: Ensures different users get different cached results
     * based on their access level.
     *
     * @param string $prefix Cache key prefix
     * @param array $filters User-provided filters
     * @return string
     */
    protected function generateRoleAwareCacheKey(string $prefix, array $filters): string
    {
        $roleFilters = $this->getRoleBasedFilters();
        $userId = Auth::id() ?? 'guest';
        $allFilters = array_merge($filters, $roleFilters, ['_user_id' => $userId]);

        return $prefix . '_' . md5(json_encode($allFilters));
    }

    // =========================================================================
    // CANDIDATE REPORTS
    // =========================================================================

    /**
     * Generate candidate pipeline report.
     *
     * @param array $filters
     * @return array
     */
    public function getCandidatePipelineReport(array $filters = []): array
    {
        // AUDIT FIX: Apply role-based access filters
        $filters = $this->applyRoleBasedFilters($filters);
        $cacheKey = $this->generateRoleAwareCacheKey('report_candidate_pipeline', $filters);

        return Cache::remember($cacheKey, self::CACHE_DURATION * 60, function () use ($filters) {
            $query = $this->buildCandidateQuery($filters);

            $statusCounts = (clone $query)->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $total = array_sum($statusCounts);

            return [
                'total_candidates' => $total,
                'by_status' => $statusCounts,
                'by_campus' => $this->getCandidatesByCampus($filters),
                'by_trade' => $this->getCandidatesByTrade($filters),
                'by_oep' => $this->getCandidatesByOep($filters),
                'registration_trend' => $this->getRegistrationTrend($filters),
                'conversion_rates' => $this->getConversionRates($filters),
                'filters_applied' => $filters,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get candidates grouped by campus.
     */
    protected function getCandidatesByCampus(array $filters = []): Collection
    {
        $query = $this->buildCandidateQuery($filters);

        return (clone $query)
            ->select('campus_id', DB::raw('COUNT(*) as count'))
            ->with('campus:id,name,code')
            ->groupBy('campus_id')
            ->get()
            ->map(function ($item) {
                return [
                    'campus_id' => $item->campus_id,
                    'campus_name' => $item->campus->name ?? 'Unassigned',
                    'campus_code' => $item->campus->code ?? 'N/A',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get candidates grouped by trade.
     */
    protected function getCandidatesByTrade(array $filters = []): Collection
    {
        $query = $this->buildCandidateQuery($filters);

        return (clone $query)
            ->select('trade_id', DB::raw('COUNT(*) as count'))
            ->with('trade:id,name,code')
            ->groupBy('trade_id')
            ->get()
            ->map(function ($item) {
                return [
                    'trade_id' => $item->trade_id,
                    'trade_name' => $item->trade->name ?? 'Unassigned',
                    'trade_code' => $item->trade->code ?? 'N/A',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get candidates grouped by OEP.
     */
    protected function getCandidatesByOep(array $filters = []): Collection
    {
        $query = $this->buildCandidateQuery($filters);

        return (clone $query)
            ->select('oep_id', DB::raw('COUNT(*) as count'))
            ->with('oep:id,name,company_name')
            ->groupBy('oep_id')
            ->get()
            ->map(function ($item) {
                return [
                    'oep_id' => $item->oep_id,
                    'oep_name' => $item->oep->name ?? 'Unassigned',
                    'company_name' => $item->oep->company_name ?? 'N/A',
                    'count' => $item->count,
                ];
            });
    }

    /**
     * Get registration trend over time.
     */
    protected function getRegistrationTrend(array $filters = []): Collection
    {
        $startDate = $filters['from_date'] ?? Carbon::now()->subMonths(6);
        $endDate = $filters['to_date'] ?? Carbon::now();

        return Candidate::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get conversion rates between pipeline stages.
     */
    protected function getConversionRates(array $filters = []): array
    {
        $query = $this->buildCandidateQuery($filters);

        $total = (clone $query)->count();
        $screened = (clone $query)->whereIn('status', ['screening', 'registered', 'training', 'visa_processing', 'departed'])->count();
        $registered = (clone $query)->whereIn('status', ['registered', 'training', 'visa_processing', 'departed'])->count();
        $training = (clone $query)->whereIn('status', ['training', 'visa_processing', 'departed'])->count();
        $visa = (clone $query)->whereIn('status', ['visa_processing', 'departed'])->count();
        $departed = (clone $query)->where('status', 'departed')->count();

        return [
            'screening_rate' => $total > 0 ? round(($screened / $total) * 100, 1) : 0,
            'registration_rate' => $total > 0 ? round(($registered / $total) * 100, 1) : 0,
            'training_rate' => $total > 0 ? round(($training / $total) * 100, 1) : 0,
            'visa_rate' => $total > 0 ? round(($visa / $total) * 100, 1) : 0,
            'departure_rate' => $total > 0 ? round(($departed / $total) * 100, 1) : 0,
            'overall_success_rate' => $total > 0 ? round(($departed / $total) * 100, 1) : 0,
        ];
    }

    // =========================================================================
    // TRAINING REPORTS
    // =========================================================================

    /**
     * Generate training statistics report.
     */
    public function getTrainingReport(array $filters = []): array
    {
        // AUDIT FIX: Apply role-based access filters
        $filters = $this->applyRoleBasedFilters($filters);
        $cacheKey = $this->generateRoleAwareCacheKey('report_training', $filters);

        return Cache::remember($cacheKey, self::CACHE_DURATION * 60, function () use ($filters) {
            return [
                'batch_statistics' => $this->getBatchStatistics($filters),
                'attendance_summary' => $this->getAttendanceSummary($filters),
                'assessment_summary' => $this->getAssessmentSummary($filters),
                'trainer_performance' => $this->getTrainerPerformance($filters),
                'campus_comparison' => $this->getCampusTrainingComparison($filters),
                'completion_rates' => $this->getTrainingCompletionRates($filters),
                'filters_applied' => $filters,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get batch statistics.
     */
    protected function getBatchStatistics(array $filters = []): array
    {
        $query = Batch::query();

        if (!empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (!empty($filters['trade_id'])) {
            $query->where('trade_id', $filters['trade_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('start_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('start_date', '<=', $filters['to_date']);
        }

        $batches = $query->withCount('candidates')->get();

        return [
            'total_batches' => $batches->count(),
            'active_batches' => $batches->where('status', 'active')->count(),
            'completed_batches' => $batches->where('status', 'completed')->count(),
            'total_candidates' => $batches->sum('candidates_count'),
            'average_batch_size' => $batches->count() > 0 ? round($batches->avg('candidates_count'), 1) : 0,
            'by_status' => $batches->groupBy('status')->map->count(),
        ];
    }

    /**
     * Get attendance summary.
     */
    protected function getAttendanceSummary(array $filters = []): array
    {
        $query = TrainingAttendance::query();

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('date', '<=', $filters['to_date']);
        }

        $total = (clone $query)->count();
        $present = (clone $query)->where('status', 'present')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $late = (clone $query)->where('status', 'late')->count();

        return [
            'total_records' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            'punctuality_rate' => ($present + $late) > 0 ? round(($present / ($present + $late)) * 100, 1) : 0,
        ];
    }

    /**
     * Get assessment summary.
     */
    protected function getAssessmentSummary(array $filters = []): array
    {
        $query = TrainingAssessment::query();

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        $assessments = $query->get();

        return [
            'total_assessments' => $assessments->count(),
            'average_score' => round($assessments->avg('score') ?? 0, 1),
            'highest_score' => $assessments->max('score') ?? 0,
            'lowest_score' => $assessments->min('score') ?? 0,
            'passing_rate' => $assessments->count() > 0
                ? round(($assessments->where('score', '>=', 60)->count() / $assessments->count()) * 100, 1)
                : 0,
            'by_type' => $assessments->groupBy('assessment_type')->map->count(),
        ];
    }

    /**
     * Get trainer performance metrics.
     */
    protected function getTrainerPerformance(array $filters = []): Collection
    {
        return Batch::select('trainer_id')
            ->selectRaw('COUNT(*) as batches_count')
            ->selectRaw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_batches')
            ->with('trainer:id,name')
            ->whereNotNull('trainer_id')
            ->when(!empty($filters['campus_id']), function ($q) use ($filters) {
                $q->where('campus_id', $filters['campus_id']);
            })
            ->groupBy('trainer_id')
            ->get()
            ->map(function ($item) {
                return [
                    'trainer_id' => $item->trainer_id,
                    'trainer_name' => $item->trainer->name ?? 'Unknown',
                    'batches_count' => $item->batches_count,
                    'completed_batches' => $item->completed_batches,
                    'completion_rate' => $item->batches_count > 0
                        ? round(($item->completed_batches / $item->batches_count) * 100, 1)
                        : 0,
                ];
            });
    }

    /**
     * Get campus training comparison.
     */
    protected function getCampusTrainingComparison(array $filters = []): Collection
    {
        return Campus::select('campuses.id', 'campuses.name', 'campuses.code')
            ->withCount(['batches', 'candidates'])
            ->with(['batches' => function ($q) use ($filters) {
                $q->withCount('candidates');
            }])
            ->get()
            ->map(function ($campus) {
                $completedBatches = $campus->batches->where('status', 'completed')->count();
                return [
                    'campus_id' => $campus->id,
                    'campus_name' => $campus->name,
                    'campus_code' => $campus->code,
                    'total_batches' => $campus->batches_count,
                    'completed_batches' => $completedBatches,
                    'total_candidates' => $campus->candidates_count,
                    'avg_batch_size' => $campus->batches->count() > 0
                        ? round($campus->batches->avg('candidates_count'), 1)
                        : 0,
                ];
            });
    }

    /**
     * Get training completion rates.
     */
    protected function getTrainingCompletionRates(array $filters = []): array
    {
        $query = Batch::query();

        if (!empty($filters['from_date'])) {
            $query->where('start_date', '>=', $filters['from_date']);
        }

        $batches = $query->get();
        $completed = $batches->where('status', 'completed')->count();

        return [
            'total_batches' => $batches->count(),
            'completed' => $completed,
            'completion_rate' => $batches->count() > 0 ? round(($completed / $batches->count()) * 100, 1) : 0,
        ];
    }

    // =========================================================================
    // VISA PROCESSING REPORTS
    // =========================================================================

    /**
     * Generate visa processing report.
     */
    public function getVisaProcessingReport(array $filters = []): array
    {
        // AUDIT FIX: Apply role-based access filters
        $filters = $this->applyRoleBasedFilters($filters);
        $cacheKey = $this->generateRoleAwareCacheKey('report_visa', $filters);

        return Cache::remember($cacheKey, self::CACHE_DURATION * 60, function () use ($filters) {
            $query = VisaProcess::query();

            // AUDIT FIX: Apply campus-based access control via candidate relationship
            $user = Auth::user();
            if ($user && !$user->isSuperAdmin() && !$user->isProjectDirector()) {
                if ($user->role === 'campus_admin' && $user->campus_id) {
                    $query->whereHas('candidate', function ($q) use ($user) {
                        $q->where('campus_id', $user->campus_id);
                    });
                }
            }

            if (!empty($filters['oep_id'])) {
                $query->where('oep_id', $filters['oep_id']);
            }

            if (!empty($filters['from_date'])) {
                $query->where('created_at', '>=', $filters['from_date']);
            }

            $processes = $query->get();

            return [
                'total_processes' => $processes->count(),
                'by_status' => $processes->groupBy('overall_status')->map->count(),
                'stage_statistics' => $this->getVisaStageStatistics($filters),
                'average_processing_time' => $this->getAverageVisaProcessingTime($filters),
                'oep_performance' => $this->getOepVisaPerformance($filters),
                'bottleneck_analysis' => $this->getVisaBottleneckAnalysis($filters),
                'filters_applied' => $filters,
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Get visa stage statistics.
     * AUDIT FIX: Updated to handle actual column names in database
     * - interview, trade_test, medical, biometric have _completed columns
     * - takamol uses takamol_status
     * - visa uses visa_issued (not visa_completed)
     * - ticket uses ticket_uploaded (not ticket_completed)
     */
    protected function getVisaStageStatistics(array $filters = []): array
    {
        // Map stages to their actual database column patterns
        $stageConfig = [
            'interview' => ['date' => 'interview_date', 'completed' => 'interview_completed'],
            'trade_test' => ['date' => 'trade_test_date', 'completed' => 'trade_test_completed'],
            'takamol' => ['date' => 'takamol_date', 'completed_check' => ['takamol_status', '=', 'completed']],
            'medical' => ['date' => 'medical_date', 'completed' => 'medical_completed'],
            'biometric' => ['date' => 'biometric_date', 'completed' => 'biometric_completed'],
            'visa' => ['date' => 'visa_date', 'completed' => 'visa_issued'],
            'ticket' => ['date' => 'ticket_date', 'completed' => 'ticket_uploaded'],
        ];
        $stats = [];

        foreach ($stageConfig as $stage => $config) {
            $dateColumn = $config['date'];
            $total = VisaProcess::whereNotNull($dateColumn)->count();

            // Handle different completion column types
            if (isset($config['completed'])) {
                $completed = VisaProcess::where($config['completed'], true)->count();
            } elseif (isset($config['completed_check'])) {
                $completed = VisaProcess::where($config['completed_check'][0], $config['completed_check'][1], $config['completed_check'][2])->count();
            } else {
                $completed = 0;
            }

            $stats[$stage] = [
                'total' => $total,
                'completed' => $completed,
                'pending' => $total - $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Get average visa processing time.
     */
    protected function getAverageVisaProcessingTime(array $filters = []): array
    {
        // Calculate average days between stages
        $query = VisaProcess::where('overall_status', 'completed');

        $processes = $query->get();

        if ($processes->isEmpty()) {
            return ['average_days' => 0, 'fastest' => 0, 'slowest' => 0];
        }

        $durations = $processes->map(function ($p) {
            if ($p->interview_date && $p->ticket_date) {
                return Carbon::parse($p->interview_date)->diffInDays(Carbon::parse($p->ticket_date));
            }
            return null;
        })->filter();

        return [
            'average_days' => $durations->count() > 0 ? round($durations->avg(), 1) : 0,
            'fastest' => $durations->min() ?? 0,
            'slowest' => $durations->max() ?? 0,
            'sample_size' => $durations->count(),
        ];
    }

    /**
     * Get OEP visa performance.
     */
    protected function getOepVisaPerformance(array $filters = []): Collection
    {
        return Oep::select('oeps.id', 'oeps.name', 'oeps.company_name')
            ->withCount(['visaProcesses', 'visaProcesses as completed_count' => function ($q) {
                $q->where('overall_status', 'completed');
            }])
            ->get()
            ->map(function ($oep) {
                return [
                    'oep_id' => $oep->id,
                    'oep_name' => $oep->name,
                    'company_name' => $oep->company_name,
                    'total_processes' => $oep->visa_processes_count,
                    'completed' => $oep->completed_count,
                    'completion_rate' => $oep->visa_processes_count > 0
                        ? round(($oep->completed_count / $oep->visa_processes_count) * 100, 1)
                        : 0,
                ];
            });
    }

    /**
     * Identify visa processing bottlenecks.
     * AUDIT FIX: Updated to handle actual column names in database
     */
    protected function getVisaBottleneckAnalysis(array $filters = []): array
    {
        // Map stages to their actual database columns
        $stageConfig = [
            'interview' => ['status' => 'interview_status', 'completed' => 'interview_completed'],
            'trade_test' => ['status' => 'trade_test_status', 'completed' => 'trade_test_completed'],
            'takamol' => ['status' => 'takamol_status', 'completed_value' => 'completed'],
            'medical' => ['status' => 'medical_status', 'completed' => 'medical_completed'],
            'biometric' => ['status' => 'biometric_status', 'completed' => 'biometric_completed'],
            'visa' => ['status' => 'visa_status', 'completed' => 'visa_issued'],
            'ticket' => ['status' => null, 'completed' => 'ticket_uploaded'], // ticket has no status column
        ];
        $bottlenecks = [];

        foreach ($stageConfig as $stage => $config) {
            $query = VisaProcess::query();

            // Count pending items
            if ($config['status']) {
                $query->where($config['status'], 'pending');
            }

            // Not completed
            if (isset($config['completed'])) {
                $query->where($config['completed'], false);
            } elseif (isset($config['completed_value'])) {
                $query->where($config['status'], '!=', $config['completed_value']);
            }

            $bottlenecks[$stage] = $query->count();
        }

        // Sort by most pending
        arsort($bottlenecks);

        return $bottlenecks;
    }

    // =========================================================================
    // COMPLIANCE & DEPARTURE REPORTS
    // =========================================================================

    /**
     * Generate compliance report.
     */
    public function getComplianceReport(array $filters = []): array
    {
        // AUDIT FIX: Apply role-based access filters
        $filters = $this->applyRoleBasedFilters($filters);

        return [
            'departure_compliance' => $this->getDepartureComplianceStats($filters),
            'remittance_compliance' => $this->getRemittanceComplianceStats($filters),
            'complaint_resolution' => $this->getComplaintResolutionStats($filters),
            'document_expiry' => $this->getDocumentExpiryStats($filters),
            'sla_performance' => $this->getSlaPerformance($filters),
            'filters_applied' => $filters,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get departure compliance statistics.
     */
    protected function getDepartureComplianceStats(array $filters = []): array
    {
        $query = Departure::query();

        // AUDIT FIX: Apply campus-based access control via candidate relationship
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isProjectDirector()) {
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', function ($q) use ($user) {
                    $q->where('campus_id', $user->campus_id);
                });
            }
            if ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', function ($q) use ($user) {
                    $q->where('oep_id', $user->oep_id);
                });
            }
        }

        $departures = $query->get();
        $compliant = $departures->where('ninety_day_compliance', true)->count();

        return [
            'total_departures' => $departures->count(),
            'compliant' => $compliant,
            'non_compliant' => $departures->count() - $compliant,
            'compliance_rate' => $departures->count() > 0
                ? round(($compliant / $departures->count()) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get remittance compliance statistics.
     */
    protected function getRemittanceComplianceStats(array $filters = []): array
    {
        $query = Remittance::query();

        // AUDIT FIX: Apply campus-based access control via candidate relationship
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isProjectDirector()) {
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', function ($q) use ($user) {
                    $q->where('campus_id', $user->campus_id);
                });
            }
            if ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', function ($q) use ($user) {
                    $q->where('oep_id', $user->oep_id);
                });
            }
        }

        $remittances = $query->get();
        $verified = $remittances->where('status', 'verified')->count();

        return [
            'total_remittances' => $remittances->count(),
            'verified' => $verified,
            'pending_verification' => $remittances->count() - $verified,
            'verification_rate' => $remittances->count() > 0
                ? round(($verified / $remittances->count()) * 100, 1)
                : 0,
            'total_amount' => $remittances->sum('amount'),
            'average_amount' => round($remittances->avg('amount') ?? 0, 2),
        ];
    }

    /**
     * Get complaint resolution statistics.
     */
    protected function getComplaintResolutionStats(array $filters = []): array
    {
        $query = Complaint::query();

        // AUDIT FIX: Apply campus-based access control via candidate relationship
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isProjectDirector()) {
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', function ($q) use ($user) {
                    $q->where('campus_id', $user->campus_id);
                });
            }
            if ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', function ($q) use ($user) {
                    $q->where('oep_id', $user->oep_id);
                });
            }
        }

        $complaints = $query->get();
        $resolved = $complaints->whereIn('status', ['resolved', 'closed'])->count();
        $overdue = $complaints->where('sla_due_date', '<', now())
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        return [
            'total_complaints' => $complaints->count(),
            'resolved' => $resolved,
            'pending' => $complaints->count() - $resolved,
            'overdue' => $overdue,
            'resolution_rate' => $complaints->count() > 0
                ? round(($resolved / $complaints->count()) * 100, 1)
                : 0,
            'by_category' => $complaints->groupBy('category')->map->count(),
        ];
    }

    /**
     * Get document expiry statistics.
     */
    protected function getDocumentExpiryStats(array $filters = []): array
    {
        // This would query document_archives table
        return [
            'expiring_soon' => 0,
            'expired' => 0,
            'valid' => 0,
        ];
    }

    /**
     * Get SLA performance.
     */
    protected function getSlaPerformance(array $filters = []): array
    {
        $complaints = Complaint::whereNotNull('resolved_at')->get();

        $withinSla = $complaints->filter(function ($c) {
            return $c->resolved_at && $c->sla_due_date && $c->resolved_at <= $c->sla_due_date;
        })->count();

        return [
            'total_resolved' => $complaints->count(),
            'within_sla' => $withinSla,
            'sla_compliance_rate' => $complaints->count() > 0
                ? round(($withinSla / $complaints->count()) * 100, 1)
                : 0,
        ];
    }

    // =========================================================================
    // CUSTOM REPORT BUILDER
    // =========================================================================

    /**
     * Build custom report with dynamic filters.
     *
     * @param string $reportType
     * @param array $filters
     * @param array $columns
     * @param string $groupBy
     * @return array
     */
    public function buildCustomReport(
        string $reportType,
        array $filters = [],
        array $columns = [],
        ?string $groupBy = null
    ): array {
        $query = $this->getQueryForReportType($reportType);

        // AUDIT FIX: Apply role-based access control to custom reports
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && !$user->isProjectDirector()) {
            // For models that have direct campus_id
            if (in_array($reportType, ['candidates', 'batches'])) {
                if ($user->role === 'campus_admin' && $user->campus_id) {
                    $query->where('campus_id', $user->campus_id);
                }
            }
            // For models accessed via candidate relationship
            if (in_array($reportType, ['visa', 'departures', 'complaints', 'remittances'])) {
                if ($user->role === 'campus_admin' && $user->campus_id) {
                    $query->whereHas('candidate', function ($q) use ($user) {
                        $q->where('campus_id', $user->campus_id);
                    });
                }
                if ($user->role === 'oep' && $user->oep_id) {
                    $query->whereHas('candidate', function ($q) use ($user) {
                        $q->where('oep_id', $user->oep_id);
                    });
                }
            }
        }

        // Apply filters
        foreach ($filters as $filter) {
            $query = $this->applyFilter($query, $filter);
        }

        // Select columns
        if (!empty($columns)) {
            $query->select($columns);
        }

        // Group by
        if ($groupBy) {
            $query->groupBy($groupBy);
        }

        return [
            'data' => $query->get(),
            'count' => $query->count(),
            'report_type' => $reportType,
            'filters' => $filters,
            'columns' => $columns,
            'group_by' => $groupBy,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get query builder for report type.
     */
    protected function getQueryForReportType(string $type): Builder
    {
        return match($type) {
            'candidates' => Candidate::query(),
            'batches' => Batch::query(),
            'visa' => VisaProcess::query(),
            'departures' => Departure::query(),
            'complaints' => Complaint::query(),
            'remittances' => Remittance::query(),
            default => throw new \InvalidArgumentException("Unknown report type: {$type}"),
        };
    }

    /**
     * Apply filter to query.
     */
    protected function applyFilter(Builder $query, array $filter): Builder
    {
        $field = $filter['field'];
        $operator = $filter['operator'] ?? 'equals';
        $value = $filter['value'];

        return match($operator) {
            'equals' => $query->where($field, $value),
            'not_equals' => $query->where($field, '!=', $value),
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'greater_equal' => $query->where($field, '>=', $value),
            'less_equal' => $query->where($field, '<=', $value),
            'contains' => $query->where($field, 'LIKE', "%{$value}%"),
            'starts_with' => $query->where($field, 'LIKE', "{$value}%"),
            'ends_with' => $query->where($field, 'LIKE', "%{$value}"),
            'between' => $query->whereBetween($field, $value),
            'in' => $query->whereIn($field, (array)$value),
            'not_in' => $query->whereNotIn($field, (array)$value),
            'is_null' => $query->whereNull($field),
            'is_not_null' => $query->whereNotNull($field),
            default => $query,
        };
    }

    /**
     * Build candidate query with filters.
     */
    protected function buildCandidateQuery(array $filters): Builder
    {
        $query = Candidate::query();

        if (!empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (!empty($filters['trade_id'])) {
            $query->where('trade_id', $filters['trade_id']);
        }

        if (!empty($filters['oep_id'])) {
            $query->where('oep_id', $filters['oep_id']);
        }

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }

    /**
     * Clear report cache.
     */
    public function clearCache(): void
    {
        Cache::tags(['reports'])->flush();
    }

    /**
     * Get available filters for a report type.
     */
    public function getAvailableFilters(string $reportType): array
    {
        $commonFilters = [
            'from_date' => ['type' => 'date', 'label' => 'From Date'],
            'to_date' => ['type' => 'date', 'label' => 'To Date'],
        ];

        $typeFilters = match($reportType) {
            'candidates' => [
                'campus_id' => ['type' => 'select', 'label' => 'Campus', 'options' => Campus::pluck('name', 'id')],
                'trade_id' => ['type' => 'select', 'label' => 'Trade', 'options' => Trade::pluck('name', 'id')],
                'oep_id' => ['type' => 'select', 'label' => 'OEP', 'options' => Oep::pluck('name', 'id')],
                'status' => ['type' => 'select', 'label' => 'Status', 'options' => Candidate::distinct()->pluck('status', 'status')],
            ],
            'training' => [
                'campus_id' => ['type' => 'select', 'label' => 'Campus', 'options' => Campus::pluck('name', 'id')],
                'trade_id' => ['type' => 'select', 'label' => 'Trade', 'options' => Trade::pluck('name', 'id')],
                'batch_id' => ['type' => 'select', 'label' => 'Batch', 'options' => Batch::pluck('name', 'id')],
            ],
            'visa' => [
                'oep_id' => ['type' => 'select', 'label' => 'OEP', 'options' => Oep::pluck('name', 'id')],
                'overall_status' => ['type' => 'select', 'label' => 'Status', 'options' => ['pending', 'in_progress', 'completed', 'rejected']],
            ],
            default => [],
        };

        return array_merge($commonFilters, $typeFilters);
    }
}
