<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    /**
     * Display a listing of candidates in registration phase.
     */
    public function index()
    {
        $this->authorize('viewAny', Candidate::class);

        $query = Candidate::with(['trade', 'campus', 'documents', 'nextOfKin'])
            ->whereIn('status', ['screening_passed', 'registered', 'pending_registration']);

        // Filter by campus for campus_admin users
        if (auth()->user()->role === 'campus_admin' && auth()->user()->campus_id) {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        $candidates = $query->latest()->paginate(20);

        return view('registration.index', compact('candidates'));
    }

    /**
     * Display the specified candidate's registration details.
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $candidate->load(['documents', 'nextOfKin', 'undertakings', 'campus', 'trade']);

        return view('registration.show', compact('candidate'));
    }

    /**
     * Upload a document for the candidate.
     */
    public function uploadDocument(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'document_type' => 'required|string|in:cnic,passport,education,police_clearance,medical,photo,other',
            'document_number' => 'nullable|string|max:100',
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'issue_date' => 'nullable|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:issue_date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Store file
            $filePath = $request->file('file')->store('candidates/documents', 'public');

            if (!$filePath) {
                throw new \Exception('Failed to store file');
            }

            $validated['candidate_id'] = $candidate->id;
            $validated['file_path'] = $filePath;
            $validated['status'] = 'pending';
            $validated['uploaded_by'] = auth()->id();

            $document = RegistrationDocument::create($validated);

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log('Document uploaded: ' . $validated['document_type']);

            DB::commit();

            return back()->with('success', 'Document uploaded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete file if it was stored but DB operation failed
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return back()->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Delete a candidate's document.
     * CRITICAL FIX: Added authorization and ownership checks.
     */
    public function deleteDocument(RegistrationDocument $document)
    {
        try {
            // Load the candidate relationship
            $document->load('candidate');

            // Check if document belongs to a candidate
            if (!$document->candidate) {
                return back()->with('error', 'Invalid document reference.');
            }

            // Authorize based on the candidate
            $this->authorize('update', $document->candidate);

            // Additional check: Campus admin users can only delete documents for their campus candidates
            if (auth()->user()->role === 'campus_admin' && auth()->user()->campus_id) {
                if ($document->candidate->campus_id !== auth()->user()->campus_id) {
                    abort(403, 'Unauthorized: Document does not belong to your campus.');
                }
            }

            DB::beginTransaction();

            $documentType = $document->document_type;
            $candidateName = $document->candidate->name;

            // Delete file from storage if it exists
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Delete database record
            $document->delete();

            // Log activity
            activity()
                ->performedOn($document->candidate)
                ->causedBy(auth()->user())
                ->log("Document deleted: {$documentType}");

            DB::commit();

            return back()->with('success', 'Document deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Save or update next of kin information.
     */
    public function saveNextOfKin(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'cnic' => 'required|digits:13',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'occupation' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $validated['candidate_id'] = $candidate->id;

            NextOfKin::updateOrCreate(
                ['candidate_id' => $candidate->id],
                $validated
            );

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log('Next of kin information saved');

            return back()->with('success', 'Next of kin information saved successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save next of kin information: ' . $e->getMessage());
        }
    }

    /**
     * Save undertaking information.
     */
    public function saveUndertaking(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'undertaking_type' => 'required|string|in:employment,financial,behavior,other',
            'content' => 'required|string|max:2000',
            'signature' => 'nullable|file|max:1024|mimes:jpg,jpeg,png',
            'witness_name' => 'nullable|string|max:255',
            'witness_cnic' => 'nullable|digits:13',
        ]);

        try {
            DB::beginTransaction();

            $validated['candidate_id'] = $candidate->id;
            $validated['signed_at'] = now();
            $validated['is_completed'] = true;

            // Store signature if provided
            if ($request->hasFile('signature')) {
                $signaturePath = $request->file('signature')->store('undertakings/signatures', 'public');

                if (!$signaturePath) {
                    throw new \Exception('Failed to store signature');
                }

                $validated['signature_path'] = $signaturePath;
            }

            $undertaking = Undertaking::create($validated);

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log('Undertaking signed: ' . $validated['undertaking_type']);

            DB::commit();

            return back()->with('success', 'Undertaking recorded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete signature file if it was stored but DB operation failed
            if (isset($signaturePath) && Storage::disk('public')->exists($signaturePath)) {
                Storage::disk('public')->delete($signaturePath);
            }

            return back()->with('error', 'Failed to save undertaking: ' . $e->getMessage());
        }
    }

    /**
     * Complete the registration process for a candidate.
     */
    public function completeRegistration(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        try {
            // Check if all required documents are uploaded
            $requiredDocs = ['cnic', 'passport', 'education', 'police_clearance'];
            $uploadedDocs = $candidate->documents()
                ->whereIn('document_type', $requiredDocs)
                ->pluck('document_type')
                ->toArray();

            $missing = array_diff($requiredDocs, $uploadedDocs);

            if (!empty($missing)) {
                return back()->with('error',
                    'Missing required documents: ' . implode(', ', array_map('ucfirst', $missing))
                );
            }

            // Check if next of kin exists
            if (!$candidate->nextOfKin) {
                return back()->with('error', 'Next of kin information is required!');
            }

            // Check if undertaking is signed
            if ($candidate->undertakings()->count() === 0) {
                return back()->with('error', 'At least one undertaking must be signed!');
            }

            DB::beginTransaction();

            // Update candidate status
            $candidate->status = 'registered';
            $candidate->registered_at = now();
            $candidate->save();

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log('Registration completed');

            DB::commit();

            return redirect()->route('registration.index')
                ->with('success', 'Registration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete registration: ' . $e->getMessage());
        }
    }
}
