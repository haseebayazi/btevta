<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BatchController extends Controller
{
    /**
     * Display a listing of batches.
     */
    public function index()
    {
        $this->authorize('viewAny', Batch::class);

        $batches = Batch::with(['campus', 'trade'])
            ->withCount('candidates')
            ->latest()
            ->paginate(20);

        return view('admin.batches.index', compact('batches'));
    }

    /**
     * Show the form for creating a new batch.
     */
    public function create()
    {
        $this->authorize('create', Batch::class);

        // PERFORMANCE: Use cached dropdown data
        $campuses = Cache::remember('active_campuses', 86400, function () {
            return Campus::where('is_active', true)->pluck('name', 'id');
        });

        $trades = Cache::remember('active_trades', 86400, function () {
            return Trade::where('is_active', true)->pluck('name', 'id');
        });

        $users = Cache::remember('active_trainers', 3600, function () {
            return User::where('role', 'trainer')->where('is_active', true)->pluck('name', 'id');
        });

        return view('admin.batches.create', compact('campuses', 'trades', 'users'));
    }

    /**
     * Store a newly created batch.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Batch::class);

        $validated = $request->validate([
            'batch_code' => 'required|string|max:100|unique:batches,batch_code',
            'name' => 'nullable|string|max:255',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'required|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'trainer_id' => 'nullable|exists:users,id',
            'coordinator_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'intake_period' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:planned,active,completed,cancelled',
        ]);

        try {
            $batch = Batch::create($validated);

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch created');

            return redirect()->route('batches.index')
                ->with('success', 'Batch created successfully!');
        } catch (\Exception $e) {
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
        $users = User::where('role', 'trainer')->where('is_active', true)->pluck('name', 'id');

        return view('admin.batches.edit', compact('batch', 'campuses', 'trades', 'users'));
    }

    /**
     * Update the specified batch.
     */
    public function update(Request $request, Batch $batch)
    {
        $this->authorize('update', $batch);

        $validated = $request->validate([
            'batch_code' => 'required|string|max:100|unique:batches,batch_code,' . $batch->id,
            'name' => 'nullable|string|max:255',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'required|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'trainer_id' => 'nullable|exists:users,id',
            'coordinator_id' => 'nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'capacity' => 'required|integer|min:1',
            'intake_period' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:planned,active,completed,cancelled',
        ]);

        try {
            $batch->update($validated);

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log('Batch updated');

            return redirect()->route('batches.index')
                ->with('success', 'Batch updated successfully!');
        } catch (\Exception $e) {
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
}
