<?php
// ============================================
// File: app/Http/Controllers/RegistrationController.php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RegistrationController extends Controller
{
    public function index()
    {
        $query = Candidate::with(['trade', 'campus', 'documents', 'nextOfKin'])
            ->whereIn('status', ['screening', 'registered']);

        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        $candidates = $query->paginate(20);

        return view('registration.index', compact('candidates'));
    }

    public function show(Candidate $candidate)
    {
        $candidate->load(['documents', 'nextOfKin', 'undertakings']);
        return view('registration.show', compact('candidate'));
    }

    public function uploadDocument(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'document_type' => 'required|string',
            'document_number' => 'nullable|string',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
        ]);

        $validated['candidate_id'] = $candidate->id;
        $validated['file_path'] = $request->file('file')->store('candidates/documents', 'public');
        $validated['verification_status'] = 'pending';

        RegistrationDocument::create($validated);

        activity()
            ->performedOn($candidate)
            ->log('Document uploaded: ' . $validated['document_type']);

        return back()->with('success', 'Document uploaded successfully!');
    }

    public function deleteDocument(RegistrationDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document deleted successfully!');
    }

    public function saveNextOfKin(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'cnic' => 'required|digits:13',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
        ]);

        $validated['candidate_id'] = $candidate->id;

        NextOfKin::updateOrCreate(
            ['candidate_id' => $candidate->id],
            $validated
        );

        activity()
            ->performedOn($candidate)
            ->log('Next of kin information saved');

        return back()->with('success', 'Next of kin information saved!');
    }

    public function saveUndertaking(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'undertaking_type' => 'required|string',
            'content' => 'required|string',
            'signature' => 'nullable|file|max:1024|mimes:jpg,jpeg,png',
        ]);

        $validated['candidate_id'] = $candidate->id;
        $validated['signed_at'] = now();
        $validated['is_completed'] = true;

        if ($request->hasFile('signature')) {
            $validated['signature_path'] = $request->file('signature')->store('undertakings/signatures', 'public');
        }

        Undertaking::create($validated);

        activity()
            ->performedOn($candidate)
            ->log('Undertaking signed: ' . $validated['undertaking_type']);

        return back()->with('success', 'Undertaking recorded successfully!');
    }

    public function completeRegistration(Request $request, Candidate $candidate)
    {
        // Check if all required documents are uploaded
        $requiredDocs = ['cnic', 'passport', 'education', 'police_clearance'];
        $uploadedDocs = $candidate->documents->pluck('document_type')->toArray();

        $missing = array_diff($requiredDocs, $uploadedDocs);

        if (!empty($missing)) {
            return back()->with('error', 'Missing documents: ' . implode(', ', $missing));
        }

        // Check if next of kin exists
        if (!$candidate->nextOfKin) {
            return back()->with('error', 'Next of kin information is required!');
        }

        $candidate->status = 'registered';
        $candidate->save();

        activity()
            ->performedOn($candidate)
            ->log('Registration completed');

        return back()->with('success', 'Registration completed successfully!');
    }
}