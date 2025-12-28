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
     * Required document types for registration completion.
     */
    const REQUIRED_DOCUMENTS = ['cnic', 'education', 'photo'];

    /**
     * Complete the registration process for a candidate.
     * Validates all required documents, expiry dates, next of kin, and undertaking.
     */
    public function completeRegistration(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        try {
            // Check if all required documents are uploaded
            $uploadedDocs = $candidate->documents()
                ->whereIn('document_type', self::REQUIRED_DOCUMENTS)
                ->get();

            $uploadedTypes = $uploadedDocs->pluck('document_type')->toArray();
            $missing = array_diff(self::REQUIRED_DOCUMENTS, $uploadedTypes);

            if (!empty($missing)) {
                $docLabels = [
                    'cnic' => 'CNIC Copy',
                    'education' => 'Educational Certificate',
                    'photo' => 'Passport Size Photo',
                ];
                $missingLabels = array_map(fn($type) => $docLabels[$type] ?? ucfirst($type), $missing);
                return back()->with('error',
                    'Missing required documents: ' . implode(', ', $missingLabels)
                );
            }

            // Check for expired documents
            $expiredDocs = $uploadedDocs->filter(function ($doc) {
                return $doc->expiry_date && $doc->expiry_date->isPast();
            });

            if ($expiredDocs->isNotEmpty()) {
                $expiredTypes = $expiredDocs->pluck('document_type')->toArray();
                return back()->with('error',
                    'Cannot complete registration - expired documents: ' . implode(', ', $expiredTypes) .
                    '. Please upload valid documents.'
                );
            }

            // Check for documents expiring soon (warning only)
            $expiringDocs = $uploadedDocs->filter(function ($doc) {
                return $doc->expiry_date && $doc->expiry_date->isBetween(now(), now()->addDays(30));
            });

            // Check if next of kin exists
            if (!$candidate->nextOfKin) {
                return back()->with('error', 'Next of kin information is required!');
            }

            // Check if undertaking is signed
            $undertaking = $candidate->undertakings()->where('is_completed', true)->first();
            if (!$undertaking) {
                return back()->with('error', 'At least one completed undertaking is required!');
            }

            DB::beginTransaction();

            // Update candidate status
            $candidate->status = 'registered';
            $candidate->registration_date = now();
            $candidate->save();

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'documents_count' => $uploadedDocs->count(),
                    'has_next_of_kin' => true,
                    'has_undertaking' => true,
                ])
                ->log('Registration completed');

            DB::commit();

            $message = 'Registration completed successfully!';
            if ($expiringDocs->isNotEmpty()) {
                $message .= ' Note: Some documents expire within 30 days.';
            }

            return redirect()->route('registration.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete registration: ' . $e->getMessage());
        }
    }

    /**
     * Verify a document (admin only).
     */
    public function verifyDocument(Request $request, RegistrationDocument $document)
    {
        $document->load('candidate');

        if (!$document->candidate) {
            return back()->with('error', 'Invalid document reference.');
        }

        $this->authorize('update', $document->candidate);

        // Only admin and campus_admin can verify
        if (!in_array(auth()->user()->role, ['admin', 'campus_admin'])) {
            abort(403, 'Only administrators can verify documents.');
        }

        $validated = $request->validate([
            'verification_remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $document->status = 'verified';
            $document->verification_status = 'verified';
            $document->verification_remarks = $validated['verification_remarks'] ?? null;
            $document->updated_by = auth()->id();
            $document->save();

            activity()
                ->performedOn($document->candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'document_type' => $document->document_type,
                    'document_id' => $document->id,
                ])
                ->log('Document verified: ' . $document->document_type);

            DB::commit();

            return back()->with('success', 'Document verified successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to verify document: ' . $e->getMessage());
        }
    }

    /**
     * Reject a document (admin only).
     */
    public function rejectDocument(Request $request, RegistrationDocument $document)
    {
        $document->load('candidate');

        if (!$document->candidate) {
            return back()->with('error', 'Invalid document reference.');
        }

        $this->authorize('update', $document->candidate);

        // Only admin and campus_admin can reject
        if (!in_array(auth()->user()->role, ['admin', 'campus_admin'])) {
            abort(403, 'Only administrators can reject documents.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $document->status = 'rejected';
            $document->verification_status = 'rejected';
            $document->verification_remarks = $validated['rejection_reason'];
            $document->updated_by = auth()->id();
            $document->save();

            activity()
                ->performedOn($document->candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'document_type' => $document->document_type,
                    'document_id' => $document->id,
                    'reason' => $validated['rejection_reason'],
                ])
                ->log('Document rejected: ' . $document->document_type);

            DB::commit();

            return back()->with('success', 'Document marked as rejected.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject document: ' . $e->getMessage());
        }
    }

    /**
     * Get registration completion status for a candidate.
     */
    public function status(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $documents = $candidate->documents()->get();
        $requiredDocs = self::REQUIRED_DOCUMENTS;

        $docStatus = [];
        foreach ($requiredDocs as $type) {
            $doc = $documents->where('document_type', $type)->first();
            $docStatus[$type] = [
                'label' => $this->getDocumentLabel($type),
                'uploaded' => $doc !== null,
                'status' => $doc ? $doc->status : 'missing',
                'expired' => $doc && $doc->expiry_date && $doc->expiry_date->isPast(),
                'expiry_date' => $doc && $doc->expiry_date ? $doc->expiry_date->format('Y-m-d') : null,
            ];
        }

        $status = [
            'documents' => $docStatus,
            'documents_complete' => collect($docStatus)->every(fn($d) => $d['uploaded'] && !$d['expired']),
            'next_of_kin' => $candidate->nextOfKin !== null,
            'undertaking' => $candidate->undertakings()->where('is_completed', true)->exists(),
            'can_complete' => false,
        ];

        $status['can_complete'] = $status['documents_complete']
            && $status['next_of_kin']
            && $status['undertaking'];

        if (request()->wantsJson()) {
            return response()->json([
                'candidate' => [
                    'id' => $candidate->id,
                    'btevta_id' => $candidate->btevta_id,
                    'name' => $candidate->name,
                    'status' => $candidate->status,
                ],
                'registration_status' => $status,
            ]);
        }

        return view('registration.status', compact('candidate', 'status'));
    }

    /**
     * Start training for a registered candidate.
     * Transitions status from REGISTERED to TRAINING.
     */
    public function startTraining(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        // Verify candidate is in REGISTERED status
        if ($candidate->status !== 'registered') {
            return back()->with('error', 'Only registered candidates can start training. Current status: ' . $candidate->status);
        }

        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Assign to batch
            $candidate->batch_id = $validated['batch_id'];

            // Update status to training
            $candidate->status = 'training';
            $candidate->training_status = 'in_progress';
            $candidate->training_start_date = now();
            $candidate->save();

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'batch_id' => $validated['batch_id'],
                    'previous_status' => 'registered',
                    'new_status' => 'training',
                ])
                ->log('Training started');

            DB::commit();

            return redirect()->route('training.index')
                ->with('success', 'Candidate has started training!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to start training: ' . $e->getMessage());
        }
    }

    /**
     * Get document type label.
     */
    private function getDocumentLabel($type)
    {
        $labels = [
            'cnic' => 'CNIC Copy',
            'education' => 'Educational Certificate',
            'domicile' => 'Domicile Certificate',
            'photo' => 'Passport Size Photo',
            'passport' => 'Passport Copy',
            'police_clearance' => 'Police Character Certificate',
            'medical' => 'Medical Fitness Certificate',
            'other' => 'Other Document',
        ];

        return $labels[$type] ?? ucfirst($type);
    }
}
