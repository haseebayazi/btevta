<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use App\Models\PreDepartureDocumentPage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class PreDepartureDocumentService
{
    /**
     * Upload a pre-departure document for a candidate
     *
     * @param Candidate $candidate
     * @param DocumentChecklist $checklist
     * @param UploadedFile $file
     * @param array $metadata Additional metadata (notes, document_number, dates, etc.)
     * @return PreDepartureDocument
     * @throws \Exception
     */
    public function uploadDocument(
        Candidate $candidate,
        DocumentChecklist $checklist,
        UploadedFile $file,
        array $metadata = []
    ): PreDepartureDocument {
        // Validate file
        $this->validateFile($file);

        // Generate secure file path
        $filePath = $this->storeFile($file, $candidate, $checklist);

        // Use DB::transaction() closure to properly support nested transactions/savepoints
        $document = DB::transaction(function () use ($candidate, $checklist, $file, $filePath, $metadata) {
            // Check if document already exists (including soft-deleted records)
            // Use withTrashed() to find soft-deleted records that would conflict with unique constraint
            // Use lockForUpdate() to prevent race conditions
            $existingDoc = PreDepartureDocument::withTrashed()
                ->where('candidate_id', $candidate->id)
                ->where('document_checklist_id', $checklist->id)
                ->lockForUpdate()
                ->first();

            if ($existingDoc) {
                // Delete old file if it exists
                if ($existingDoc->file_path) {
                    Storage::disk('private')->delete($existingDoc->file_path);
                }

                // Delete old page files if any
                foreach ($existingDoc->pages as $page) {
                    Storage::disk('private')->delete($page->file_path);
                }
                $existingDoc->pages()->delete();

                // Restore if soft-deleted, then update
                if ($existingDoc->trashed()) {
                    $existingDoc->restore();
                }

                // Update existing record
                $existingDoc->update([
                    'file_path' => $filePath,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'notes' => $metadata['notes'] ?? null,
                    'uploaded_at' => now(),
                    'uploaded_by' => auth()->id(),
                    'verified_at' => null, // Reset verification on re-upload
                    'verified_by' => null,
                    'verification_notes' => null,
                ]);

                $document = $existingDoc;

                // Log replacement activity
                activity()
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'candidate_id' => $candidate->id,
                        'document_type' => $checklist->name,
                        'action' => 'replaced',
                    ])
                    ->log('Pre-departure document replaced');
            } else {
                // Create new document record
                $document = PreDepartureDocument::create([
                    'candidate_id' => $candidate->id,
                    'document_checklist_id' => $checklist->id,
                    'file_path' => $filePath,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'notes' => $metadata['notes'] ?? null,
                    'uploaded_at' => now(),
                    'uploaded_by' => auth()->id(),
                ]);

                // Log creation activity
                activity()
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'candidate_id' => $candidate->id,
                        'document_type' => $checklist->name,
                    ])
                    ->log('Pre-departure document uploaded');
            }

            return $document;
        });

        // Load relationships and return
        $document->load(['candidate', 'documentChecklist', 'uploader', 'pages']);
        return $document;
    }

    /**
     * Upload a pre-departure document with multiple files/pages
     *
     * @param Candidate $candidate
     * @param DocumentChecklist $checklist
     * @param array $files Array of UploadedFile objects
     * @param array $metadata Additional metadata (notes, etc.)
     * @return PreDepartureDocument
     * @throws \Exception
     */
    public function uploadDocumentWithPages(
        Candidate $candidate,
        DocumentChecklist $checklist,
        array $files,
        array $metadata = []
    ): PreDepartureDocument {
        if (empty($files)) {
            throw new \Exception('At least one file is required.');
        }

        // Validate max pages
        $maxPages = $checklist->max_pages ?? 5;
        if (count($files) > $maxPages) {
            throw new \Exception("Maximum {$maxPages} pages allowed for this document type.");
        }

        // Validate all files first
        foreach ($files as $file) {
            $this->validateFile($file);
        }

        // Store the first file as the main document
        $mainFile = array_shift($files);
        $mainFilePath = $this->storeFile($mainFile, $candidate, $checklist);

        $document = DB::transaction(function () use ($candidate, $checklist, $mainFile, $mainFilePath, $files, $metadata) {
            // Check if document already exists (including soft-deleted records)
            // Use withTrashed() to find soft-deleted records that would conflict with unique constraint
            $existingDoc = PreDepartureDocument::withTrashed()
                ->where('candidate_id', $candidate->id)
                ->where('document_checklist_id', $checklist->id)
                ->lockForUpdate()
                ->first();

            if ($existingDoc) {
                // Delete old main file if it exists
                if ($existingDoc->file_path) {
                    Storage::disk('private')->delete($existingDoc->file_path);
                }

                // Delete old page files
                foreach ($existingDoc->pages as $page) {
                    Storage::disk('private')->delete($page->file_path);
                }
                $existingDoc->pages()->delete();

                // Restore if soft-deleted, then update
                if ($existingDoc->trashed()) {
                    $existingDoc->restore();
                }

                // Update existing record
                $existingDoc->update([
                    'file_path' => $mainFilePath,
                    'original_filename' => $mainFile->getClientOriginalName(),
                    'mime_type' => $mainFile->getMimeType(),
                    'file_size' => $mainFile->getSize(),
                    'notes' => $metadata['notes'] ?? null,
                    'uploaded_at' => now(),
                    'uploaded_by' => auth()->id(),
                    'verified_at' => null,
                    'verified_by' => null,
                    'verification_notes' => null,
                ]);

                $document = $existingDoc;

                activity()
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'candidate_id' => $candidate->id,
                        'document_type' => $checklist->name,
                        'action' => 'replaced',
                        'page_count' => count($files) + 1,
                    ])
                    ->log('Pre-departure document replaced with multiple pages');
            } else {
                // Create new document record
                $document = PreDepartureDocument::create([
                    'candidate_id' => $candidate->id,
                    'document_checklist_id' => $checklist->id,
                    'file_path' => $mainFilePath,
                    'original_filename' => $mainFile->getClientOriginalName(),
                    'mime_type' => $mainFile->getMimeType(),
                    'file_size' => $mainFile->getSize(),
                    'notes' => $metadata['notes'] ?? null,
                    'uploaded_at' => now(),
                    'uploaded_by' => auth()->id(),
                ]);

                activity()
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'candidate_id' => $candidate->id,
                        'document_type' => $checklist->name,
                        'page_count' => count($files) + 1,
                    ])
                    ->log('Pre-departure document uploaded with multiple pages');
            }

            // Store additional pages
            $pageNumber = 2;
            foreach ($files as $file) {
                $pagePath = $this->storePageFile($file, $candidate, $checklist, $pageNumber);

                PreDepartureDocumentPage::create([
                    'pre_departure_document_id' => $document->id,
                    'page_number' => $pageNumber,
                    'file_path' => $pagePath,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                $pageNumber++;
            }

            return $document;
        });

        $document->load(['candidate', 'documentChecklist', 'uploader', 'pages']);
        return $document;
    }

    /**
     * Verify a pre-departure document
     *
     * @param PreDepartureDocument $document
     * @param User $verifier
     * @param string|null $notes
     * @return PreDepartureDocument
     */
    public function verifyDocument(
        PreDepartureDocument $document,
        User $verifier,
        ?string $notes = null
    ): PreDepartureDocument {
        $document->update([
            'verified_at' => now(),
            'verified_by' => $verifier->id,
            'verification_notes' => $notes,
        ]);

        // Log verification activity
        activity()
            ->performedOn($document)
            ->causedBy($verifier)
            ->withProperties([
                'candidate_id' => $document->candidate_id,
                'document_type' => $document->documentChecklist?->name ?? 'Unknown',
                'notes' => $notes,
            ])
            ->log('Pre-departure document verified');

        // Refresh the document with verifier relationship
        $document->load('verifier');
        return $document;
    }

    /**
     * Reject a pre-departure document
     *
     * @param PreDepartureDocument $document
     * @param User $verifier
     * @param string $reason
     * @return PreDepartureDocument
     */
    public function rejectDocument(
        PreDepartureDocument $document,
        User $verifier,
        string $reason
    ): PreDepartureDocument {
        $document->update([
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => $reason,
        ]);

        // Log rejection activity
        activity()
            ->performedOn($document)
            ->causedBy($verifier)
            ->withProperties([
                'candidate_id' => $document->candidate_id,
                'document_type' => $document->documentChecklist?->name ?? 'Unknown',
                'reason' => $reason,
            ])
            ->log('Pre-departure document rejected');

        // Refresh the document attributes
        $document->refresh();
        return $document;
    }

    /**
     * Check if documents can be edited for a candidate
     *
     * @param Candidate $candidate
     * @param User $user
     * @return bool
     */
    public function canEditDocuments(Candidate $candidate, User $user): bool
    {
        // Super Admin can always edit
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Others can only edit if candidate is in 'new' status
        if ($candidate->status !== 'new') {
            return false;
        }

        // Check if user has access to this candidate
        if ($user->hasRole('campus_admin')) {
            return $candidate->campus_id === $user->campus_id;
        }

        if ($user->hasRole('oep')) {
            return $candidate->oep_id === $user->id;
        }

        return false;
    }

    /**
     * Get document completion status for a candidate
     *
     * @param Candidate $candidate
     * @return array
     */
    public function getCompletionStatus(Candidate $candidate): array
    {
        return $candidate->getPreDepartureDocumentStatus();
    }

    /**
     * Generate individual document report for a candidate
     *
     * @param Candidate $candidate
     * @param string $format 'pdf' or 'excel'
     * @return string Path to generated file
     */
    public function generateIndividualReport(Candidate $candidate, string $format = 'pdf'): string
    {
        $documents = $candidate->preDepartureDocuments()
            ->with(['documentChecklist', 'uploader', 'verifier'])
            ->get();

        $status = $this->getCompletionStatus($candidate);

        $data = [
            'candidate' => $candidate,
            'documents' => $documents,
            'status' => $status,
            'mandatory' => DocumentChecklist::mandatory()->active()->get(),
            'optional' => DocumentChecklist::optional()->active()->get(),
            'generated_at' => now(),
            'generated_by' => auth()->user(),
        ];

        if ($format === 'pdf') {
            return $this->generateIndividualPdfReport($data);
        }

        return $this->generateIndividualExcelReport($data);
    }

    /**
     * Generate bulk document status report
     *
     * @param array $filters campus_id, status, date_from, date_to
     * @param string $format 'excel' only
     * @return string Path to generated file
     */
    public function generateBulkReport(array $filters = [], string $format = 'excel'): string
    {
        $query = Candidate::query()
            ->with(['preDepartureDocuments.documentChecklist', 'campus', 'trade', 'batch']);

        // Apply filters
        if (isset($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Role-based filtering
        $user = auth()->user();
        if ($user->hasRole('campus_admin')) {
            $query->where('campus_id', $user->campus_id);
        } elseif ($user->hasRole('oep')) {
            $query->where('oep_id', $user->id);
        }

        $candidates = $query->get();

        $checklists = DocumentChecklist::active()->orderBy('display_order')->get();

        return $this->generateBulkExcelReport($candidates, $checklists, $filters);
    }

    /**
     * Generate missing documents report
     *
     * @param array $filters
     * @return Collection
     */
    public function generateMissingDocumentsReport(array $filters = []): Collection
    {
        $query = Candidate::query()
            ->with(['preDepartureDocuments.documentChecklist', 'campus', 'trade'])
            ->whereIn('status', ['new']); // Only new candidates

        // Apply filters (same as bulk report)
        if (isset($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        // Role-based filtering
        $user = auth()->user();
        if ($user->hasRole('campus_admin')) {
            $query->where('campus_id', $user->campus_id);
        } elseif ($user->hasRole('oep')) {
            $query->where('oep_id', $user->id);
        }

        $candidates = $query->get();

        // Filter to only candidates with incomplete documents
        $incompleteCandidates = $candidates->filter(function ($candidate) {
            return !$candidate->hasCompletedPreDepartureDocuments();
        });

        // Map with missing documents details
        return $incompleteCandidates->map(function ($candidate) {
            $status = $candidate->getPreDepartureDocumentStatus();
            $missingDocs = $candidate->getMissingMandatoryDocuments();

            return [
                'candidate_id' => $candidate->id,
                'btevta_id' => $candidate->btevta_id,
                'name' => $candidate->name,
                'cnic' => $candidate->cnic,
                'campus' => $candidate->campus->name ?? 'N/A',
                'trade' => $candidate->trade->name ?? 'N/A',
                'status' => $candidate->status,
                'mandatory_uploaded' => $status['mandatory_uploaded'],
                'mandatory_total' => $status['mandatory_total'],
                'completion_percentage' => $status['completion_percentage'],
                'missing_documents' => $missingDocs->pluck('name')->toArray(),
                'created_at' => $candidate->created_at,
            ];
        });
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @return void
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Allow common MIME types for PDF, JPEG, and PNG
        // Include variants that some browsers/systems might report
        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',      // Non-standard but sometimes reported
            'image/pjpeg',    // Progressive JPEG
            'image/png',
            'image/x-png',    // Non-standard PNG variant
        ];

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check both MIME type and extension for better compatibility
        $validMime = in_array($mimeType, $allowedMimes);
        $validExtension = in_array($extension, $allowedExtensions);

        if (!$validMime && !$validExtension) {
            throw new \Exception('Invalid file type. Allowed: PDF, JPG, PNG. Received: ' . $mimeType);
        }

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds 5MB limit');
        }
    }

    /**
     * Store file securely
     *
     * @param UploadedFile $file
     * @param Candidate $candidate
     * @param DocumentChecklist $checklist
     * @return string File path
     */
    protected function storeFile(
        UploadedFile $file,
        Candidate $candidate,
        DocumentChecklist $checklist
    ): string {
        // Generate secure filename
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf(
            '%s_%s_%s.%s',
            $candidate->btevta_id,
            $checklist->code,
            now()->format('YmdHis'),
            $extension
        );

        // Store in private disk
        $path = $file->storeAs(
            "pre-departure-documents/{$candidate->id}",
            $filename,
            'private'
        );

        return $path;
    }

    /**
     * Store a page file securely
     *
     * @param UploadedFile $file
     * @param Candidate $candidate
     * @param DocumentChecklist $checklist
     * @param int $pageNumber
     * @return string File path
     */
    protected function storePageFile(
        UploadedFile $file,
        Candidate $candidate,
        DocumentChecklist $checklist,
        int $pageNumber
    ): string {
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf(
            '%s_%s_page%d_%s.%s',
            $candidate->btevta_id,
            $checklist->code,
            $pageNumber,
            now()->format('YmdHis'),
            $extension
        );

        $path = $file->storeAs(
            "pre-departure-documents/{$candidate->id}",
            $filename,
            'private'
        );

        return $path;
    }

    /**
     * Generate individual PDF report
     *
     * @param array $data
     * @return string Path to PDF
     */
    protected function generateIndividualPdfReport(array $data): string
    {
        $pdf = Pdf::loadView('reports.pre-departure.individual-pdf', $data);

        $filename = sprintf(
            'pre-departure-report_%s_%s.pdf',
            $data['candidate']->btevta_id,
            now()->format('Ymd_His')
        );

        $path = "reports/pre-departure/{$filename}";

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate individual Excel report
     *
     * @param array $data
     * @return string Path to Excel file
     */
    protected function generateIndividualExcelReport(array $data): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Pre-Departure Document Report');
        $sheet->setCellValue('A2', 'Candidate: ' . $data['candidate']->name);
        $sheet->setCellValue('A3', 'BTEVTA ID: ' . $data['candidate']->btevta_id);
        $sheet->setCellValue('A4', 'Generated: ' . now()->format('Y-m-d H:i:s'));

        // Column headers
        $row = 6;
        $headers = ['Document Type', 'Category', 'Status', 'Uploaded Date', 'Uploaded By', 'Verified Date', 'Verified By'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }

        // Data rows
        $row++;
        foreach ($data['mandatory'] as $checklist) {
            $doc = $data['documents']->firstWhere('document_checklist_id', $checklist->id);

            $sheet->setCellValue('A' . $row, $checklist->name);
            $sheet->setCellValue('B' . $row, 'Mandatory');
            $sheet->setCellValue('C' . $row, $doc ? 'Uploaded' : 'Missing');
            $sheet->setCellValue('D' . $row, $doc ? $doc->uploaded_at->format('Y-m-d') : '');
            $sheet->setCellValue('E' . $row, $doc && $doc->uploader ? $doc->uploader->name : '');
            $sheet->setCellValue('F' . $row, $doc && $doc->verified_at ? $doc->verified_at->format('Y-m-d') : '');
            $sheet->setCellValue('G' . $row, $doc && $doc->verifier ? $doc->verifier->name : '');

            $row++;
        }

        foreach ($data['optional'] as $checklist) {
            $doc = $data['documents']->firstWhere('document_checklist_id', $checklist->id);

            $sheet->setCellValue('A' . $row, $checklist->name);
            $sheet->setCellValue('B' . $row, 'Optional');
            $sheet->setCellValue('C' . $row, $doc ? 'Uploaded' : 'Not Uploaded');
            $sheet->setCellValue('D' . $row, $doc ? $doc->uploaded_at->format('Y-m-d') : '');
            $sheet->setCellValue('E' . $row, $doc && $doc->uploader ? $doc->uploader->name : '');
            $sheet->setCellValue('F' . $row, $doc && $doc->verified_at ? $doc->verified_at->format('Y-m-d') : '');
            $sheet->setCellValue('G' . $row, $doc && $doc->verifier ? $doc->verifier->name : '');

            $row++;
        }

        $filename = sprintf(
            'pre-departure-report_%s_%s.xlsx',
            $data['candidate']->btevta_id,
            now()->format('Ymd_His')
        );

        $path = "reports/pre-departure/{$filename}";

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $path));

        return $path;
    }

    /**
     * Generate bulk Excel report
     *
     * @param Collection $candidates
     * @param Collection $checklists
     * @param array $filters
     * @return string Path to Excel file
     */
    protected function generateBulkExcelReport(
        Collection $candidates,
        Collection $checklists,
        array $filters
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Bulk Pre-Departure Documents Report');
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('Y-m-d H:i:s'));

        // Column headers
        $row = 4;
        $headers = ['BTEVTA ID', 'Name', 'CNIC', 'Campus', 'Trade', 'Status'];

        // Add document columns
        foreach ($checklists as $checklist) {
            $headers[] = $checklist->name;
        }

        $headers[] = 'Completion %';

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }

        // Data rows
        $row++;
        foreach ($candidates as $candidate) {
            $uploadedIds = $candidate->preDepartureDocuments->pluck('document_checklist_id')->toArray();
            $status = $candidate->getPreDepartureDocumentStatus();

            $sheet->setCellValue('A' . $row, $candidate->btevta_id);
            $sheet->setCellValue('B' . $row, $candidate->name);
            $sheet->setCellValue('C' . $row, $candidate->cnic);
            $sheet->setCellValue('D' . $row, $candidate->campus->name ?? 'N/A');
            $sheet->setCellValue('E' . $row, $candidate->trade->name ?? 'N/A');
            $sheet->setCellValue('F' . $row, $candidate->status);

            $col = 'G';
            foreach ($checklists as $checklist) {
                $hasDoc = in_array($checklist->id, $uploadedIds);
                $doc = $candidate->preDepartureDocuments->firstWhere('document_checklist_id', $checklist->id);

                $value = $hasDoc ? ($doc && $doc->isVerified() ? 'Verified' : 'Uploaded') : '-';
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }

            $sheet->setCellValue($col . $row, $status['completion_percentage'] . '%');

            $row++;
        }

        $filename = sprintf(
            'bulk-pre-departure-report_%s.xlsx',
            now()->format('Ymd_His')
        );

        $path = "reports/pre-departure/{$filename}";

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $path));

        return $path;
    }
}
