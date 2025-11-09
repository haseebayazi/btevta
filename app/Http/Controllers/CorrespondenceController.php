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
        $campuses = Campus::where('is_active', true)->get();
        $oeps = Oep::where('is_active', true)->get();

        return view('correspondence.create', compact('campuses', 'oeps'));
    }

    public function store(Request $request)
    {
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

        $validated['file_path'] = $request->file('file')->store('correspondence', 'public');

        Correspondence::create($validated);

        return redirect()->route('correspondence.index')
            ->with('success', 'Correspondence recorded successfully!');
    }

    public function show(Correspondence $correspondence)
    {
        return view('correspondence.show', compact('correspondence'));
    }

    public function pendingReply()
    {
        $correspondences = Correspondence::where('requires_reply', true)
            ->where('replied', false)
            ->latest()
            ->paginate(20);

        return view('correspondence.pending-reply', compact('correspondences'));
    }

    public function markReplied(Request $request, Correspondence $correspondence)
    {
        $correspondence->replied = true;
        $correspondence->save();

        return back()->with('success', 'Marked as replied!');
    }

    public function register()
    {
        $correspondences = Correspondence::with(['campus', 'oep'])
            ->latest()
            ->get();

        return view('correspondence.register', compact('correspondences'));
    }
}