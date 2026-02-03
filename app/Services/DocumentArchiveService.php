<?php

namespace App\Services;

use App\Models\DocumentArchive;
use App\Models\Candidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DocumentArchiveService
{
    /**
     * Document types
     */
    const DOCUMENT_TYPES = [
        // Candidate Documents
        'cnic' => 'CNIC',
        'passport' => 'Passport',
        'education_certificate' => 'Educational Certificate',
        'domicile' => 'Domicile Certificate',
        'medical_certificate' => 'Medical Certificate',
        'police_clearance' => 'Police Character Certificate',
        'photo' => 'Passport Photo',
        
        // Visa Documents
        'gamca_certificate' => 'GAMCA Medical Certificate',
        'takamol_certificate' => 'Takamol Test Certificate',
        'visa_copy' => 'Visa Copy',
        'ticket' => 'Travel Ticket',
        'ptn_document' => 'PTN Document',
        
        // Training Documents
        'training_certificate' => 'Training Certificate',
        'attendance_sheet' => 'Attendance Sheet',
        'assessment_report' => 'Assessment Report',
        
        // Post-Departure Documents
        'iqama' => 'Iqama Copy',
        'employment_contract' => 'Employment Contract',
        'accommodation_proof' => 'Accommodation Proof',
        'salary_slip' => 'Salary Slip',
        
        // Administrative Documents
        'undertaking' => 'Undertaking',
        'correspondence' => 'Official Correspondence',
        'complaint_evidence' => 'Complaint Evidence',
        'other' => 'Other Documents',
    ];

    /**
     * Get all document types
     */
    public function getDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }

    /**
     * Upload document to archive
     */
    public function uploadDocument($data, $file): DocumentArchive
    {
        // Check if document already exists for this candidate/type (get current version)
        $existingDoc = DocumentArchive::where('candidate_id', $data['candidate_id'])
            ->where('document_type', $data['document_type'])
            ->where('is_current_version', true)
            ->whereNull('deleted_at')
            ->orderBy('version', 'desc')
            ->first();

        $version = 1;

        // If document exists, archive old version and increment version number
        if ($existingDoc) {
            $version = $existingDoc->version + 1;

            // Archive the old version (soft delete)
            $existingDoc->update([
                'is_current_version' => false,
                'archived_at' => now(),
            ]);
        }

        // ERROR HANDLING: Store the file with error handling
        try {
            $path = $file->store("archive/{$data['document_type']}", 'public');
        } catch (\Exception $e) {
            throw new \Exception("Failed to store file: " . $e->getMessage());
        }

        // Create new document record
        $document = DocumentArchive::create([
            'candidate_id' => $data['candidate_id'] ?? null,
            'campus_id' => $data['campus_id'] ?? null,
            'trade_id' => $data['trade_id'] ?? null,
            'oep_id' => $data['oep_id'] ?? null,
            'document_type' => $data['document_type'],
            'document_name' => $data['document_name'] ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'version' => $version,
            'expiry_date' => $data['expiry_date'] ?? null,
            'upload_date' => now(),
            'uploaded_by' => auth()->id(),
            'is_current_version' => true,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $data['description'] ?? null,
        ]);

        // Log activity
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log("Document uploaded: {$data['document_type']} (v{$version})");

        return $document;
    }

    /**
     * Get document by ID with access logging
     */
    public function getDocument($documentId): DocumentArchive
    {
        $document = DocumentArchive::findOrFail($documentId);
        
        // Increment download count
        $document->increment('download_count');
        
        // Log access
        $this->logAccess($document, 'view');
        
        return $document;
    }

    /**
     * Download document with access logging
     */
    public function downloadDocument($documentId): array
    {
        $document = DocumentArchive::findOrFail($documentId);
        
        // Increment download count
        $document->increment('download_count');
        
        // Log access
        $this->logAccess($document, 'download');
        
        return [
            'path' => Storage::disk('public')->path($document->file_path),
            'name' => $document->document_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Log document access
     * FIXED: Changed from private to public so controller can call it
     */
    public function logAccess($document, $action): void
    {
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => $action,
                'document_type' => $document->document_type,
                'version' => $document->version,
            ])
            ->log("Document {$action}: {$document->document_name}");
    }

    /**
     * Get document versions
     */
    public function getVersions($candidateId, $documentType): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentArchive::withTrashed()
            ->where('candidate_id', $candidateId)
            ->where('document_type', $documentType)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Restore previous version
     */
    public function restoreVersion($documentId): DocumentArchive
    {
        $document = DocumentArchive::withTrashed()->findOrFail($documentId);
        
        // Find current version
        $currentDoc = DocumentArchive::where('candidate_id', $document->candidate_id)
            ->where('document_type', $document->document_type)
            ->where('is_current_version', true)
            ->first();

        if ($currentDoc) {
            // Archive current version
            $currentDoc->update([
                'is_current_version' => false,
                'archived_at' => now(),
            ]);
        }

        // Restore old version as current
        $document->restore();
        $document->update([
            'is_current_version' => true,
            'version' => ($currentDoc ? $currentDoc->version : 0) + 1,
        ]);

        // Log activity
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log("Document version restored: v{$document->version}");

        return $document;
    }

    /**
     * Search documents with filters
     */
    public function searchDocuments($filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DocumentArchive::with(['candidate', 'campus', 'trade', 'oep', 'uploadedByUser'])
            ->where('is_current_version', true);

        // Candidate filter
        if (!empty($filters['candidate_id'])) {
            $query->where('candidate_id', $filters['candidate_id']);
        }

        // Campus filter
        if (!empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        // Trade filter
        if (!empty($filters['trade_id'])) {
            $query->where('trade_id', $filters['trade_id']);
        }

        // OEP filter
        if (!empty($filters['oep_id'])) {
            $query->where('oep_id', $filters['oep_id']);
        }

        // Document type filter
        if (!empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        // Date range filter
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Expiry filter
        if (!empty($filters['expiring_soon'])) {
            $days = $filters['expiring_days'] ?? 30;
            $query->whereNotNull('expiry_date')
                  ->whereDate('expiry_date', '<=', Carbon::now()->addDays($days))
                  ->whereDate('expiry_date', '>=', Carbon::now());
        }

        // Search by name
        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('document_name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 25);
    }

    /**
     * Get expiring documents
     */
    public function getExpiringDocuments($days = 30): \Illuminate\Support\Collection
    {
        $alertDate = Carbon::now()->addDays($days);
        $today = Carbon::now();

        return DocumentArchive::with(['candidate', 'campus'])
            ->where('is_current_version', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $alertDate)
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function($document) {
                $daysUntilExpiry = Carbon::now()->diffInDays($document->expiry_date, false);
                
                return [
                    'document' => $document,
                    'days_until_expiry' => max(0, $daysUntilExpiry),
                    'urgency' => $this->calculateExpiryUrgency($daysUntilExpiry),
                ];
            });
    }

    /**
     * Calculate expiry urgency level
     */
    private function calculateExpiryUrgency($daysUntilExpiry): string
    {
        if ($daysUntilExpiry <= 7) return 'critical';
        if ($daysUntilExpiry <= 15) return 'high';
        if ($daysUntilExpiry <= 30) return 'medium';
        return 'low';
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments(): \Illuminate\Support\Collection
    {
        return DocumentArchive::with(['candidate', 'campus'])
            ->where('is_current_version', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', Carbon::now())
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function($document) {
                $daysPastExpiry = Carbon::parse($document->expiry_date)->diffInDays(Carbon::now());
                
                return [
                    'document' => $document,
                    'days_past_expiry' => $daysPastExpiry,
                ];
            });
    }

    /**
     * Get missing documents for candidate
     */
    public function getMissingDocuments($candidateId): array
    {
        $candidate = Candidate::findOrFail($candidateId);
        
        // Required documents based on candidate status
        $requiredDocs = $this->getRequiredDocuments($candidate->status);
        
        // Get existing documents
        $existingDocs = DocumentArchive::where('candidate_id', $candidateId)
            ->where('is_current_version', true)
            ->pluck('document_type')
            ->toArray();
        
        // Find missing documents
        $missingDocs = array_diff($requiredDocs, $existingDocs);
        
        return [
            'candidate' => $candidate,
            'required_documents' => $requiredDocs,
            'existing_documents' => $existingDocs,
            'missing_documents' => $missingDocs,
            'completion_percentage' => count($requiredDocs) > 0 
                ? round((count($existingDocs) / count($requiredDocs)) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get required documents based on candidate status
     */
    private function getRequiredDocuments($status): array
    {
        $baseDocuments = ['cnic', 'passport', 'education_certificate', 'photo'];
        
        switch ($status) {
            case 'registered':
            case 'in_training':
                return array_merge($baseDocuments, ['domicile', 'medical_certificate', 'police_clearance']);
            
            case 'training_completed':
            case 'visa_processing':
                return array_merge($baseDocuments, [
                    'domicile', 'medical_certificate', 'police_clearance',
                    'training_certificate', 'undertaking'
                ]);
            
            case 'visa_completed':
            case 'departed':
                return array_merge($baseDocuments, [
                    'domicile', 'medical_certificate', 'police_clearance',
                    'training_certificate', 'undertaking',
                    'gamca_certificate', 'visa_copy', 'ticket'
                ]);
            
            default:
                return $baseDocuments;
        }
    }

    /**
     * Get document statistics
     */
    public function getStatistics($filters = []): array
    {
        $query = DocumentArchive::where('is_current_version', true);

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $documents = $query->get();
        $total = $documents->count();

        return [
            'total_documents' => $total,
            'total_candidates' => $documents->whereNotNull('candidate_id')->unique('candidate_id')->count(),
            'by_type' => $this->groupByType($documents),
            'by_campus' => $this->groupByCampus($documents),
            'expiring_soon' => $this->getExpiringDocuments(30)->count(),
            'expired' => $this->getExpiredDocuments()->count(),
            'total_downloads' => $documents->sum('download_count'),
            'total_storage' => $this->formatBytes($documents->sum('file_size')),
            'average_version' => round($documents->avg('version'), 1),
        ];
    }

    /**
     * Group documents by type
     */
    private function groupByType($documents): \Illuminate\Support\Collection
    {
        return $documents->groupBy('document_type')->map(function($group, $type) {
            return [
                'type' => self::DOCUMENT_TYPES[$type] ?? $type,
                'count' => $group->count(),
                'total_downloads' => $group->sum('download_count'),
            ];
        })->sortByDesc('count');
    }

    /**
     * Group documents by campus
     */
    private function groupByCampus($documents): \Illuminate\Support\Collection
    {
        return $documents->filter(function($doc) {
            return !is_null($doc->campus_id);
        })->groupBy(function($doc) {
            // NULL CHECK: Handle case when campus relationship is null
            return $doc->campus?->name ?? 'Unknown';
        })->map(function($group) {
            return [
                'count' => $group->count(),
                'total_size' => $this->formatBytes($group->sum('file_size')),
            ];
        });
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get audit log for document
     */
    public function getAuditLog($documentId, $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $document = DocumentArchive::findOrFail($documentId);
        
        return activity()
            ->forSubject($document)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk delete documents
     */
    public function bulkDelete($documentIds): array
    {
        $documents = DocumentArchive::whereIn('id', $documentIds)->get();
        
        // Use DB::transaction() closure to properly support nested transactions/savepoints
        DB::transaction(function () use ($documents) {
            foreach ($documents as $document) {
                // Soft delete
                $document->delete();
                
                // Log activity
                activity()
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->log("Document deleted: {$document->document_name}");
            }
        });

        return ['success' => true, 'deleted' => count($documentIds)];
    }

    /**
     * Cleanup old archived versions
     */
    public function cleanupOldVersions($keepVersions = 3, $olderThanDays = 90): array
    {
        $cutoffDate = Carbon::now()->subDays($olderThanDays);

        // Get all document types for each candidate
        $documentGroups = DocumentArchive::withTrashed()
            ->whereNotNull('candidate_id')
            ->where('is_current_version', false)
            ->where('archived_at', '<', $cutoffDate)
            ->get()
            ->groupBy(function($doc) {
                return $doc->candidate_id . '_' . $doc->document_type;
            });

        $deletedCount = 0;

        foreach ($documentGroups as $group) {
            // Keep only the latest $keepVersions
            $toDelete = $group->sortByDesc('version')->skip($keepVersions);

            foreach ($toDelete as $document) {
                // ERROR HANDLING: Permanently delete file with error handling
                try {
                    if (Storage::disk('public')->exists($document->file_path)) {
                        Storage::disk('public')->delete($document->file_path);
                    }
                } catch (\Exception $e) {
                    \Log::warning("Failed to delete file: {$document->file_path}", ['error' => $e->getMessage()]);
                    // Continue with database deletion even if file deletion fails
                }

                // Force delete from database
                $document->forceDelete();
                $deletedCount++;
            }
        }

        return [
            'cleaned' => $deletedCount,
            'message' => "Cleaned up {$deletedCount} old document versions",
        ];
    }

    /**
     * Generate document verification report
     */
    public function generateVerificationReport($filters = []): array
    {
        $query = Candidate::with(['documentArchives' => function($q) {
            $q->where('is_current_version', true);
        }]);

        // Apply filters
        if (!empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (!empty($filters['oep_id'])) {
            $query->where('oep_id', $filters['oep_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $candidates = $query->get();

        $report = $candidates->map(function($candidate) {
            $missing = $this->getMissingDocuments($candidate->id);
            
            return [
                'candidate' => $candidate,
                'document_status' => $missing,
                'is_complete' => empty($missing['missing_documents']),
            ];
        });

        return [
            'summary' => [
                'total_candidates' => $report->count(),
                'complete' => $report->where('is_complete', true)->count(),
                'incomplete' => $report->where('is_complete', false)->count(),
            ],
            'details' => $report,
        ];
    }

    /**
     * Send expiry alerts
     */
    public function sendExpiryAlerts($days = 30): array
    {
        $expiringDocs = $this->getExpiringDocuments($days);
        
        // Group by urgency
        $grouped = $expiringDocs->groupBy('urgency');
        
        // Here you would integrate with notification service
        // For now, just return the grouped documents
        
        return [
            'total' => $expiringDocs->count(),
            'by_urgency' => $grouped->map->count(),
            'documents' => $expiringDocs,
        ];
    }

    /**
     * Get version history for a document
     * ADDED - Called by controller line 130, 258
     */
    public function getVersionHistory($documentId): \Illuminate\Database\Eloquent\Collection
    {
        $document = DocumentArchive::findOrFail($documentId);

        return DocumentArchive::withTrashed()
            ->where('candidate_id', $document->candidate_id)
            ->where('document_type', $document->document_type)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Update document metadata
     * ADDED - Called by controller line 160-163
     */
    public function updateDocumentMetadata($documentId, array $metadata): DocumentArchive
    {
        $document = DocumentArchive::findOrFail($documentId);

        $document->update($metadata);

        // Log activity
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log('Document metadata updated');

        return $document->fresh();
    }

    /**
     * Upload new version of existing document
     * ADDED - Called by controller line 184-188
     * AUDIT FIX: Corrected field names to match model schema
     */
    public function uploadNewVersion($documentId, $file, $versionNotes = null): DocumentArchive
    {
        $oldDocument = DocumentArchive::findOrFail($documentId);

        // Archive the old version
        $oldDocument->update([
            'is_current_version' => false,
            'archived_at' => now(),
        ]);

        // Store new file
        $path = $file->store("archive/{$oldDocument->document_type}", 'public');

        // Create new version
        $newVersion = DocumentArchive::create([
            'candidate_id' => $oldDocument->candidate_id,
            'campus_id' => $oldDocument->campus_id,
            'trade_id' => $oldDocument->trade_id,
            'oep_id' => $oldDocument->oep_id,
            'document_type' => $oldDocument->document_type,
            'document_name' => $oldDocument->document_name,
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'version' => $oldDocument->version + 1,
            'uploaded_by' => auth()->id(),
            'is_current_version' => true,
            'expiry_date' => $oldDocument->expiry_date,
            'description' => $versionNotes ?? $oldDocument->description,
        ]);

        // Log activity
        activity()
            ->performedOn($newVersion)
            ->causedBy(auth()->user())
            ->withProperties(['old_version' => $oldDocument->version, 'new_version' => $newVersion->version])
            ->log("New version uploaded: v{$newVersion->version}");

        return $newVersion;
    }

    /**
     * Get all documents for a candidate
     * ADDED - Called by controller line 343
     */
    public function getCandidateDocuments($candidateId): \Illuminate\Database\Eloquent\Collection
    {
        return DocumentArchive::with(['uploadedByUser'])
            ->where('candidate_id', $candidateId)
            ->where('is_current_version', true)
            ->orderBy('document_type')
            ->get();
    }

    /**
     * Get access logs for a document
     * ADDED - Called by controller line 357
     */
    public function getAccessLogs($documentId): \Illuminate\Database\Eloquent\Collection
    {
        $document = DocumentArchive::findOrFail($documentId);

        return activity()
            ->forSubject($document)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }

    /**
     * Get storage statistics
     * ADDED - Called by controller line 371
     */
    public function getStorageStatistics(): array
    {
        return $this->getStatistics();
    }

    /**
     * Archive a document (soft delete)
     * ADDED - Called by controller line 429
     */
    public function archiveDocument($documentId): bool
    {
        $document = DocumentArchive::findOrFail($documentId);

        $document->update([
            'is_current_version' => false,
            'archived_at' => now(),
        ]);
        $document->delete();

        // Log activity
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log('Document archived');

        return true;
    }

    /**
     * Restore an archived document
     * ADDED - Called by controller line 443
     */
    public function restoreDocument($documentId): DocumentArchive
    {
        $document = DocumentArchive::withTrashed()->findOrFail($documentId);

        $document->restore();
        $document->update([
            'is_current_version' => true,
            'archived_at' => null,
        ]);

        // Log activity
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->log('Document restored from archive');

        return $document;
    }

    /**
     * Permanently delete a document
     * ADDED - Called by controller line 458
     */
    public function deleteDocument($documentId): bool
    {
        $document = DocumentArchive::withTrashed()->findOrFail($documentId);

        // Delete physical file
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Log activity before deletion
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'document_name' => $document->document_name,
                'document_type' => $document->document_type,
                'candidate_id' => $document->candidate_id,
            ])
            ->log('Document permanently deleted');

        // Force delete from database
        $document->forceDelete();

        return true;
    }

    /**
     * Generate document report
     * ADDED - Called by controller line 479-483
     */
    public function generateReport($startDate, $endDate, $category = null): array
    {
        $query = DocumentArchive::with(['candidate', 'campus', 'uploader'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($category) {
            $query->where('document_category', $category);
        }

        $documents = $query->get();

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_documents' => $documents->count(),
            'by_category' => $documents->groupBy('document_category')->map->count(),
            'by_type' => $documents->groupBy('document_type')->map->count(),
            'by_campus' => $documents->filter(fn($d) => !is_null($d->campus_id))
                                     ->groupBy(fn($d) => $d->campus?->name ?? 'Unknown')
                                     ->map->count(),
            'total_size' => $this->formatBytes($documents->sum('file_size')),
            'uploaded_by_user' => $documents->groupBy('uploaded_by')
                                            ->map(function($group) {
                                                return [
                                                    'count' => $group->count(),
                                                    'user' => $group->first()->uploader?->name ?? 'Unknown',
                                                ];
                                            }),
            'documents' => $documents,
        ];
    }

    /**
     * Send expiry reminders to relevant users
     * ADDED - Called by controller line 497
     */
    public function sendExpiryReminders(): int
    {
        $expiringDocs = $this->getExpiringDocuments(30);

        $count = 0;

        foreach ($expiringDocs as $item) {
            $document = $item['document'];
            $urgency = $item['urgency'];

            // Send notification (would integrate with NotificationService)
            // For now, just log the activity
            activity()
                ->performedOn($document)
                ->causedBy(auth()->user())
                ->withProperties([
                    'urgency' => $urgency,
                    'days_until_expiry' => $item['days_until_expiry'],
                ])
                ->log('Expiry reminder sent');

            $count++;
        }

        return $count;
    }
}