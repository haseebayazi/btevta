<?php

namespace App\Http\Controllers;

use App\Models\ImplementingPartner;
use Illuminate\Http\Request;

class ImplementingPartnerController extends Controller
{
    /**
     * Display a listing of implementing partners.
     */
    public function index()
    {
        $this->authorize('viewAny', ImplementingPartner::class);

        $partners = ImplementingPartner::withCount(['candidates'])
            ->latest()
            ->paginate(20);

        return view('admin.implementing-partners.index', compact('partners'));
    }

    /**
     * Show the form for creating a new implementing partner.
     */
    public function create()
    {
        $this->authorize('create', ImplementingPartner::class);

        return view('admin.implementing-partners.create');
    }

    /**
     * Store a newly created implementing partner.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ImplementingPartner::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:implementing_partners,name',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $partner = ImplementingPartner::create($validated);

            // Log activity
            activity()
                ->performedOn($partner)
                ->causedBy(auth()->user())
                ->log('Implementing Partner created');

            return redirect()->route('admin.implementing-partners.index')
                ->with('success', 'Implementing Partner created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create implementing partner: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified implementing partner.
     */
    public function show(ImplementingPartner $implementingPartner)
    {
        $this->authorize('view', $implementingPartner);

        $implementingPartner->load(['candidates' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.implementing-partners.show', compact('implementingPartner'));
    }

    /**
     * Show the form for editing the implementing partner.
     */
    public function edit(ImplementingPartner $implementingPartner)
    {
        $this->authorize('update', $implementingPartner);

        return view('admin.implementing-partners.edit', compact('implementingPartner'));
    }

    /**
     * Update the specified implementing partner.
     */
    public function update(Request $request, ImplementingPartner $implementingPartner)
    {
        $this->authorize('update', $implementingPartner);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:implementing_partners,name,' . $implementingPartner->id,
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', $implementingPartner->is_active);
            $implementingPartner->update($validated);

            // Log activity
            activity()
                ->performedOn($implementingPartner)
                ->causedBy(auth()->user())
                ->log('Implementing Partner updated');

            return redirect()->route('admin.implementing-partners.index')
                ->with('success', 'Implementing Partner updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update implementing partner: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified implementing partner.
     */
    public function destroy(ImplementingPartner $implementingPartner)
    {
        $this->authorize('delete', $implementingPartner);

        try {
            // Check for associated candidates
            $candidatesCount = $implementingPartner->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete implementing partner: {$candidatesCount} candidate(s) are associated. " .
                    "Please reassign or remove them first."
                );
            }

            // Log activity before deletion
            activity()
                ->performedOn($implementingPartner)
                ->causedBy(auth()->user())
                ->log('Implementing Partner deleted');

            $implementingPartner->delete();

            return redirect()->route('admin.implementing-partners.index')
                ->with('success', 'Implementing Partner deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete implementing partner: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of an implementing partner.
     */
    public function toggleStatus(ImplementingPartner $implementingPartner)
    {
        $this->authorize('update', $implementingPartner);

        try {
            $implementingPartner->update(['is_active' => !$implementingPartner->is_active]);

            $status = $implementingPartner->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($implementingPartner)
                ->causedBy(auth()->user())
                ->log("Implementing Partner {$status}");

            return back()->with('success', "Implementing Partner {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}
