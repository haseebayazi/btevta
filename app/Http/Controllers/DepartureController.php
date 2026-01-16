<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Departure;
use App\Services\DepartureService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('name', 'like', "%{$escapedSearch}%")
                    ->orWhere('cnic', 'like', "%{$escapedSearch}%")
                    ->orWhere('passport_number', 'like', "%{$escapedSearch}%");
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
            // AUDIT FIX: Wrap departure record creation and candidate status update in transaction
            // to ensure data consistency - both operations must succeed or both fail
            DB::beginTransaction();

            $departure = $this->departureService->recordDeparture(
                $candidate->id,
                [
                    'departure_date' => $validated['actual_departure_date'],
                    'remarks' => $validated['departure_remarks'] ?? null,
                ]
            );

            $candidate->update(['status' => 'departed']);

            DB::commit();

            // Notification sent outside transaction - non-critical operation
            // that shouldn't roll back the departure record if it fails
            try {
                $this->notificationService->sendDepartureConfirmed($candidate);
            } catch (Exception $notifyException) {
                // Log notification failure but don't fail the departure recording
                \Log::warning('Failed to send departure notification', [
                    'candidate_id' => $candidate->id,
                    'error' => $notifyException->getMessage()
                ]);
            }

            return back()->with('success', 'Departure recorded successfully!');
        } catch (Exception $e) {
            DB::rollBack();
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

        // AUDIT FIX: Added proper validation for Saudi Iqama number (10 digits starting with 1 or 2)
        $validated = $request->validate([
            'iqama_number' => ['required', 'string', 'regex:/^[12][0-9]{9}$/'],
            'iqama_issue_date' => 'required|date',
            'iqama_expiry_date' => 'nullable|date|after:iqama_issue_date',
            'post_arrival_medical' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'iqama_number.regex' => 'Iqama number must be 10 digits starting with 1 or 2.',
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
     * Confirm and verify salary receipt with detailed documentation
     */
    public function confirmSalary(Request $request, Departure $departure)
    {
        $this->authorize('recordFirstSalary', Departure::class);

        $validated = $request->validate([
            'salary_amount' => 'required|numeric|min:0',
            'salary_currency' => 'required|string|in:SAR,PKR,USD,AED',
            'first_salary_date' => 'required|date',
            'proof_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'salary_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            // Store proof document
            $proofPath = $request->file('proof_document')
                ->store('departure/salary-proof', 'public');

            // Update departure with salary confirmation
            $departure->update([
                'salary_amount' => $validated['salary_amount'],
                'salary_currency' => $validated['salary_currency'],
                'first_salary_date' => $validated['first_salary_date'],
                'salary_proof_path' => $proofPath,
                'salary_confirmed' => true,
                'salary_confirmed_by' => auth()->id(),
                'salary_confirmed_at' => now(),
                'salary_remarks' => $validated['salary_remarks'] ?? null,
            ]);

            // Log activity
            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->withProperties([
                    'salary_amount' => $validated['salary_amount'],
                    'salary_currency' => $validated['salary_currency'],
                ])
                ->log('Salary confirmed');

            // Send notification
            if ($departure->candidate) {
                $this->notificationService->sendFirstSalaryConfirmed($departure->candidate);
            }

            return back()->with('success', 'Salary confirmed successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to confirm salary: ' . $e->getMessage());
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
     * Welfare Monitoring Dashboard
     */
    public function welfareMonitoring(Request $request)
    {
        $this->authorize('viewTrackingReports', Departure::class);

        try {
            // Overall statistics
            $totalDeployed = Departure::whereNotNull('departure_date')->count();

            $stats = [
                'total_deployed' => $totalDeployed,
                'ninety_day_compliant' => Departure::where('ninety_day_compliance_status', 'compliant')->count(),
                'ninety_day_partial' => Departure::where('ninety_day_compliance_status', 'partial')->count(),
                'ninety_day_non_compliant' => Departure::where('ninety_day_compliance_status', 'non_compliant')->count(),
                'ninety_day_pending' => Departure::where('ninety_day_compliance_status', 'pending')
                    ->orWhereNull('ninety_day_compliance_status')
                    ->count(),
                'salary_confirmed' => Departure::where('salary_confirmed', true)->count(),
                'pending_salary_confirmation' => Departure::where('salary_confirmed', false)
                    ->orWhereNull('salary_confirmed')
                    ->where('departure_date', '<=', now()->subDays(30))
                    ->whereNotNull('departure_date')
                    ->count(),
                'at_risk_candidates' => $this->getAtRiskCandidates(),
                'by_country' => Departure::whereNotNull('destination')
                    ->selectRaw('destination as country, count(*) as count')
                    ->groupBy('destination')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'recent_issues' => Departure::whereNotNull('issues')
                    ->where('issues', '!=', '')
                    ->with('candidate')
                    ->latest()
                    ->limit(10)
                    ->get(),
            ];

            // Compliance rate calculation
            $stats['compliance_rate'] = $totalDeployed > 0
                ? round(($stats['ninety_day_compliant'] / $totalDeployed) * 100, 1)
                : 0;

            $stats['salary_confirmation_rate'] = $totalDeployed > 0
                ? round(($stats['salary_confirmed'] / $totalDeployed) * 100, 1)
                : 0;

            // Filter options
            $campuses = auth()->user()->role === 'campus_admin'
                ? \App\Models\Campus::where('id', auth()->user()->campus_id)->pluck('name', 'id')
                : \App\Models\Campus::where('is_active', true)->pluck('name', 'id');

            $oeps = \App\Models\Oep::where('is_active', true)->pluck('name', 'id');

            return view('departure.welfare-monitoring', compact('stats', 'campuses', 'oeps'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load welfare monitoring dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Get at-risk candidates (those who might need intervention)
     */
    private function getAtRiskCandidates()
    {
        // Candidates are at risk if:
        // 1. Departed 60+ days ago without salary confirmation
        // 2. Non-compliant 90-day status
        // 3. Have logged issues
        $atRisk = Departure::with('candidate')
            ->where(function($q) {
                $q->where(function($subQ) {
                    // No salary after 60 days
                    $subQ->where('departure_date', '<=', now()->subDays(60))
                        ->where(function($salaryQ) {
                            $salaryQ->where('salary_confirmed', false)
                                ->orWhereNull('salary_confirmed');
                        });
                })
                ->orWhere('ninety_day_compliance_status', 'non_compliant')
                ->orWhereNotNull('issues');
            })
            ->whereNotNull('departure_date')
            ->get();

        return $atRisk->map(function($departure) {
            $riskFactors = [];

            if (!$departure->salary_confirmed && $departure->departure_date <= now()->subDays(60)) {
                $riskFactors[] = 'No salary confirmation (' . $departure->departure_date->diffInDays(now()) . ' days)';
            }

            if ($departure->ninety_day_compliance_status === 'non_compliant') {
                $riskFactors[] = '90-day non-compliant';
            }

            if ($departure->issues) {
                $riskFactors[] = 'Active issues reported';
            }

            return [
                'departure' => $departure,
                'candidate' => $departure->candidate,
                'risk_factors' => $riskFactors,
                'risk_level' => count($riskFactors) >= 2 ? 'high' : 'medium',
            ];
        })->sortByDesc(function($item) {
            return count($item['risk_factors']);
        })->take(20);
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

            $candidate->update(['status' => Candidate::STATUS_RETURNED]);

            return redirect()->route('departure.index')
                ->with('success', 'Candidate marked as returned!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to mark as returned: ' . $e->getMessage());
        }
    }

    /**
     * Departure list report by date, trade, and OEP
     */
    public function departureListReport(Request $request)
    {
        $this->authorize('viewTrackingReports', Departure::class);

        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'trade_id' => 'nullable|exists:trades,id',
            'oep_id' => 'nullable|exists:oeps,id',
        ]);

        try {
            $departures = $this->departureService->getDepartureList($validated);

            $trades = \App\Models\Trade::where('is_active', true)->pluck('name', 'id');
            $oeps = \App\Models\Oep::where('is_active', true)->pluck('name', 'id');

            return view('departure.reports.list', compact('departures', 'trades', 'oeps', 'validated'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Pending Iqama or Absher activation report
     */
    public function pendingActivationsReport(Request $request)
    {
        $this->authorize('viewTrackingReports', Departure::class);

        $validated = $request->validate([
            'type' => 'nullable|in:iqama,absher,all',
            'oep_id' => 'nullable|exists:oeps,id',
        ]);

        $type = $validated['type'] ?? 'all';

        try {
            $query = Departure::with(['candidate.trade', 'candidate.oep', 'candidate.campus'])
                ->whereNotNull('departure_date');

            // Filter by pending type
            if ($type === 'iqama') {
                $query->whereNull('iqama_number');
            } elseif ($type === 'absher') {
                $query->where(function($q) {
                    $q->whereNull('absher_registered')
                      ->orWhere('absher_registered', false);
                });
            } else {
                $query->where(function($q) {
                    $q->whereNull('iqama_number')
                      ->orWhereNull('absher_registered')
                      ->orWhere('absher_registered', false);
                });
            }

            // Filter by OEP
            if (!empty($validated['oep_id'])) {
                $query->whereHas('candidate', function($q) use ($validated) {
                    $q->where('oep_id', $validated['oep_id']);
                });
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->whereHas('candidate', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                });
            }

            $departures = $query->latest('departure_date')->paginate(20);

            $oeps = \App\Models\Oep::where('is_active', true)->pluck('name', 'id');

            // Summary stats
            $stats = [
                'pending_iqama' => Departure::whereNotNull('departure_date')->whereNull('iqama_number')->count(),
                'pending_absher' => Departure::whereNotNull('departure_date')
                    ->where(function($q) {
                        $q->whereNull('absher_registered')->orWhere('absher_registered', false);
                    })->count(),
            ];

            return view('departure.reports.pending-activations', compact('departures', 'oeps', 'stats', 'type'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Salary disbursement status report
     */
    public function salaryStatusReport(Request $request)
    {
        $this->authorize('viewTrackingReports', Departure::class);

        $validated = $request->validate([
            'status' => 'nullable|in:confirmed,pending,not_received,all',
            'oep_id' => 'nullable|exists:oeps,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $status = $validated['status'] ?? 'all';

        try {
            $query = Departure::with(['candidate.trade', 'candidate.oep', 'candidate.campus'])
                ->whereNotNull('departure_date');

            // Filter by salary status
            if ($status === 'confirmed') {
                $query->where('salary_confirmed', true);
            } elseif ($status === 'pending') {
                $query->whereNotNull('first_salary_date')
                      ->where(function($q) {
                          $q->whereNull('salary_confirmed')->orWhere('salary_confirmed', false);
                      });
            } elseif ($status === 'not_received') {
                $query->whereNull('first_salary_date');
            }

            // Filter by OEP
            if (!empty($validated['oep_id'])) {
                $query->whereHas('candidate', function($q) use ($validated) {
                    $q->where('oep_id', $validated['oep_id']);
                });
            }

            // Filter by date range
            if (!empty($validated['from_date'])) {
                $query->whereDate('departure_date', '>=', $validated['from_date']);
            }
            if (!empty($validated['to_date'])) {
                $query->whereDate('departure_date', '<=', $validated['to_date']);
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->whereHas('candidate', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                });
            }

            $departures = $query->latest('departure_date')->paginate(20);

            $oeps = \App\Models\Oep::where('is_active', true)->pluck('name', 'id');

            // Summary stats
            $stats = [
                'total_departed' => Departure::whereNotNull('departure_date')->count(),
                'salary_confirmed' => Departure::where('salary_confirmed', true)->count(),
                'salary_pending' => Departure::whereNotNull('first_salary_date')
                    ->where(function($q) {
                        $q->whereNull('salary_confirmed')->orWhere('salary_confirmed', false);
                    })->count(),
                'salary_not_received' => Departure::whereNotNull('departure_date')
                    ->whereNull('first_salary_date')->count(),
                'total_salary_amount' => Departure::where('salary_confirmed', true)->sum('salary_amount'),
            ];

            return view('departure.reports.salary-status', compact('departures', 'oeps', 'stats', 'status', 'validated'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * AUDIT FIX: Added missing controller methods for routes used in views
     */

    /**
     * Show pending compliance departures
     */
    public function pendingCompliance()
    {
        $this->authorize('viewAny', Departure::class);

        $departures = Departure::with(['candidate.trade', 'candidate.oep', 'candidate.campus'])
            ->whereNotNull('departure_date')
            ->where(function($q) {
                $q->whereNull('ninety_day_report_submitted')
                  ->orWhere('ninety_day_report_submitted', false);
            })
            ->latest('departure_date')
            ->paginate(20);

        return view('departure.pending-compliance', compact('departures'));
    }

    /**
     * Mark departure as compliant
     */
    public function markCompliant(Request $request, Departure $departure)
    {
        $this->authorize('update', $departure);

        $validated = $request->validate([
            'compliance_notes' => 'nullable|string|max:1000',
        ]);

        $departure->update([
            'ninety_day_report_submitted' => true,
            'compliance_verified_date' => now(),
            'compliance_remarks' => $validated['compliance_notes'] ?? null,
        ]);

        return back()->with('success', 'Departure marked as compliant successfully!');
    }

    /**
     * Show create issue form
     */
    public function createIssue()
    {
        $this->authorize('create', Departure::class);

        $candidates = \App\Models\Candidate::with('departure')
            ->where('status', 'departed')
            ->get();

        return view('departure.issues.create', compact('candidates'));
    }

    /**
     * Export compliance report as PDF
     */
    public function complianceReportPdf(Request $request)
    {
        $this->authorize('viewReports', Departure::class);

        // Reuse compliance report logic with PDF export
        return $this->complianceReport($request->merge(['format' => 'pdf']));
    }

    /**
     * Export compliance report as Excel
     */
    public function complianceReportExcel(Request $request)
    {
        $this->authorize('viewReports', Departure::class);

        // Reuse compliance report logic with Excel export
        return $this->complianceReport($request->merge(['format' => 'excel']));
    }

    /**
     * Export 90-day tracking data
     */
    public function tracking90DaysExport(Request $request)
    {
        $this->authorize('viewTrackingReports', Departure::class);

        $departures = Departure::with(['candidate.trade', 'candidate.oep', 'candidate.campus'])
            ->whereNotNull('departure_date')
            ->get();

        // Return as CSV download
        $filename = 'departure-90-day-tracking-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['Candidate', 'TheLeap ID', 'Trade', 'Departure Date', 'Iqama', 'Absher', 'Salary Confirmed', '90-Day Status'];

        $callback = function() use ($departures, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($departures as $departure) {
                fputcsv($file, [
                    $departure->candidate->name ?? 'N/A',
                    $departure->candidate->btevta_id ?? 'N/A',
                    $departure->candidate->trade->name ?? 'N/A',
                    $departure->departure_date?->format('Y-m-d') ?? 'N/A',
                    $departure->iqama_number ? 'Yes' : 'No',
                    $departure->absher_registered ? 'Yes' : 'No',
                    $departure->salary_confirmed ? 'Yes' : 'No',
                    $departure->ninety_day_report_submitted ? 'Compliant' : 'Pending',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}