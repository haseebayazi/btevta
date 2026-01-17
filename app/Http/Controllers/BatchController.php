<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Oep;
use App\Models\Trade;
use App\Models\User;
use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Http\Requests\BulkBatchAssignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    /**
     * Display a listing of batches.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Batch::class);

        $query = Batch::with(['campus', 'trade', 'oep'])
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

        $batches = $query->latest()->paginate(20)->withQueryString();

        // Get filter options for dropdowns
        $campuses = Cache::remember('active_campuses', 3600, function () {
            return Campus::where('is_active', true)->pluck('name', 'id');
        });

        $trades = Cache::remember('active_trades', 3600, function () {
            return Trade::where('is_active', true)->pluck('name', 'id');
        });

        $statuses = Batch::getStatuses();

        return view('admin.batches.index', compact('batches', 'campuses', 'trades', 'statuses'));
    }

    /**
     * Show the form for creating a new batch.
     */
    public function create()
    {
        $this->authorize('create', Batch::class);

        // PERFORMANCE: Use cached dropdown data
        $campuses = Cache::remember('active_campuses', 3600, function () {
            return Campus::where('is_active', true)->pluck('name', 'id');
        });

        $trades = Cache::remember('active_trades', 3600, function () {
            return Trade::where('is_active', true)->pluck('name', 'id');
        });

        $oeps = Cache::remember('active_oeps', 3600, function () {
            return Oep::where('is_active', true)->pluck('name', 'id');
        });

        // Get trainers and staff who can be assigned to batches
        $users = User::whereIn('role', ['trainer', 'campus_admin', 'admin'])
            ->where('is_active', true)
            ->pluck('name', 'id');

        $statuses = Batch::getStatuses();

        return view('admin.batches.create', compact('campuses', 'trades', 'oeps', 'users', 'statuses'));
    }

    /**
     * Store a newly created batch.
     */
    public function store(StoreBatchRequest $request)
    {
        try {
            DB::beginTransaction();

            $batch = Batch::create($request->validated());

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch created');

            DB::commit();

            return redirect()->route('admin.batches.index')
                ->with('success', 'Batch created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // SECURITY: Log exception details, show generic message to user
            \Log::error('Batch creation failed', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);

            return back()->withInput()
                ->with('error', 'Failed to create batch. Please try again or contact support.');
        }
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch)
    {
        $this->authorize('view', $batch);

        $batch->load(['campus', 'trade', 'trainer', 'coordinator', 'candidates' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.batches.show', compact('batch'));
    }

    /**
     * Show the form for editing the batch.
     */
    public function edit(Batch $batch)
    {
        $this->authorize('update', $batch);

        $campuses = Campus::where('is_active', true)->pluck('name', 'id');
        $trades = Trade::where('is_active', true)->pluck('name', 'id');
        $oeps = Oep::where('is_active', true)->pluck('name', 'id');

        // Get trainers and staff who can be assigned to batches
        $users = User::whereIn('role', ['trainer', 'campus_admin', 'admin'])
            ->where('is_active', true)
            ->pluck('name', 'id');

        $statuses = Batch::getStatuses();

        return view('admin.batches.edit', compact('batch', 'campuses', 'trades', 'oeps', 'users', 'statuses'));
    }

    /**
     * Update the specified batch.
     */
    public function update(UpdateBatchRequest $request, Batch $batch)
    {
        try {
            DB::beginTransaction();

            $batch->update($request->validated());

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch updated');

            DB::commit();

            return redirect()->route('admin.batches.index')
                ->with('success', 'Batch updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // SECURITY: Log exception details, show generic message to user
            \Log::error('Batch update failed', ['batch_id' => $batch->id, 'error' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Failed to update batch. Please try again or contact support.');
        }
    }

    /**
     * Remove the specified batch.
     */
    public function destroy(Batch $batch)
    {
        $this->authorize('delete', $batch);

        try {
            // Check for associated candidates
            $candidatesCount = $batch->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete batch: {$candidatesCount} candidate(s) are enrolled in this batch. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated training schedules
            $schedulesCount = $batch->trainingSchedules()->count();
            if ($schedulesCount > 0) {
                return back()->with('error',
                    "Cannot delete batch: {$schedulesCount} training schedule(s) are associated with this batch. " .
                    "Please remove them first."
                );
            }

            $batchCode = $batch->batch_code;
            $batch->delete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log("Batch deleted: {$batchCode}");

            return back()->with('success', 'Batch deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete batch: ' . $e->getMessage());
        }
    }

    /**
     * Show candidates assigned to the batch.
     */
    public function candidates(Request $request, Batch $batch)
    {
        $this->authorize('view', $batch);

        $query = $batch->candidates()
            ->with(['trade', 'campus']);

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

        $candidates = $query->latest()->paginate(20)->withQueryString();

        return view('admin.batches.candidates', compact('batch', 'candidates'));
    }

    /**
     * Bulk assign candidates to batch.
     */
    public function bulkAssign(BulkBatchAssignRequest $request)
    {
        try {
            DB::beginTransaction();

            $batch = Batch::findOrFail($request->batch_id);
            $candidateIds = $request->candidate_ids;

            // Assign candidates to batch
            Candidate::whereIn('id', $candidateIds)
                ->update([
                    'batch_id' => $batch->id,
                    'training_status' => 'enrolled',
                    'batch_assigned_date' => now(),
                    'updated_by' => auth()->id(),
                ]);

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->withProperties(['candidate_count' => count($candidateIds)])
                ->log('Bulk assigned ' . count($candidateIds) . ' candidate(s) to batch');

            DB::commit();

            return back()->with('success', count($candidateIds) . ' candidate(s) assigned to batch successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Bulk batch assignment failed', [
                'error' => $e->getMessage(),
                'batch_id' => $request->batch_id ?? null,
                'user_id' => auth()->id()
            ]);

            return back()->with('error', 'Failed to assign candidates to batch. Please try again.');
        }
    }

    /**
     * Get batch statistics.
     */
    public function statistics(Batch $batch)
    {
        $this->authorize('view', $batch);

        $statistics = $batch->getStatistics();

        return view('admin.batches.statistics', compact('batch', 'statistics'));
    }

    /**
     * Change batch status.
     */
    public function changeStatus(Request $request, Batch $batch)
    {
        $this->authorize('changeStatus', $batch);

        $validated = $request->validate([
            'status' => 'required|in:planned,active,completed,cancelled',
        ]);

        try {
            $oldStatus = $batch->status;
            $batch->update(['status' => $validated['status']]);

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log("Batch status changed from {$oldStatus} to {$validated['status']}");

            return back()->with('success', 'Batch status updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update batch status: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to get list of active batches.
     */
    public function apiList()
    {
        $this->authorize('apiList', Batch::class);

        try {
            $batches = Batch::where('status', 'active')
                ->with(['campus:id,name', 'trade:id,name'])
                ->select('id', 'batch_code', 'name', 'campus_id', 'trade_id', 'capacity', 'start_date', 'end_date')
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $batches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch batches'
            ], 500);
        }
    }

    /**
     * API endpoint to get batches by campus.
     */
    public function byCampus(Campus $campus)
    {
        $this->authorize('byCampus', Batch::class);

        try {
            $batches = Batch::where('campus_id', $campus->id)
                ->where('status', 'active')
                ->with(['trade:id,name'])
                ->select('id', 'batch_code', 'name', 'trade_id', 'capacity', 'start_date', 'end_date')
                ->orderBy('start_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $batches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch batches for campus'
            ], 500);
        }
    }
}
