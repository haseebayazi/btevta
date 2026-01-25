<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateScreening;
use App\Models\Candidate;
use App\Http\Resources\ScreeningResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ScreeningApiController extends Controller
{
    /**
     * List all screenings with filters
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CandidateScreening::class);

        $query = CandidateScreening::with(['candidate.campus', 'candidate.oep']);

        // Apply campus filtering for campus admin users
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $request->campus_id));
        }

        if ($request->filled('oep_id')) {
            $query->whereHas('candidate', fn($q) => $q->where('oep_id', $request->oep_id));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 15);
        $screenings = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ScreeningResource::collection($screenings),
            'meta' => [
                'current_page' => $screenings->currentPage(),
                'last_page' => $screenings->lastPage(),
                'per_page' => $screenings->perPage(),
                'total' => $screenings->total(),
            ],
        ]);
    }

    /**
     * Show specific screening
     */
    public function show(Request $request, $id): JsonResponse
    {
        $screening = CandidateScreening::with(['candidate.campus', 'candidate.oep'])->findOrFail($id);

        $this->authorize('view', $screening);

        return response()->json([
            'success' => true,
            'data' => new ScreeningResource($screening),
        ]);
    }

    /**
     * Get screenings for a specific candidate
     */
    public function byCandidate(Request $request, $candidateId): JsonResponse
    {
        $candidate = Candidate::findOrFail($candidateId);

        $this->authorize('viewAny', CandidateScreening::class);

        $screenings = CandidateScreening::where('candidate_id', $candidateId)
            ->with(['candidate.campus', 'candidate.oep'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => ScreeningResource::collection($screenings),
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'btevta_id' => $candidate->btevta_id,
            ],
        ]);
    }

    /**
     * Get screening statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CandidateScreening::class);

        $baseQuery = CandidateScreening::query();

        // Apply campus filtering for campus admin users
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $baseQuery->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
        }

        // Apply date filters if provided
        if ($request->filled('from_date')) {
            $baseQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $baseQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $stats = [
            'total_screenings' => (clone $baseQuery)->count(),
            'pending' => Candidate::where('status', 'screening')
                ->when($user->isCampusAdmin() && $user->campus_id, fn($q) => $q->where('campus_id', $user->campus_id))
                ->count(),
            'completed' => (clone $baseQuery)->whereNotNull('completed_at')->count(),
            'by_outcome' => (clone $baseQuery)->whereNotNull('outcome')
                ->select('outcome', DB::raw('count(*) as count'))
                ->groupBy('outcome')
                ->get()
                ->pluck('count', 'outcome'),
            'completed_today' => (clone $baseQuery)->whereDate('completed_at', today())->count(),
            'completed_this_week' => (clone $baseQuery)->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'completed_this_month' => (clone $baseQuery)->whereMonth('completed_at', now()->month)->count(),
        ];

        // Calculate eligibility rate
        $totalWithOutcome = $stats['by_outcome']->sum();
        $stats['eligibility_rate'] = $totalWithOutcome > 0
            ? round(($stats['by_outcome']->get('eligible', 0) / $totalWithOutcome) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get pending screenings
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CandidateScreening::class);

        $query = Candidate::where('status', 'screening')
            ->with(['campus', 'oep']);

        // Apply campus filtering
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $candidates = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $candidates->items(),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
        ]);
    }

    /**
     * Create screening record
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'screening_date' => 'required|date',
            'screener_name' => 'required|string|max:255',
            'contact_method' => 'required|in:phone,video_call,in_person',
            'outcome' => 'required|in:eligible,not_eligible,pending',
            'remarks' => 'nullable|string',
            'next_steps' => 'nullable|string',
        ]);

        $screening = CandidateScreening::create(array_merge($validated, [
            'status' => 'completed',
            'completed_at' => now(),
        ]));

        // Update candidate status based on outcome
        if ($validated['outcome'] === 'eligible') {
            $screening->candidate->update(['status' => 'registration']);
        } elseif ($validated['outcome'] === 'not_eligible') {
            $screening->candidate->update(['status' => 'rejected']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Screening record created successfully',
            'data' => new ScreeningResource($screening->load(['candidate.campus', 'candidate.oep'])),
        ], 201);
    }

    /**
     * Update screening record
     */
    public function update(Request $request, $id): JsonResponse
    {
        $screening = CandidateScreening::findOrFail($id);

        $this->authorize('update', $screening);

        $validated = $request->validate([
            'screening_date' => 'sometimes|date',
            'screener_name' => 'sometimes|string|max:255',
            'contact_method' => 'sometimes|in:phone,video_call,in_person',
            'outcome' => 'sometimes|in:eligible,not_eligible,pending',
            'remarks' => 'nullable|string',
            'next_steps' => 'nullable|string',
        ]);

        $screening->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Screening record updated successfully',
            'data' => new ScreeningResource($screening->load(['candidate.campus', 'candidate.oep'])),
        ]);
    }

    /**
     * Record desk screening
     */
    public function recordDeskScreening(Request $request, Candidate $candidate): JsonResponse
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string',
        ]);

        $screening = CandidateScreening::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'screening_type' => CandidateScreening::TYPE_DESK,
            ],
            [
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
                'screened_at' => now(),
                'screened_by' => auth()->id(),
            ]
        );

        // Auto-reject candidate if desk screening fails
        if ($validated['status'] === 'failed') {
            $candidate->update(['status' => Candidate::STATUS_REJECTED]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Desk screening recorded successfully',
            'data' => new ScreeningResource($screening->load(['candidate'])),
        ]);
    }

    /**
     * Record call screening
     */
    public function recordCallScreening(Request $request, Candidate $candidate): JsonResponse
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'call_duration' => 'nullable|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        $screening = CandidateScreening::where('candidate_id', $candidate->id)
            ->where('screening_type', CandidateScreening::TYPE_CALL)
            ->first();

        if ($screening) {
            // Update existing call screening
            $screening->update([
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
                'call_duration' => $validated['call_duration'] ?? 0,
                'call_count' => $screening->call_count + 1,
                'screened_at' => now(),
                'screened_by' => auth()->id(),
            ]);
        } else {
            // Create new call screening
            $screening = CandidateScreening::create([
                'candidate_id' => $candidate->id,
                'screening_type' => CandidateScreening::TYPE_CALL,
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
                'call_duration' => $validated['call_duration'] ?? 0,
                'call_count' => 1,
                'screened_at' => now(),
                'screened_by' => auth()->id(),
            ]);
        }

        // Check if max attempts reached
        if ($screening->call_count >= 3 && $validated['status'] !== 'passed') {
            $screening->update(['status' => 'failed']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Call screening recorded successfully',
            'data' => new ScreeningResource($screening->load(['candidate'])),
        ]);
    }

    /**
     * Record physical screening
     */
    public function recordPhysicalScreening(Request $request, Candidate $candidate): JsonResponse
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string',
        ]);

        $screening = CandidateScreening::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'screening_type' => CandidateScreening::TYPE_PHYSICAL,
            ],
            [
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
                'screened_at' => now(),
                'screened_by' => auth()->id(),
            ]
        );

        // Auto-progress candidate when all screenings pass
        $this->checkAndProgressCandidate($candidate);

        return response()->json([
            'success' => true,
            'message' => 'Physical screening recorded successfully',
            'data' => new ScreeningResource($screening->load(['candidate'])),
        ]);
    }

    /**
     * Upload evidence for screening
     */
    public function uploadEvidence(Request $request, Candidate $candidate): JsonResponse
    {
        $validated = $request->validate([
            'screening_id' => 'required|exists:candidate_screenings,id',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $screening = CandidateScreening::findOrFail($validated['screening_id']);

        $this->authorize('update', $screening);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('screening-evidence', 'public');
            $screening->update(['evidence_path' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Evidence uploaded successfully',
            'data' => new ScreeningResource($screening->load(['candidate'])),
        ]);
    }

    /**
     * Get screening progress for a candidate
     */
    public function getProgress(Request $request, Candidate $candidate): JsonResponse
    {
        $this->authorize('viewAny', CandidateScreening::class);

        $screenings = $candidate->screenings;

        $deskStatus = $screenings->where('screening_type', 'desk')->first()?->status ?? 'not_started';
        $callStatus = $screenings->where('screening_type', 'call')->first()?->status ?? 'not_started';
        $physicalStatus = $screenings->where('screening_type', 'physical')->first()?->status ?? 'not_started';

        $passedCount = collect([$deskStatus, $callStatus, $physicalStatus])
            ->filter(fn($status) => $status === 'passed')
            ->count();

        $totalRequired = 3;
        $isComplete = $passedCount === $totalRequired;
        $progressPercentage = round(($passedCount / $totalRequired) * 100, 2);

        return response()->json([
            'success' => true,
            'screenings' => [
                'desk' => $deskStatus,
                'call' => $callStatus,
                'physical' => $physicalStatus,
            ],
            'passed_count' => $passedCount,
            'total_required' => $totalRequired,
            'is_complete' => $isComplete,
            'progress_percentage' => $progressPercentage,
        ]);
    }

    /**
     * Check if all screenings are passed and auto-progress candidate
     */
    protected function checkAndProgressCandidate(Candidate $candidate): void
    {
        $screenings = $candidate->screenings;

        $deskPassed = $screenings->where('screening_type', 'desk')->where('status', 'passed')->isNotEmpty();
        $callPassed = $screenings->where('screening_type', 'call')->where('status', 'passed')->isNotEmpty();
        $physicalPassed = $screenings->where('screening_type', 'physical')->where('status', 'passed')->isNotEmpty();

        if ($deskPassed && $callPassed && $physicalPassed) {
            $candidate->update(['status' => Candidate::STATUS_REGISTERED]);
        }

        // Check if any screening failed
        $anyFailed = $screenings->where('status', 'failed')->isNotEmpty();
        if ($anyFailed) {
            $candidate->update(['status' => Candidate::STATUS_REJECTED]);
        }
    }
}
