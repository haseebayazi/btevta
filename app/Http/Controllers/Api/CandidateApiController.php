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
            'data' => $candidates,
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
            'name' => 'required|string|max:255',
            'cnic' => 'required|string|size:13|unique:candidates,cnic',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string',
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

        if (auth()->user()->isCampusAdmin()) {
            $query->where('campus_id', auth()->user()->campus_id);
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
