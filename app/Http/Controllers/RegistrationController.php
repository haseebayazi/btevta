<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\ImplementingPartner;
use App\Models\Course;
use App\Models\PaymentMethod;
use App\Enums\CandidateStatus;
use App\Http\Requests\RegistrationAllocationRequest;
use App\Services\AutoBatchService;
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

        $query = Candidate::with(['trade', 'campus', 'batch', 'documents', 'nextOfKin'])
            ->where('status', CandidateStatus::SCREENED->value);

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
    public function show($id)
    {
        $candidate = Candidate::findOrFail($id);

        $this->authorize('view', $candidate);

        $candidate->load([
            'documents', 'preDepartureDocuments.documentChecklist',
            'nextOfKin', 'nextOfKin.paymentMethod', 'undertakings',
            'campus', 'trade', 'program', 'batch', 'oep', 'implementingPartner', 'courses',
        ]);

        // Count mandatory pre-departure docs uploaded and verified
        $mandatoryChecklistIds = \App\Models\DocumentChecklist::where('is_mandatory', true)
            ->where('is_active', true)
            ->pluck('id');
        $uploadedMandatoryCount = $candidate->preDepartureDocuments
            ->whereIn('document_checklist_id', $mandatoryChecklistIds)
            ->count();
        $totalMandatory = $mandatoryChecklistIds->count();
        $allPreDepartureDocsUploaded = $uploadedMandatoryCount >= $totalMandatory && $totalMandatory > 0;

        return view('registration.show', compact('candidate', 'allPreDepartureDocsUploaded'));
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

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully',
                    'document' => $document,
                ]);
            }

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

            $nok = NextOfKin::updateOrCreate(
                ['candidate_id' => $candidate->id],
                $validated
            );

            // Link NOK to candidate via belongsTo relationship
            if (!$candidate->next_of_kin_id || $candidate->next_of_kin_id !== $nok->id) {
                $candidate->update(['next_of_kin_id' => $nok->id]);
            }

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
     * Gate: Only screened candidates can proceed to registration.
     */
    public function completeRegistration(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        // Gate: Only screened candidates can complete registration
        if ($candidate->status !== CandidateStatus::SCREENED->value) {
            return back()->with('error', 'Only screened candidates can be registered. Current status: ' . ucfirst(str_replace('_', ' ', $candidate->status)));
        }

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

            // AUDIT FIX: Use CandidateStatus enum instead of hardcoded string
            $candidate->status = CandidateStatus::REGISTERED->value;
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

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document verified successfully',
                    'document' => $document->fresh(),
                ]);
            }

            return back()->with('success', 'Document verified successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify document: ' . $e->getMessage(),
                ], 500);
            }

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
            'rejection_reason' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:500',
        ]);

        $rejectionReason = $validated['rejection_reason'] ?? $validated['reason'] ?? null;

        if (!$rejectionReason) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rejection reason is required',
                ], 422);
            }
            return back()->withErrors(['rejection_reason' => 'Rejection reason is required']);
        }

        try {
            DB::beginTransaction();

            $document->status = 'rejected';
            $document->verification_status = 'rejected';
            $document->verification_remarks = $rejectionReason;
            $document->rejection_reason = $rejectionReason;  // Also set rejection_reason column if it exists
            $document->updated_by = auth()->id();
            $document->save();

            activity()
                ->performedOn($document->candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'document_type' => $document->document_type,
                    'document_id' => $document->id,
                    'reason' => $rejectionReason,
                ])
                ->log('Document rejected: ' . $document->document_type);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document marked as rejected',
                    'document' => $document->fresh(),
                ]);
            }

            return back()->with('success', 'Document marked as rejected.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject document: ' . $e->getMessage(),
                ], 500);
            }

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

        // Identify missing requirements
        $missingRequirements = [];
        if (!$status['documents_complete']) {
            foreach ($docStatus as $type => $doc) {
                if (!$doc['uploaded'] || $doc['expired']) {
                    $missingRequirements[] = $doc['label'];
                }
            }
        }
        if (!$status['next_of_kin']) {
            $missingRequirements[] = 'Next of Kin information';
        }
        if (!$status['undertaking']) {
            $missingRequirements[] = 'Undertaking form';
        }

        if (request()->wantsJson()) {
            return response()->json([
                'candidate' => [
                    'id' => $candidate->id,
                    'btevta_id' => $candidate->btevta_id,
                    'name' => $candidate->name,
                    'status' => $candidate->status,
                ],
                'documents' => $docStatus,
                'next_of_kin' => $status['next_of_kin'],
                'undertaking' => $status['undertaking'],
                'can_start_training' => $status['can_complete'],
                'missing_requirements' => $missingRequirements,
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

        // AUDIT FIX: Use CandidateStatus enum for status comparison
        if ($candidate->status !== CandidateStatus::REGISTERED->value) {
            $message = 'Only registered candidates can start training. Current status: ' . $candidate->status;
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // Check registration requirements
        $missingRequirements = [];

        // Check documents
        $requiredDocs = ['cnic', 'education', 'photo'];
        $verifiedDocs = $candidate->documents()
            ->whereIn('document_type', $requiredDocs)
            ->where('status', 'verified')
            ->pluck('document_type')
            ->toArray();

        if (count($verifiedDocs) < count($requiredDocs)) {
            $missing = array_diff($requiredDocs, $verifiedDocs);
            $missingRequirements[] = 'Missing verified documents: ' . implode(', ', $missing);
        }

        // Check next of kin
        if (!$candidate->nextOfKin && !$candidate->next_of_kin_id) {
            $missingRequirements[] = 'Next of Kin information is required';
        }

        // Check undertaking
        if (!$candidate->undertakings()->where('is_completed', true)->exists()) {
            $missingRequirements[] = 'Completed undertaking is required';
        }

        if (!empty($missingRequirements)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot start training. Missing requirements.',
                    'missing_requirements' => $missingRequirements,
                ], 422);
            }
            return back()->with('error', 'Cannot start training: ' . implode('; ', $missingRequirements));
        }

        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Assign to batch
            $candidate->batch_id = $validated['batch_id'];

            // AUDIT FIX: Use CandidateStatus enum instead of hardcoded string
            $candidate->status = CandidateStatus::TRAINING->value;
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

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Training started successfully',
                    'candidate' => $candidate->fresh(),
                ]);
            }

            return redirect()->route('training.index')
                ->with('success', 'Candidate has started training!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start training: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to start training: ' . $e->getMessage());
        }
    }

    /**
     * Verify registration via QR code (public route).
     * AUDIT FIX: Added this method for QR code verification from undertaking PDFs.
     * Uses signed URLs for tamper-proof verification.
     *
     * @param int $id Candidate ID
     * @param string $token Verification token (SHA-256 hash)
     */
    public function verifyQRCode(Request $request, $id, $token)
    {
        // Find the candidate
        $candidate = Candidate::with(['campus', 'trade', 'documents', 'undertakings'])
            ->find($id);

        if (!$candidate) {
            return response()->view('registration.verify-result', [
                'success' => false,
                'message' => 'Candidate not found.',
            ], 404);
        }

        // If a persisted token exists, prefer it for verification (more secure & random)
        if (!empty($candidate->registration_verification_token)) {
            if (!hash_equals($candidate->registration_verification_token, $token)) {
                return response()->view('registration.verify-result', [
                    'success' => false,
                    'message' => 'Invalid verification token.',
                ], 403);
            }
        } else {
            // Fallback for legacy tokens: deterministic SHA-256 based token
            $expectedToken = hash('sha256', $candidate->id . $candidate->cnic . config('app.key'));

            if (!hash_equals($expectedToken, $token)) {
                return response()->view('registration.verify-result', [
                    'success' => false,
                    'message' => 'Invalid verification token.',
                ], 403);
            }
        }

        // Verification successful - return candidate registration status
        $registrationComplete = $candidate->status !== 'new' && $candidate->status !== 'screening';

        return view('registration.verify-result', [
            'success' => true,
            'candidate' => [
                'name' => $candidate->name,
                'btevta_id' => $candidate->btevta_id,
                'status' => $candidate->status,
                'campus' => $candidate->campus?->name ?? 'Not Assigned',
                'trade' => $candidate->trade?->name ?? 'Not Assigned',
                'registration_date' => $candidate->registration_date?->format('d M, Y'),
            ],
            'registration_complete' => $registrationComplete,
            'message' => $registrationComplete
                ? 'Registration verified successfully.'
                : 'Registration is pending or incomplete.',
        ]);
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

    /**
     * AUDIT FIX: Added missing CRUD methods for Route::resource()
     */

    /**
     * Show the form for creating a new registration.
     * Note: Registration is typically initiated from candidate workflow,
     * redirecting to registration.show for existing candidates.
     */
    public function create()
    {
        // Registration creation is handled through the candidate workflow
        // Redirect to candidates listing where users can select a candidate to register
        return redirect()->route('candidates.index')
            ->with('info', 'Select a candidate to start the registration process.');
    }

    /**
     * Store a newly created registration.
     * Note: Registration records are created as part of the candidate workflow.
     */
    public function store(Request $request)
    {
        // Registration is typically created when a candidate progresses through screening
        // This stub redirects to the proper workflow
        return redirect()->route('candidates.index')
            ->with('info', 'Registrations are created through the candidate workflow.');
    }

    /**
     * Show the form for editing a registration.
     */
    public function edit($id)
    {
        // Registration editing is done through registration.show
        return redirect()->route('registration.show', $id);
    }

    /**
     * Update the specified registration.
     */
    public function update(Request $request, $id)
    {
        // Registration updates are handled through specific endpoints
        // (upload-document, next-of-kin, undertaking, complete)
        return redirect()->route('registration.show', $id)
            ->with('info', 'Use the specific forms to update registration details.');
    }

    /**
     * Show allocation form for registration.
     * Module 3: Registration with Campus, Program, OEP, Implementing Partner allocation.
     */
    public function allocation(Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        // Verify candidate is screened (Module 3 gate)
        if ($candidate->status !== CandidateStatus::SCREENED->value) {
            return redirect()->route('registration.show', $candidate)
                ->with('error', 'Only screened candidates can proceed to allocation. Current status: ' . ucfirst(str_replace('_', ' ', $candidate->status)));
        }

        $candidate->load(['campus', 'trade', 'program', 'oep', 'implementingPartner', 'nextOfKin']);

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $trades = Trade::orderBy('name')->get();
        $oeps = Oep::where('is_active', true)->orderBy('name')->get();
        $partners = ImplementingPartner::where('is_active', true)->orderBy('name')->get();
        $courses = Course::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('display_order')->get();
        $batchSizes = config('wasl.allowed_batch_sizes', [20, 25, 30]);
        $relationships = NextOfKin::getRelationshipTypes();

        return view('registration.allocation', compact(
            'candidate', 'campuses', 'programs', 'trades', 'oeps', 'partners',
            'courses', 'paymentMethods', 'batchSizes', 'relationships'
        ));
    }

    /**
     * Store allocation and complete registration.
     * Module 3: Auto-batch assignment, course assignment, and NOK with financial details.
     */
    public function storeAllocation(RegistrationAllocationRequest $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        // Verify candidate is screened (Module 3 gate)
        if ($candidate->status !== CandidateStatus::SCREENED->value) {
            return redirect()->route('registration.show', $candidate)
                ->with('error', 'Only screened candidates can proceed to registration. Current status: ' . ucfirst(str_replace('_', ' ', $candidate->status)));
        }

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // 1. Update candidate allocation
            $candidate->update([
                'campus_id' => $validated['campus_id'],
                'program_id' => $validated['program_id'],
                'trade_id' => $validated['trade_id'],
                'oep_id' => $validated['oep_id'] ?? null,
                'implementing_partner_id' => $validated['implementing_partner_id'] ?? null,
            ]);

            // 2. Auto-assign to batch using AutoBatchService
            $autoBatchService = app(AutoBatchService::class);
            $batch = $autoBatchService->assignOrCreateBatch($candidate);

            // 3. Assign course
            $candidate->courses()->attach($validated['course_id'], [
                'start_date' => $validated['course_start_date'],
                'end_date' => $validated['course_end_date'],
                'status' => 'assigned',
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);

            // 4. Update/Create Next of Kin with financial details
            $nokData = [
                'name' => $validated['nok_name'],
                'relationship' => $validated['nok_relationship'],
                'cnic' => $validated['nok_cnic'],
                'phone' => $validated['nok_phone'],
                'address' => $validated['nok_address'] ?? null,
                'payment_method_id' => $validated['nok_payment_method_id'],
                'account_number' => $validated['nok_account_number'],
                'bank_name' => $validated['nok_bank_name'] ?? null,
            ];

            // Handle ID card upload
            if ($request->hasFile('nok_id_card')) {
                $file = $request->file('nok_id_card');
                $path = $file->store('next-of-kin/id-cards/' . $candidate->id, 'private');
                $nokData['id_card_path'] = $path;
            }

            $nok = NextOfKin::updateOrCreate(
                ['candidate_id' => $candidate->id],
                $nokData
            );

            // 5. Update candidate status to registered and link NOK
            $candidate->update([
                'status' => CandidateStatus::REGISTERED->value,
                'registration_date' => now(),
                'next_of_kin_id' => $nok->id,
            ]);

            // Log activity
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'allocated_number' => $candidate->allocated_number,
                    'program_id' => $validated['program_id'],
                    'course_id' => $validated['course_id'],
                ])
                ->log('Registration completed with allocation');

            DB::commit();

            return redirect()->route('registration.show', $candidate)
                ->with('success', 'Registration completed successfully! Allocated Number: ' . $candidate->allocated_number . ' | Batch: ' . $batch->batch_code);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified registration.
     * Note: Registration records should not be deleted directly.
     */
    public function destroy($id)
    {
        // Registration records are tied to candidates and should not be deleted independently
        return redirect()->route('registration.index')
            ->with('error', 'Registration records cannot be deleted. Manage candidates instead.');
    }
}
