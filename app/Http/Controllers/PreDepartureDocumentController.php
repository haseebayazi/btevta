<?php

namespace App\Http\Controllers;

use App\Models\PreDepartureDocument;
use App\Models\Candidate;
use App\Models\DocumentChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PreDepartureDocumentController extends Controller
{
    /**
     * Display pre-departure documents for a candidate.
     */
    public function index(Candidate $candidate)
    {
        $this->authorize('viewAny', PreDepartureDocument::class);

        $candidate->load(['preDepartureDocuments.documentChecklist', 'preDepartureDocuments.verifier']);

        $checklists = DocumentChecklist::active()->get();
        $mandatoryDocs = $checklists->where('is_mandatory', true);
        $optionalDocs = $checklists->where('is_mandatory', false);

        // Calculate completion percentage
        $mandatoryCount = $mandatoryDocs->count();
        $uploadedMandatoryCount = $candidate->preDepartureDocuments()
            ->whereHas('documentChecklist', function ($q) {
                $q->where('is_mandatory', true);
            })
            ->whereNotNull('uploaded_at')
            ->count();

        $completionPercentage = $mandatoryCount > 0
            ? round(($uploadedMandatoryCount / $mandatoryCount) * 100)
            : 0;

        return view('admin.pre-departure-documents.index', compact(
            'candidate',
            'mandatoryDocs',
            'optionalDocs',
            'completionPercentage'
        ));
    }

    /**
     * Store a newly uploaded pre-departure document.
     */
    public function store(Request $request, Candidate $candidate)
    {
        $this->authorize('create', PreDepartureDocument::class);

        $validated = $request->validate([
            'document_checklist_id' => 'required|exists:document_checklists,id',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Check if document already exists for this type
            $existing = $candidate->preDepartureDocuments()
                ->where('document_checklist_id', $validated['document_checklist_id'])
                ->first();

            if ($existing) {
                // Delete old file
                if ($existing->file_path) {
                    Storage::disk('private')->delete($existing->file_path);
                }
            }

            // Store new document
            $path = $request->file('document')->store(
                'pre-departure-documents/' . $candidate->id,
                'private'
            );

            $documentData = [
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $validated['document_checklist_id'],
                'file_path' => $path,
                'uploaded_at' => now(),
                'uploaded_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ];

            if ($existing) {
                $existing->update($documentData);
                $document = $existing;
                $action = 'updated';
            } else {
                $document = PreDepartureDocument::create($documentData);
                $action = 'uploaded';
            }

            // Log activity
            activity()
                ->performedOn($document)
                ->causedBy(auth()->user())
                ->log("Pre-departure document {$action}");

            return back()->with('success', "Document {$action} successfully!");
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to upload document: ' . $e->getMessage());
        }
    }

    /**
     * Verify a pre-departure document.
     */
    public function verify(Request $request, PreDepartureDocument $preDepartureDocument)
    {
        $this->authorize('update', $preDepartureDocument);

        $validated = $request->validate([
            'verified' => 'required|boolean',
            'verification_notes' => 'nullable|string|max:500',
        ]);

        try {
            if ($validated['verified']) {
                $preDepartureDocument->update([
                    'verified_at' => now(),
                    'verified_by' => auth()->id(),
                    'notes' => $validated['verification_notes'] ?? $preDepartureDocument->notes,
                ]);

                $message = 'Document verified successfully!';
                $logMessage = 'Pre-departure document verified';
            } else {
                $preDepartureDocument->update([
                    'verified_at' => null,
                    'verified_by' => null,
                    'notes' => $validated['verification_notes'] ?? $preDepartureDocument->notes,
                ]);

                $message = 'Document verification removed!';
                $logMessage = 'Pre-departure document verification removed';
            }

            // Log activity
            activity()
                ->performedOn($preDepartureDocument)
                ->causedBy(auth()->user())
                ->log($logMessage);

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update verification: ' . $e->getMessage());
        }
    }

    /**
     * Download a pre-departure document.
     */
    public function download(PreDepartureDocument $preDepartureDocument)
    {
        $this->authorize('view', $preDepartureDocument);

        if (!$preDepartureDocument->file_path || !Storage::disk('private')->exists($preDepartureDocument->file_path)) {
            return back()->with('error', 'Document file not found.');
        }

        $checklist = $preDepartureDocument->documentChecklist;
        $candidate = $preDepartureDocument->candidate;

        $filename = sprintf(
            '%s-%s-%s.%s',
            $candidate->btevta_id ?? $candidate->id,
            str_replace(' ', '-', $checklist->name),
            $preDepartureDocument->id,
            pathinfo($preDepartureDocument->file_path, PATHINFO_EXTENSION)
        );

        return Storage::disk('private')->download($preDepartureDocument->file_path, $filename);
    }

    /**
     * Delete a pre-departure document.
     */
    public function destroy(PreDepartureDocument $preDepartureDocument)
    {
        $this->authorize('delete', $preDepartureDocument);

        try {
            // Delete file from storage
            if ($preDepartureDocument->file_path) {
                Storage::disk('private')->delete($preDepartureDocument->file_path);
            }

            // Log activity before deletion
            activity()
                ->performedOn($preDepartureDocument)
                ->causedBy(auth()->user())
                ->log('Pre-departure document deleted');

            $preDepartureDocument->delete();

            return back()->with('success', 'Document deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Bulk upload documents for a candidate.
     */
    public function bulkUpload(Request $request, Candidate $candidate)
    {
        $this->authorize('create', PreDepartureDocument::class);

        $validated = $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*.checklist_id' => 'required|exists:document_checklists,id',
            'documents.*.file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'documents.*.notes' => 'nullable|string|max:500',
        ]);

        $uploaded = 0;
        $errors = [];

        foreach ($validated['documents'] as $index => $docData) {
            try {
                // Check if document already exists
                $existing = $candidate->preDepartureDocuments()
                    ->where('document_checklist_id', $docData['checklist_id'])
                    ->first();

                if ($existing && $existing->file_path) {
                    Storage::disk('private')->delete($existing->file_path);
                }

                $path = $docData['file']->store(
                    'pre-departure-documents/' . $candidate->id,
                    'private'
                );

                $documentData = [
                    'candidate_id' => $candidate->id,
                    'document_checklist_id' => $docData['checklist_id'],
                    'file_path' => $path,
                    'uploaded_at' => now(),
                    'uploaded_by' => auth()->id(),
                    'notes' => $docData['notes'] ?? null,
                ];

                if ($existing) {
                    $existing->update($documentData);
                } else {
                    PreDepartureDocument::create($documentData);
                }

                $uploaded++;
            } catch (\Exception $e) {
                $errors[] = "Document {$index}: " . $e->getMessage();
            }
        }

        if ($uploaded > 0) {
            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log("Bulk uploaded {$uploaded} pre-departure documents");
        }

        if (count($errors) > 0) {
            return back()->with('warning', "Uploaded {$uploaded} documents. Errors: " . implode(', ', $errors));
        }

        return back()->with('success', "Successfully uploaded {$uploaded} documents!");
    }
}
