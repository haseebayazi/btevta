<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePreDepartureDocumentRequest;
use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use App\Models\PreDepartureDocumentPage;
use App\Services\PreDepartureDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreDepartureDocumentController extends Controller
{
    protected PreDepartureDocumentService $service;

    public function __construct(PreDepartureDocumentService $service)
    {
        $this->service = $service;
    }

    /**
     * Display pre-departure documents page for a candidate
     */
    public function index(Candidate $candidate)
    {
        $this->authorize('viewAny', [PreDepartureDocument::class, $candidate]);

        $documents = $candidate->preDepartureDocuments()
            ->with(['documentChecklist', 'uploader', 'verifier', 'pages'])
            ->get();

        $checklists = DocumentChecklist::active()->orderBy('display_order')->get();
        $status = $candidate->getPreDepartureDocumentStatus();
        $licenses = $candidate->licenses;

        return view('candidates.pre-departure-documents.index', compact(
            'candidate',
            'documents',
            'checklists',
            'status',
            'licenses'
        ));
    }

    /**
     * Upload a pre-departure document
     */
    public function store(Candidate $candidate, StorePreDepartureDocumentRequest $request)
    {
        $this->authorize('create', [PreDepartureDocument::class, $candidate]);

        $checklist = DocumentChecklist::findOrFail($request->document_checklist_id);

        // Check if multiple files were uploaded
        if ($request->hasFile('files') && is_array($request->file('files'))) {
            $files = $request->file('files');
            $document = $this->service->uploadDocumentWithPages(
                $candidate,
                $checklist,
                $files,
                ['notes' => $request->notes]
            );
            $pageCount = count($files);
            $message = "Document '{$checklist->name}' uploaded successfully with {$pageCount} page(s).";
        } else {
            $document = $this->service->uploadDocument(
                $candidate,
                $checklist,
                $request->file('file'),
                ['notes' => $request->notes]
            );
            $message = "Document '{$checklist->name}' uploaded successfully.";
        }

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', $message);
    }

    /**
     * Delete a pre-departure document
     */
    public function destroy(Candidate $candidate, PreDepartureDocument $document)
    {
        $this->authorize('delete', $document);

        // Delete main file from storage
        Storage::disk('private')->delete($document->file_path);

        // Delete all page files
        foreach ($document->pages as $page) {
            Storage::disk('private')->delete($page->file_path);
        }

        // Log deletion
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'document_type' => $document->documentChecklist->name,
                'page_count' => $document->pages->count() + 1,
            ])
            ->log('Pre-departure document deleted');

        // Delete pages first (cascade should handle this, but be explicit)
        $document->pages()->delete();
        $document->delete();

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', 'Document deleted successfully.');
    }

    /**
     * Download a pre-departure document
     */
    public function download(Candidate $candidate, PreDepartureDocument $document)
    {
        $this->authorize('view', $document);

        return Storage::disk('private')->download(
            $document->file_path,
            $document->original_filename
        );
    }

    /**
     * Download a specific page of a pre-departure document
     */
    public function downloadPage(Candidate $candidate, PreDepartureDocument $document, PreDepartureDocumentPage $page)
    {
        $this->authorize('view', $document);

        // Verify page belongs to document
        if ($page->pre_departure_document_id !== $document->id) {
            abort(404);
        }

        return Storage::disk('private')->download(
            $page->file_path,
            $page->original_filename
        );
    }

    /**
     * Verify a pre-departure document
     */
    public function verify(Candidate $candidate, PreDepartureDocument $document, Request $request)
    {
        $this->authorize('verify', $document);

        $this->service->verifyDocument($document, auth()->user(), $request->notes);

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', 'Document verified successfully.');
    }

    /**
     * Reject a pre-departure document
     */
    public function reject(Candidate $candidate, PreDepartureDocument $document, Request $request)
    {
        $this->authorize('reject', $document);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->service->rejectDocument($document, auth()->user(), $request->reason);

        return redirect()
            ->route('candidates.pre-departure-documents.index', $candidate)
            ->with('success', 'Document rejected. Candidate has been notified.');
    }
}
