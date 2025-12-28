<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\Complaint;
use App\Models\Correspondence;
use App\Models\CandidateScreening;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\DocumentArchive;
use App\Models\TrainingAttendance;
use App\Models\Remittance;
use App\Models\RemittanceAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Role-based data filtering
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        $oepFilter = $user->role === 'oep' ? $user->oep_id : null;

        $stats = $this->getStatistics($campusFilter, $oepFilter);
        $recentActivities = $this->getRecentActivities($campusFilter);
        $alerts = $this->getAlerts($campusFilter);

        // Select role-specific dashboard view
        $view = $this->getDashboardViewForRole($user->role);

        // Add role-specific data
        $roleData = $this->getRoleSpecificData($user, $campusFilter, $oepFilter);

        return view($view, compact('stats', 'recentActivities', 'alerts', 'roleData'));
    }

    /**
     * Get the appropriate dashboard view for the user's role
     */
    private function getDashboardViewForRole(string $role): string
    {
        return match ($role) {
            'super_admin', 'admin', 'project_director' => 'dashboard.admin',
            'campus_admin' => 'dashboard.campus-admin',
            'oep' => 'dashboard.oep',
            'visa_partner' => 'dashboard.visa-partner',
            'trainer', 'instructor' => 'dashboard.instructor',
            default => 'dashboard.index',
        };
    }

    /**
     * Get role-specific data for the dashboard
     */
    private function getRoleSpecificData($user, $campusFilter, $oepFilter): array
    {
        return match ($user->role) {
            'super_admin', 'admin', 'project_director' => $this->getAdminDashboardData(),
            'campus_admin' => $this->getCampusAdminDashboardData($campusFilter),
            'oep' => $this->getOepDashboardData($oepFilter),
            'visa_partner' => $this->getVisaPartnerDashboardData($user->visa_partner_id),
            'trainer', 'instructor' => $this->getInstructorDashboardData($user->id),
            default => [],
        };
    }

    private function getAdminDashboardData(): array
    {
        return Cache::remember('admin_dashboard_data', 300, function () {
            return [
                'campuses' => Campus::withCount('candidates')->get(),
                'top_performers' => Campus::withCount(['candidates as departed_count' => function ($q) {
                    $q->where('status', 'departed');
                }])->orderByDesc('departed_count')->limit(5)->get(),
                'monthly_trends' => $this->getMonthlyTrends(),
            ];
        });
    }

    private function getCampusAdminDashboardData($campusId): array
    {
        if (!$campusId) return [];

        $cacheKey = "campus_admin_dashboard_{$campusId}";
        return Cache::remember($cacheKey, 300, function () use ($campusId) {
            $campus = Campus::find($campusId);
            return [
                'campus' => $campus,
                'active_batches' => Batch::where('campus_id', $campusId)
                    ->where('status', 'active')
                    ->with('instructor')
                    ->get(),
                'pending_registrations' => Candidate::where('campus_id', $campusId)
                    ->where('status', 'screening')
                    ->count(),
                'attendance_today' => TrainingAttendance::whereHas('candidate', fn($q) => $q->where('campus_id', $campusId))
                    ->whereDate('date', today())
                    ->get()
                    ->groupBy('status'),
            ];
        });
    }

    private function getOepDashboardData($oepId): array
    {
        if (!$oepId) return [];

        $cacheKey = "oep_dashboard_{$oepId}";
        return Cache::remember($cacheKey, 300, function () use ($oepId) {
            return [
                'candidates_assigned' => Candidate::where('oep_id', $oepId)->count(),
                'visa_in_progress' => VisaProcess::whereHas('candidate', fn($q) => $q->where('oep_id', $oepId))
                    ->where('visa_issued', false)
                    ->count(),
                'recent_departures' => Departure::whereHas('candidate', fn($q) => $q->where('oep_id', $oepId))
                    ->latest('departure_date')
                    ->limit(10)
                    ->with('candidate')
                    ->get(),
                'pending_compliance' => Departure::whereHas('candidate', fn($q) => $q->where('oep_id', $oepId))
                    ->where('ninety_day_report_submitted', false)
                    ->whereNotNull('departure_date')
                    ->whereDate('departure_date', '<=', now()->subDays(75))
                    ->count(),
            ];
        });
    }

    private function getVisaPartnerDashboardData($visaPartnerId): array
    {
        if (!$visaPartnerId) return [];

        $cacheKey = "visa_partner_dashboard_{$visaPartnerId}";
        return Cache::remember($cacheKey, 300, function () use ($visaPartnerId) {
            return [
                'pending_interview' => VisaProcess::where('visa_partner_id', $visaPartnerId)
                    ->where('interview_completed', false)
                    ->count(),
                'pending_trade_test' => VisaProcess::where('visa_partner_id', $visaPartnerId)
                    ->where('interview_completed', true)
                    ->where('trade_test_completed', false)
                    ->count(),
                'pending_medical' => VisaProcess::where('visa_partner_id', $visaPartnerId)
                    ->where('trade_test_completed', true)
                    ->where('medical_completed', false)
                    ->count(),
                'pending_biometric' => VisaProcess::where('visa_partner_id', $visaPartnerId)
                    ->where('medical_completed', true)
                    ->where('biometric_completed', false)
                    ->count(),
                'pending_visa_issue' => VisaProcess::where('visa_partner_id', $visaPartnerId)
                    ->where('biometric_completed', true)
                    ->where('visa_issued', false)
                    ->count(),
                'recent_visas' => VisaProcess::where('visa_partner_id', $visaPartnerId)
                    ->where('visa_issued', true)
                    ->latest('visa_issue_date')
                    ->limit(10)
                    ->with('candidate')
                    ->get(),
            ];
        });
    }

    private function getInstructorDashboardData($userId): array
    {
        $cacheKey = "instructor_dashboard_{$userId}";
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $instructor = \App\Models\Instructor::where('user_id', $userId)->first();
            if (!$instructor) return [];

            return [
                'instructor' => $instructor,
                'current_batches' => Batch::where('instructor_id', $instructor->id)
                    ->where('status', 'active')
                    ->withCount('candidates')
                    ->get(),
                'total_students' => Candidate::whereHas('batch', fn($q) => $q->where('instructor_id', $instructor->id))
                    ->where('status', 'training')
                    ->count(),
                'todays_schedule' => \App\Models\TrainingSchedule::where('instructor_id', $instructor->id)
                    ->whereDate('date', today())
                    ->with('batch')
                    ->get(),
                'pending_assessments' => Batch::where('instructor_id', $instructor->id)
                    ->where('status', 'active')
                    ->whereDoesntHave('assessments', fn($q) => $q->whereMonth('created_at', now()->month))
                    ->count(),
            ];
        });
    }

    private function getMonthlyTrends(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'month' => $date->format('M Y'),
                'registered' => Candidate::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'departed' => Departure::whereYear('departure_date', $date->year)
                    ->whereMonth('departure_date', $date->month)
                    ->count(),
            ]);
        }
        return $months->toArray();
    }

    /**
     * Compliance Monitoring Dashboard
     */
    public function complianceMonitoring(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;

        // Document Compliance
        $documentCompliance = $this->getDocumentComplianceStats($campusFilter);

        // Training Compliance
        $trainingCompliance = $this->getTrainingComplianceStats($campusFilter);

        // Departure Compliance (90-day reporting)
        $departureCompliance = $this->getDepartureComplianceStats($campusFilter);

        // Complaint SLA Compliance
        $complaintCompliance = $this->getComplaintComplianceStats($campusFilter);

        // Overall compliance score
        $overallScore = $this->calculateOverallComplianceScore([
            $documentCompliance['rate'],
            $trainingCompliance['attendance_rate'],
            $departureCompliance['rate'],
            $complaintCompliance['sla_rate'],
        ]);

        return view('dashboard.compliance-monitoring', compact(
            'documentCompliance',
            'trainingCompliance',
            'departureCompliance',
            'complaintCompliance',
            'overallScore'
        ));
    }

    private function getDocumentComplianceStats($campusId): array
    {
        $totalCandidates = Candidate::whereIn('status', ['registered', 'training', 'visa_processing', 'departed'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->count();

        $withCompleteDocs = Candidate::whereIn('status', ['registered', 'training', 'visa_processing', 'departed'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->whereHas('documents', function ($q) {
                $q->havingRaw('COUNT(*) >= 4'); // Minimum 4 required documents
            })
            ->count();

        $expiredDocs = DocumentArchive::where('expiry_date', '<', now())
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->count();

        $expiringDocs = DocumentArchive::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->count();

        return [
            'total' => $totalCandidates,
            'complete' => $withCompleteDocs,
            'rate' => $totalCandidates > 0 ? round(($withCompleteDocs / $totalCandidates) * 100, 1) : 0,
            'expired' => $expiredDocs,
            'expiring_soon' => $expiringDocs,
        ];
    }

    private function getTrainingComplianceStats($campusId): array
    {
        $totalAttendance = TrainingAttendance::when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->whereMonth('date', now()->month)
            ->count();

        $presentCount = TrainingAttendance::when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->whereMonth('date', now()->month)
            ->where('status', 'present')
            ->count();

        $activeBatches = Batch::where('status', 'active')
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->count();

        $batchesWithSchedule = Batch::where('status', 'active')
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->whereHas('schedules')
            ->count();

        return [
            'attendance_rate' => $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 1) : 0,
            'total_sessions' => $totalAttendance,
            'present_count' => $presentCount,
            'active_batches' => $activeBatches,
            'batches_with_schedule' => $batchesWithSchedule,
            'schedule_rate' => $activeBatches > 0 ? round(($batchesWithSchedule / $activeBatches) * 100, 1) : 0,
        ];
    }

    private function getDepartureComplianceStats($campusId): array
    {
        $totalDeparted = Departure::whereNotNull('departure_date')
            ->whereDate('departure_date', '<=', now()->subDays(90))
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->count();

        $compliant = Departure::whereNotNull('departure_date')
            ->whereDate('departure_date', '<=', now()->subDays(90))
            ->where('ninety_day_report_submitted', true)
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->count();

        $overdue = $totalDeparted - $compliant;

        $dueSoon = Departure::whereNotNull('departure_date')
            ->whereDate('departure_date', '<=', now()->subDays(75))
            ->whereDate('departure_date', '>', now()->subDays(90))
            ->where('ninety_day_report_submitted', false)
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($sq) => $sq->where('campus_id', $campusId)))
            ->count();

        return [
            'total' => $totalDeparted,
            'compliant' => $compliant,
            'rate' => $totalDeparted > 0 ? round(($compliant / $totalDeparted) * 100, 1) : 100,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
        ];
    }

    private function getComplaintComplianceStats($campusId): array
    {
        $totalResolved = Complaint::whereIn('status', ['resolved', 'closed'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->count();

        $withinSla = Complaint::whereIn('status', ['resolved', 'closed'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->whereColumn('resolved_at', '<=', DB::raw('DATE_ADD(created_at, INTERVAL sla_days DAY)'))
            ->count();

        $currentOverdue = Complaint::overdue()
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->count();

        $avgResolutionTime = Complaint::whereIn('status', ['resolved', 'closed'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(DATEDIFF(resolved_at, created_at)) as avg_days')
            ->value('avg_days');

        return [
            'total_resolved' => $totalResolved,
            'within_sla' => $withinSla,
            'sla_rate' => $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 100,
            'current_overdue' => $currentOverdue,
            'avg_resolution_days' => round($avgResolutionTime ?? 0, 1),
        ];
    }

    private function calculateOverallComplianceScore(array $rates): array
    {
        $validRates = array_filter($rates, fn($r) => $r > 0);
        $score = count($validRates) > 0 ? round(array_sum($validRates) / count($validRates), 1) : 0;

        return [
            'score' => $score,
            'grade' => match (true) {
                $score >= 90 => 'A',
                $score >= 80 => 'B',
                $score >= 70 => 'C',
                $score >= 60 => 'D',
                default => 'F',
            },
            'status' => match (true) {
                $score >= 80 => 'Excellent',
                $score >= 60 => 'Good',
                $score >= 40 => 'Needs Improvement',
                default => 'Critical',
            },
        ];
    }

    private function getStatistics($campusId = null, $oepId = null)
    {
        // PERFORMANCE: Cache dashboard statistics for 5 minutes
        // Cache key includes campus_id and oep_id for role-based isolation
        $cacheKey = 'dashboard_stats_' . ($campusId ?? 'all') . '_' . ($oepId ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($campusId) {
            // Use single query with CASE statements instead of 8 separate queries
            // PHASE 8 FIX: Fixed status constants to match Candidate model
            // STATUS_NEW = 'new' (was 'listed'), STATUS_VISA_PROCESS = 'visa_process' (was 'visa_processing')
            $candidateStats = DB::table('candidates')
            ->selectRaw('
                COUNT(*) as total_candidates,
                SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as listed,
                SUM(CASE WHEN status = "screening" THEN 1 ELSE 0 END) as screening,
                SUM(CASE WHEN status = "registered" THEN 1 ELSE 0 END) as registered,
                SUM(CASE WHEN status = "training" THEN 1 ELSE 0 END) as in_training,
                SUM(CASE WHEN status = "visa_process" THEN 1 ELSE 0 END) as visa_processing,
                SUM(CASE WHEN status = "departed" THEN 1 ELSE 0 END) as departed,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected
            ')
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->whereNull('deleted_at')
            ->first();

        // Remittance statistics
        $remittanceQuery = Remittance::query();
        if ($campusId) {
            $remittanceQuery->whereHas('candidate', fn($q) => $q->where('campus_id', $campusId));
        }

        $remittanceStats = $remittanceQuery
            ->selectRaw('
                COUNT(*) as total_remittances,
                SUM(amount) as total_amount,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_verification,
                SUM(CASE WHEN has_proof = 0 THEN 1 ELSE 0 END) as missing_proof
            ')
            ->first();

        $currentMonthRemittances = Remittance::query()
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($q2) => $q2->where('campus_id', $campusId)))
            ->where('year', date('Y'))
            ->where('month', date('n'))
            ->selectRaw('COUNT(*) as count, SUM(amount) as amount')
            ->first();

        return [
            'total_candidates' => $candidateStats->total_candidates ?? 0,
            'listed' => $candidateStats->listed ?? 0,
            'screening' => $candidateStats->screening ?? 0,
            'registered' => $candidateStats->registered ?? 0,
            'in_training' => $candidateStats->in_training ?? 0,
            'visa_processing' => $candidateStats->visa_processing ?? 0,
            'departed' => $candidateStats->departed ?? 0,
            'rejected' => $candidateStats->rejected ?? 0,
            'active_batches' => Batch::where('status', 'active')
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->count(),
            'pending_complaints' => Complaint::whereIn('status', ['registered', 'under_review', 'assigned', 'in_progress'])
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->count(),
            'pending_correspondence' => Correspondence::where('requires_reply', true)
                ->where('replied', false)
                ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
                ->count(),

            // Remittance stats
            'remittances_total' => $remittanceStats->total_remittances ?? 0,
            'remittances_amount' => $remittanceStats->total_amount ?? 0,
            'remittances_this_month_count' => $currentMonthRemittances->count ?? 0,
            'remittances_this_month_amount' => $currentMonthRemittances->amount ?? 0,
            'remittances_pending' => $remittanceStats->pending_verification ?? 0,
            'remittances_missing_proof' => $remittanceStats->missing_proof ?? 0,
        ];
        });
    }

    private function getRecentActivities($campusId = null)
    {
        return DB::table('audit_logs')
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->when($campusId, fn($q) => $q->where('users.campus_id', $campusId))
            ->select('audit_logs.*', 'users.name as user_name')
            ->orderBy('audit_logs.created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getAlerts($campusId = null)
    {
        // PERFORMANCE: Cache alerts for 1 minute (more dynamic than stats)
        $cacheKey = 'dashboard_alerts_' . ($campusId ?? 'all');

        return Cache::remember($cacheKey, 60, function () use ($campusId) {
            $alerts = [];

        // Document expiry alerts
        $expiringDocs = DB::table('registration_documents')
            ->join('candidates', 'registration_documents.candidate_id', '=', 'candidates.id')
            ->when($campusId, fn($q) => $q->where('candidates.campus_id', $campusId))
            ->where('registration_documents.expiry_date', '<=', now()->addDays(30))
            ->where('registration_documents.expiry_date', '>=', now())
            ->count();

        if ($expiringDocs > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$expiringDocs} documents expiring within 30 days",
                'action_url' => route('document-archive.expiring'),
            ];
        }

        // Pending screenings
        $pendingScreenings = Candidate::where('status', 'screening')
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->withCount('screenings')
            ->having('screenings_count', '<', 3)
            ->count();

        if ($pendingScreenings > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$pendingScreenings} candidates pending screening completion",
                'action_url' => route('screening.pending'),
            ];
        }

        // Overdue complaints
        $overdueComplaints = Complaint::overdue()
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->count();

        if ($overdueComplaints > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$overdueComplaints} complaints overdue SLA",
                'action_url' => route('complaints.overdue'),
            ];
        }

        // Remittance alerts
        $criticalAlerts = RemittanceAlert::where('is_resolved', false)
            ->where('severity', 'critical')
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($q2) => $q2->where('campus_id', $campusId)))
            ->count();

        if ($criticalAlerts > 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "{$criticalAlerts} critical remittance alerts require attention",
                'action_url' => route('remittance.alerts.index', ['severity' => 'critical']),
            ];
        }

        $pendingVerification = Remittance::where('status', 'pending')
            ->when($campusId, fn($q) => $q->whereHas('candidate', fn($q2) => $q2->where('campus_id', $campusId)))
            ->count();

        if ($pendingVerification > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$pendingVerification} remittances pending verification",
                'action_url' => route('remittances.index', ['status' => 'pending']),
            ];
        }

        return $alerts;
        });
    }

    // ============================================
    // TAB 1: CANDIDATES LISTING
    // ============================================
    public function candidatesListing(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedSearch = $request->search ? str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search) : null;

        $candidates = Candidate::with(['batch', 'campus', 'trade'])
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->when($escapedSearch, fn($q) =>
                $q->where('name', 'like', '%'.$escapedSearch.'%')
                  ->orWhere('btevta_id', 'like', '%'.$escapedSearch.'%')
                  ->orWhere('cnic', 'like', '%'.$escapedSearch.'%')
            )
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->trade_id, fn($q) => $q->where('trade_id', $request->trade_id))
            ->when($request->batch_id, fn($q) => $q->where('batch_id', $request->batch_id))
            ->latest()
            ->paginate(20);
        
        $trades = \App\Models\Trade::pluck('name', 'id');
        $batches = Batch::pluck('batch_code', 'id');
        
        return view('dashboard.tabs.candidates-listing', compact('candidates', 'trades', 'batches'));
    }

    // ============================================
    // TAB 2: SCREENING
    // ============================================
    public function screening(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $pendingCall1 = Candidate::where('status', 'listed')
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->count();
        
        $pendingCall2 = CandidateScreening::whereIn('screening_stage', [1, 2])
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) =>
                $sq->where('campus_id', $campusFilter)))
            ->select('candidate_id')
            ->distinct()
            ->count();

        $pendingCall3 = CandidateScreening::where('screening_stage', 2)
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) =>
                $sq->where('campus_id', $campusFilter)))
            ->select('candidate_id')
            ->distinct()
            ->count();
        
        $screeningQueue = Candidate::where('status', 'screening')
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->with(['screenings', 'campus'])
            ->withCount('screenings')
            ->latest()
            ->paginate(15);
        
        return view('dashboard.tabs.screening', compact(
            'pendingCall1',
            'pendingCall2',
            'pendingCall3',
            'screeningQueue'
        ));
    }

    // ============================================
    // TAB 3: REGISTRATION
    // ============================================
    public function registration(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $pendingRegistrations = Candidate::where('status', 'screening')
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->with(['documents', 'nextOfKin', 'undertakings', 'campus'])
            ->withCount('documents', 'undertakings')
            ->latest()
            ->paginate(15);
        
        $stats = [
            'total_pending' => Candidate::where('status', 'screening')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
                ->count(),
            'complete_docs' => Candidate::where('status', 'screening')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
                ->withCount('documents')
                ->having('documents_count', '>', 0)
                ->count(),
            'incomplete_docs' => Candidate::where('status', 'screening')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
                ->withCount('documents')
                ->having('documents_count', '=', 0)
                ->count(),
        ];
        
        return view('dashboard.tabs.registration', compact('pendingRegistrations', 'stats'));
    }

    // ============================================
    // TAB 4: TRAINING
    // ============================================
    public function training(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $activeBatches = Batch::where('status', 'active')
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->with('candidates', 'campus')
            ->withCount('candidates')
            ->latest()
            ->paginate(15);
        
        $stats = [
            'active_batches' => Batch::where('status', 'active')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
                ->count(),
            'in_progress' => Candidate::where('status', 'training')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
                ->count(),
            'completed' => Candidate::where('status', 'visa_processing')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
                ->count(),
            'completed_count' => TrainingAttendance::where('status', 'completed')
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
        ];
        
        return view('dashboard.tabs.training', compact('activeBatches', 'stats'));
    }

    // ============================================
    // TAB 5: VISA PROCESSING
    // ============================================
    public function visaProcessing(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $visaProcessing = VisaProcess::with(['candidate', 'oep', 'candidate.campus'])
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('campus_id', $campusFilter)))
            ->latest()
            ->paginate(15);
        
        $visaStats = [
            'interview' => VisaProcess::where('interview_completed', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'trade_test' => VisaProcess::where('trade_test_completed', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'medical' => VisaProcess::where('medical_completed', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'biometric' => VisaProcess::where('biometric_completed', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'visa_issued' => VisaProcess::where('visa_issued', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
        ];
        
        return view('dashboard.tabs.visa-processing', compact('visaProcessing', 'visaStats'));
    }

    // ============================================
    // TAB 6: DEPARTURE
    // ============================================
    public function departure(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $departures = Departure::with(['candidate', 'candidate.campus', 'oep'])
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('campus_id', $campusFilter)))
            ->latest()
            ->paginate(15);
        
        $stats = [
            'total_departed' => Departure::when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('campus_id', $campusFilter)))->count(),
            'briefing_completed' => Departure::where('briefing_completed', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'ready_to_depart' => Departure::where('ready_for_departure', true)
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'post_arrival_90' => Departure::whereDate('departure_date', '<=', now()->subDays(90))
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
        ];
        
        return view('dashboard.tabs.departure', compact('departures', 'stats'));
    }

    // ============================================
    // TAB 7: CORRESPONDENCE
    // ============================================
    public function correspondence(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedCorrespondenceSearch = $request->search ? str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search) : null;

        $correspondences = Correspondence::with(['createdBy', 'campus'])
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->when($escapedCorrespondenceSearch, fn($q) =>
                $q->where('reference_number', 'like', '%'.$escapedCorrespondenceSearch.'%')
                  ->orWhere('subject', 'like', '%'.$escapedCorrespondenceSearch.'%')
            )
            ->when($request->type, fn($q) => $q->where('correspondence_type', $request->type))
            ->latest()
            ->paginate(15);
        
        $correspondenceStats = [
            'total' => Correspondence::when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'incoming' => Correspondence::where('correspondence_type', 'incoming')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'outgoing' => Correspondence::where('correspondence_type', 'outgoing')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'pending_reply' => Correspondence::where('requires_reply', true)
                ->where('replied', false)
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
        ];
        
        return view('dashboard.tabs.correspondence', compact('correspondences', 'correspondenceStats'));
    }

    // ============================================
    // TAB 8: COMPLAINTS
    // ============================================
    public function complaints(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $complaintsList = Complaint::with(['candidate', 'assignedTo', 'campus'])
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->latest()
            ->paginate(15);
        
        $complaintStats = [
            'total' => Complaint::when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'pending' => Complaint::whereIn('status', ['registered', 'under_review', 'assigned', 'in_progress'])
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'resolved' => Complaint::where('status', 'resolved')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'overdue' => Complaint::overdue()
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
        ];
        
        return view('dashboard.tabs.complaints', compact('complaintsList', 'complaintStats'));
    }

    // ============================================
    // TAB 9: DOCUMENT ARCHIVE
    // ============================================
    public function documentArchive(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        // Escape special LIKE characters to prevent SQL LIKE injection
        $escapedDocSearch = $request->search ? str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search) : null;

        $documents = DocumentArchive::with(['candidate', 'candidate.campus', 'uploadedBy'])
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) =>
                $sq->where('campus_id', $campusFilter)))
            ->when($escapedDocSearch, fn($q) =>
                $q->where('document_name', 'like', '%'.$escapedDocSearch.'%')
                  ->orWhere('document_type', 'like', '%'.$escapedDocSearch.'%')
            )
            ->when($request->document_type, fn($q) => $q->where('document_type', $request->document_type))
            ->latest()
            ->paginate(15);
        
        $docStats = [
            'total_documents' => DocumentArchive::when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('campus_id', $campusFilter)))->count(),
            'expiring_soon' => DocumentArchive::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>=', now())
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
            'expired' => DocumentArchive::where('expiry_date', '<', now())
                ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                    $sq->where('campus_id', $campusFilter)))
                ->count(),
        ];
        
        return view('dashboard.tabs.document-archive', compact('documents', 'docStats'));
    }

    // ============================================
    // TAB 10: REPORTS
    // ============================================
    public function reports(Request $request)
    {
        $user = auth()->user();
        $campusFilter = $user->role === 'campus_admin' ? $user->campus_id : null;
        
        $reportStats = [
            'total_candidates' => Candidate::when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'completed_process' => Candidate::where('status', 'departed')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'in_process' => Candidate::whereIn('status', ['screening', 'registered', 'training', 'visa_processing'])
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
            'rejected' => Candidate::where('status', 'rejected')
                ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))->count(),
        ];
        
        return view('dashboard.tabs.reports', compact('reportStats'));
    }
}