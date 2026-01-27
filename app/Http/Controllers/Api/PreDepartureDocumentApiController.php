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
        ]);
    }

    /**
     * Upload a pre-departure document
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

        return new PreDepartureDocumentResource($document);
    }

    /**
     * Get a specific pre-departure document
     */
    public function show(Candidate $candidate, PreDepartureDocument $document)
    {
        $this->authorize('view', $document);

        return new PreDepartureDocumentResource($document->load(['documentChecklist', 'uploader', 'verifier']));
    }

    /**
     * Delete a pre-departure document
     */
    public function destroy(Candidate $candidate, PreDepartureDocument $document)
    {
        $this->authorize('delete', $document);

        Storage::disk('private')->delete($document->file_path);

        activity()
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->withProperties([
                'candidate_id' => $candidate->id,
                'document_type' => $document->documentChecklist->name,
            ])
            ->log('Pre-departure document deleted');

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.']);
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
     * Verify a pre-departure document
     */
    public function verify(Candidate $candidate, PreDepartureDocument $document, Request $request)
    {
        $this->authorize('verify', $document);

        $document = $this->service->verifyDocument($document, auth()->user(), $request->notes);

        return new PreDepartureDocumentResource($document);
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

        $document = $this->service->rejectDocument($document, auth()->user(), $request->reason);

        return new PreDepartureDocumentResource($document);
    }
}
