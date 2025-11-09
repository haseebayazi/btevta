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
    public function getDocumentTypes()
    {
        return self::DOCUMENT_TYPES;
    }

    /**
     * Upload document to archive
     */
    public function uploadDocument($data, $file)
    {
        // Check if document already exists for this candidate/type
        $existingDoc = DocumentArchive::where('candidate_id', $data['candidate_id'])
            ->where('document_type', $data['document_type'])
            ->whereNull('deleted_at')
            ->first();

        $version = 1;
        
        // If document exists, archive old version and increment version number
        if ($existingDoc) {
            $version = $existingDoc->version + 1;
            
            // Archive the old version (soft delete)
            $existingDoc->update([
                'is_current' => false,
                'archived_at' => now(),
            ]);
        }

        // Store the file
        $path = $file->store("archive/{$data['document_type']}", 'public');

        // Create new document record
        $document = DocumentArchive::create([
            'candidate_id' => $data['candidate_id'] ?? null,
            'campus_id' => $data['campus_id'] ?? null,
            'trade_id' => $data['trade_id'] ?? null,
            'oep_id' => $data['oep_id'] ?? null,
            'document_type' => $data['document_type'],
            'document_name' => $data['document_name'] ?? $file->getClientOriginalName(),
            'document_path' => $path,
            'version' => $version,
            'expiry_date' => $data['expiry_date'] ?? null,
            'uploaded_by' => auth()->id(),
            'is_current' => true,
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
    public function getDocument($documentId)
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
    public function downloadDocument($documentId)
    {
        $document = DocumentArchive::findOrFail($documentId);
        
        // Increment download count
        $document->increment('download_count');
        
        // Log access
        $this->logAccess($document, 'download');
        
        return [
            'path' => Storage::disk('public')->path($document->document_path),
            'name' => $document->document_name,
            'mime_type' => $document->mime_type,
        ];
    }

    /**
     * Log document access
     */
    private function logAccess($document, $action)
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
    public function getVersions($candidateId, $documentType)
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
    public function restoreVersion($documentId)
    {
        $document = DocumentArchive::withTrashed()->findOrFail($documentId);
        
        // Find current version
        $currentDoc = DocumentArchive::where('candidate_id', $document->candidate_id)
            ->where('document_type', $document->document_type)
            ->where('is_current', true)
            ->first();

        if ($currentDoc) {
            // Archive current version
            $currentDoc->update([
                'is_current' => false,
                'archived_at' => now(),
            ]);
        }

        // Restore old version as current
        $document->restore();
        $document->update([
            'is_current' => true,
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
    public function searchDocuments($filters = [])
    {
        $query = DocumentArchive::with(['candidate', 'campus', 'trade', 'oep', 'uploadedByUser'])
            ->where('is_current', true);

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
    public function getExpiringDocuments($days = 30)
    {
        $alertDate = Carbon::now()->addDays($days);
        $today = Carbon::now();

        return DocumentArchive::with(['candidate', 'campus'])
            ->where('is_current', true)
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
    private function calculateExpiryUrgency($daysUntilExpiry)
    {
        if ($daysUntilExpiry <= 7) return 'critical';
        if ($daysUntilExpiry <= 15) return 'high';
        if ($daysUntilExpiry <= 30) return 'medium';
        return 'low';
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments()
    {
        return DocumentArchive::with(['candidate', 'campus'])
            ->where('is_current', true)
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
    public function getMissingDocuments($candidateId)
    {
        $candidate = Candidate::findOrFail($candidateId);
        
        // Required documents based on candidate status
        $requiredDocs = $this->getRequiredDocuments($candidate->status);
        
        // Get existing documents
        $existingDocs = DocumentArchive::where('candidate_id', $candidateId)
            ->where('is_current', true)
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
    private function getRequiredDocuments($status)
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
    public function getStatistics($filters = [])
    {
        $query = DocumentArchive::where('is_current', true);

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
    private function groupByType($documents)
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
    private function groupByCampus($documents)
    {
        return $documents->filter(function($doc) {
            return !is_null($doc->campus_id);
        })->groupBy('campus.name')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_size' => $this->formatBytes($group->sum('file_size')),
            ];
        });
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes)
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
    public function getAuditLog($documentId, $limit = 50)
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
    public function bulkDelete($documentIds)
    {
        $documents = DocumentArchive::whereIn('id', $documentIds)->get();
        
        DB::beginTransaction();
        try {
            foreach ($documents as $document) {
                // Soft delete
                $document->delete();
                
                // Log activity
                activity()
                    ->performedOn($document)
                    ->causedBy(auth()->user())
                    ->log("Document deleted: {$document->document_name}");
            }
            
            DB::commit();
            return ['success' => true, 'deleted' => count($documentIds)];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cleanup old archived versions
     */
    public function cleanupOldVersions($keepVersions = 3, $olderThanDays = 90)
    {
        $cutoffDate = Carbon::now()->subDays($olderThanDays);
        
        // Get all document types for each candidate
        $documentGroups = DocumentArchive::withTrashed()
            ->whereNotNull('candidate_id')
            ->where('is_current', false)
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
                // Permanently delete file
                if (Storage::disk('public')->exists($document->document_path)) {
                    Storage::disk('public')->delete($document->document_path);
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
    public function generateVerificationReport($filters = [])
    {
        $query = Candidate::with(['documentArchives' => function($q) {
            $q->where('is_current', true);
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
    public function sendExpiryAlerts($days = 30)
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
}