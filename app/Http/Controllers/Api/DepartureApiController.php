<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\CandidateStatus;
use App\Models\Departure;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * AUDIT FIX: API-001 - Added missing Departure API endpoints
 */
class DepartureApiController extends Controller
{
    /**
     * Get paginated list of departures
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Departure::class);

        $query = Departure::with(['candidate:id,name,btevta_id,cnic']);

        // Filter by campus (for campus admins)
        if (auth()->user()->isCampusAdmin()) {
            $query->whereHas('candidate', function ($q) {
                $q->where('campus_id', auth()->user()->campus_id);
            });
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('departure_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('departure_date', '<=', $request->to_date);
        }

        // Filter by compliance status
        if ($request->filled('is_compliant')) {
            $complianceValue = $request->boolean('is_compliant') ? 'compliant' : 'non_compliant';
            $query->where('ninety_day_compliance_status', $complianceValue);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $departures = $query->orderBy('departure_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $departures,
        ]);
    }

    /**
     * Get single departure details
     */
    public function show(int $id): JsonResponse
    {
        $departure = Departure::with(['candidate'])->findOrFail($id);
        $this->authorize('view', $departure);

        return response()->json([
            'success' => true,
            'data' => $departure,
        ]);
    }

    /**
     * Record new departure
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Departure::class);

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'departure_date' => 'required|date',
            'flight_number' => 'nullable|string|max:50',
            'destination' => 'required|string|max:100',
            'employer_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check candidate is ready for departure
        $candidate = Candidate::findOrFail($request->candidate_id);
        if ($candidate->status !== CandidateStatus::READY_TO_DEPART->value) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate must be in "ready_to_depart" status for departure',
            ], 422);
        }

        $data = $validator->validated();
        $data['created_by'] = auth()->id();

        // AUDIT FIX: Wrap departure creation and status update in transaction
        try {
            DB::beginTransaction();

            $departure = Departure::create($data);

            // Update candidate status
            $candidate->update(['status' => CandidateStatus::DEPARTED->value]);

            DB::commit();

            activity()
                ->performedOn($departure)
                ->causedBy(auth()->user())
                ->log('Departure recorded via API');

            return response()->json([
                'success' => true,
                'message' => 'Departure recorded successfully',
                'data' => $departure,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record departure: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update departure details
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $departure = Departure::findOrFail($id);
        $this->authorize('update', $departure);

        $validator = Validator::make($request->all(), [
            'iqama_number' => 'nullable|string|max:50',
            'iqama_issue_date' => 'nullable|date',
            'iqama_expiry_date' => 'nullable|date|after:iqama_issue_date',
            'absher_registered' => 'nullable|boolean',
            'wps_registered' => 'nullable|boolean',
            'first_salary_date' => 'nullable|date',
            'first_salary_amount' => 'nullable|numeric|min:0',
            'ninety_day_compliance_status' => 'nullable|string|in:pending,compliant,non_compliant,partial',
            'remarks' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $departure->update($validator->validated());

        activity()
            ->performedOn($departure)
            ->causedBy(auth()->user())
            ->log('Departure updated via API');

        return response()->json([
            'success' => true,
            'message' => 'Departure updated successfully',
            'data' => $departure->fresh(),
        ]);
    }

    /**
     * Get departure statistics
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Departure::class);

        // AUDIT FIX: Apply campus/OEP filtering for statistics
        $query = Departure::query();
        $user = auth()->user();

        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
        } elseif ($user->isOep() && $user->oep_id) {
            $query->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
        }

        $stats = $query->selectRaw("
            COUNT(*) as total_departures,
            SUM(CASE WHEN ninety_day_compliance_status = 'compliant' THEN 1 ELSE 0 END) as compliant_count,
            SUM(CASE WHEN iqama_number IS NOT NULL THEN 1 ELSE 0 END) as with_iqama,
            SUM(CASE WHEN absher_registered = 1 THEN 1 ELSE 0 END) as absher_registered,
            SUM(CASE WHEN first_salary_date IS NOT NULL THEN 1 ELSE 0 END) as first_salary_received
        ")->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get departures by candidate
     */
    public function byCandidate(int $candidateId): JsonResponse
    {
        $candidate = Candidate::findOrFail($candidateId);
        $this->authorize('view', $candidate);

        $departure = Departure::where('candidate_id', $candidateId)->first();

        return response()->json([
            'success' => true,
            'data' => $departure,
        ]);
    }
}
