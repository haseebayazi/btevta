<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Country;
use App\Http\Requests\StoreEmployerRequest;
use App\Http\Requests\UpdateEmployerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployerController extends Controller
{
    /**
     * Display a listing of employers.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Employer::class);

        $query = Employer::with(['country', 'creator'])
            ->withCount(['candidates']);

        // Filter by country
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('visa_issuing_company', 'like', "%{$search}%")
                  ->orWhere('permission_number', 'like', "%{$search}%")
                  ->orWhere('sector', 'like', "%{$search}%");
            });
        }

        $employers = $query->latest()->paginate(20);
        $countries = Country::destinationCountries()->active()->get();

        return view('admin.employers.index', compact('employers', 'countries'));
    }

    /**
     * Show the form for creating a new employer.
     */
    public function create()
    {
        $this->authorize('create', Employer::class);

        $countries = Country::destinationCountries()->active()->get();

        return view('admin.employers.create', compact('countries'));
    }

    /**
     * Store a newly created employer.
     */
    public function store(StoreEmployerRequest $request)
    {
        try {
            $validated = $request->validated();
            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                $path = $request->file('evidence')->store('employers/evidence', 'private');
                $validated['evidence_path'] = $path;
            }

            $validated['food_by_company'] = $request->boolean('food_by_company');
            $validated['transport_by_company'] = $request->boolean('transport_by_company');
            $validated['accommodation_by_company'] = $request->boolean('accommodation_by_company');
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['created_by'] = auth()->id();

            $employer = Employer::create($validated);

            // Log activity
            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer created');

            return redirect()->route('admin.employers.index')
                ->with('success', 'Employer created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create employer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified employer.
     */
    public function show(Employer $employer)
    {
        $this->authorize('view', $employer);

        $employer->load(['country', 'creator', 'candidates' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('admin.employers.show', compact('employer'));
    }

    /**
     * Show the form for editing the employer.
     */
    public function edit(Employer $employer)
    {
        $this->authorize('update', $employer);

        $countries = Country::destinationCountries()->active()->get();

        return view('admin.employers.edit', compact('employer', 'countries'));
    }

    /**
     * Update the specified employer.
     */
    public function update(UpdateEmployerRequest $request, Employer $employer)
    {
        try {
            $validated = $request->validated();
            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                // Delete old evidence
                if ($employer->evidence_path) {
                    Storage::disk('private')->delete($employer->evidence_path);
                }
                $path = $request->file('evidence')->store('employers/evidence', 'private');
                $validated['evidence_path'] = $path;
            }

            $validated['food_by_company'] = $request->boolean('food_by_company');
            $validated['transport_by_company'] = $request->boolean('transport_by_company');
            $validated['accommodation_by_company'] = $request->boolean('accommodation_by_company');
            $validated['is_active'] = $request->boolean('is_active', $employer->is_active);

            $employer->update($validated);

            // Log activity
            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer updated');

            return redirect()->route('admin.employers.index')
                ->with('success', 'Employer updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update employer: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified employer.
     */
    public function destroy(Employer $employer)
    {
        $this->authorize('delete', $employer);

        try {
            // Check for associated candidates
            $candidatesCount = $employer->candidates()->count();
            if ($candidatesCount > 0) {
                return back()->with('error',
                    "Cannot delete employer: {$candidatesCount} candidate(s) are associated. " .
                    "Please reassign or remove them first."
                );
            }

            // Delete evidence file
            if ($employer->evidence_path) {
                Storage::disk('private')->delete($employer->evidence_path);
            }

            // Log activity before deletion
            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log('Employer deleted');

            $employer->delete();

            return redirect()->route('admin.employers.index')
                ->with('success', 'Employer deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete employer: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of an employer.
     */
    public function toggleStatus(Employer $employer)
    {
        $this->authorize('update', $employer);

        try {
            $employer->update(['is_active' => !$employer->is_active]);

            $status = $employer->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($employer)
                ->causedBy(auth()->user())
                ->log("Employer {$status}");

            return back()->with('success', "Employer {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update employer status: ' . $e->getMessage());
        }
    }

    /**
     * Download employer evidence file.
     */
    public function downloadEvidence(Employer $employer)
    {
        $this->authorize('view', $employer);

        if (!$employer->evidence_path || !Storage::disk('private')->exists($employer->evidence_path)) {
            return back()->with('error', 'Evidence file not found.');
        }

        return Storage::disk('private')->download(
            $employer->evidence_path,
            'employer-' . $employer->id . '-evidence.' . pathinfo($employer->evidence_path, PATHINFO_EXTENSION)
        );
    }
}
