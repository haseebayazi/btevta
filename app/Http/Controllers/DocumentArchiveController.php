<?php

namespace App\Http\Controllers;

use App\Models\DocumentArchive;
use App\Models\Candidate;
use App\Services\DocumentArchiveService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest('uploaded_at')->paginate(20);
        $candidates = Candidate::select('id', 'name', 'cnic')->get();

        return view('document-archive.index', compact('documents', 'candidates'));
    }

    /**
     * Show form to upload new document
     */
    public function create()
    {
        $this->authorize('create', DocumentArchive::class);

        $candidates = Candidate::select('id', 'name', 'cnic', 'passport_number')->get();

        return view('document-archive.create', compact('candidates'));
    }

    /**
     * Upload new document
     */
    public function store(Request $request)
    {
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
            $document = $this->documentService->uploadDocument(
                $request->file('file'),
                $validated['document_category'],
                $validated['document_type'],
                $validated['document_name'],
                $validated['candidate_id'] ?? null,
                $validated['document_number'] ?? null,
                $validated['issue_date'] ?? null,
                $validated['expiry_date'] ?? null,
                $validated['description'] ?? null,
                $validated['tags'] ?? null
            );

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
        $candidates = Candidate::select('id', 'name', 'cnic')->get();

        return view('document-archive.edit', compact('document', 'candidates'));
    }

    /**
     * Update document metadata
     */
    public function update(Request $request, DocumentArchive $document)
    {
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
            // Log the access
            $this->documentService->logAccess($document->id, 'download');

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
            // Log the access
            $this->documentService->logAccess($document->id, 'view');

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
        try {
            $versions = $this->documentService->getVersionHistory($document->id);

            return view('document-archive.versions', compact('document', 'versions'));
        } catch (Exception $e) {
            return back()->with('error', 'Failed to fetch versions: ' . $e->getMessage());
        }
    }

    /**
     * Restore previous version
     */
    public function restoreVersion(Request $request, DocumentArchive $document)
    {
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
        $validated = $request->validate([
            'term' => 'required|string|min:2',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        try {
            $documents = $this->documentService->searchDocuments(
                $validated['term'],
                $validated['category'] ?? null,
                $validated['type'] ?? null
            );

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
     * Get candidate documents
     */
    public function candidateDocuments(Candidate $candidate)
    {
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
                    $this->documentService->uploadDocument(
                        $file,
                        $validated['document_category'],
                        $validated['document_type'],
                        $file->getClientOriginalName()
                    );
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
        try {
            $count = $this->documentService->sendExpiryReminders();

            return back()->with('success', "Sent reminders for $count documents!");
        } catch (Exception $e) {
            return back()->with('error', 'Failed to send reminders: ' . $e->getMessage());
        }
    }
}