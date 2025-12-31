<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\RegistrationDocument;
use App\Models\Undertaking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RegistrationService
{
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
            I, _____________________, {$candidate->nextOfKin?->relationship ?? 'Guardian'} of the above-named candidate,
            have read and understood the undertaking and consent to the candidate's participation in the program.

            _____________________
            Signature of Guardian/Parent
            Name: {$candidate->nextOfKin?->name ?? ''}
            CNIC: {$candidate->nextOfKin?->formatted_cnic ?? ''}
            Contact: {$candidate->nextOfKin?->phone ?? ''}


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
        // SECURITY: Use Laravel's signed URL feature for tamper-proof verification
        // This generates a URL with a cryptographic signature that expires
        $verificationUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'registration.verify',
            [
                'id' => $candidate->id,
                'token' => hash('sha256', $candidate->id . $candidate->cnic . config('app.key'))
            ],
            now()->addDays(365) // Signature valid for 1 year
        );

        // Return signed URL for QR code generation
        return $verificationUrl;
    }

    /**
     * Allocate OEP (Overseas Employment Promoter)
     */
    public function allocateOEP($candidate): string
    {
        // Logic to auto-allocate OEP based on trade, district, etc.
        $oepMapping = [
            'electrician' => ['OEP001', 'OEP002'],
            'plumber' => ['OEP003', 'OEP004'],
            'welder' => ['OEP005', 'OEP006'],
            // Add more mappings
        ];

        // NULL CHECK: Handle case when trade relationship is null
        $trade = $candidate->trade?->code ?? null;
        $availableOEPs = $oepMapping[$trade] ?? ['OEP_DEFAULT'];
        
        // Select OEP with least candidates
        // This is simplified - in production, you'd query the database
        return $availableOEPs[array_rand($availableOEPs)];
    }

    /**
     * Validate document authenticity
     */
    public function validateDocument($documentPath, $type): array
    {
        // This could integrate with AI/ML services for document verification
        // For now, basic validation

        // ERROR HANDLING: Check file existence
        if (!Storage::disk('public')->exists($documentPath)) {
            return ['valid' => false, 'reason' => 'File not found'];
        }

        try {
            $file = Storage::disk('public')->get($documentPath);
            $size = strlen($file);
        } catch (\Exception $e) {
            return ['valid' => false, 'reason' => 'Error reading file: ' . $e->getMessage()];
        }

        // Basic size validation
        if ($size < 1024) { // Less than 1KB
            return ['valid' => false, 'reason' => 'File too small'];
        }

        if ($size > 10485760) { // More than 10MB
            return ['valid' => false, 'reason' => 'File too large'];
        }

        // Type-specific validation could go here
        switch ($type) {
            case 'cnic':
                // Could use OCR to validate CNIC format
                break;
            case 'education':
                // Could verify with education board APIs
                break;
        }

        return ['valid' => true];
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
}