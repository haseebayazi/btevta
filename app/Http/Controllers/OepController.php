<?php

namespace App\Http\Controllers;

use App\Models\Oep;
use Illuminate\Http\Request;

class OepController extends Controller
{
    /**
     * Display a listing of OEPs.
     */
    public function index()
    {
        $this->authorize('viewAny', Oep::class);

        $oeps = Oep::withCount(['candidates', 'batches'])
            ->latest()
            ->paginate(20);

        return view('admin.oeps.index', compact('oeps'));
    }

    /**
     * Show the form for creating a new OEP.
     */
    public function create()
    {
        $this->authorize('create', Oep::class);

        return view('admin.oeps.create');
    }

    /**
     * Store a newly created OEP.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Oep::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:oeps,name',
            'code' => 'required|string|max:50|unique:oeps,code',
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string|max:500',
            'license_number' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
        ]);

        try {
            $validated['is_active'] = true;
            $oep = Oep::create($validated);

            // Log activity
            activity()
                ->performedOn($oep)
                ->causedBy(auth()->user())
                ->log('OEP created');

            return redirect()->route('oeps.index')
                ->with('success', 'OEP created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create OEP: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified OEP.
     */
    public function show(Oep $oep)
    {
        $this->authorize('view', $oep);

        $oep->load(['candidates' => function ($query) {
            $query->latest()->limit(10);
        }, 'batches' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.oeps.show', compact('oep'));
    }

    /**
     * Show the form for editing the OEP.
     */
    public function edit(Oep $oep)
    {
        $this->authorize('update', $oep);

        return view('admin.oeps.edit', compact('oep'));
    }

    /**
     * Update the specified OEP.
     */
    public function update(Request $request, Oep $oep)
    {
        $this->authorize('update', $oep);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:oeps,name,' . $oep->id,
            'code' => 'required|string|max:50|unique:oeps,code,' . $oep->id,
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string|max:500',
            'license_number' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
        ]);

        try {
            $oep->update($validated);

            // Log activity
            activity()
                ->performedOn($oep)
                ->causedBy(auth()->user())
                ->log('OEP updated');

            return redirect()->route('oeps.index')
                ->with('success', 'OEP updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update OEP: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified OEP.
     */
    public function destroy(Oep $oep)
    {
        $this->authorize('delete', $oep);

        try {
            // Check for associated candidates
            $candidatesCount = $oep->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete OEP: {$candidatesCount} candidate(s) are associated with this OEP. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated batches
            $batchesCount = $oep->batches()->count();
            if ($batchesCount > 0) {
                return back()->with('error',
                    "Cannot delete OEP: {$batchesCount} batch(es) are associated with this OEP. " .
                    "Please reassign or remove them first."
                );
            }

            // Check for associated users
            $usersCount = $oep->users()->count();
            if ($usersCount > 0) {
                return back()->with('error',
                    "Cannot delete OEP: {$usersCount} user(s) are associated with this OEP. " .
                    "Please reassign or remove them first."
                );
            }

            $oepName = $oep->name;
            $oep->delete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->log("OEP deleted: {$oepName}");

            return back()->with('success', 'OEP deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete OEP: ' . $e->getMessage());
        }
    }

    /**
     * Toggle OEP active status.
     */
    public function toggleStatus(Oep $oep)
    {
        $this->authorize('toggleStatus', $oep);

        try {
            $oep->is_active = !$oep->is_active;
            $oep->save();

            $status = $oep->is_active ? 'activated' : 'deactivated';

            // Log activity
            activity()
                ->performedOn($oep)
                ->causedBy(auth()->user())
                ->log("OEP {$status}");

            return back()->with('success', "OEP {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle OEP status: ' . $e->getMessage());
        }
    }

    /**
     * API endpoint to get list of active OEPs.
     */
    public function apiList()
    {
        try {
            $oeps = Oep::where('is_active', true)
                ->select('id', 'name', 'code', 'country', 'city')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $oeps
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch OEPs'
            ], 500);
        }
    }
}
