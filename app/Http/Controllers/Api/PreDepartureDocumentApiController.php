<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePreDepartureDocumentRequest;
use App\Http\Resources\PreDepartureDocumentResource;
use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use App\Services\PreDepartureDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreDepartureDocumentApiController extends Controller
{
    protected PreDepartureDocumentService $service;

    public function __construct(PreDepartureDocumentService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all pre-departure documents for a candidate
     */
    public function index(Candidate $candidate)
    {
        $this->authorize('viewAny', [PreDepartureDocument::class, $candidate]);

        $documents = $candidate->preDepartureDocuments()
            ->with(['documentChecklist', 'uploader', 'verifier'])
            ->get();

        $status = $candidate->getPreDepartureDocumentStatus();

        return response()->json([
            'documents' => PreDepartureDocumentResource::collection($documents),
            'status' => $status,
            'checklists' => DocumentChecklist::active()->orderBy('display_order')->get(),
        ]);
    }

    /**
     * Upload a document
     */
    public function store(Candidate $candidate, StorePreDepartureDocumentRequest $request)
    {
        $this->authorize('create', [PreDepartureDocument::class, $candidate]);

        $checklist = DocumentChecklist::findOrFail($request->document_checklist_id);

        $document = $this->service->uploadDocument(
            $candidate,
            $checklist,
            $request->file('file'),
            ['notes' => $request->notes]
        );

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => new PreDepartureDocumentResource($document),
        ], 201);
    }

    /**
     * Get a specific document
     */
    public function show(Candidate $candidate, PreDepartureDocument $document)
    {
        $this->authorize('view', $document);

        return new PreDepartureDocumentResource($document->load(['documentChecklist', 'uploader', 'verifier']));
    }

    /**
     * Delete a document
     */
    public function destroy(Candidate $candidate, PreDepartureDocument $document)
    {
        $this->authorize('delete', $document);

        // Delete file
        Storage::disk('private')->delete($document->file_path);

        // Log activity
        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'document_type' => $document->documentChecklist->name,
            ])
            ->log('Pre-departure document deleted via API');

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * Download a document
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
     * Verify a document
     */
    public function verify(Candidate $candidate, PreDepartureDocument $document, Request $request)
    {
        $this->authorize('verify', $document);

        $this->service->verifyDocument($document, auth()->user(), $request->notes);

        return response()->json([
            'message' => 'Document verified successfully',
            'document' => new PreDepartureDocumentResource($document->fresh(['verifier'])),
        ]);
    }

    /**
     * Reject a document
     */
    public function reject(Candidate $candidate, PreDepartureDocument $document, Request $request)
    {
        $this->authorize('reject', $document);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->service->rejectDocument($document, auth()->user(), $request->reason);

        return response()->json([
            'message' => 'Document rejected successfully',
            'document' => new PreDepartureDocumentResource($document->fresh()),
        ]);
    }
}
