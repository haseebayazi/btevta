<?php

namespace App\Http\Controllers;

use App\Models\TrainingClass;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Instructor;
use App\Models\Batch;
use App\Models\Candidate;
use Illuminate\Http\Request;

class TrainingClassController extends Controller
{
    /**
     * Display a listing of training classes
     */
    public function index(Request $request)
    {
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

        TrainingClass::create($validated);

        return redirect()->route('classes.index')->with('success', 'Training class created successfully!');
    }

    /**
     * Display the specified class
     */
    public function show(TrainingClass $class)
    {
        $class->load(['campus', 'trade', 'instructor', 'batch', 'candidates']);

        return view('classes.show', compact('class'));
    }

    /**
     * Show the form for editing the specified class
     */
    public function edit(TrainingClass $class)
    {
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

        $class->update($validated);

        return redirect()->route('classes.index')->with('success', 'Training class updated successfully!');
    }

    /**
     * Remove the specified class from storage
     */
    public function destroy(TrainingClass $class)
    {
        $class->delete();

        return redirect()->route('classes.index')->with('success', 'Training class deleted successfully!');
    }

    /**
     * Assign candidates to class
     */
    public function assignCandidates(Request $request, TrainingClass $class)
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        foreach ($validated['candidate_ids'] as $candidateId) {
            try {
                $class->enrollCandidate($candidateId);
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return back()->with('success', 'Candidates assigned successfully!');
    }

    /**
     * Remove candidate from class
     */
    public function removeCandidate(TrainingClass $class, Candidate $candidate)
    {
        $class->removeCandidate($candidate->id);

        return back()->with('success', 'Candidate removed from class successfully!');
    }
}
