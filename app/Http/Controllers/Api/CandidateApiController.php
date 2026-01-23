<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AUDIT FIX: API-001 - Added missing Candidate CRUD API endpoints
 */
class CandidateApiController extends Controller
{
    /**
     * Get paginated list of candidates
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Candidate::class);

        $query = Candidate::with(['trade:id,name', 'campus:id,name', 'batch:id,name']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by campus (for campus admins)
        if (auth()->user()->isCampusAdmin()) {
            $query->where('campus_id', auth()->user()->campus_id);
        } elseif ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        // Filter by OEP
        if (auth()->user()->isOep()) {
            $query->where('oep_id', auth()->user()->oep_id);
        } elseif ($request->filled('oep_id')) {
            $query->where('oep_id', $request->oep_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('btevta_id', 'like', "%{$search}%")
                    ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 20), 100);
        $candidates = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $candidates->items(),
            'pagination' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ],
        ]);
    }

    /**
     * Get single candidate details
     */
    public function show(int $id): JsonResponse
    {
        $candidate = Candidate::with([
            'trade', 'campus', 'batch', 'oep', 'visaPartner',
            'screenings', 'departure', 'visaProcess', 'nextOfKin'
        ])->findOrFail($id);

        $this->authorize('view', $candidate);

        return response()->json([
            'success' => true,
            'data' => $candidate,
        ]);
    }

    /**
     * Create new candidate
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Candidate::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|regex:/^(?!\s+$).+/',
            'cnic' => 'required|string|size:13|unique:candidates,cnic|regex:/^[0-9]{13}$/|not_in:0000000000000',
            'phone' => 'required|string|max:20|regex:/^(\\+?92[-\\s]?)?0?3[0-9]{2}[-\\s]?[0-9]{7}$/',
            'email' => 'nullable|email|max:255',
            'father_name' => 'required|string|max:255|regex:/^(?!\s+$).+/',
            'date_of_birth' => 'required|date|before:today|after:1930-01-01|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string|max:1000',
            'district' => 'required|string|max:100',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $data['btevta_id'] = Candidate::generateBtevtaId();
            $data['status'] = Candidate::STATUS_NEW;
            $data['created_by'] = auth()->id();

            $candidate = Candidate::create($data);

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log('Candidate created via API');

            return response()->json([
                'success' => true,
                'message' => 'Candidate created successfully',
                'data' => $candidate,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Candidate creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create candidate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update candidate
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);
        $this->authorize('update', $candidate);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'sometimes|string',
            'district' => 'sometimes|string|max:100',
            'campus_id' => 'nullable|exists:campuses,id',
            'batch_id' => 'nullable|exists:batches,id',
            'oep_id' => 'nullable|exists:oeps,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // AUDIT FIX: Validate campus_id/batch_id/oep_id assignment based on user role
        $user = auth()->user();

        // Campus admins cannot reassign candidates to different campuses
        if ($user->role === 'campus_admin' && $user->campus_id) {
            if (isset($data['campus_id']) && $data['campus_id'] != $user->campus_id) {
                return response()->json([
                    'success' => false,
                    'errors' => ['campus_id' => ['You cannot reassign candidates to a different campus.']],
                ], 403);
            }
            // If campus_id is being set, force it to user's campus
            if (isset($data['campus_id'])) {
                $data['campus_id'] = $user->campus_id;
            }
        }

        // OEP users cannot reassign candidates to different OEPs
        if ($user->role === 'oep' && $user->oep_id) {
            if (isset($data['oep_id']) && $data['oep_id'] != $user->oep_id) {
                return response()->json([
                    'success' => false,
                    'errors' => ['oep_id' => ['You cannot reassign candidates to a different OEP.']],
                ], 403);
            }
        }

        // Validate that batch belongs to candidate's campus if being assigned
        if (isset($data['batch_id']) && $data['batch_id']) {
            $batch = \App\Models\Batch::find($data['batch_id']);
            $targetCampusId = $data['campus_id'] ?? $candidate->campus_id;
            if ($batch && $batch->campus_id != $targetCampusId) {
                return response()->json([
                    'success' => false,
                    'errors' => ['batch_id' => ['The selected batch does not belong to the candidate\'s campus.']],
                ], 422);
            }
        }

        $data['updated_by'] = auth()->id();

        $candidate->update($data);

        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->log('Candidate updated via API');

        return response()->json([
            'success' => true,
            'message' => 'Candidate updated successfully',
            'data' => $candidate->fresh(),
        ]);
    }

    /**
     * Delete candidate (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $candidate = Candidate::findOrFail($id);
        $this->authorize('delete', $candidate);

        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->log('Candidate deleted via API');

        $candidate->delete();

        return response()->json([
            'success' => true,
            'message' => 'Candidate deleted successfully',
        ]);
    }

    /**
     * Get candidate statistics
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Candidate::class);

        $query = Candidate::query();
        $user = auth()->user();

        // AUDIT FIX: Add OEP filtering in addition to campus filtering
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        } elseif ($user->isOep() && $user->oep_id) {
            $query->where('oep_id', $user->oep_id);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = "screening" THEN 1 ELSE 0 END) as screening_count,
            SUM(CASE WHEN status = "registered" THEN 1 ELSE 0 END) as registered_count,
            SUM(CASE WHEN status = "training" THEN 1 ELSE 0 END) as training_count,
            SUM(CASE WHEN status = "visa_process" THEN 1 ELSE 0 END) as visa_count,
            SUM(CASE WHEN status = "departed" THEN 1 ELSE 0 END) as departed_count
        ')->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
