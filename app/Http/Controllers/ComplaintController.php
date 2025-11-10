<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\User;
use App\Services\ComplaintService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Exception;

class ComplaintController extends Controller
{
    protected $complaintService;
    protected $notificationService;

    public function __construct(
        ComplaintService $complaintService,
        NotificationService $notificationService
    ) {
        $this->complaintService = $complaintService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of complaints
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Complaint::class);

        $query = Complaint::with(['candidate', 'campus', 'oep', 'assignedTo'])->latest();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('complaint_number', 'like', "%{$search}%")
                    ->orWhere('complainant_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $complaints = $query->paginate(20);

        // PERFORMANCE: Only select needed fields and filter by campus if needed
        $users = User::where('role', 'admin')
            ->when(auth()->user()->role === 'campus_admin', fn($q) =>
                $q->where('campus_id', auth()->user()->campus_id))
            ->select('id', 'name', 'email')
            ->get();

        return view('complaints.index', compact('complaints', 'users'));
    }

    /**
     * Show form to create new complaint
     */
    public function create()
    {
        $this->authorize('create', Complaint::class);

        $candidates = Candidate::select('id', 'name', 'cnic', 'passport_number')->get();
        $campuses = Campus::where('is_active', true)->get();
        $oeps = Oep::where('is_active', true)->get();

        return view('complaints.create', compact('candidates', 'campuses', 'oeps'));
    }

    /**
     * Store new complaint
     */
    public function store(Request $request)
    {
        $this->authorize('create', Complaint::class);

        $validated = $request->validate([
            'candidate_id' => 'nullable|exists:candidates,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'complainant_name' => 'required|string|max:255',
            'complainant_contact' => 'required|string|max:20',
            'complainant_email' => 'nullable|email|max:255',
            'category' => 'required|in:screening,training,visa,salary,conduct,facility,medical,document,other',
            'subject' => 'required|string|max:500',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $evidencePath = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')
                    ->store('complaints/evidence', 'public');
            }

            $complaint = $this->complaintService->registerComplaint(
                $validated['complainant_name'],
                $validated['complainant_contact'],
                $validated['category'],
                $validated['subject'],
                $validated['description'],
                $validated['priority'],
                $validated['candidate_id'] ?? null,
                $validated['campus_id'] ?? null,
                $validated['oep_id'] ?? null,
                $validated['complainant_email'] ?? null,
                $evidencePath
            );

            $this->notificationService->sendComplaintRegistered($complaint);

            return redirect()->route('complaints.show', $complaint)
                ->with('success', 'Complaint registered successfully! Complaint Number: ' . $complaint->complaint_number);
        } catch (Exception $e) {
            // SECURITY: Log exception details, show generic message to user
            \Log::error('Complaint registration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()
                ->with('error', 'Failed to register complaint. Please try again or contact support.');
        }
    }

    /**
     * Display complaint details
     */
    public function show(Complaint $complaint)
    {
        $this->authorize('view', $complaint);

        $complaint->load([
            'candidate',
            'complainant',
            'campus',
            'oep',
            'assignee',
            'registeredBy',
            'updates',
            'evidence'
        ]);

        // Get SLA status
        $slaStatus = $this->complaintService->checkSLAStatus($complaint->id);

        return view('complaints.show', compact('complaint', 'slaStatus'));
    }

    /**
     * Show form to edit complaint
     */
    public function edit(Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $candidates = Candidate::select('id', 'name', 'cnic')->get();
        $campuses = Campus::where('is_active', true)->get();
        $oeps = Oep::where('is_active', true)->get();
        $users = User::where('role', 'admin')->get();

        return view('complaints.edit', compact('complaint', 'candidates', 'campuses', 'oeps', 'users'));
    }

    /**
     * Update complaint
     */
    public function update(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'priority' => 'nullable|in:low,medium,high,critical',
            'status' => 'nullable|in:registered,investigating,resolved,closed',
        ]);

        try {
            if (isset($validated['priority'])) {
                $complaint = $this->complaintService->updatePriority(
                    $complaint->id,
                    $validated['priority']
                );
            }

            if (isset($validated['status'])) {
                $complaint = $this->complaintService->updateStatus(
                    $complaint->id,
                    $validated['status']
                );
            }

            return redirect()->route('complaints.show', $complaint)
                ->with('success', 'Complaint updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update complaint: ' . $e->getMessage());
        }
    }

    /**
     * Assign complaint to user
     */
    public function assign(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'assignment_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $complaint = $this->complaintService->assignComplaint(
                $complaint->id,
                $validated['assigned_to'],
                $validated['assignment_notes'] ?? null
            );

            $this->notificationService->sendComplaintAssigned(
                $complaint,
                User::find($validated['assigned_to'])
            );

            return back()->with('success', 'Complaint assigned successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to assign complaint: ' . $e->getMessage());
        }
    }

    /**
     * Add update to complaint
     */
    public function addUpdate(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'update_text' => 'required|string',
            'is_internal' => 'nullable|boolean',
        ]);

        try {
            $update = $this->complaintService->addUpdate(
                $complaint->id,
                $validated['update_text'],
                $validated['is_internal'] ?? false
            );

            return back()->with('success', 'Update added successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to add update: ' . $e->getMessage());
        }
    }

    /**
     * Add evidence to complaint
     */
    public function addEvidence(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'evidence_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'evidence_description' => 'nullable|string|max:500',
        ]);

        try {
            $evidencePath = $request->file('evidence_file')
                ->store('complaints/evidence', 'public');

            $evidence = $this->complaintService->addEvidence(
                $complaint->id,
                $evidencePath,
                $validated['evidence_description'] ?? null
            );

            return back()->with('success', 'Evidence added successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to add evidence: ' . $e->getMessage());
        }
    }

    /**
     * Escalate complaint
     */
    public function escalate(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'escalation_reason' => 'required|string',
        ]);

        try {
            $complaint = $this->complaintService->escalateComplaint(
                $complaint->id,
                $validated['escalation_reason']
            );

            $this->notificationService->sendComplaintEscalated($complaint);

            return back()->with('success', 'Complaint escalated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to escalate complaint: ' . $e->getMessage());
        }
    }

    /**
     * Resolve complaint
     */
    public function resolve(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'resolution_details' => 'required|string',
            'resolution_date' => 'required|date',
            'resolution_satisfactory' => 'nullable|boolean',
        ]);

        try {
            $complaint = $this->complaintService->resolveComplaint(
                $complaint->id,
                $validated['resolution_details'],
                $validated['resolution_date'],
                $validated['resolution_satisfactory'] ?? null
            );

            $this->notificationService->sendComplaintResolved($complaint);

            return redirect()->route('complaints.show', $complaint)
                ->with('success', 'Complaint marked as resolved!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to resolve complaint: ' . $e->getMessage());
        }
    }

    /**
     * Close complaint
     */
    public function close(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'closure_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $complaint = $this->complaintService->closeComplaint(
                $complaint->id,
                $validated['closure_notes'] ?? null
            );

            $this->notificationService->sendComplaintClosed($complaint);

            return redirect()->route('complaints.index')
                ->with('success', 'Complaint closed successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to close complaint: ' . $e->getMessage());
        }
    }

    /**
     * Reopen complaint
     */
    public function reopen(Request $request, Complaint $complaint)
    {
        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'reopen_reason' => 'required|string',
        ]);

        try {
            $complaint = $this->complaintService->reopenComplaint(
                $complaint->id,
                $validated['reopen_reason']
            );

            return back()->with('success', 'Complaint reopened successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to reopen complaint: ' . $e->getMessage());
        }
    }

    /**
     * Get overdue complaints
     */
    public function overdue()
    {
        $this->authorize('viewAny', Complaint::class);

        try {
            $overdueComplaints = $this->complaintService->getOverdueComplaints();

            return view('complaints.overdue', compact('overdueComplaints'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch overdue complaints: ' . $e->getMessage());
        }
    }

    /**
     * Get complaints by category
     */
    public function byCategory(Request $request)
    {
        $this->authorize('viewAny', Complaint::class);

        $validated = $request->validate([
            'category' => 'required|in:screening,training,visa,salary,conduct,facility,medical,document,other',
        ]);

        try {
            $complaints = $this->complaintService->getComplaintsByCategory($validated['category']);

            return view('complaints.by-category', compact('complaints'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch complaints: ' . $e->getMessage());
        }
    }

    /**
     * Get assigned complaints
     */
    public function myAssignments()
    {
        $this->authorize('viewAny', Complaint::class);

        try {
            $assignedComplaints = $this->complaintService->getAssignedComplaints(auth()->id());

            return view('complaints.my-assignments', compact('assignedComplaints'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch assignments: ' . $e->getMessage());
        }
    }

    /**
     * Generate analytics report
     */
    public function analytics(Request $request)
    {
        $this->authorize('viewAny', Complaint::class);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'category' => 'nullable|string',
        ]);

        try {
            $analytics = $this->complaintService->generateAnalytics(
                $validated['start_date'],
                $validated['end_date'],
                $validated['category'] ?? null
            );

            return view('complaints.analytics', compact('analytics'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get SLA performance report
     */
    public function slaReport(Request $request)
    {
        $this->authorize('viewAny', Complaint::class);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $slaReport = $this->complaintService->getSLAPerformance(
                $validated['start_date'],
                $validated['end_date']
            );

            return view('complaints.sla-report', compact('slaReport'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate SLA report: ' . $e->getMessage());
        }
    }

    /**
     * Export complaints
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Complaint::class);

        $validated = $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'nullable|string',
        ]);

        try {
            $filePath = $this->complaintService->exportComplaints(
                $validated['format'],
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $validated['status'] ?? null
            );

            return response()->download(storage_path('app/public/' . $filePath));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to export complaints: ' . $e->getMessage());
        }
    }

    /**
     * Delete complaint
     */
    public function destroy(Complaint $complaint)
    {
        $this->authorize('delete', $complaint);

        try {
            $this->complaintService->deleteComplaint($complaint->id);

            return redirect()->route('complaints.index')
                ->with('success', 'Complaint deleted successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete complaint: ' . $e->getMessage());
        }
    }

    /**
     * Display complaint statistics
     */
    public function statistics()
    {
        $this->authorize('viewAny', Complaint::class);

        try {
            // Get overall statistics
            $totalComplaints = Complaint::count();
            $openComplaints = Complaint::whereIn('status', ['registered', 'investigating'])->count();
            $resolvedComplaints = Complaint::where('status', 'resolved')->count();
            $closedComplaints = Complaint::where('status', 'closed')->count();

            // Complaints by category
            $byCategory = Complaint::select('category', \DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category');

            // Complaints by priority
            $byPriority = Complaint::select('priority', \DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority');

            // Complaints by status
            $byStatus = Complaint::select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            // Average resolution time (in days)
            $avgResolutionTime = Complaint::whereNotNull('resolved_at')
                ->selectRaw('AVG(DATEDIFF(resolved_at, created_at)) as avg_days')
                ->value('avg_days') ?? 0;

            // Monthly trends (last 6 months)
            $monthlyTrends = Complaint::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            // Top assigned users
            // FIXED: N+1 query - load assignedTo relationship properly after grouping
            $topAssignees = Complaint::select('assigned_to', \DB::raw('count(*) as count'))
                ->whereNotNull('assigned_to')
                ->groupBy('assigned_to')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();

            // Load the assignedTo relationship for the results
            $topAssignees->load('assignedTo:id,name');

            // SLA compliance rate
            $slaCompliant = Complaint::whereNotNull('resolved_at')
                ->whereRaw('DATEDIFF(resolved_at, created_at) <= sla_days')
                ->count();
            $totalResolved = Complaint::whereNotNull('resolved_at')->count();
            $slaComplianceRate = $totalResolved > 0 ? ($slaCompliant / $totalResolved) * 100 : 0;

            // Recent complaints
            $recentComplaints = Complaint::with(['candidate', 'assignedTo'])
                ->latest()
                ->limit(10)
                ->get();

            $statistics = compact(
                'totalComplaints',
                'openComplaints',
                'resolvedComplaints',
                'closedComplaints',
                'byCategory',
                'byPriority',
                'byStatus',
                'avgResolutionTime',
                'monthlyTrends',
                'topAssignees',
                'slaComplianceRate',
                'recentComplaints'
            );

            return view('complaints.statistics', compact('statistics'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate statistics: ' . $e->getMessage());
        }
    }
}