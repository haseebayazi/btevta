<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * API endpoints for Batch Management
 */
class BatchApiController extends Controller
{
    /**
     * Get paginated list of batches
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::with(['campus:id,name', 'trade:id,name', 'oep:id,name'])
            ->withCount('candidates');

        // Apply role-based filtering
        $user = auth()->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        } elseif ($user->isTrainer() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        // Apply OEP filtering for OEP users
        if ($user->oep_id) {
            $query->where('oep_id', $user->oep_id);
        } elseif ($request->filled('oep_id')) {
            $query->where('oep_id', $request->oep_id);
        }

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Campus filter
        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        // Trade filter
        if ($request->filled('trade_id')) {
            $query->where('trade_id', $request->trade_id);
        }

        // District filter
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        // Availability filter
        if ($request->filled('available') && $request->available) {
            $query->available();
        }

        $perPage = min($request->input('per_page', 20), 100);
        $batches = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    /**
     * Get single batch details
     */
    public function show(int $id): JsonResponse
    {
        $batch = Batch::with([
            'campus', 'trade', 'oep', 'trainer', 'coordinator'
        ])->withCount('candidates')->findOrFail($id);

        $this->authorize('view', $batch);

        // Add computed statistics
        $batch->statistics = $batch->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $batch,
        ]);
    }

    /**
     * Create new batch
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Batch::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'batch_code' => 'nullable|string|max:50|unique:batches,batch_code',
            'campus_id' => 'required|exists:campuses,id',
            'trade_id' => 'required|exists:trades,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'capacity' => 'required|integer|min:1|max:500',
            'status' => 'required|in:planned,active,completed,cancelled',
            'description' => 'nullable|string|max:1000',
            'trainer_id' => 'nullable|exists:users,id',
            'coordinator_id' => 'nullable|exists:users,id',
            'district' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'intake_period' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $data['created_by'] = auth()->id();

            $batch = Batch::create($data);

            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch created via API');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch created successfully',
                'data' => $batch->fresh(['campus', 'trade', 'oep']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('API batch creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create batch',
            ], 500);
        }
    }

    /**
     * Update batch
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $batch = Batch::findOrFail($id);
        $this->authorize('update', $batch);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'batch_code' => 'sometimes|string|max:50|unique:batches,batch_code,' . $id,
            'campus_id' => 'sometimes|exists:campuses,id',
            'trade_id' => 'sometimes|exists:trades,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'capacity' => 'sometimes|integer|min:1|max:500',
            'status' => 'sometimes|in:planned,active,completed,cancelled',
            'description' => 'nullable|string|max:1000',
            'trainer_id' => 'nullable|exists:users,id',
            'coordinator_id' => 'nullable|exists:users,id',
            'district' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'intake_period' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate capacity reduction
        if ($request->has('capacity')) {
            $currentEnrollment = $batch->candidates()->count();
            if ($request->capacity < $currentEnrollment) {
                return response()->json([
                    'success' => false,
                    'errors' => ['capacity' => ["Cannot reduce capacity below current enrollment ({$currentEnrollment} candidates)."]],
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            $data['updated_by'] = auth()->id();

            $batch->update($data);

            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch updated via API');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Batch updated successfully',
                'data' => $batch->fresh(['campus', 'trade', 'oep']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('API batch update failed', [
                'batch_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update batch',
            ], 500);
        }
    }

    /**
     * Delete batch (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $batch = Batch::findOrFail($id);
        $this->authorize('delete', $batch);

        // Check for associated candidates
        $candidatesCount = $batch->candidates()->count();
        if ($candidatesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete batch: {$candidatesCount} candidate(s) are enrolled. Please reassign or remove them first.",
            ], 422);
        }

        // Check for associated training schedules
        $schedulesCount = $batch->trainingSchedules()->count();
        if ($schedulesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete batch: {$schedulesCount} training schedule(s) are associated. Please remove them first.",
            ], 422);
        }

        try {
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch deleted via API');

            $batch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Batch deleted successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('API batch deletion failed', [
                'batch_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete batch',
            ], 500);
        }
    }

    /**
     * Get batch statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $batch = Batch::findOrFail($id);
        $this->authorize('view', $batch);

        $statistics = $batch->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get candidates in a batch
     */
    public function candidates(Request $request, int $id): JsonResponse
    {
        $batch = Batch::findOrFail($id);
        $this->authorize('view', $batch);

        $query = $batch->candidates()
            ->with(['trade:id,name', 'campus:id,name']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('btevta_id', 'like', "%{$search}%")
                  ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('training_status')) {
            $query->where('training_status', $request->training_status);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $candidates = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $candidates,
        ]);
    }

    /**
     * Bulk assign candidates to batch
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'required|integer|exists:candidates,id',
            'batch_id' => 'required|integer|exists:batches,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $batch = Batch::findOrFail($request->batch_id);
        $this->authorize('assignCandidates', $batch);

        // Check batch capacity
        $candidateCount = count($request->candidate_ids);
        if (!$batch->canAddCandidates($candidateCount)) {
            return response()->json([
                'success' => false,
                'message' => "Batch capacity exceeded. Available slots: {$batch->available_slots}, Requested: {$candidateCount}",
            ], 422);
        }

        try {
            DB::beginTransaction();

            Candidate::whereIn('id', $request->candidate_ids)
                ->update([
                    'batch_id' => $batch->id,
                    'training_status' => 'enrolled',
                    'batch_assigned_date' => now(),
                    'updated_by' => auth()->id(),
                ]);

            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->withProperties(['candidate_count' => $candidateCount])
                ->log('Bulk assigned ' . $candidateCount . ' candidate(s) to batch via API');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$candidateCount} candidate(s) assigned to batch successfully",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('API bulk batch assignment failed', [
                'error' => $e->getMessage(),
                'batch_id' => $request->batch_id ?? null,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign candidates to batch',
            ], 500);
        }
    }

    /**
     * Change batch status
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $batch = Batch::findOrFail($id);
        $this->authorize('update', $batch);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:planned,active,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $oldStatus = $batch->status;
            $batch->update(['status' => $request->status]);

            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log("Batch status changed from {$oldStatus} to {$request->status} via API");

            return response()->json([
                'success' => true,
                'message' => 'Batch status updated successfully',
                'data' => $batch->fresh(),
            ]);
        } catch (\Exception $e) {
            \Log::error('API batch status change failed', [
                'batch_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update batch status',
            ], 500);
        }
    }

    /**
     * Get batches by campus
     */
    public function byCampus(int $campusId): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $batches = Batch::where('campus_id', $campusId)
            ->where('status', 'active')
            ->with(['trade:id,name'])
            ->withCount('candidates')
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    /**
     * Get active batches (for dropdowns)
     */
    public function active(): JsonResponse
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::where('status', 'active')
            ->with(['campus:id,name', 'trade:id,name'])
            ->withCount('candidates');

        // Apply role-based filtering
        $user = auth()->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        if ($user->oep_id) {
            $query->where('oep_id', $user->oep_id);
        }

        $batches = $query->orderBy('start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }
}
