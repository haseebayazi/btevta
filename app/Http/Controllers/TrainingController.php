<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Services\TrainingService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class TrainingController extends Controller
{
    protected $trainingService;
    protected $notificationService;

    public function __construct(
        TrainingService $trainingService,
        NotificationService $notificationService
    ) {
        $this->trainingService = $trainingService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of candidates in training
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

        $query = Candidate::with(['trade', 'campus', 'batch', 'attendances'])
            ->where('status', 'training');

        // Filter by campus for campus admins
        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        // Apply filters
        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('search')) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('name', 'like', "%{$escapedSearch}%")
                    ->orWhere('cnic', 'like', "%{$escapedSearch}%");
            });
        }

        $candidates = $query->paginate(20);
        $batches = Batch::where('status', 'active')->get();

        return view('training.index', compact('candidates', 'batches'));
    }

    /**
     * Show form to create training batch assignment
     */
    public function create()
    {
        $this->authorize('create', Candidate::class);

        $batches = Batch::where('status', 'active')->get();
        $candidates = Candidate::whereIn('status', ['screening_passed', 'registered'])
            ->with(['trade', 'campus'])
            ->get();

        return view('training.create', compact('batches', 'candidates'));
    }

    /**
     * Assign candidates to training batch
     */
    public function store(Request $request)
    {
        $this->authorize('create', Candidate::class);

        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'exists:candidates,id',
            'training_start_date' => 'required|date',
            'training_end_date' => 'required|date|after:training_start_date',
        ]);

        try {
            $batch = $this->trainingService->assignCandidatesToBatch(
                $validated['batch_id'],
                $validated['candidate_ids'],
                $validated['training_start_date'],
                $validated['training_end_date']
            );

            // PERFORMANCE: Load all candidates at once instead of N+1 queries
            $candidates = Candidate::whereIn('id', $validated['candidate_ids'])->get();
            foreach ($candidates as $candidate) {
                $this->notificationService->sendTrainingAssigned($candidate, $batch);
            }

            return redirect()->route('training.index')
                ->with('success', count($validated['candidate_ids']) . ' candidates assigned to training successfully!');
        } catch (Exception $e) {
            // SECURITY: Log exception details, show generic message to user
            Log::error('Training assignment failed', ['error' => $e->getMessage()]);
            return back()->withInput()
                ->with('error', 'Failed to assign candidates. Please try again.');
        }
    }

    /**
     * Display candidate training details
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $candidate->load([
            'trade',
            'campus',
            'batch',
            'attendances' => function ($query) {
                $query->orderBy('date', 'desc');
            },
            'assessments',
            'certificate'
        ]);

        // Get attendance statistics
        $attendanceStats = $this->trainingService->getAttendanceStatistics($candidate->id);

        return view('training.show', compact('candidate', 'attendanceStats'));
    }

    /**
     * Show form to edit training assignment
     */
    public function edit(Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $batches = Batch::where('status', 'active')->get();
        $candidate->load('batch');

        return view('training.edit', compact('candidate', 'batches'));
    }

    /**
     * Update training assignment
     */
    public function update(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
        ]);

        try {
            $this->trainingService->transferCandidateToBatch(
                $candidate->id,
                $validated['batch_id']
            );

            return redirect()->route('training.show', $candidate)
                ->with('success', 'Training batch updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update batch: ' . $e->getMessage());
        }
    }

    /**
     * Show attendance marking form
     */
    public function attendance(Request $request)
    {
        $this->authorize('viewAttendance', Candidate::class);

        $batches = Batch::where('status', 'active')->with('candidates')->get();
        $selectedBatch = null;
        $date = $request->get('date', now()->toDateString());

        if ($request->filled('batch_id')) {
            $selectedBatch = Batch::with(['candidates' => function ($query) use ($date) {
                $query->with(['attendances' => function ($q) use ($date) {
                    $q->where('date', $date);
                }]);
            }])->findOrFail($request->batch_id);
        }

        return view('training.attendance', compact('batches', 'selectedBatch', 'date'));
    }

    /**
     * Mark attendance for a candidate
     */
    public function markAttendance(Request $request, Candidate $candidate)
    {
        $this->authorize('markAttendance', Candidate::class);

        $validated = $request->validate([
            'date' => 'required|date',
            'status' => 'required|in:present,absent,leave',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            $attendance = $this->trainingService->markAttendance(
                $candidate->id,
                $validated['date'],
                $validated['status'],
                $validated['remarks'] ?? null
            );

            return back()->with('success', 'Attendance marked successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to mark attendance: ' . $e->getMessage());
        }
    }

    /**
     * Bulk mark attendance for batch
     */
    public function bulkAttendance(Request $request)
    {
        $this->authorize('markAttendance', Candidate::class);

        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.candidate_id' => 'required|exists:candidates,id',
            'attendances.*.status' => 'required|in:present,absent,leave',
            'attendances.*.remarks' => 'nullable|string|max:500',
        ]);

        try {
            $this->trainingService->bulkMarkAttendance(
                $validated['batch_id'],
                $validated['date'],
                $validated['attendances']
            );

            return back()->with('success', 'Bulk attendance marked successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to mark bulk attendance: ' . $e->getMessage());
        }
    }

    /**
     * Show assessment form
     */
    public function assessment(Candidate $candidate)
    {
        $this->authorize('createAssessment', Candidate::class);

        $candidate->load(['assessments', 'trade']);

        return view('training.assessment', compact('candidate'));
    }

    /**
     * Store assessment
     */
    public function storeAssessment(Request $request, Candidate $candidate)
    {
        $this->authorize('createAssessment', Candidate::class);

        $validated = $request->validate([
            'assessment_type' => 'required|in:theory,practical,final',
            'assessment_date' => 'required|date',
            'total_marks' => 'required|integer|min:0',
            'obtained_marks' => 'required|integer|min:0|lte:total_marks',
            'grade' => 'required|in:A+,A,B,C,D,F',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $assessment = $this->trainingService->recordAssessment(
                $candidate->id,
                $validated['assessment_type'],
                $validated['assessment_date'],
                $validated['obtained_marks'],
                $validated['total_marks'],
                $validated['grade'],
                $validated['remarks'] ?? null
            );

            return redirect()->route('training.show', $candidate)
                ->with('success', 'Assessment recorded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to record assessment: ' . $e->getMessage());
        }
    }

    /**
     * Update assessment
     */
    public function updateAssessment(Request $request, TrainingAssessment $assessment)
    {
        $this->authorize('updateAssessment', $assessment);

        $validated = $request->validate([
            'obtained_marks' => 'required|integer|min:0',
            'grade' => 'required|in:A+,A,B,C,D,F',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $assessment = $this->trainingService->updateAssessment(
                $assessment->id,
                $validated['obtained_marks'],
                $validated['grade'],
                $validated['remarks'] ?? null
            );

            return back()->with('success', 'Assessment updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to update assessment: ' . $e->getMessage());
        }
    }

    /**
     * Generate certificate
     */
    public function generateCertificate(Request $request, Candidate $candidate)
    {
        $this->authorize('generateCertificate', Candidate::class);

        $validated = $request->validate([
            'certificate_number' => 'required|string|unique:training_certificates,certificate_number',
            'issue_date' => 'required|date',
            'grade' => 'required|in:A+,A,B,C,D',
        ]);

        try {
            $certificate = $this->trainingService->generateCertificate(
                $candidate->id,
                $validated['certificate_number'],
                $validated['issue_date'],
                $validated['grade']
            );

            // Generate PDF
            $pdfPath = $this->trainingService->generateCertificatePDF($certificate->id);

            $this->notificationService->sendCertificateIssued($candidate);

            return redirect()->route('training.show', $candidate)
                ->with('success', 'Certificate generated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate certificate: ' . $e->getMessage());
        }
    }

    /**
     * Download certificate PDF
     */
    public function downloadCertificate(Candidate $candidate)
    {
        $this->authorize('downloadCertificate', Candidate::class);

        try {
            if (!$candidate->certificate) {
                throw new Exception('No certificate found for this candidate');
            }

            $pdfPath = $this->trainingService->generateCertificatePDF($candidate->certificate->id);

            return response()->download(
                storage_path('app/public/' . $pdfPath),
                'Certificate_' . $candidate->name . '.pdf'
            );
        } catch (Exception $e) {
            return back()->with('error', 'Failed to download certificate: ' . $e->getMessage());
        }
    }

    /**
     * Complete training
     */
    public function complete(Candidate $candidate)
    {
        $this->authorize('completeTraining', Candidate::class);

        try {
            $this->trainingService->completeTraining($candidate->id);

            $candidate->update(['status' => 'training_completed']);

            $this->notificationService->sendTrainingCompleted($candidate);

            return redirect()->route('training.index')
                ->with('success', 'Training marked as complete!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to complete training: ' . $e->getMessage());
        }
    }

    /**
     * Get attendance report
     */
    public function attendanceReport(Request $request)
    {
        $this->authorize('viewAttendanceReport', Candidate::class);

        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $report = $this->trainingService->generateAttendanceReport(
                $validated['batch_id'],
                $validated['start_date'],
                $validated['end_date']
            );

            $batch = Batch::find($validated['batch_id']);

            return view('training.attendance-report', compact('report', 'batch'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Get assessment report
     */
    public function assessmentReport(Request $request)
    {
        $this->authorize('viewAssessmentReport', Candidate::class);

        $validated = $request->validate([
            'batch_id' => 'nullable|exists:batches,id',
            'assessment_type' => 'nullable|in:theory,practical,final',
        ]);

        try {
            $report = $this->trainingService->generateAssessmentReport(
                $validated['batch_id'] ?? null,
                $validated['assessment_type'] ?? null
            );

            return view('training.assessment-report', compact('report'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Get batch performance
     */
    public function batchPerformance(Batch $batch)
    {
        $this->authorize('viewBatchPerformance', Candidate::class);

        try {
            $performance = $this->trainingService->getBatchPerformance($batch->id);

            return view('training.batch-performance', compact('batch', 'performance'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch performance data: ' . $e->getMessage());
        }
    }

    /**
     * Remove candidate from training
     */
    public function destroy(Candidate $candidate)
    {
        $this->authorize('delete', $candidate);

        try {
            $this->trainingService->removeCandidateFromTraining($candidate->id);

            $candidate->update([
                'status' => 'screening_passed',
                'batch_id' => null
            ]);

            return redirect()->route('training.index')
                ->with('success', 'Candidate removed from training!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to remove candidate: ' . $e->getMessage());
        }
    }
}