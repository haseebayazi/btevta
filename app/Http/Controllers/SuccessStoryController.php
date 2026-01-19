<?php

namespace App\Http\Controllers;

use App\Models\SuccessStory;
use App\Models\Candidate;
use App\Enums\EvidenceType;
use App\Http\Requests\StoreSuccessStoryRequest;
use App\Http\Requests\UpdateSuccessStoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuccessStoryController extends Controller
{
    /**
     * Display a listing of success stories.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', SuccessStory::class);

        $query = SuccessStory::with(['candidate', 'recorder']);

        // Filter by featured status
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Filter by evidence type
        if ($request->filled('evidence_type')) {
            $query->where('evidence_type', $request->evidence_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('written_note', 'like', "%{$search}%")
                  ->orWhereHas('candidate', function ($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%")
                            ->orWhere('btevta_id', 'like', "%{$search}%");
                  });
            });
        }

        $stories = $query->latest('recorded_at')->paginate(20);
        $evidenceTypes = EvidenceType::toArray();

        return view('admin.success-stories.index', compact('stories', 'evidenceTypes'));
    }

    /**
     * Show the form for creating a new success story.
     */
    public function create(Request $request)
    {
        $this->authorize('create', SuccessStory::class);

        $candidateId = $request->query('candidate_id');
        $candidate = $candidateId ? Candidate::findOrFail($candidateId) : null;
        $evidenceTypes = EvidenceType::toArray();

        return view('admin.success-stories.create', compact('candidate', 'evidenceTypes'));
    }

    /**
     * Store a newly created success story.
     */
    public function store(StoreSuccessStoryRequest $request)
    {
        try {
            $validated = $request->validated();

            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                $path = $request->file('evidence')->store(
                    'success-stories/' . $validated['candidate_id'],
                    'private'
                );
                $validated['evidence_path'] = $path;
                $validated['evidence_filename'] = $request->file('evidence')->getClientOriginalName();
            }

            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['recorded_by'] = auth()->id();
            $validated['recorded_at'] = now();

            $story = SuccessStory::create($validated);

            // Log activity
            activity()
                ->performedOn($story)
                ->causedBy(auth()->user())
                ->log('Success story created');

            return redirect()->route('admin.success-stories.index')
                ->with('success', 'Success story created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create success story: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified success story.
     */
    public function show(SuccessStory $successStory)
    {
        $this->authorize('view', $successStory);

        $successStory->load(['candidate', 'departure', 'recorder']);

        return view('admin.success-stories.show', compact('successStory'));
    }

    /**
     * Show the form for editing the success story.
     */
    public function edit(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        $evidenceTypes = EvidenceType::toArray();

        return view('admin.success-stories.edit', compact('successStory', 'evidenceTypes'));
    }

    /**
     * Update the specified success story.
     */
    public function update(UpdateSuccessStoryRequest $request, SuccessStory $successStory)
    {
        try {
            $validated = $request->validated();

            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                // Delete old evidence
                if ($successStory->evidence_path) {
                    Storage::disk('private')->delete($successStory->evidence_path);
                }

                $path = $request->file('evidence')->store(
                    'success-stories/' . $successStory->candidate_id,
                    'private'
                );
                $validated['evidence_path'] = $path;
                $validated['evidence_filename'] = $request->file('evidence')->getClientOriginalName();
            }

            $validated['is_featured'] = $request->boolean('is_featured', $successStory->is_featured);

            $successStory->update($validated);

            // Log activity
            activity()
                ->performedOn($successStory)
                ->causedBy(auth()->user())
                ->log('Success story updated');

            return redirect()->route('admin.success-stories.index')
                ->with('success', 'Success story updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update success story: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified success story.
     */
    public function destroy(SuccessStory $successStory)
    {
        $this->authorize('delete', $successStory);

        try {
            // Delete evidence file
            if ($successStory->evidence_path) {
                Storage::disk('private')->delete($successStory->evidence_path);
            }

            // Log activity before deletion
            activity()
                ->performedOn($successStory)
                ->causedBy(auth()->user())
                ->log('Success story deleted');

            $successStory->delete();

            return redirect()->route('admin.success-stories.index')
                ->with('success', 'Success story deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete success story: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the featured status of a success story.
     */
    public function toggleFeatured(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        try {
            $successStory->update(['is_featured' => !$successStory->is_featured]);

            $status = $successStory->is_featured ? 'featured' : 'unfeatured';

            activity()
                ->performedOn($successStory)
                ->causedBy(auth()->user())
                ->log("Success story {$status}");

            return back()->with('success', "Success story {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update featured status: ' . $e->getMessage());
        }
    }

    /**
     * Download success story evidence.
     */
    public function downloadEvidence(SuccessStory $successStory)
    {
        $this->authorize('view', $successStory);

        if (!$successStory->evidence_path || !Storage::disk('private')->exists($successStory->evidence_path)) {
            return back()->with('error', 'Evidence file not found.');
        }

        $filename = $successStory->evidence_filename ?? 'success-story-' . $successStory->id . '.' .
            pathinfo($successStory->evidence_path, PATHINFO_EXTENSION);

        return Storage::disk('private')->download($successStory->evidence_path, $filename);
    }

    /**
     * Display public featured success stories (no auth required).
     */
    public function featured()
    {
        $stories = SuccessStory::featured()
            ->with(['candidate'])
            ->latest('recorded_at')
            ->paginate(12);

        return view('public.success-stories', compact('stories'));
    }
}
