<?php
// ============================================
// File: app/Http/Controllers/CorrespondenceController.php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\Campus;
use App\Models\Oep;
use Illuminate\Http\Request;

class CorrespondenceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::with(['campus', 'oep'])->latest();

        if ($request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $correspondences = $query->paginate(20);

        return view('correspondence.index', compact('correspondences'));
    }

    public function create()
    {
        $this->authorize('create', Correspondence::class);

        $campuses = Campus::where('is_active', true)->get();
        $oeps = Oep::where('is_active', true)->get();

        return view('correspondence.create', compact('campuses', 'oeps'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Correspondence::class);

        $validated = $request->validate([
            'reference_number' => 'required|unique:correspondences,reference_number',
            'date' => 'required|date',
            'subject' => 'required|string|max:500',
            'type' => 'required|in:incoming,outgoing',
            'sender' => 'required|string|max:255',
            'recipient' => 'required|string|max:255',
            'organization_type' => 'required|in:btevta,oep,embassy,campus,government,other',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'summary' => 'nullable|string',
            'file' => 'required|file|max:10240|mimes:pdf',
            'requires_reply' => 'boolean',
            'reply_deadline' => 'nullable|date|after:date',
        ]);

        try {
            $validated['file_path'] = $request->file('file')->store('correspondence', 'public');

            $correspondence = Correspondence::create($validated);

            activity()
                ->performedOn($correspondence)
                ->causedBy(auth()->user())
                ->log('Correspondence recorded');

            return redirect()->route('correspondence.index')
                ->with('success', 'Correspondence recorded successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to record correspondence: ' . $e->getMessage());
        }
    }

    public function show(Correspondence $correspondence)
    {
        $this->authorize('view', $correspondence);

        return view('correspondence.show', compact('correspondence'));
    }

    public function pendingReply()
    {
        $this->authorize('viewAny', Correspondence::class);

        $correspondences = Correspondence::where('requires_reply', true)
            ->where('replied', false)
            ->latest()
            ->paginate(20);

        return view('correspondence.pending-reply', compact('correspondences'));
    }

    public function markReplied(Request $request, Correspondence $correspondence)
    {
        $this->authorize('update', $correspondence);

        // FIXED: Added validation
        $request->validate([
            'reply_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $correspondence->replied = true;
            $correspondence->replied_at = now();
            if ($request->filled('reply_notes')) {
                $correspondence->reply_notes = $request->reply_notes;
            }
            $correspondence->save();

            activity()
                ->performedOn($correspondence)
                ->causedBy(auth()->user())
                ->log('Correspondence marked as replied');

            return back()->with('success', 'Marked as replied!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark as replied: ' . $e->getMessage());
        }
    }

    public function register()
    {
        $this->authorize('viewAny', Correspondence::class);

        // FIXED: Changed from get() to paginate() to prevent loading all records
        $correspondences = Correspondence::with(['campus', 'oep'])
            ->latest()
            ->paginate(50); // Show 50 records per page for register view

        return view('correspondence.register', compact('correspondences'));
    }
}