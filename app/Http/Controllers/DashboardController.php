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
        
        $stats = $this->getStatistics($campusFilter);
        $recentActivities = $this->getRecentActivities($campusFilter);
        $alerts = $this->getAlerts($campusFilter);
        
        return view('dashboard.index', compact('stats', 'recentActivities', 'alerts'));
    }

    private function getStatistics($campusId = null)
    {
        // PERFORMANCE: Cache dashboard statistics for 5 minutes
        // Cache key includes campus_id for role-based isolation
        $cacheKey = 'dashboard_stats_' . ($campusId ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($campusId) {
            // Use single query with CASE statements instead of 8 separate queries
            $candidateStats = DB::table('candidates')
            ->selectRaw('
                COUNT(*) as total_candidates,
                SUM(CASE WHEN status = "listed" THEN 1 ELSE 0 END) as listed,
                SUM(CASE WHEN status = "screening" THEN 1 ELSE 0 END) as screening,
                SUM(CASE WHEN status = "registered" THEN 1 ELSE 0 END) as registered,
                SUM(CASE WHEN status = "training" THEN 1 ELSE 0 END) as in_training,
                SUM(CASE WHEN status = "visa_processing" THEN 1 ELSE 0 END) as visa_processing,
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
        $overdueComplaints = Complaint::whereIn('status', ['registered', 'under_review', 'assigned', 'in_progress'])
            ->when($campusId, fn($q) => $q->where('campus_id', $campusId))
            ->whereRaw('DATE_ADD(registered_at, INTERVAL CAST(sla_days AS SIGNED) DAY) < NOW()')
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
        
        $candidates = Candidate::with(['batch', 'campus', 'trade'])
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->when($request->search, fn($q) => 
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('btevta_id', 'like', '%'.$request->search.'%')
                  ->orWhere('cnic', 'like', '%'.$request->search.'%')
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
            ->distinct('candidate_id')
            ->count();
        
        $pendingCall3 = CandidateScreening::where('screening_stage', 2)
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('campus_id', $campusFilter)))
            ->distinct('candidate_id')
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
        
        $correspondences = Correspondence::with(['createdBy', 'campus'])
            ->when($campusFilter, fn($q) => $q->where('campus_id', $campusFilter))
            ->when($request->search, fn($q) => 
                $q->where('reference_number', 'like', '%'.$request->search.'%')
                  ->orWhere('subject', 'like', '%'.$request->search.'%')
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
            'overdue' => Complaint::whereIn('status', ['registered', 'under_review', 'assigned', 'in_progress'])
                ->whereRaw('DATE_ADD(registered_at, INTERVAL CAST(sla_days AS SIGNED) DAY) < NOW()')
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
        
        $documents = DocumentArchive::with(['candidate', 'candidate.campus', 'uploadedBy'])
            ->when($campusFilter, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('campus_id', $campusFilter)))
            ->when($request->search, fn($q) => 
                $q->where('document_name', 'like', '%'.$request->search.'%')
                  ->orWhere('document_type', 'like', '%'.$request->search.'%')
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