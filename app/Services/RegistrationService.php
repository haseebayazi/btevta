<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Oep;
use App\Models\RegistrationDocument;
use App\Models\Undertaking;
use App\Services\AllocationService;
use App\Services\AutoBatchService;
use App\Services\ScreeningService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    /**
     * The allocation service instance.
     */
    protected AllocationService $allocationService;

    /**
     * The auto batch service instance.
     */
    protected AutoBatchService $autoBatchService;

    /**
     * The screening service instance.
     */
    protected ScreeningService $screeningService;

    /**
     * Create a new service instance.
     */
    public function __construct(
        AllocationService $allocationService,
        AutoBatchService $autoBatchService,
        ScreeningService $screeningService
    ) {
        $this->allocationService = $allocationService;
        $this->autoBatchService = $autoBatchService;
        $this->screeningService = $screeningService;
    }

    /**
     * Get required documents list
     */
    public function getRequiredDocuments(): array
    {
        return [
            'cnic' => 'CNIC Copy',
            'education' => 'Educational Certificate',
            'domicile' => 'Domicile Certificate',
            'photo' => 'Passport Size Photo',
            'passport' => 'Passport Copy (if available)',
            'police_clearance' => 'Police Character Certificate',
            'medical' => 'Medical Fitness Certificate',
        ];
    }

    /**
     * Count candidates with complete documents
     */
    public function countCompleteDocuments(): int
    {
        $requiredDocs = ['cnic', 'education', 'domicile', 'photo'];
        
        return Candidate::whereHas('registrationDocuments', function($q) use ($requiredDocs) {
            $q->whereIn('document_type', $requiredDocs)
              ->where('status', 'verified')
              ->groupBy('candidate_id')
              ->havingRaw('COUNT(DISTINCT document_type) = ?', [count($requiredDocs)]);
        })->count();
    }

    /**
     * Check document completeness for a candidate
     */
    public function checkDocumentCompleteness($candidate): array
    {
        $required = ['cnic', 'education', 'domicile', 'photo'];
        $optional = ['passport', 'police_clearance', 'medical'];
        
        $uploaded = $candidate->registrationDocuments()
            ->pluck('status', 'document_type')
            ->toArray();

        $result = [
            'required' => [],
            'optional' => [],
            'is_complete' => true,
            'completion_percentage' => 0
        ];

        foreach ($required as $doc) {
            $result['required'][$doc] = [
                'uploaded' => isset($uploaded[$doc]),
                'status' => $uploaded[$doc] ?? 'missing',
                'label' => $this->getRequiredDocuments()[$doc]
            ];
            
            if (!isset($uploaded[$doc]) || $uploaded[$doc] !== 'verified') {
                $result['is_complete'] = false;
            }
        }

        foreach ($optional as $doc) {
            $result['optional'][$doc] = [
                'uploaded' => isset($uploaded[$doc]),
                'status' => $uploaded[$doc] ?? 'not_required',
                'label' => $this->getRequiredDocuments()[$doc] ?? ucfirst(str_replace('_', ' ', $doc))
            ];
        }

        $totalRequired = count($required);
        $uploadedRequired = count(array_filter($result['required'], function($doc) {
            return $doc['status'] === 'verified';
        }));
        
        $result['completion_percentage'] = round(($uploadedRequired / $totalRequired) * 100);

        return $result;
    }

    /**
     * Generate undertaking content
     */
    public function generateUndertakingContent($candidate): string
    {
        $template = "
            GOVERNMENT OF PUNJAB
            PUNJAB BOARD OF TECHNICAL EDUCATION
            UNDERTAKING FOR OVERSEAS EMPLOYMENT TRAINING

            Date: " . now()->format('d F, Y') . "

            I, {$candidate->name}, S/O / D/O {$candidate->father_name}, 
            holding CNIC No. {$candidate->formatted_cnic}, 
            resident of {$candidate->address}, District {$candidate->district}, 
            Province {$candidate->province}, 

            Do hereby solemnly declare and undertake that:

            1. All the information provided by me in the application form and during the registration process is true, correct, and complete to the best of my knowledge and belief.

            2. I have not suppressed any material information which may render me unsuitable for the overseas employment program.

            3. I understand that any false declaration or concealment of information will result in immediate cancellation of my candidature/selection at any stage.

            4. I will strictly abide by all rules, regulations, and code of conduct during the training period.

            5. I will maintain at least 80% attendance during the training program and understand that failure to do so may result in termination from the program.

            6. I will appear in all assessments, examinations, and evaluations as required during the training.

            7. Upon successful completion of training and selection for overseas employment:
               a) I will comply with all laws and regulations of the host country
               b) I will uphold the dignity and reputation of Pakistan
               c) I will not engage in any illegal or unethical activities
               d) I will return to Pakistan upon completion of my employment contract

            8. I understand that the training does not guarantee overseas employment and selection will be based on merit and employer requirements.

            9. I will not hold the Punjab Board of Technical Education or any associated institution responsible for any issues arising during my overseas employment.

            10. I authorize the Board to verify all my documents and credentials from the concerned authorities.

            11. I will provide accurate contact information and keep the Board informed of any changes in my contact details.

            12. In case of any emergency or requirement, I will cooperate fully with the Board and relevant authorities.


            DECLARATION:
            I have read and understood all the above terms and conditions and agree to abide by them. This undertaking is given by me voluntarily without any coercion or undue influence.


            _____________________
            Signature of Candidate
            Name: {$candidate->name}
            CNIC: {$candidate->formatted_cnic}
            Date: " . now()->format('d-m-Y') . "


            ATTESTATION BY GUARDIAN/PARENT:
            I, _____________________, " . ($candidate->nextOfKin?->relationship ?? 'Guardian') . " of the above-named candidate,
            have read and understood the undertaking and consent to the candidate's participation in the program.

            _____________________
            Signature of Guardian/Parent
            Name: " . ($candidate->nextOfKin?->name ?? '') . "
            CNIC: " . ($candidate->nextOfKin?->formatted_cnic ?? '') . "
            Contact: " . ($candidate->nextOfKin?->phone ?? '') . "


            FOR OFFICIAL USE ONLY:
            Verified and Accepted by:
            
            _____________________
            Registration Officer
            Name: 
            Designation:
            Date:
            
            Official Stamp/Seal
        ";

        return $template;
    }

    /**
     * Generate undertaking PDF
     */
    public function generateUndertakingPDF($candidate): \Barryvdh\DomPDF\PDF
    {
        $data = [
            'candidate' => $candidate,
            'content' => $this->generateUndertakingContent($candidate),
            'date' => now()->format('d F, Y'),
            'qr_code' => $this->generateQRCode($candidate)
        ];

        $pdf = PDF::loadView('registration.undertaking-pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf;
    }

    /**
     * Generate QR code for verification
     * AUDIT FIX: Use cryptographically secure signed URLs instead of predictable MD5 hash
     */
    protected function generateQRCode($candidate): string
    {
        // Generate a cryptographically secure random token and persist it for verification
        $token = bin2hex(random_bytes(16));

        // Persist token and timestamp on the candidate (idempotent)
        try {
            $candidate->update([
                'registration_verification_token' => $token,
                'registration_verification_sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If persistence fails, fall back to an in-memory signed url using deterministic token
            \Log::warning('Failed to persist registration verification token: ' . $e->getMessage());
            $token = hash('sha256', $candidate->id . $candidate->cnic . config('app.key'));
        }

        // Use Laravel's signed URL feature to generate tamper-proof verification link
        $verificationUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'registration.verify',
            [
                'id' => $candidate->id,
                'token' => $token,
            ],
            now()->addDays(365) // Signature valid for 1 year
        );

        return $verificationUrl;
    }

    /**
     * Allocate OEP (Overseas Employment Promoter)
     *
     * AUDIT FIX: Implemented proper database-driven load balancing.
     * Previously used random selection which could cause uneven distribution.
     * Now queries the database to find OEP with least active candidates.
     */
    public function allocateOEP($candidate): ?int
    {
        // Get candidate's trade for OEP matching
        $tradeId = $candidate->trade_id;

        // Build query for active OEPs that handle this trade
        $query = Oep::where('is_active', true);

        // If trade specified, prefer OEPs that specialize in this trade
        // (assuming OEPs might have a trades relationship or trade_ids field)

        // Use database-driven load balancing: select OEP with least candidates
        $oepWithLeastCandidates = $query
            ->withCount(['candidates' => function ($q) {
                // Count only active candidates (not departed/rejected)
                $q->whereNotIn('status', ['departed', 'rejected', 'dropped', 'returned']);
            }])
            ->orderBy('candidates_count', 'asc')
            ->first();

        if ($oepWithLeastCandidates) {
            // Log the allocation for audit purposes
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'oep_id' => $oepWithLeastCandidates->id,
                    'oep_code' => $oepWithLeastCandidates->oep_code ?? $oepWithLeastCandidates->id,
                    'current_candidates' => $oepWithLeastCandidates->candidates_count,
                ])
                ->log('OEP auto-allocated based on load balancing');

            return $oepWithLeastCandidates->id;
        }

        // Fallback: return null if no active OEPs found
        \Log::warning('No active OEPs available for allocation', [
            'candidate_id' => $candidate->id,
            'trade_id' => $tradeId,
        ]);

        return null;
    }

    /**
     * Validate document authenticity
     *
     * AUDIT FIX: Implemented proper document validation with MIME type verification.
     * Previously only checked file size. Now validates file type, MIME, and format.
     */
    public function validateDocument($documentPath, $type): array
    {
        // Check file existence
        if (!Storage::disk('public')->exists($documentPath)) {
            return ['valid' => false, 'reason' => 'File not found'];
        }

        try {
            $fullPath = Storage::disk('public')->path($documentPath);
            $size = Storage::disk('public')->size($documentPath);
            $mimeType = Storage::disk('public')->mimeType($documentPath);
        } catch (\Exception $e) {
            return ['valid' => false, 'reason' => 'Error reading file: ' . $e->getMessage()];
        }

        // Size validation
        if ($size < 1024) { // Less than 1KB
            return ['valid' => false, 'reason' => 'File too small - may be corrupted'];
        }

        if ($size > 10485760) { // More than 10MB
            return ['valid' => false, 'reason' => 'File too large - max 10MB allowed'];
        }

        // MIME type validation
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return [
                'valid' => false,
                'reason' => 'Invalid file type. Allowed: PDF, JPEG, PNG',
            ];
        }

        // Type-specific validation
        $typeValidation = $this->validateDocumentType($type, $mimeType, $size);
        if (!$typeValidation['valid']) {
            return $typeValidation;
        }

        return [
            'valid' => true,
            'mime_type' => $mimeType,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
        ];
    }

    /**
     * Validate specific document type requirements.
     */
    private function validateDocumentType(string $type, string $mimeType, int $size): array
    {
        switch ($type) {
            case 'cnic':
                // CNIC should be image (scanned) - allow PDF or images
                // Minimum 50KB for readable scan
                if ($size < 51200) {
                    return ['valid' => false, 'reason' => 'CNIC scan appears too small - minimum 50KB for readability'];
                }
                break;

            case 'passport':
                // Passport typically should be PDF or high-quality image
                if ($size < 102400) { // 100KB minimum
                    return ['valid' => false, 'reason' => 'Passport scan appears too small - minimum 100KB for readability'];
                }
                break;

            case 'education':
            case 'education_certificate':
                // Education certificates should have reasonable size
                if ($size < 51200) {
                    return ['valid' => false, 'reason' => 'Education certificate scan too small'];
                }
                break;

            case 'photo':
                // Photo must be image type, not PDF
                if ($mimeType === 'application/pdf') {
                    return ['valid' => false, 'reason' => 'Photo must be JPEG or PNG, not PDF'];
                }
                // Photo should be at least 20KB for passport-size quality
                if ($size < 20480) {
                    return ['valid' => false, 'reason' => 'Photo too small - minimum 20KB for passport quality'];
                }
                break;

            case 'medical_certificate':
            case 'police_clearance':
                // Official documents typically PDF
                // Allow any format but warn if very small
                if ($size < 30720) {
                    return ['valid' => false, 'reason' => 'Document appears too small to be a valid scan'];
                }
                break;
        }

        return ['valid' => true];
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Create registration summary
     */
    public function createRegistrationSummary($candidate): array
    {
        $documents = $candidate->registrationDocuments;
        $nextOfKin = $candidate->nextOfKin;
        $undertaking = $candidate->undertakings()->latest()->first();

        return [
            'candidate_info' => [
                'name' => $candidate->name,
                'cnic' => $candidate->formatted_cnic,
                'application_id' => $candidate->application_id,
                // NULL CHECK: Handle case when registration_date is null
                'registration_date' => $candidate->registration_date?->format('d-m-Y') ?? 'N/A',
                'status' => $candidate->status_label,
            ],
            'documents' => $documents->map(function($doc) {
                return [
                    'type' => $doc->document_type,
                    'status' => $doc->status,
                    'uploaded_date' => $doc->created_at->format('d-m-Y'),
                    'verified' => $doc->verified_at ? $doc->verified_at->format('d-m-Y') : 'Pending'
                ];
            }),
            'next_of_kin' => $nextOfKin ? [
                'name' => $nextOfKin->name,
                'relationship' => $nextOfKin->relationship_label,
                'contact' => $nextOfKin->phone,
            ] : null,
            'undertaking' => $undertaking ? [
                'signed' => $undertaking->status === 'signed',
                'date' => $undertaking->signed_at ? $undertaking->signed_at->format('d-m-Y') : null,
            ] : null,
            'completion' => $this->checkDocumentCompleteness($candidate),
        ];
    }

    // =========================================================================
    // UPDATED REGISTRATION WORKFLOW (WASL v3)
    // =========================================================================

    /**
     * Register a candidate with allocation and auto-batch assignment.
     *
     * @param Candidate $candidate
     * @param array $registrationData
     * @return array
     * @throws \Exception
     */
    public function registerCandidateWithAllocation(Candidate $candidate, array $registrationData): array
    {
        // Check if candidate can proceed to registration (must be screened)
        $eligibility = $this->screeningService->canProceedToRegistration($candidate);
        if (!$eligibility['can_proceed']) {
            throw new \Exception($eligibility['reason']);
        }

        DB::beginTransaction();
        try {
            // Step 1: Allocate campus, program, implementing partner, and trade
            $allocationData = [
                'campus_id' => $registrationData['campus_id'],
                'program_id' => $registrationData['program_id'],
                'implementing_partner_id' => $registrationData['implementing_partner_id'] ?? null,
                'trade_id' => $registrationData['trade_id'],
                'oep_id' => $registrationData['oep_id'] ?? $this->allocateOEP($candidate),
            ];

            $candidate = $this->allocationService->allocate($candidate, $allocationData);

            // Step 2: Auto-create or assign to batch
            $batch = $this->autoBatchService->assignOrCreateBatch($candidate);

            // Step 3: Update candidate with registration data
            $candidate->update([
                'status' => \App\Enums\CandidateStatus::REGISTERED->value,
                'registration_date' => now(),
            ]);

            DB::commit();

            // Log registration
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_code,
                    'allocated_number' => $candidate->allocated_number,
                ])
                ->log('Candidate registered with allocation and batch assignment');

            return [
                'success' => true,
                'candidate' => $candidate->fresh(),
                'batch' => $batch,
                'allocation' => $this->allocationService->getAllocationSummary($candidate),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get registration statistics.
     *
     * @return array
     */
    public function getRegistrationStatistics(): array
    {
        $total = Candidate::where('status', \App\Enums\CandidateStatus::REGISTERED->value)->count();

        $byBatch = DB::table('candidates')
            ->select('batch_id', DB::raw('COUNT(*) as count'))
            ->where('status', \App\Enums\CandidateStatus::REGISTERED->value)
            ->whereNotNull('batch_id')
            ->groupBy('batch_id')
            ->get();

        $byCampus = DB::table('candidates')
            ->select('campus_id', DB::raw('COUNT(*) as count'))
            ->where('status', \App\Enums\CandidateStatus::REGISTERED->value)
            ->whereNotNull('campus_id')
            ->groupBy('campus_id')
            ->get();

        $byProgram = DB::table('candidates')
            ->select('program_id', DB::raw('COUNT(*) as count'))
            ->where('status', \App\Enums\CandidateStatus::REGISTERED->value)
            ->whereNotNull('program_id')
            ->groupBy('program_id')
            ->get();

        return [
            'total_registered' => $total,
            'by_batch' => $byBatch,
            'by_campus' => $byCampus,
            'by_program' => $byProgram,
            'registrations_today' => Candidate::where('status', \App\Enums\CandidateStatus::REGISTERED->value)
                ->whereDate('registration_date', today())
                ->count(),
            'registrations_this_week' => Candidate::where('status', \App\Enums\CandidateStatus::REGISTERED->value)
                ->whereBetween('registration_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'registrations_this_month' => Candidate::where('status', \App\Enums\CandidateStatus::REGISTERED->value)
                ->whereMonth('registration_date', now()->month)
                ->whereYear('registration_date', now()->year)
                ->count(),
        ];
    }

    /**
     * Validate candidate eligibility for registration.
     *
     * @param Candidate $candidate
     * @return array
     */
    public function validateRegistrationEligibility(Candidate $candidate): array
    {
        $errors = [];

        // Check screening status
        $screeningCheck = $this->screeningService->canProceedToRegistration($candidate);
        if (!$screeningCheck['can_proceed']) {
            $errors[] = $screeningCheck['reason'];
        }

        // Check if candidate already registered
        if ($candidate->status === \App\Enums\CandidateStatus::REGISTERED->value) {
            $errors[] = 'Candidate is already registered.';
        }

        // Check if candidate has required pre-departure documents
        // (if documents are mandatory before registration)
        // This can be customized based on requirements

        return [
            'is_eligible' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Bulk register candidates with allocation and batch assignment.
     *
     * @param array $candidateIds
     * @param array $registrationData
     * @return array
     */
    public function bulkRegisterCandidates(array $candidateIds, array $registrationData): array
    {
        $successful = [];
        $failed = [];

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $candidateId) {
                try {
                    $candidate = Candidate::findOrFail($candidateId);
                    $result = $this->registerCandidateWithAllocation($candidate, $registrationData);
                    $successful[] = [
                        'candidate_id' => $candidateId,
                        'batch_number' => $result['batch']->batch_code,
                        'allocated_number' => $result['candidate']->allocated_number,
                    ];
                } catch (\Exception $e) {
                    $failed[] = [
                        'candidate_id' => $candidateId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            // Log bulk registration
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'successful_count' => count($successful),
                    'failed_count' => count($failed),
                ])
                ->log('Bulk candidate registration');

            return [
                'successful' => $successful,
                'failed' => $failed,
                'total_processed' => count($candidateIds),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}