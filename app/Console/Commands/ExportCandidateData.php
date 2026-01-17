<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use ZipArchive;

/**
 * ExportCandidateData Command
 *
 * GDPR-style data export for candidates.
 * Exports all personal data associated with a candidate in a portable format.
 *
 * Compliance: Data portability rights (GDPR Article 20)
 */
class ExportCandidateData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'candidate:export-data
                            {candidate : Candidate ID or CNIC}
                            {--format=json : Export format (json, zip)}
                            {--include-documents : Include uploaded documents in export}
                            {--output= : Output directory}';

    /**
     * The console command description.
     */
    protected $description = 'Export all personal data for a candidate (GDPR compliance)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  CANDIDATE DATA EXPORT - Data Portability');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        try {
            // Find candidate
            $candidate = $this->findCandidate($this->argument('candidate'));

            if (!$candidate) {
                $this->error('Candidate not found.');
                return Command::FAILURE;
            }

            $this->info("Candidate: {$candidate->full_name}");
            $this->info("CNIC: {$candidate->cnic}");
            $this->info("ID: {$candidate->id}");
            $this->newLine();

            // Collect all data
            $this->info('Collecting data...');
            $data = $this->collectCandidateData($candidate);

            // Generate export file
            $format = $this->option('format');
            $includeDocuments = $this->option('include-documents');

            if ($format === 'zip' || $includeDocuments) {
                $filePath = $this->exportAsZip($candidate, $data, $includeDocuments);
            } else {
                $filePath = $this->exportAsJson($candidate, $data);
            }

            // Get file info
            $fileSize = filesize($filePath);
            $fileSizeKb = round($fileSize / 1024, 2);

            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('  EXPORT COMPLETE');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line("  File: {$filePath}");
            $this->line("  Size: {$fileSizeKb} KB");

            // Log the export for audit
            Log::info('Candidate data exported', [
                'candidate_id' => $candidate->id,
                'cnic' => $candidate->cnic,
                'file' => $filePath,
                'include_documents' => $includeDocuments,
                'exported_by' => 'console',
            ]);

            activity()
                ->performedOn($candidate)
                ->withProperties(['file' => basename($filePath)])
                ->log('Personal data exported (GDPR request)');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            Log::error('Candidate data export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Find candidate by ID or CNIC
     */
    private function findCandidate(string $identifier): ?Candidate
    {
        // Try by ID first
        if (is_numeric($identifier)) {
            $candidate = Candidate::find($identifier);
            if ($candidate) {
                return $candidate;
            }
        }

        // Try by CNIC
        return Candidate::where('cnic', $identifier)->first();
    }

    /**
     * Collect all candidate data
     */
    private function collectCandidateData(Candidate $candidate): array
    {
        return [
            'export_info' => [
                'generated_at' => now()->toISOString(),
                'format_version' => '1.0',
                'system' => 'WASL/BTEVTA',
            ],

            'personal_info' => $this->getPersonalInfo($candidate),
            'contact_info' => $this->getContactInfo($candidate),
            'identification' => $this->getIdentificationInfo($candidate),
            'next_of_kin' => $this->getNextOfKinInfo($candidate),
            'education' => $this->getEducationInfo($candidate),
            'screening' => $this->getScreeningData($candidate),
            'training' => $this->getTrainingData($candidate),
            'visa_processing' => $this->getVisaData($candidate),
            'departure' => $this->getDepartureData($candidate),
            'remittances' => $this->getRemittanceData($candidate),
            'complaints' => $this->getComplaintData($candidate),
            'documents' => $this->getDocumentList($candidate),
            'activity_log' => $this->getActivityLog($candidate),
        ];
    }

    /**
     * Get personal information
     */
    private function getPersonalInfo(Candidate $candidate): array
    {
        return [
            'full_name' => $candidate->full_name,
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'father_name' => $candidate->father_name,
            'date_of_birth' => $candidate->date_of_birth?->toDateString(),
            'gender' => $candidate->gender,
            'marital_status' => $candidate->marital_status,
            'religion' => $candidate->religion,
            'nationality' => $candidate->nationality ?? 'Pakistani',
            'blood_group' => $candidate->blood_group,
        ];
    }

    /**
     * Get contact information
     */
    private function getContactInfo(Candidate $candidate): array
    {
        return [
            'phone' => $candidate->phone,
            'mobile' => $candidate->mobile,
            'email' => $candidate->email,
            'whatsapp' => $candidate->whatsapp,
            'address' => [
                'street' => $candidate->address,
                'city' => $candidate->city,
                'district' => $candidate->district,
                'province' => $candidate->province,
                'postal_code' => $candidate->postal_code,
            ],
            'permanent_address' => $candidate->permanent_address,
        ];
    }

    /**
     * Get identification information
     */
    private function getIdentificationInfo(Candidate $candidate): array
    {
        return [
            'cnic' => $candidate->cnic,
            'cnic_expiry' => $candidate->cnic_expiry?->toDateString(),
            'passport_number' => $candidate->passport_number,
            'passport_expiry' => $candidate->passport_expiry?->toDateString(),
            'passport_issue_place' => $candidate->passport_issue_place,
            'btevta_id' => $candidate->btevta_id,
        ];
    }

    /**
     * Get next of kin information
     */
    private function getNextOfKinInfo(Candidate $candidate): array
    {
        return [
            'name' => $candidate->nok_name,
            'relationship' => $candidate->nok_relationship,
            'cnic' => $candidate->nok_cnic,
            'phone' => $candidate->nok_phone,
            'address' => $candidate->nok_address,
        ];
    }

    /**
     * Get education information
     */
    private function getEducationInfo(Candidate $candidate): array
    {
        return [
            'qualification' => $candidate->qualification,
            'institution' => $candidate->institution,
            'year_passed' => $candidate->year_passed,
            'grade' => $candidate->grade,
            'trade' => $candidate->trade?->name,
            'trade_code' => $candidate->trade?->code,
        ];
    }

    /**
     * Get screening data
     */
    private function getScreeningData(Candidate $candidate): array
    {
        $screenings = $candidate->screenings ?? collect();

        return $screenings->map(function ($screening) {
            return [
                'call_number' => $screening->call_number,
                'call_date' => $screening->call_date?->toDateString(),
                'outcome' => $screening->outcome,
                'notes' => $screening->notes,
                'screener' => $screening->screener?->name,
            ];
        })->toArray();
    }

    /**
     * Get training data
     */
    private function getTrainingData(Candidate $candidate): array
    {
        $training = $candidate->training;

        if (!$training) {
            return ['enrolled' => false];
        }

        return [
            'enrolled' => true,
            'batch' => $training->batch?->name,
            'campus' => $training->batch?->campus?->name,
            'start_date' => $training->start_date?->toDateString(),
            'end_date' => $training->end_date?->toDateString(),
            'status' => $training->status,
            'attendance_percentage' => $training->attendance_percentage,
            'assessment_scores' => [
                'midterm' => $training->midterm_score,
                'final' => $training->final_score,
                'practical' => $training->practical_score,
            ],
            'certificate_issued' => $training->certificate_issued,
            'certificate_date' => $training->certificate_date?->toDateString(),
        ];
    }

    /**
     * Get visa processing data
     */
    private function getVisaData(Candidate $candidate): array
    {
        $visa = $candidate->visaProcess;

        if (!$visa) {
            return ['processing' => false];
        }

        return [
            'processing' => true,
            'current_stage' => $visa->current_stage,
            'oep' => $visa->oep?->name,
            'destination_country' => $visa->destination_country,
            'employer' => $visa->employer_name,
            'job_title' => $visa->job_title,
            'stages' => [
                'interview' => [
                    'date' => $visa->interview_date?->toDateString(),
                    'result' => $visa->interview_result,
                ],
                'trade_test' => [
                    'date' => $visa->trade_test_date?->toDateString(),
                    'result' => $visa->trade_test_result,
                ],
                'medical' => [
                    'date' => $visa->medical_date?->toDateString(),
                    'result' => $visa->medical_result,
                    'gamca_number' => $visa->gamca_number,
                ],
                'visa' => [
                    'number' => $visa->visa_number,
                    'issue_date' => $visa->visa_issue_date?->toDateString(),
                    'expiry_date' => $visa->visa_expiry_date?->toDateString(),
                ],
            ],
            'e_number' => $visa->e_number,
            'ptn_number' => $visa->ptn_number,
        ];
    }

    /**
     * Get departure data
     */
    private function getDepartureData(Candidate $candidate): array
    {
        $departure = $candidate->departure;

        if (!$departure) {
            return ['departed' => false];
        }

        return [
            'departed' => true,
            'departure_date' => $departure->departure_date?->toDateString(),
            'flight_number' => $departure->flight_number,
            'airline' => $departure->airline,
            'destination_airport' => $departure->destination_airport,
            'arrival_confirmed' => $departure->arrival_confirmed,
            'arrival_date' => $departure->arrival_date?->toDateString(),
            'iqama_number' => $departure->iqama_number,
            'absher_registered' => $departure->absher_registered,
            'qiwa_id' => $departure->qiwa_id,
            'first_salary_date' => $departure->first_salary_date?->toDateString(),
        ];
    }

    /**
     * Get remittance data
     */
    private function getRemittanceData(Candidate $candidate): array
    {
        $remittances = $candidate->remittances ?? collect();

        return [
            'total_count' => $remittances->count(),
            'total_amount' => $remittances->sum('amount'),
            'records' => $remittances->map(function ($remittance) {
                return [
                    'date' => $remittance->transfer_date?->toDateString(),
                    'amount' => $remittance->amount,
                    'currency' => $remittance->currency,
                    'method' => $remittance->transfer_method,
                    'verified' => $remittance->is_verified,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get complaint data
     */
    private function getComplaintData(Candidate $candidate): array
    {
        $complaints = $candidate->complaints ?? collect();

        return $complaints->map(function ($complaint) {
            return [
                'id' => $complaint->id,
                'category' => $complaint->category,
                'priority' => $complaint->priority,
                'status' => $complaint->status,
                'description' => $complaint->description,
                'created_at' => $complaint->created_at?->toDateString(),
                'resolved_at' => $complaint->resolved_at?->toDateString(),
                'resolution' => $complaint->resolution,
            ];
        })->toArray();
    }

    /**
     * Get document list
     */
    private function getDocumentList(Candidate $candidate): array
    {
        $documents = $candidate->documents ?? collect();

        return $documents->map(function ($doc) {
            return [
                'type' => $doc->document_type,
                'filename' => $doc->original_filename,
                'uploaded_at' => $doc->created_at?->toDateString(),
                'expiry_date' => $doc->expiry_date?->toDateString(),
            ];
        })->toArray();
    }

    /**
     * Get activity log for candidate
     */
    private function getActivityLog(Candidate $candidate): array
    {
        $activities = DB::table('activity_log')
            ->where('subject_type', Candidate::class)
            ->where('subject_id', $candidate->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return $activities->map(function ($activity) {
            return [
                'description' => $activity->description,
                'created_at' => $activity->created_at,
                'properties' => json_decode($activity->properties ?? '{}', true),
            ];
        })->toArray();
    }

    /**
     * Export as JSON file
     */
    private function exportAsJson(Candidate $candidate, array $data): string
    {
        $outputDir = $this->option('output') ?? storage_path('app/exports/gdpr');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = sprintf(
            'candidate_%s_%s.json',
            $candidate->id,
            now()->format('Ymd_His')
        );

        $filePath = $outputDir . '/' . $filename;

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }

    /**
     * Export as ZIP file (with documents)
     */
    private function exportAsZip(Candidate $candidate, array $data, bool $includeDocuments): string
    {
        $outputDir = $this->option('output') ?? storage_path('app/exports/gdpr');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = sprintf(
            'candidate_%s_%s.zip',
            $candidate->id,
            now()->format('Ymd_His')
        );

        $filePath = $outputDir . '/' . $filename;

        $zip = new ZipArchive();
        $zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Add data JSON
        $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Add README
        $readme = $this->generateReadme($candidate);
        $zip->addFromString('README.txt', $readme);

        // Add documents if requested
        if ($includeDocuments) {
            $documents = $candidate->documents ?? collect();

            foreach ($documents as $doc) {
                if (Storage::disk('private')->exists($doc->file_path)) {
                    $content = Storage::disk('private')->get($doc->file_path);
                    $zip->addFromString('documents/' . $doc->original_filename, $content);
                }
            }
        }

        $zip->close();

        return $filePath;
    }

    /**
     * Generate README for export
     */
    private function generateReadme(Candidate $candidate): string
    {
        return <<<README
WASL/BTEVTA - Personal Data Export
===================================

Candidate: {$candidate->full_name}
CNIC: {$candidate->cnic}
Export Date: {$this->getFormattedDate()}

Contents:
- data.json: All personal data in JSON format
- documents/: Uploaded documents (if included)

This export contains all personal data associated with your
profile in the WASL system. The data is provided in accordance
with data portability rights.

For questions or data correction requests, contact:
Email: support@theleap.org

Generated by WASL v1.3.0
README;
    }

    /**
     * Get formatted date
     */
    private function getFormattedDate(): string
    {
        return now()->format('Y-m-d H:i:s');
    }
}
