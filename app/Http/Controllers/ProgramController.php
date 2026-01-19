<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Http\Requests\StoreProgramRequest;
use App\Http\Requests\UpdateProgramRequest;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Display a listing of programs.
     */
    public function index()
    {
        $this->authorize('viewAny', Program::class);

        $programs = Program::withCount(['candidates'])
            ->latest()
            ->paginate(20);

        return view('admin.programs.index', compact('programs'));
    }

    /**
     * Show the form for creating a new program.
     */
    public function create()
    {
        $this->authorize('create', Program::class);

        return view('admin.programs.create');
    }

    /**
     * Store a newly created program.
     */
    public function store(StoreProgramRequest $request)
    {
        try {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', true);
            $program = Program::create($validated);

            // Log activity
            activity()
                ->performedOn($program)
                ->causedBy(auth()->user())
                ->log('Program created');

            return redirect()->route('admin.programs.index')
                ->with('success', 'Program created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create program: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified program.
     */
    public function show(Program $program)
    {
        $this->authorize('view', $program);

        $program->load(['candidates' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.programs.show', compact('program'));
    }

    /**
     * Show the form for editing the program.
     */
    public function edit(Program $program)
    {
        $this->authorize('update', $program);

        return view('admin.programs.edit', compact('program'));
    }

    /**
     * Update the specified program.
     */
    public function update(UpdateProgramRequest $request, Program $program)
    {
        try {
            $validated = $request->validated();
            $validated['is_active'] = $request->boolean('is_active', $program->is_active);
            $program->update($validated);

            // Log activity
            activity()
                ->performedOn($program)
                ->causedBy(auth()->user())
                ->log('Program updated');

            return redirect()->route('admin.programs.index')
                ->with('success', 'Program updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update program: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified program.
     */
    public function destroy(Program $program)
    {
        $this->authorize('delete', $program);

        try {
            // Check for associated candidates
            $candidatesCount = $program->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete program: {$candidatesCount} candidate(s) are associated with this program. " .
                    "Please reassign or remove them first."
                );
            }

            // Log activity before deletion
            activity()
                ->performedOn($program)
                ->causedBy(auth()->user())
                ->log('Program deleted');

            $program->delete();

            return redirect()->route('admin.programs.index')
                ->with('success', 'Program deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete program: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of a program.
     */
    public function toggleStatus(Program $program)
    {
        $this->authorize('update', $program);

        try {
            $program->update(['is_active' => !$program->is_active]);

            $status = $program->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($program)
                ->causedBy(auth()->user())
                ->log("Program {$status}");

            return back()->with('success', "Program {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update program status: ' . $e->getMessage());
        }
    }
}
