<?php

namespace App\Http\Controllers;

use App\Models\DocumentArchive;
use App\Models\Candidate;
use App\Services\DocumentArchiveService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class DocumentArchiveController extends Controller
{
    protected $documentService;
    protected $notificationService;

    public function __construct(
        DocumentArchiveService $documentService,
        NotificationService $notificationService
    ) {
        $this->documentService = $documentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of documents
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $query = DocumentArchive::with(['candidate', 'uploader'])
            ->where('is_current_version', true);

        // AUDIT FIX: Apply campus filtering for campus admin users
        $user = Auth::user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
        }

        // Apply filters
        if ($request->filled('document_category')) {
            $query->where('document_category', $request->document_category);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('search')) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('document_name', 'like', "%{$escapedSearch}%")
                    ->orWhere('document_number', 'like', "%{$escapedSearch}%");
            });
        }

        $documents = $query->latest('uploaded_at')->paginate(20);

        // AUDIT FIX: Filter candidates dropdown by campus for campus admins
        $candidatesQuery = Candidate::select('id', 'name', 'cnic');
        if ($user->isCampusAdmin() && $user->campus_id) {
            $candidatesQuery->where('campus_id', $user->campus_id);
        }
        $candidates = $candidatesQuery->limit(200)->get();

        return view('document-archive.index', compact('documents', 'candidates'));
    }

    /**
     * Show form to upload new document
     */
    public function create()
    {
        $this->authorize('create', DocumentArchive::class);

        // AUDIT FIX: Filter candidates dropdown by campus for campus admins
        $candidatesQuery = Candidate::select('id', 'name', 'cnic');
        $user = Auth::user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $candidatesQuery->where('campus_id', $user->campus_id);
        }
        $candidates = $candidatesQuery->limit(200)->get();

        return view('document-archive.create', compact('candidates'));
    }

    /**
     * Upload new document
     */
    public function store(Request $request)
    {
        $this->authorize('create', DocumentArchive::class);

        $validated = $request->validate([
            'candidate_id' => 'nullable|exists:candidates,id',
            'document_category' => 'required|in:candidate,campus,oep,contract,legal,certificate,other',
            'document_type' => 'required|string|max:100',
            'document_name' => 'required|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'file' => 'required|file|max:20480',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
        ]);

        try {
            // FIXED: Service expects ($data, $file) not individual parameters
            $document = $this->documentService->uploadDocument([
                'candidate_id' => $validated['candidate_id'] ?? null,
                'document_category' => $validated['document_category'],
                'document_type' => $validated['document_type'],
                'document_name' => $validated['document_name'],
                'document_number' => $validated['document_number'] ?? null,
                'issue_date' => $validated['issue_date'] ?? null,
                'expiry_date' => $validated['expiry_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'tags' => $validated['tags'] ?? null,
            ], $request->file('file'));

            // Send notification if expiry date is set
            if ($validated['expiry_date']) {
                $this->notificationService->sendDocumentUploaded($document);
            }

            return redirect()->route('document-archive.show', $document)
                ->with('success', 'Document uploaded successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Display document details
     */
    public function show(DocumentArchive $document)
    {
        $this->authorize('view', $document);

        $document->load(['candidate', 'uploader', 'accessLogs' => function ($query) {
            $query->orderBy('accessed_at', 'desc')->limit(10);
        }]);

        // Get version history
        $versions = $this->documentService->getVersionHistory($document->id);

        return view('document-archive.show', compact('document', 'versions'));
    }

    /**
     * Show form to edit document metadata
     */
    public function edit(DocumentArchive $document)
    {
        $this->authorize('update', $document);

        $candidates = Candidate::select('id', 'name', 'cnic')->get();

        return view('document-archive.edit', compact('document', 'candidates'));
    }

    /**
     * Update document metadata
     */
    public function update(Request $request, DocumentArchive $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'document_name' => 'nullable|string|max:255',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|string|max:500',
        ]);

        try {
            $document = $this->documentService->updateDocumentMetadata(
                $document->id,
                array_filter($validated)
            );

            return redirect()->route('document-archive.show', $document)
                ->with('success', 'Document metadata updated successfully!');
        } catch (Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update document: ' . $e->getMessage());
        }
    }

    /**
     * Upload new version of document
     */
    public function uploadVersion(Request $request, DocumentArchive $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'file' => 'required|file|max:20480',
            'version_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $newVersion = $this->documentService->uploadNewVersion(
                $document->id,
                $request->file('file'),
                $validated['version_notes'] ?? null
            );

            return redirect()->route('document-archive.show', $newVersion)
                ->with('success', 'New version uploaded successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to upload new version: ' . $e->getMessage());
        }
    }

    /**
     * Download document
     */
    public function download(DocumentArchive $document)
    {
        $this->authorize('view', $document);

        try {
            // Log the access - FIXED: Pass $document object, not ID
            $this->documentService->logAccess($document, 'download');

            return Storage::disk('public')->download(
                $document->file_path,
                $document->document_name . '.' . $document->file_type
            );
        } catch (Exception $e) {
            return back()->with('error', 'Failed to download document: ' . $e->getMessage());
        }
    }

    /**
     * View document (inline)
     */
    public function view(DocumentArchive $document)
    {
        $this->authorize('view', $document);

        try {
            // Log the access - FIXED: Pass $document object, not ID
            $this->documentService->logAccess($document, 'view');

            $filePath = storage_path('app/public/' . $document->file_path);

            if (!file_exists($filePath)) {
                throw new Exception('File not found');
            }

            return response()->file($filePath);
        } catch (Exception $e) {
            return back()->with('error', 'Failed to view document: ' . $e->getMessage());
        }
    }

    /**
     * Get version history
     */
    public function versions(DocumentArchive $document)
    {
        $this->authorize('view', $document);

        try {
            $versions = $this->documentService->getVersionHistory($document->id);

            return view('document-archive.versions', compact('document', 'versions'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch versions: ' . $e->getMessage());
        }
    }

    /**
     * Compare two document versions side-by-side
     */
    public function compareVersions(Request $request, DocumentArchive $document)
    {
        $this->authorize('view', $document);

        $validated = $request->validate([
            'version1_id' => 'required|exists:document_archives,id',
            'version2_id' => 'required|exists:document_archives,id',
        ]);

        $version1 = DocumentArchive::findOrFail($validated['version1_id']);
        $version2 = DocumentArchive::findOrFail($validated['version2_id']);

        // Ensure both versions belong to the same document chain
        if (($version1->replaces_document_id !== $document->id && $version1->id !== $document->id) ||
            ($version2->replaces_document_id !== $document->id && $version2->id !== $document->id)) {
            abort(403, 'Invalid version comparison');
        }

        // Get comparison data
        $comparison = [
            'metadata' => [
                'document_name' => [
                    'v1' => $version1->document_name,
                    'v2' => $version2->document_name,
                    'changed' => $version1->document_name !== $version2->document_name
                ],
                'document_number' => [
                    'v1' => $version1->document_number,
                    'v2' => $version2->document_number,
                    'changed' => $version1->document_number !== $version2->document_number
                ],
                'file_size' => [
                    'v1' => $this->formatFileSize($version1->file_size),
                    'v2' => $this->formatFileSize($version2->file_size),
                    'changed' => $version1->file_size !== $version2->file_size
                ],
                'file_type' => [
                    'v1' => $version1->file_type,
                    'v2' => $version2->file_type,
                    'changed' => $version1->file_type !== $version2->file_type
                ],
                'uploaded_at' => [
                    'v1' => $version1->uploaded_at ? $version1->uploaded_at->format('Y-m-d H:i:s') : 'N/A',
                    'v2' => $version2->uploaded_at ? $version2->uploaded_at->format('Y-m-d H:i:s') : 'N/A',
                    'changed' => true // Always different
                ],
                'uploaded_by' => [
                    'v1' => $version1->uploader ? $version1->uploader->name : 'Unknown',
                    'v2' => $version2->uploader ? $version2->uploader->name : 'Unknown',
                    'changed' => $version1->uploaded_by !== $version2->uploaded_by
                ],
                'expiry_date' => [
                    'v1' => $version1->expiry_date ? $version1->expiry_date->format('Y-m-d') : 'N/A',
                    'v2' => $version2->expiry_date ? $version2->expiry_date->format('Y-m-d') : 'N/A',
                    'changed' => $version1->expiry_date != $version2->expiry_date
                ],
                'description' => [
                    'v1' => $version1->description ?? 'N/A',
                    'v2' => $version2->description ?? 'N/A',
                    'changed' => $version1->description !== $version2->description
                ],
            ]
        ];

        return view('document-archive.compare-versions', compact('document', 'version1', 'version2', 'comparison'));
    }

    /**
     * Format file size for display
     */
    private function formatFileSize($bytes)
    {
        if (!$bytes) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Restore previous version
     */
    public function restoreVersion(Request $request, DocumentArchive $document)
    {
        $this->authorize('restore', $document);

        $validated = $request->validate([
            'version_id' => 'required|exists:document_archives,id',
        ]);

        try {
            $restoredDocument = $this->documentService->restoreVersion(
                $validated['version_id']
            );

            return redirect()->route('document-archive.show', $restoredDocument)
                ->with('success', 'Version restored successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to restore version: ' . $e->getMessage());
        }
    }

    /**
     * Get expiring documents
     */
    public function expiring(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $days = $request->get('days', 30);

        try {
            $expiringDocuments = $this->documentService->getExpiringDocuments($days);

            return view('document-archive.expiring', compact('expiringDocuments', 'days'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch expiring documents: ' . $e->getMessage());
        }
    }

    /**
     * Get expired documents
     */
    public function expired()
    {
        $this->authorize('viewAny', DocumentArchive::class);

        try {
            $expiredDocuments = $this->documentService->getExpiredDocuments();

            return view('document-archive.expired', compact('expiredDocuments'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch expired documents: ' . $e->getMessage());
        }
    }

    /**
     * Search documents
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $validated = $request->validate([
            'term' => 'required|string|min:2',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        try {
            // FIXED: Service expects single $filters array, not individual parameters
            $documents = $this->documentService->searchDocuments([
                'search' => $validated['term'],
                'document_category' => $validated['category'] ?? null,
                'document_type' => $validated['type'] ?? null,
            ]);

            if ($request->expectsJson()) {
                return response()->json($documents);
            }

            return view('document-archive.search-results', compact('documents'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Search failed: ' . $e->getMessage());
        }
    }

    /**
     * Advanced search with multiple filters
     */
    public function advancedSearch(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'document_type' => 'nullable|string',
            'document_category' => 'nullable|string',
            'candidate_id' => 'nullable|exists:candidates,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'uploaded_by' => 'nullable|exists:users,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:document_tags,id',
            'upload_date_from' => 'nullable|date',
            'upload_date_to' => 'nullable|date|after_or_equal:upload_date_from',
            'expiry_date_from' => 'nullable|date',
            'expiry_date_to' => 'nullable|date|after_or_equal:expiry_date_from',
            'file_type' => 'nullable|string',
            'has_expiry' => 'nullable|boolean',
            'is_expired' => 'nullable|boolean',
        ]);

        try {
            $query = DocumentArchive::with(['candidate', 'campus', 'uploader', 'tags'])
                ->currentVersion();

            // Apply campus filter for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
            }

            // Keyword search across multiple fields
            if (!empty($validated['keyword'])) {
                $keyword = $validated['keyword'];
                $query->where(function($q) use ($keyword) {
                    $q->where('document_name', 'like', "%{$keyword}%")
                        ->orWhere('document_number', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhereHas('candidate', function($cq) use ($keyword) {
                            $cq->where('name', 'like', "%{$keyword}%")
                                ->orWhere('cnic', 'like', "%{$keyword}%");
                        });
                });
            }

            // Filter by document type
            if (!empty($validated['document_type'])) {
                $query->where('document_type', $validated['document_type']);
            }

            // Filter by document category
            if (!empty($validated['document_category'])) {
                $query->where('document_category', $validated['document_category']);
            }

            // Filter by candidate
            if (!empty($validated['candidate_id'])) {
                $query->where('candidate_id', $validated['candidate_id']);
            }

            // Filter by campus
            if (!empty($validated['campus_id']) && auth()->user()->role !== 'campus_admin') {
                $query->where('campus_id', $validated['campus_id']);
            }

            // Filter by uploader
            if (!empty($validated['uploaded_by'])) {
                $query->where('uploaded_by', $validated['uploaded_by']);
            }

            // Filter by tags (ANY logic - document has any of the selected tags)
            if (!empty($validated['tag_ids'])) {
                $query->whereHas('tags', function($tq) use ($validated) {
                    $tq->whereIn('document_tags.id', $validated['tag_ids']);
                });
            }

            // Filter by upload date range
            if (!empty($validated['upload_date_from'])) {
                $query->whereDate('uploaded_at', '>=', $validated['upload_date_from']);
            }
            if (!empty($validated['upload_date_to'])) {
                $query->whereDate('uploaded_at', '<=', $validated['upload_date_to']);
            }

            // Filter by expiry date range
            if (!empty($validated['expiry_date_from'])) {
                $query->whereDate('expiry_date', '>=', $validated['expiry_date_from']);
            }
            if (!empty($validated['expiry_date_to'])) {
                $query->whereDate('expiry_date', '<=', $validated['expiry_date_to']);
            }

            // Filter by file type
            if (!empty($validated['file_type'])) {
                $query->where('file_type', $validated['file_type']);
            }

            // Filter by expiry status
            if (isset($validated['has_expiry']) && $validated['has_expiry']) {
                $query->whereNotNull('expiry_date');
            }

            if (isset($validated['is_expired']) && $validated['is_expired']) {
                $query->expired();
            }

            // Sort by most recent first
            $documents = $query->orderBy('uploaded_at', 'desc')->paginate(20);

            // Get filter options for the form
            $filterOptions = [
                'document_types' => DocumentArchive::distinct()->pluck('document_type')->filter(),
                'document_categories' => DocumentArchive::distinct()->pluck('document_category')->filter(),
                'campuses' => auth()->user()->role === 'campus_admin'
                    ? \App\Models\Campus::where('id', auth()->user()->campus_id)->pluck('name', 'id')
                    : \App\Models\Campus::where('is_active', true)->pluck('name', 'id'),
                'uploaders' => \App\Models\User::whereHas('uploadedDocuments')->pluck('name', 'id'),
                'tags' => \App\Models\DocumentTag::orderBy('name')->get(),
                'file_types' => DocumentArchive::distinct()->pluck('file_type')->filter(),
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'documents' => $documents,
                    'filter_options' => $filterOptions,
                ]);
            }

            return view('document-archive.advanced-search', compact('documents', 'filterOptions', 'validated'));
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Advanced search failed: ' . $e->getMessage());
        }
    }

    /**
     * Get candidate documents
     */
    public function candidateDocuments(Candidate $candidate)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        try {
            $documents = $this->documentService->getCandidateDocuments($candidate->id);

            return view('document-archive.candidate-documents', compact('candidate', 'documents'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch candidate documents: ' . $e->getMessage());
        }
    }

    /**
     * Get access logs for document
     */
    public function accessLogs(DocumentArchive $document)
    {
        $this->authorize('view', $document);

        try {
            $accessLogs = $this->documentService->getAccessLogs($document->id);

            return view('document-archive.access-logs', compact('document', 'accessLogs'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch access logs: ' . $e->getMessage());
        }
    }

    /**
     * Get storage statistics
     */
    public function statistics()
    {
        $this->authorize('viewAny', DocumentArchive::class);

        try {
            $statistics = $this->documentService->getStorageStatistics();

            return view('document-archive.statistics', compact('statistics'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch statistics: ' . $e->getMessage());
        }
    }

    /**
     * Bulk upload documents
     */
    public function bulkUpload(Request $request)
    {
        $this->authorize('create', DocumentArchive::class);

        $validated = $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|max:20480',
            'document_category' => 'required|string',
            'document_type' => 'required|string',
        ]);

        try {
            $uploadedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($request->file('files') as $file) {
                try {
                    // FIXED: Service expects ($data, $file) not individual parameters
                    $this->documentService->uploadDocument([
                        'document_category' => $validated['document_category'],
                        'document_type' => $validated['document_type'],
                        'document_name' => $file->getClientOriginalName(),
                    ], $file);
                    $uploadedCount++;
                } catch (Exception $e) {
                    $failedCount++;
                    $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                }
            }

            $message = "Uploaded: $uploadedCount documents";
            if ($failedCount > 0) {
                $message .= ", Failed: $failedCount documents";
            }

            return back()->with('success', $message)
                ->with('errors', $errors);
        } catch (Exception $e) {
            return back()->with('error', 'Bulk upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Archive document (soft delete but keep)
     */
    public function archive(DocumentArchive $document)
    {
        $this->authorize('archive', $document);

        try {
            $this->documentService->archiveDocument($document->id);

            return back()->with('success', 'Document archived successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to archive document: ' . $e->getMessage());
        }
    }

    /**
     * Restore archived document
     */
    public function restore($documentId)
    {
        // FIXED: Need to find document first to authorize
        $document = DocumentArchive::withTrashed()->findOrFail($documentId);
        $this->authorize('restore', $document);

        try {
            $document = $this->documentService->restoreDocument($documentId);

            return redirect()->route('document-archive.show', $document)
                ->with('success', 'Document restored successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to restore document: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete document
     */
    public function destroy(DocumentArchive $document)
    {
        $this->authorize('delete', $document);

        try {
            $this->documentService->deleteDocument($document->id);

            return redirect()->route('document-archive.index')
                ->with('success', 'Document permanently deleted!');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Generate document report
     */
    public function report(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'category' => 'nullable|string',
        ]);

        try {
            $report = $this->documentService->generateReport(
                $validated['start_date'],
                $validated['end_date'],
                $validated['category'] ?? null
            );

            return view('document-archive.report', compact('report'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Send expiry reminders
     */
    public function sendExpiryReminders()
    {
        $this->authorize('create', DocumentArchive::class);

        try {
            $count = $this->documentService->sendExpiryReminders();

            return back()->with('success', "Sent reminders for $count documents!");
        } catch (Exception $e) {
            return back()->with('error', 'Failed to send reminders: ' . $e->getMessage());
        }
    }

    /**
     * Missing document summary report
     */
    public function missingDocuments(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $validated = $request->validate([
            'campus_id' => 'nullable|exists:campuses,id',
            'status' => 'nullable|string',
        ]);

        try {
            // Define required document types per candidate status
            $requiredDocsByStatus = [
                'registered' => ['cnic', 'education_certificate', 'domicile', 'photo'],
                'training' => ['cnic', 'education_certificate', 'domicile', 'photo', 'medical_certificate'],
                'visa_processing' => ['cnic', 'education_certificate', 'domicile', 'photo', 'medical_certificate', 'passport'],
                'departed' => ['cnic', 'education_certificate', 'domicile', 'photo', 'medical_certificate', 'passport', 'visa', 'ticket'],
            ];

            $query = Candidate::with(['documents', 'campus', 'trade', 'oep'])
                ->whereIn('status', array_keys($requiredDocsByStatus));

            // Filter by campus
            if (!empty($validated['campus_id'])) {
                $query->where('campus_id', $validated['campus_id']);
            }

            // Filter by status
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
            }

            $candidates = $query->get();

            $candidatesWithMissing = [];
            $missingCounts = [
                'cnic' => 0,
                'education_certificate' => 0,
                'domicile' => 0,
                'photo' => 0,
                'medical_certificate' => 0,
                'passport' => 0,
                'visa' => 0,
                'ticket' => 0,
            ];

            foreach ($candidates as $candidate) {
                $required = $requiredDocsByStatus[$candidate->status] ?? [];
                $uploadedTypes = $candidate->documents->pluck('document_type')->map(function($type) {
                    return strtolower(str_replace(' ', '_', $type));
                })->toArray();

                $missing = [];
                foreach ($required as $doc) {
                    if (!in_array($doc, $uploadedTypes)) {
                        $missing[] = $doc;
                        if (isset($missingCounts[$doc])) {
                            $missingCounts[$doc]++;
                        }
                    }
                }

                if (!empty($missing)) {
                    $candidatesWithMissing[] = [
                        'candidate' => $candidate,
                        'missing' => $missing,
                        'missing_count' => count($missing),
                        'required_count' => count($required),
                        'uploaded_count' => count($uploadedTypes),
                        'completion_percentage' => count($required) > 0
                            ? round(((count($required) - count($missing)) / count($required)) * 100, 1)
                            : 100,
                    ];
                }
            }

            // Sort by missing count (highest first)
            usort($candidatesWithMissing, function($a, $b) {
                return $b['missing_count'] - $a['missing_count'];
            });

            $campuses = \App\Models\Campus::where('is_active', true)->pluck('name', 'id');
            $statuses = array_keys($requiredDocsByStatus);

            $stats = [
                'total_candidates_with_missing' => count($candidatesWithMissing),
                'missing_counts' => $missingCounts,
            ];

            return view('document-archive.reports.missing', compact(
                'candidatesWithMissing', 'campuses', 'statuses', 'stats', 'validated'
            ));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Document verification status by OEP and Campus
     */
    public function verificationStatus(Request $request)
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $validated = $request->validate([
            'view_by' => 'nullable|in:campus,oep',
        ]);

        $viewBy = $validated['view_by'] ?? 'campus';

        try {
            // Required document count for full verification
            $requiredDocCount = 5;

            if ($viewBy === 'campus') {
                $data = \App\Models\Campus::where('is_active', true)
                    ->withCount([
                        'candidates as total_candidates',
                        'candidates as complete_docs' => function($q) use ($requiredDocCount) {
                            $q->whereHas('documents', function($dq) {}, '>=', $requiredDocCount);
                        },
                        'candidates as verified_docs' => function($q) {
                            $q->whereHas('documents', function($dq) {
                                $dq->where('verification_status', 'verified');
                            });
                        },
                    ])
                    ->get()
                    ->map(function($item) {
                        $item->completion_rate = $item->total_candidates > 0
                            ? round(($item->complete_docs / $item->total_candidates) * 100, 1)
                            : 0;
                        $item->verification_rate = $item->total_candidates > 0
                            ? round(($item->verified_docs / $item->total_candidates) * 100, 1)
                            : 0;
                        return $item;
                    });
            } else {
                $data = \App\Models\Oep::where('is_active', true)
                    ->withCount([
                        'candidates as total_candidates',
                        'candidates as complete_docs' => function($q) use ($requiredDocCount) {
                            $q->whereHas('documents', function($dq) {}, '>=', $requiredDocCount);
                        },
                        'candidates as verified_docs' => function($q) {
                            $q->whereHas('documents', function($dq) {
                                $dq->where('verification_status', 'verified');
                            });
                        },
                    ])
                    ->get()
                    ->map(function($item) {
                        $item->completion_rate = $item->total_candidates > 0
                            ? round(($item->complete_docs / $item->total_candidates) * 100, 1)
                            : 0;
                        $item->verification_rate = $item->total_candidates > 0
                            ? round(($item->verified_docs / $item->total_candidates) * 100, 1)
                            : 0;
                        return $item;
                    });
            }

            // Overall stats
            $totalCandidates = Candidate::when(auth()->user()->role === 'campus_admin', function($q) {
                $q->where('campus_id', auth()->user()->campus_id);
            })->count();

            $candidatesWithDocs = Candidate::whereHas('documents')
                ->when(auth()->user()->role === 'campus_admin', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                })->count();

            $candidatesWithCompleteDocs = Candidate::whereHas('documents', function($q) {}, '>=', $requiredDocCount)
                ->when(auth()->user()->role === 'campus_admin', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                })->count();

            $stats = [
                'total_candidates' => $totalCandidates,
                'with_documents' => $candidatesWithDocs,
                'with_complete_documents' => $candidatesWithCompleteDocs,
                'overall_completion_rate' => $totalCandidates > 0
                    ? round(($candidatesWithCompleteDocs / $totalCandidates) * 100, 1)
                    : 0,
            ];

            return view('document-archive.reports.verification-status', compact('data', 'viewBy', 'stats'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }
}