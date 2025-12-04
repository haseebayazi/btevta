<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    /**
     * Display a listing of campuses.
     */
    public function index()
    {
        $this->authorize('viewAny', Campus::class);

        $campuses = Campus::withCount(['candidates', 'batches'])
            ->latest()
            ->paginate(20);

        return view('admin.campuses.index', compact('campuses'));
    }

    /**
     * Show the form for creating a new campus.
     */
    public function create()
    {
        $this->authorize('create', Campus::class);

        return view('admin.campuses.create');
    }

    /**
     * Store a newly created campus.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Campus::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:campuses,name',
            'location' => 'required|string|max:255',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        try {
            $validated['is_active'] = true;
            $campus = Campus::create($validated);

            // Log activity
            activity()
                ->performedOn($campus)
                ->causedBy(auth()->user())
                ->log('Campus created');

            return redirect()->route('admin.campuses.index')
                ->with('success', 'Campus created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create campus: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified campus.
     */
    public function show(Campus $campus)
    {
        $this->authorize('view', $campus);

        $campus->load(['candidates' => function ($query) {
            $query->latest()->limit(10);
        }, 'batches' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.campuses.show', compact('campus'));
    }

    /**
     * Show the form for editing the campus.
     */
    public function edit(Campus $campus)
    {
        $this->authorize('update', $campus);

        return view('admin.campuses.edit', compact('campus'));
    }

    /**
     * Update the specified campus.
     */
    public function update(Request $request, Campus $campus)
    {
        $this->authorize('update', $campus);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:campuses,name,' . $campus->id,
            'location' => 'required|string|max:255',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        try {
            $campus->update($validated);

            // Log activity
            activity()
                ->performedOn($campus)
                ->causedBy(auth()->user())
                ->log('Campus updated');

            return redirect()->route('admin.campuses.index')
                ->with('success', 'Campus updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update campus: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified campus.
     */
    public function destroy(Campus $campus)
    {
        $this->authorize('delete', $campus);

        try {
            // Check for associated candidates
            $candidatesCount = $campus->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete campus: {$candidatesCount} candidate(s) are associated with this campus. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated batches
            $batchesCount = $campus->batches()->count();
            if ($batchesCount > 0) {
                return back()->with('error',
                    "Cannot delete campus: {$batchesCount} batch(es) are associated with this campus. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated users
            $usersCount = $campus->users()->count();
            if ($usersCount > 0) {
                return back()->with('error',
                    "Cannot delete campus: {$usersCount} user(s) are associated with this campus. " .
                    "Please reassign or remove them first."
                );
            }

            $campusName = $campus->name;
            $campus->delete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log("Campus deleted: {$campusName}");

            return back()->with('success', 'Campus deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete campus: ' . $e->getMessage());
        }
    }

    /**
     * Toggle campus active status.
     */
    public function toggleStatus(Campus $campus)
    {
        $this->authorize('toggleStatus', $campus);

        try {
            $campus->is_active = !$campus->is_active;
            $campus->save();

            $status = $campus->is_active ? 'activated' : 'deactivated';

            // Log activity
            activity()
                ->performedOn($campus)
                ->causedBy(auth()->user())
                ->log("Campus {$status}");

            return back()->with('success', "Campus {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle campus status: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to get list of active campuses.
     */
    public function apiList()
    {
        $this->authorize('apiList', Campus::class);

        try {
            $campuses = Campus::where('is_active', true)
                ->select('id', 'name', 'location', 'province', 'district')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $campuses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campuses'
            ], 500);
        }
    }
}