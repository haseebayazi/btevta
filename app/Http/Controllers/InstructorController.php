<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\Campus;
use App\Models\Trade;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * Display a listing of instructors
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Instructor::class);

        $instructors = Instructor::with(['campus', 'trade'])
            ->when($request->search, function ($query) use ($request) {
                // Escape special LIKE characters to prevent SQL LIKE injection
                $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
                $query->where('name', 'like', '%' . $escapedSearch . '%')
                    ->orWhere('cnic', 'like', '%' . $escapedSearch . '%')
                    ->orWhere('email', 'like', '%' . $escapedSearch . '%');
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

        return view('instructors.index', compact('instructors', 'campuses'));
    }

    /**
     * Show the form for creating a new instructor
     */
    public function create()
    {
        $this->authorize('create', Instructor::class);

        $campuses = Campus::all();
        $trades = Trade::all();

        return view('instructors.create', compact('campuses', 'trades'));
    }

    /**
     * Store a newly created instructor in storage
     */
    public function store(Request $request)
    {
        $this->authorize('create', Instructor::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnic' => 'required|string|max:15|unique:instructors,cnic',
            'email' => 'required|email|unique:instructors,email',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'specialization' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'nullable|exists:trades,id',
            'employment_type' => 'required|in:permanent,contract,visiting',
            'joining_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave,terminated',
            'photo_path' => 'nullable|string',
        ]);

        try {
            $instructor = Instructor::create($validated);

            activity()
                ->performedOn($instructor)
                ->causedBy(auth()->user())
                ->log('Instructor created');

            return redirect()->route('instructors.index')->with('success', 'Instructor created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create instructor: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified instructor
     */
    public function show(Instructor $instructor)
    {
        $this->authorize('view', $instructor);

        $instructor->load(['campus', 'trade', 'trainingClasses']);

        return view('instructors.show', compact('instructor'));
    }

    /**
     * Show the form for editing the specified instructor
     */
    public function edit(Instructor $instructor)
    {
        $this->authorize('update', $instructor);

        $campuses = Campus::all();
        $trades = Trade::all();

        return view('instructors.edit', compact('instructor', 'campuses', 'trades'));
    }

    /**
     * Update the specified instructor in storage
     */
    public function update(Request $request, Instructor $instructor)
    {
        $this->authorize('update', $instructor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cnic' => 'required|string|max:15|unique:instructors,cnic,' . $instructor->id,
            'email' => 'required|email|unique:instructors,email,' . $instructor->id,
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'specialization' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
            'campus_id' => 'nullable|exists:campuses,id',
            'trade_id' => 'nullable|exists:trades,id',
            'employment_type' => 'required|in:permanent,contract,visiting',
            'joining_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave,terminated',
            'photo_path' => 'nullable|string',
        ]);

        try {
            $instructor->update($validated);

            activity()
                ->performedOn($instructor)
                ->causedBy(auth()->user())
                ->log('Instructor updated');

            return redirect()->route('instructors.index')->with('success', 'Instructor updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update instructor: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified instructor from storage
     */
    public function destroy(Instructor $instructor)
    {
        $this->authorize('delete', $instructor);

        try {
            // FIXED: Check for associated training classes
            $classesCount = $instructor->trainingClasses()->count();
            if ($classesCount > 0) {
                return back()->with('error',
                    "Cannot delete instructor: {$classesCount} training class(es) are associated with this instructor. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated attendance records (if relationship exists)
            if (method_exists($instructor, 'attendances')) {
                $attendanceCount = $instructor->attendances()->count();
                if ($attendanceCount > 0) {
                    return back()->with('error',
                        "Cannot delete instructor: {$attendanceCount} attendance record(s) are associated with this instructor. " .
                        "Please remove them first."
                    );
                }
            }

            $instructorName = $instructor->name;
            $instructor->delete();

            activity()
                ->causedBy(auth()->user())
                ->log("Instructor deleted: {$instructorName}");

            return redirect()->route('instructors.index')->with('success', 'Instructor deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete instructor: ' . $e->getMessage());
        }
    }
}
