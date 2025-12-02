<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Departure;
use App\Services\DepartureService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Exception;

class DepartureController extends Controller
{
    protected $departureService;
    protected $notificationService;

    public function __construct(
        DepartureService $departureService,
        NotificationService $notificationService
    ) {
        $this->departureService = $departureService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of departed candidates
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Departure::class);

        $query = Candidate::with(['trade', 'oep', 'departure'])
            ->where('status', 'departed');

        // Filter by campus for campus admins
        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        // Apply filters
        if ($request->filled('compliance_stage')) {
            $query->whereHas('departure', function ($q) use ($request) {
                $q->where('compliance_stage', $request->compliance_stage);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%");
            });
        }

        $candidates = $query->paginate(20);

        return view('departure.index', compact('candidates'));
    }

    /**
     * Show departure details
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate->departure ?? new Departure());

        $candidate->load(['departure', 'trade', 'oep', 'campus']);

        if (!$candidate->departure) {
            return redirect()->route('departure.index')
                ->with('error', 'No departure record found for this candidate');
        }

        // Get compliance checklist
        $checklist = $this->departureService->getComplianceChecklist($candidate->id);

        return view('departure.show', compact('candidate', 'checklist'));
    }

    /**
     * Record pre-departure briefing
     */
    public function recordBriefing(Request $request, Candidate $candidate)
    {
        $this->authorize('recordBriefing', Departure::class);

        $validated = $request->validate([
            'departure_date' => 'required|date',
            'flight_number' => 'required|string|max:50',
            'destination' => 'required|string|max:100',
            'briefing_date' => 'required|date',
            'briefing_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $departure = $this->departureService->recordPreDepartureBriefing(
                $candidate->id,
                [
                    'briefing_date' => $validated['briefing_date'],
                    'departure_date' => $validated['departure_date'],
                    'flight_number' => $validated['flight_number'],
                    'destination' => $validated['destination'],
                    'remarks' => $validated['briefing_remarks'] ?? null,
                ]
            );

            $this->notificationService->sendBriefingCompleted($candidate);

            return back()->with('success', 'Pre-departure briefing recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record briefing: ' . $e->getMessage());
        }
    }

    /**
     * Record departure
     */
    public function recordDeparture(Request $request, Candidate $candidate)
    {
        $this->authorize('recordDeparture', Departure::class);

        $validated = $request->validate([
            'actual_departure_date' => 'required|date',
            'departure_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $departure = $this->departureService->recordDeparture(
                $candidate->id,
                [
                    'departure_date' => $validated['actual_departure_date'],
                    'remarks' => $validated['departure_remarks'] ?? null,
                ]
            );

            $candidate->update(['status' => 'departed']);

            $this->notificationService->sendDepartureConfirmed($candidate);

            return back()->with('success', 'Departure recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record departure: ' . $e->getMessage());
        }
    }

    /**
     * Record Iqama details
     */
    public function recordIqama(Request $request, Candidate $candidate)
    {
        $this->authorize('recordIqama', Departure::class);

        $validated = $request->validate([
            'iqama_number' => 'required|string|max:50',
            'iqama_issue_date' => 'required|date',
            'iqama_expiry_date' => 'nullable|date|after:iqama_issue_date',
            'post_arrival_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $medicalPath = null;
            if ($request->hasFile('post_arrival_medical')) {
                $medicalPath = $request->file('post_arrival_medical')
                    ->store('departure/medical', 'public');
            }

            $departure = $this->departureService->recordIqamaDetails(
                $candidate->id,
                $validated['iqama_number'],
                $validated['iqama_issue_date'],
                $validated['iqama_expiry_date'] ?? null,
                $medicalPath
            );

            $this->notificationService->sendIqamaRecorded($candidate);

            return back()->with('success', 'Iqama details recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record Iqama: ' . $e->getMessage());
        }
    }

    /**
     * Record Absher registration
     */
    public function recordAbsher(Request $request, Candidate $candidate)
    {
        $this->authorize('recordAbsher', Departure::class);

        $validated = $request->validate([
            'absher_registration_date' => 'required|date',
            'absher_id' => 'nullable|string|max:50',
            'absher_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $departure = $this->departureService->recordAbsherRegistration(
                $candidate->id,
                $validated['absher_registration_date'],
                $validated['absher_id'] ?? null,
                $validated['absher_remarks'] ?? null
            );

            return back()->with('success', 'Absher registration recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record Absher: ' . $e->getMessage());
        }
    }

    /**
     * Record WPS registration
     */
    public function recordWps(Request $request, Candidate $candidate)
    {
        $this->authorize('recordWps', Departure::class);

        $validated = $request->validate([
            'wps_registration_date' => 'required|date',
            'wps_id' => 'nullable|string|max:50',
            'wps_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $departure = $this->departureService->recordWPSRegistration(
                $candidate->id,
                $validated['wps_registration_date'],
                $validated['wps_id'] ?? null,
                $validated['wps_remarks'] ?? null
            );

            return back()->with('success', 'WPS registration recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record WPS: ' . $e->getMessage());
        }
    }

    /**
     * Record first salary receipt
     */
    public function recordFirstSalary(Request $request, Candidate $candidate)
    {
        $this->authorize('recordFirstSalary', Departure::class);

        $validated = $request->validate([
            'first_salary_date' => 'required|date',
            'salary_amount' => 'required|numeric|min:0',
            'salary_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $proofPath = null;
            if ($request->hasFile('salary_proof')) {
                $proofPath = $request->file('salary_proof')
                    ->store('departure/salary-proof', 'public');
            }

            $departure = $this->departureService->recordFirstSalary(
                $candidate->id,
                $validated['first_salary_date'],
                $validated['salary_amount'],
                $proofPath
            );

            $this->notificationService->sendFirstSalaryConfirmed($candidate);

            return back()->with('success', 'First salary recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record salary: ' . $e->getMessage());
        }
    }

    /**
     * Record 90-day compliance
     */
    public function record90DayCompliance(Request $request, Candidate $candidate)
    {
        $this->authorize('record90DayCompliance', Departure::class);

        $validated = $request->validate([
            'compliance_date' => 'required|date',
            'is_compliant' => 'required|boolean',
            'compliance_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $departure = $this->departureService->record90DayCompliance(
                $candidate->id,
                $validated['compliance_date'],
                $validated['is_compliant'],
                $validated['compliance_remarks'] ?? null
            );

            if ($validated['is_compliant']) {
                $this->notificationService->sendComplianceAchieved($candidate);
            }

            return back()->with('success', '90-day compliance recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record compliance: ' . $e->getMessage());
        }
    }

    /**
     * Report post-departure issue
     */
    public function reportIssue(Request $request, Candidate $candidate)
    {
        $this->authorize('reportIssue', Departure::class);

        $validated = $request->validate([
            'issue_type' => 'required|in:salary_delay,contract_violation,work_condition,accommodation,medical,other',
            'issue_date' => 'required|date',
            'issue_description' => 'required|string',
            'severity' => 'required|in:low,medium,high,critical',
            'evidence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $evidencePath = null;
            if ($request->hasFile('evidence')) {
                $evidencePath = $request->file('evidence')
                    ->store('departure/issues', 'public');
            }

            $issue = $this->departureService->reportIssue(
                $candidate->id,
                $validated['issue_type'],
                $validated['issue_date'],
                $validated['issue_description'],
                $validated['severity'],
                $evidencePath
            );

            $this->notificationService->sendIssueReported($candidate, $issue);

            return back()->with('success', 'Issue reported successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to report issue: ' . $e->getMessage());
        }
    }

    /**
     * Update issue status
     */
    public function updateIssue(Request $request, $issueId)
    {
        $this->authorize('updateIssue', Departure::class);

        $validated = $request->validate([
            'status' => 'required|in:open,investigating,resolved,closed',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $issue = $this->departureService->updateIssueStatus(
                $issueId,
                $validated['status'],
                $validated['resolution_notes'] ?? null
            );

            return back()->with('success', 'Issue status updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update issue: ' . $e->getMessage());
        }
    }

    /**
     * Get departure timeline
     */
    public function timeline(Candidate $candidate)
    {
        $this->authorize('viewTimeline', Departure::class);

        try {
            $timeline = $this->departureService->getDepartureTimeline($candidate->id);

            return view('departure.timeline', compact('candidate', 'timeline'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch timeline: ' . $e->getMessage());
        }
    }

    /**
     * Get compliance report
     */
    public function complianceReport(Request $request)
    {
        $this->authorize('viewComplianceReport', Departure::class);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'oep_id' => 'nullable|exists:oeps,id',
        ]);

        try {
            $report = $this->departureService->generateComplianceReport(
                $validated['start_date'],
                $validated['end_date'],
                $validated['oep_id'] ?? null
            );

            return view('departure.compliance-report', compact('report'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Get 90-day tracking report
     */
    public function tracking90Days()
    {
        $this->authorize('viewTrackingReports', Departure::class);

        try {
            $tracking = $this->departureService->get90DayTracking();

            return view('departure.90-day-tracking', compact('tracking'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch tracking data: ' . $e->getMessage());
        }
    }

    /**
     * Get non-compliant candidates
     */
    public function nonCompliant()
    {
        $this->authorize('viewTrackingReports', Departure::class);

        try {
            $nonCompliantCandidates = $this->departureService->getNonCompliantCandidates();

            return view('departure.non-compliant', compact('nonCompliantCandidates'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch data: ' . $e->getMessage());
        }
    }

    /**
     * Get active issues
     */
    public function activeIssues()
    {
        $this->authorize('viewTrackingReports', Departure::class);

        try {
            $activeIssues = $this->departureService->getActiveIssues();

            return view('departure.active-issues', compact('activeIssues'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch issues: ' . $e->getMessage());
        }
    }

    /**
     * Mark as returned
     */
    public function markReturned(Request $request, Candidate $candidate)
    {
        $this->authorize('markReturned', Departure::class);

        $validated = $request->validate([
            'return_date' => 'required|date',
            'return_reason' => 'required|string',
            'return_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $this->departureService->markAsReturned(
                $candidate->id,
                $validated['return_date'],
                $validated['return_reason'],
                $validated['return_remarks'] ?? null
            );

            $candidate->update(['status' => 'returned']);

            return redirect()->route('departure.index')
                ->with('success', 'Candidate marked as returned!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to mark as returned: ' . $e->getMessage());
        }
    }
}