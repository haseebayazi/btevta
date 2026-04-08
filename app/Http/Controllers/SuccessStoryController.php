<?php

namespace App\Http\Controllers;

use App\Enums\EvidenceType;
use App\Enums\StoryEvidenceType;
use App\Enums\StoryStatus;
use App\Enums\StoryType;
use App\Http\Requests\StoreSuccessStoryRequest;
use App\Http\Requests\UpdateSuccessStoryRequest;
use App\Models\Candidate;
use App\Models\Country;
use App\Models\SuccessStory;
use App\Models\SuccessStoryEvidence;
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

        $query = SuccessStory::with(['candidate', 'recorder', 'country', 'primaryEvidence']);

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->filled('evidence_type')) {
            $query->where('evidence_type', $request->evidence_type);
        }

        if ($request->filled('story_type')) {
            $query->where('story_type', $request->story_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('written_note', 'like', "%{$search}%")
                    ->orWhere('headline', 'like', "%{$search}%")
                    ->orWhere('employer_name', 'like', "%{$search}%")
                    ->orWhereHas('candidate', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('btevta_id', 'like', "%{$search}%");
                    });
            });
        }

        $stories      = $query->latest('recorded_at')->paginate(20);
        $evidenceTypes = EvidenceType::toArray();
        $storyTypes   = StoryType::options();
        $statuses     = StoryStatus::options();

        return view('admin.success-stories.index', compact(
            'stories', 'evidenceTypes', 'storyTypes', 'statuses'
        ));
    }

    /**
     * Show the form for creating a new success story.
     */
    public function create(Request $request)
    {
        $this->authorize('create', SuccessStory::class);

        $candidateId  = $request->query('candidate_id');
        $candidate    = $candidateId ? Candidate::findOrFail($candidateId) : null;
        $evidenceTypes = EvidenceType::toArray();
        $storyTypes   = StoryType::options();
        $countries    = Country::orderBy('name')->get();

        return view('admin.success-stories.create', compact(
            'candidate', 'evidenceTypes', 'storyTypes', 'countries'
        ));
    }

    /**
     * Store a newly created success story.
     */
    public function store(StoreSuccessStoryRequest $request)
    {
        try {
            $validated = $request->validated();

            if ($request->hasFile('evidence')) {
                $path = $request->file('evidence')->store(
                    'success-stories/'.$validated['candidate_id'],
                    'private'
                );
                $validated['evidence_path']     = $path;
                $validated['evidence_filename'] = $request->file('evidence')->getClientOriginalName();
            }

            $validated['is_featured'] = $request->boolean('is_featured', false);
            $validated['recorded_by'] = auth()->id();
            $validated['recorded_at'] = now();
            $validated['status']      = StoryStatus::DRAFT->value;

            $story = SuccessStory::create($validated);

            activity()
                ->performedOn($story)
                ->causedBy(auth()->user())
                ->log('Success story created');

            return redirect()->route('admin.success-stories.show', $story)
                ->with('success', 'Success story created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create success story: '.$e->getMessage());
        }
    }

    /**
     * Display the specified success story.
     */
    public function show(SuccessStory $successStory)
    {
        $this->authorize('view', $successStory);

        $successStory->load(['candidate', 'departure', 'recorder', 'country', 'approvedBy', 'evidence.uploadedBy']);
        $successStory->incrementViews();

        return view('admin.success-stories.show', compact('successStory'));
    }

    /**
     * Show the form for editing the success story.
     */
    public function edit(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        $evidenceTypes = EvidenceType::toArray();
        $storyTypes   = StoryType::options();
        $countries    = Country::orderBy('name')->get();

        return view('admin.success-stories.edit', compact(
            'successStory', 'evidenceTypes', 'storyTypes', 'countries'
        ));
    }

    /**
     * Update the specified success story.
     */
    public function update(UpdateSuccessStoryRequest $request, SuccessStory $successStory)
    {
        try {
            $validated = $request->validated();

            if ($request->hasFile('evidence')) {
                if ($successStory->evidence_path) {
                    Storage::disk('private')->delete($successStory->evidence_path);
                }

                $path = $request->file('evidence')->store(
                    'success-stories/'.$successStory->candidate_id,
                    'private'
                );
                $validated['evidence_path']     = $path;
                $validated['evidence_filename'] = $request->file('evidence')->getClientOriginalName();
            }

            $validated['is_featured'] = $request->boolean('is_featured', $successStory->is_featured);

            $successStory->update($validated);

            activity()
                ->performedOn($successStory)
                ->causedBy(auth()->user())
                ->log('Success story updated');

            return redirect()->route('admin.success-stories.show', $successStory)
                ->with('success', 'Success story updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update success story: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified success story.
     */
    public function destroy(SuccessStory $successStory)
    {
        $this->authorize('delete', $successStory);

        try {
            if ($successStory->evidence_path) {
                Storage::disk('private')->delete($successStory->evidence_path);
            }

            activity()
                ->performedOn($successStory)
                ->causedBy(auth()->user())
                ->log('Success story deleted');

            $successStory->delete();

            return redirect()->route('admin.success-stories.index')
                ->with('success', 'Success story deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete success story: '.$e->getMessage());
        }
    }

    /**
     * Toggle the featured status of a success story.
     */
    public function toggleFeatured(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        try {
            $successStory->update(['is_featured' => ! $successStory->is_featured]);

            $status = $successStory->is_featured ? 'featured' : 'unfeatured';

            activity()
                ->performedOn($successStory)
                ->causedBy(auth()->user())
                ->log("Success story {$status}");

            return back()->with('success', "Success story {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update featured status: '.$e->getMessage());
        }
    }

    /**
     * Download success story evidence.
     */
    public function downloadEvidence(SuccessStory $successStory)
    {
        $this->authorize('view', $successStory);

        if (! $successStory->evidence_path || ! Storage::disk('private')->exists($successStory->evidence_path)) {
            return back()->with('error', 'Evidence file not found.');
        }

        $filename = $successStory->evidence_filename ?? 'success-story-'.$successStory->id.'.'.
            pathinfo($successStory->evidence_path, PATHINFO_EXTENSION);

        return Storage::disk('private')->download($successStory->evidence_path, $filename);
    }

    /**
     * Submit story for review.
     */
    public function submitForReview(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        if (! $successStory->status->canSubmitForReview()) {
            return back()->with('error', 'This story cannot be submitted for review in its current status.');
        }

        $successStory->submitForReview();

        return back()->with('success', 'Story submitted for review.');
    }

    /**
     * Approve a story.
     */
    public function approve(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        if (! $successStory->status->canApprove()) {
            return back()->with('error', 'This story cannot be approved in its current status.');
        }

        $successStory->approve();

        return back()->with('success', 'Story approved.');
    }

    /**
     * Publish a story.
     */
    public function publish(SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        if (! $successStory->status->canPublish()) {
            return back()->with('error', 'Only approved stories can be published.');
        }

        $successStory->publish();

        return back()->with('success', 'Story published successfully.');
    }

    /**
     * Reject a story.
     */
    public function reject(Request $request, SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        $request->validate(['reason' => 'required|string|max:1000']);

        if (! $successStory->status->canReject()) {
            return back()->with('error', 'This story cannot be rejected in its current status.');
        }

        $successStory->reject($request->reason);

        return back()->with('success', 'Story rejected.');
    }

    /**
     * Add evidence to a story.
     */
    public function addEvidence(Request $request, SuccessStory $successStory)
    {
        $this->authorize('update', $successStory);

        $validated = $request->validate([
            'evidence_type' => 'required|in:photo,video,document,interview,testimonial,certificate',
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string|max:1000',
            'file'          => 'required|file|max:102400',
            'is_primary'    => 'boolean',
        ]);

        $evidenceType = StoryEvidenceType::from($validated['evidence_type']);
        $extension    = strtolower($request->file('file')->getClientOriginalExtension());

        if (! in_array($extension, $evidenceType->allowedMimes())) {
            return back()->with('error', 'Invalid file type for this evidence category. Allowed: '.implode(', ', $evidenceType->allowedMimes()));
        }

        $path = $request->file('file')->store("success-stories/{$successStory->id}/evidence", 'private');

        // Clear other primary flags if setting this as primary
        if ($request->boolean('is_primary')) {
            $successStory->evidence()->update(['is_primary' => false]);
        }

        SuccessStoryEvidence::create([
            'success_story_id' => $successStory->id,
            'evidence_type'    => $validated['evidence_type'],
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'file_path'        => $path,
            'mime_type'        => $request->file('file')->getMimeType(),
            'file_size'        => $request->file('file')->getSize(),
            'is_primary'       => $request->boolean('is_primary'),
            'display_order'    => $successStory->evidence()->count(),
            'uploaded_by'      => auth()->id(),
        ]);

        activity()
            ->performedOn($successStory)
            ->causedBy(auth()->user())
            ->withProperties(['type' => $validated['evidence_type']])
            ->log('Evidence added to success story');

        return back()->with('success', 'Evidence added successfully.');
    }

    /**
     * Delete a specific evidence item.
     */
    public function deleteEvidence(SuccessStory $successStory, SuccessStoryEvidence $evidence)
    {
        $this->authorize('update', $successStory);

        Storage::disk('private')->delete($evidence->file_path);
        $evidence->delete();

        return back()->with('success', 'Evidence deleted.');
    }

    /**
     * Public gallery of published stories.
     */
    public function publicGallery()
    {
        $stories = SuccessStory::published()
            ->with(['candidate', 'primaryEvidence', 'country'])
            ->orderBy('is_featured', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        $storyTypes = StoryType::cases();

        return view('success-stories.public-gallery', compact('stories', 'storyTypes'));
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
