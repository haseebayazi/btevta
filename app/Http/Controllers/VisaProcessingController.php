<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\VisaProcess;
use App\Services\VisaProcessingService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Exception;

class VisaProcessingController extends Controller
{
    protected $visaService;
    protected $notificationService;

    public function __construct(
        VisaProcessingService $visaService,
        NotificationService $notificationService
    ) {
        $this->visaService = $visaService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of candidates in visa processing
     */
    public function index(Request $request)
    {
        $query = Candidate::with(['trade', 'campus', 'oep', 'visaProcess'])
            ->where('status', 'visa_processing');

        // Filter by campus for campus admins
        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        // Apply filters
        if ($request->filled('stage')) {
            $query->whereHas('visaProcess', function ($q) use ($request) {
                $q->where('overall_status', $request->stage);
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

        return view('visa-processing.index', compact('candidates'));
    }

    /**
     * Show form to create new visa process
     */
    public function create(Request $request)
    {
        // Get candidates eligible for visa processing (completed training)
        $candidates = Candidate::where('status', 'training_completed')
            ->orWhere('status', 'screening_passed')
            ->with(['trade', 'campus'])
            ->get();

        return view('visa-processing.create', compact('candidates'));
    }

    /**
     * Store new visa process
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'interview_date' => 'nullable|date',
            'interview_status' => 'nullable|in:pending,passed,failed',
            'interview_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->createVisaProcess(
                $validated['candidate_id'],
                $validated
            );

            // Send notification
            $candidate = $visaProcess->candidate;
            $this->notificationService->sendVisaProcessInitiated($candidate);

            return redirect()->route('visa-processing.show', $candidate)
                ->with('success', 'Visa process initiated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create visa process: ' . $e->getMessage());
        }
    }

    /**
     * Display visa process details
     */
    public function show(Candidate $candidate)
    {
        $candidate->load(['visaProcess', 'trade', 'campus', 'oep']);

        if (!$candidate->visaProcess) {
            return redirect()->route('visa-processing.index')
                ->with('error', 'No visa process found for this candidate');
        }

        return view('visa-processing.show', compact('candidate'));
    }

    /**
     * Show form to edit visa process
     */
    public function edit(Candidate $candidate)
    {
        $candidate->load('visaProcess');

        if (!$candidate->visaProcess) {
            return redirect()->route('visa-processing.index')
                ->with('error', 'No visa process found for this candidate');
        }

        return view('visa-processing.edit', compact('candidate'));
    }

    /**
     * Update visa process
     */
    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'overall_status' => 'nullable|string',
            'remarks' => 'nullable|string|max:2000',
        ]);

        try {
            if (!$candidate->visaProcess) {
                throw new Exception('No visa process found');
            }

            $visaProcess = $this->visaService->updateVisaProcess(
                $candidate->visaProcess->id,
                $validated
            );

            return redirect()->route('visa-processing.show', $candidate)
                ->with('success', 'Visa process updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update visa process: ' . $e->getMessage());
        }
    }

    /**
     * Update interview stage
     */
    public function updateInterview(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'interview_date' => 'required|date',
            'interview_status' => 'required|in:pending,passed,failed',
            'interview_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->updateInterview(
                $candidate->visaProcess->id,
                $validated
            );

            // Send notification on status change
            if ($validated['interview_status'] === 'passed') {
                $this->notificationService->sendVisaStageCompleted($candidate, 'Interview');
            }

            return back()->with('success', 'Interview details updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update interview: ' . $e->getMessage());
        }
    }

    /**
     * Update trade test stage
     */
    public function updateTradeTest(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'trade_test_date' => 'required|date',
            'trade_test_status' => 'required|in:pending,passed,failed',
            'trade_test_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->updateTradeTest(
                $candidate->visaProcess->id,
                $validated
            );

            if ($validated['trade_test_status'] === 'passed') {
                $this->notificationService->sendVisaStageCompleted($candidate, 'Trade Test');
            }

            return back()->with('success', 'Trade test details updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update trade test: ' . $e->getMessage());
        }
    }

    /**
     * Update Takamol/Musaned stage
     */
    public function updateTakamol(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'takamol_date' => 'required|date',
            'takamol_status' => 'required|in:pending,completed,failed',
            'takamol_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->updateTakamol(
                $candidate->visaProcess->id,
                $validated
            );

            if ($validated['takamol_status'] === 'completed') {
                $this->notificationService->sendVisaStageCompleted($candidate, 'Takamol');
            }

            return back()->with('success', 'Takamol details updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update Takamol: ' . $e->getMessage());
        }
    }

    /**
     * Update medical/GAMCA stage
     */
    public function updateMedical(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'medical_date' => 'required|date',
            'medical_status' => 'required|in:pending,fit,unfit',
            'medical_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->updateMedical(
                $candidate->visaProcess->id,
                $validated
            );

            if ($validated['medical_status'] === 'fit') {
                $this->notificationService->sendVisaStageCompleted($candidate, 'Medical');
            }

            return back()->with('success', 'Medical details updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update medical: ' . $e->getMessage());
        }
    }

    /**
     * Update biometric stage
     */
    public function updateBiometric(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'biometric_date' => 'required|date',
            'biometric_status' => 'required|in:pending,completed,failed',
            'biometric_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->updateBiometric(
                $candidate->visaProcess->id,
                $validated
            );

            if ($validated['biometric_status'] === 'completed') {
                $this->notificationService->sendVisaStageCompleted($candidate, 'Biometric');
            }

            return back()->with('success', 'Biometric details updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update biometric: ' . $e->getMessage());
        }
    }

    /**
     * Update visa issuance
     */
    public function updateVisa(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'visa_date' => 'required|date',
            'visa_number' => 'required|string|max:50',
            'visa_status' => 'required|in:pending,issued,rejected',
            'visa_remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $visaProcess = $this->visaService->updateVisaIssuance(
                $candidate->visaProcess->id,
                $validated
            );

            if ($validated['visa_status'] === 'issued') {
                $this->notificationService->sendVisaIssued($candidate);
            }

            return back()->with('success', 'Visa details updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update visa: ' . $e->getMessage());
        }
    }

    /**
     * Upload ticket
     */
    public function uploadTicket(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'ticket_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'ticket_date' => 'required|date',
        ]);

        try {
            $visaProcess = $this->visaService->uploadTicket(
                $candidate->visaProcess->id,
                $request->file('ticket_file'),
                $validated['ticket_date']
            );

            $this->notificationService->sendTicketUploaded($candidate);

            return back()->with('success', 'Ticket uploaded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to upload ticket: ' . $e->getMessage());
        }
    }

    /**
     * Get visa processing timeline
     */
    public function timeline(Candidate $candidate)
    {
        try {
            $timeline = $this->visaService->getTimeline($candidate->visaProcess->id);

            return view('visa-processing.timeline', compact('candidate', 'timeline'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch timeline: ' . $e->getMessage());
        }
    }

    /**
     * Get overdue visa processes
     */
    public function overdue()
    {
        try {
            $overdueCandidates = $this->visaService->getOverdueProcesses();

            return view('visa-processing.overdue', compact('overdueCandidates'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch overdue processes: ' . $e->getMessage());
        }
    }

    /**
     * Complete visa process
     */
    public function complete(Candidate $candidate)
    {
        try {
            $visaProcess = $this->visaService->completeVisaProcess($candidate->visaProcess->id);

            // Update candidate status to ready for departure
            $candidate->update(['status' => 'visa_completed']);

            $this->notificationService->sendVisaProcessCompleted($candidate);

            return redirect()->route('visa-processing.index')
                ->with('success', 'Visa process completed successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to complete visa process: ' . $e->getMessage());
        }
    }

    /**
     * Generate visa processing report
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        try {
            $report = $this->visaService->generateReport(
                $validated['start_date'],
                $validated['end_date'],
                $validated['campus_id'] ?? null
            );

            return view('visa-processing.report', compact('report'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Delete visa process
     */
    public function destroy(Candidate $candidate)
    {
        try {
            if (!$candidate->visaProcess) {
                throw new Exception('No visa process found');
            }

            $this->visaService->deleteVisaProcess($candidate->visaProcess->id);

            $candidate->update(['status' => 'training_completed']);

            return redirect()->route('visa-processing.index')
                ->with('success', 'Visa process deleted successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete visa process: ' . $e->getMessage());
        }
    }
}