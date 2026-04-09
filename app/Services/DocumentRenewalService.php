<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\DocumentRenewalRequest;
use App\Models\PreDepartureDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DocumentRenewalService
{
    /**
     * Create a renewal request for an expiring/expired document.
     */
    public function requestRenewal(
        Candidate $candidate,
        string $documentType,
        Model $documentable,
        ?string $notes = null
    ): DocumentRenewalRequest {
        return DocumentRenewalRequest::create([
            'candidate_id'        => $candidate->id,
            'document_type'       => $documentType,
            'documentable_type'   => get_class($documentable),
            'documentable_id'     => $documentable->id,
            'current_expiry_date' => $documentable->expiry_date ?? null,
            'requested_date'      => now()->toDateString(),
            'status'              => 'pending',
            'notes'               => $notes,
            'requested_by'        => auth()->id() ?? 1,
        ]);
    }

    /**
     * Process a renewal by uploading the new document and updating the original record.
     */
    public function processRenewal(
        DocumentRenewalRequest $request,
        UploadedFile $newDocumentFile,
        string $newExpiryDate
    ): void {
        DB::transaction(function () use ($request, $newDocumentFile, $newExpiryDate) {
            $path = $newDocumentFile->store(
                "renewals/{$request->candidate_id}",
                'private'
            );

            $request->documentable->update([
                'file_path'   => $path,
                'expiry_date' => $newExpiryDate,
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]);

            $request->update([
                'status'            => 'completed',
                'new_document_path' => $path,
                'new_expiry_date'   => $newExpiryDate,
                'processed_by'      => auth()->id(),
                'processed_at'      => now(),
            ]);

            activity()
                ->performedOn($request->documentable)
                ->causedBy(auth()->user())
                ->withProperties(['new_expiry_date' => $newExpiryDate])
                ->log('Document renewed');
        });
    }

    /**
     * Get all pending renewal requests, optionally filtered by campus.
     */
    public function getPendingRenewals(?int $campusId = null): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentRenewalRequest::with(['candidate.campus', 'documentable', 'requestedBy'])
            ->where('status', 'pending')
            ->when(
                $campusId,
                fn($q) => $q->whereHas('candidate', fn($q2) => $q2->where('campus_id', $campusId))
            )
            ->orderBy('current_expiry_date')
            ->get();
    }

    /**
     * Auto-create renewal requests for documents expiring within the next 30 days.
     * Called by the daily cron command.
     */
    public function createRenewalRequestsForExpiringDocuments(): int
    {
        $expiringDocs = PreDepartureDocument::with('candidate')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->whereDoesntHave(
                'renewalRequests',
                fn($q) => $q->whereIn('status', ['pending', 'in_progress'])
            )
            ->get();

        $count = 0;
        foreach ($expiringDocs as $doc) {
            if (!$doc->candidate) {
                continue;
            }

            $checklist = $doc->documentChecklist;
            $type      = $checklist?->code ?? $checklist?->name ?? 'document';

            $this->requestRenewal(
                $doc->candidate,
                $type,
                $doc,
                'Auto-generated: Document expiring within 30 days'
            );
            $count++;
        }

        return $count;
    }
}
