<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
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
        DB::beginTransaction();

        try {
            // Validate file
            $this->validateFile($file);

            // Generate secure file path
            $filePath = $this->storeFile($file, $candidate, $checklist);

            // Check if document already exists (for replacement)
            $existingDoc = PreDepartureDocument::where('candidate_id', $candidate->id)
                ->where('document_checklist_id', $checklist->id)
                ->first();

            if ($existingDoc) {
                // Delete old file
                Storage::disk('private')->delete($existingDoc->file_path);

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

            DB::commit();

            // Load relationships and return
            $document->load(['candidate', 'documentChecklist', 'uploader']);
            return $document;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to upload pre-departure document', [
                'candidate_id' => $candidate->id,
                'checklist_id' => $checklist->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to upload document: ' . $e->getMessage());
        }
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
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new \Exception('Invalid file type. Allowed: PDF, JPG, PNG');
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
