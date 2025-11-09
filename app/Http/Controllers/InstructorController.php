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
        $instructors = Instructor::with(['campus', 'trade'])
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('cnic', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
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
        $campuses = Campus::all();
        $trades = Trade::all();

        return view('instructors.create', compact('campuses', 'trades'));
    }

    /**
     * Store a newly created instructor in storage
     */
    public function store(Request $request)
    {
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

        Instructor::create($validated);

        return redirect()->route('instructors.index')->with('success', 'Instructor created successfully!');
    }

    /**
     * Display the specified instructor
     */
    public function show(Instructor $instructor)
    {
        $instructor->load(['campus', 'trade', 'trainingClasses']);

        return view('instructors.show', compact('instructor'));
    }

    /**
     * Show the form for editing the specified instructor
     */
    public function edit(Instructor $instructor)
    {
        $campuses = Campus::all();
        $trades = Trade::all();

        return view('instructors.edit', compact('instructor', 'campuses', 'trades'));
    }

    /**
     * Update the specified instructor in storage
     */
    public function update(Request $request, Instructor $instructor)
    {
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

        $instructor->update($validated);

        return redirect()->route('instructors.index')->with('success', 'Instructor updated successfully!');
    }

    /**
     * Remove the specified instructor from storage
     */
    public function destroy(Instructor $instructor)
    {
        $instructor->delete();

        return redirect()->route('instructors.index')->with('success', 'Instructor deleted successfully!');
    }
}
