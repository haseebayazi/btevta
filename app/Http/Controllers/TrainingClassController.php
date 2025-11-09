<?php

namespace App\Http\Controllers;

use App\Models\TrainingClass;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Instructor;
use App\Models\Batch;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingClassController extends Controller
{
    /**
     * Display a listing of training classes
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TrainingClass::class);

        $classes = TrainingClass::with(['campus', 'trade', 'instructor', 'batch'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('class_name', 'like', '%' . $request->search . '%')
                    ->orWhere('class_code', 'like', '%' . $request->search . '%');
            })
            ->when($request->campus_id, function ($query) use ($request) {
                $query->where('campus_id', $request->campus_id);
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(15);

        $campuses = Campus::all();

        return view('classes.index', compact('classes', 'campuses'));
    }

    /**
     * Show the form for creating a new class
     */
    public function create()
    {
        $this->authorize('create', TrainingClass::class);

        $campuses = Campus::all();
        $trades = Trade::all();
        $instructors = Instructor::active()->get();
        $batches = Batch::all();

        return view('classes.create', compact('campuses', 'trades', 'instructors', 'batches'));
    }

    /**
     * Store a newly created class in storage
     */
    public function store(Request $request)
    {
        $this->authorize('create', TrainingClass::class);

        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'class_code' => 'nullable|string|max:50|unique:training_classes,class_code',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'nullable|exists:trades,id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'batch_id' => 'nullable|exists:batches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_capacity' => 'required|integer|min:1',
            'room_number' => 'nullable|string',
            'schedule' => 'nullable|string',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        try {
            $class = TrainingClass::create($validated);

            activity()
                ->performedOn($class)
                ->causedBy(auth()->user())
                ->log('Training class created');

            return redirect()->route('classes.index')->with('success', 'Training class created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create training class: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified class
     */
    public function show(TrainingClass $class)
    {
        $this->authorize('view', $class);

        $class->load(['campus', 'trade', 'instructor', 'batch', 'candidates']);

        return view('classes.show', compact('class'));
    }

    /**
     * Show the form for editing the specified class
     */
    public function edit(TrainingClass $class)
    {
        $this->authorize('update', $class);

        $campuses = Campus::all();
        $trades = Trade::all();
        $instructors = Instructor::active()->get();
        $batches = Batch::all();

        return view('classes.edit', compact('class', 'campuses', 'trades', 'instructors', 'batches'));
    }

    /**
     * Update the specified class in storage
     */
    public function update(Request $request, TrainingClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'class_name' => 'required|string|max:255',
            'class_code' => 'nullable|string|max:50|unique:training_classes,class_code,' . $class->id,
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'nullable|exists:trades,id',
            'instructor_id' => 'nullable|exists:instructors,id',
            'batch_id' => 'nullable|exists:batches,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'max_capacity' => 'required|integer|min:1',
            'room_number' => 'nullable|string',
            'schedule' => 'nullable|string',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        try {
            $class->update($validated);

            activity()
                ->performedOn($class)
                ->causedBy(auth()->user())
                ->log('Training class updated');

            return redirect()->route('classes.index')->with('success', 'Training class updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update training class: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified class from storage
     */
    public function destroy(TrainingClass $class)
    {
        $this->authorize('delete', $class);

        try {
            $className = $class->class_name;
            $class->delete();

            activity()
                ->causedBy(auth()->user())
                ->log("Training class deleted: {$className}");

            return redirect()->route('classes.index')->with('success', 'Training class deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete training class: ' . $e->getMessage());
        }
    }

    /**
     * Assign candidates to class
     */
    public function assignCandidates(Request $request, TrainingClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        try {
            // FIXED: Wrap in transaction to ensure all or nothing assignment
            DB::beginTransaction();

            $assignedCount = 0;
            $errors = [];

            foreach ($validated['candidate_ids'] as $candidateId) {
                try {
                    $class->enrollCandidate($candidateId);
                    $assignedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Candidate ID {$candidateId}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return back()->with('error', 'Failed to assign some candidates: ' . implode(', ', $errors));
            }

            activity()
                ->performedOn($class)
                ->causedBy(auth()->user())
                ->withProperties(['assigned_count' => $assignedCount])
                ->log("Assigned {$assignedCount} candidate(s) to class");

            DB::commit();

            return back()->with('success', "Successfully assigned {$assignedCount} candidate(s) to class!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign candidates: ' . $e->getMessage());
        }
    }

    /**
     * Remove candidate from class
     */
    public function removeCandidate(TrainingClass $class, Candidate $candidate)
    {
        $this->authorize('update', $class);

        try {
            $class->removeCandidate($candidate->id);

            activity()
                ->performedOn($class)
                ->causedBy(auth()->user())
                ->withProperties(['candidate_id' => $candidate->id, 'candidate_name' => $candidate->name])
                ->log('Removed candidate from class');

            return back()->with('success', 'Candidate removed from class successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to remove candidate: ' . $e->getMessage());
        }
    }
}
