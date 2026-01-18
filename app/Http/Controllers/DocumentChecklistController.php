<?php

namespace App\Http\Controllers;

use App\Models\DocumentChecklist;
use Illuminate\Http\Request;

class DocumentChecklistController extends Controller
{
    /**
     * Display a listing of document checklists.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', DocumentChecklist::class);

        $query = DocumentChecklist::withCount(['preDepartureDocuments']);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by mandatory status
        if ($request->filled('is_mandatory')) {
            $query->where('is_mandatory', $request->boolean('is_mandatory'));
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $checklists = $query->orderBy('display_order')->paginate(50);

        return view('admin.document-checklists.index', compact('checklists'));
    }

    /**
     * Show the form for creating a new document checklist.
     */
    public function create()
    {
        $this->authorize('create', DocumentChecklist::class);

        return view('admin.document-checklists.create');
    }

    /**
     * Store a newly created document checklist.
     */
    public function store(Request $request)
    {
        $this->authorize('create', DocumentChecklist::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:document_checklists,code',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:mandatory,optional',
            'is_mandatory' => 'boolean',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_mandatory'] = $request->boolean('is_mandatory');
            $validated['is_active'] = $request->boolean('is_active', true);

            $checklist = DocumentChecklist::create($validated);

            // Log activity
            activity()
                ->performedOn($checklist)
                ->causedBy(auth()->user())
                ->log('Document checklist created');

            return redirect()->route('admin.document-checklists.index')
                ->with('success', 'Document checklist created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to create document checklist: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified document checklist.
     */
    public function show(DocumentChecklist $documentChecklist)
    {
        $this->authorize('view', $documentChecklist);

        $documentChecklist->load(['preDepartureDocuments' => function ($query) {
            $query->with('candidate')->latest()->limit(20);
        }]);

        return view('admin.document-checklists.show', compact('documentChecklist'));
    }

    /**
     * Show the form for editing the document checklist.
     */
    public function edit(DocumentChecklist $documentChecklist)
    {
        $this->authorize('update', $documentChecklist);

        return view('admin.document-checklists.edit', compact('documentChecklist'));
    }

    /**
     * Update the specified document checklist.
     */
    public function update(Request $request, DocumentChecklist $documentChecklist)
    {
        $this->authorize('update', $documentChecklist);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:document_checklists,code,' . $documentChecklist->id,
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:mandatory,optional',
            'is_mandatory' => 'boolean',
            'display_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $validated['is_mandatory'] = $request->boolean('is_mandatory');
            $validated['is_active'] = $request->boolean('is_active', $documentChecklist->is_active);

            $documentChecklist->update($validated);

            // Log activity
            activity()
                ->performedOn($documentChecklist)
                ->causedBy(auth()->user())
                ->log('Document checklist updated');

            return redirect()->route('admin.document-checklists.index')
                ->with('success', 'Document checklist updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to update document checklist: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified document checklist.
     */
    public function destroy(DocumentChecklist $documentChecklist)
    {
        $this->authorize('delete', $documentChecklist);

        try {
            // Check for associated documents
            $documentsCount = $documentChecklist->preDepartureDocuments()->count();
            if ($documentsCount > 0) {
                return back()->with('error',
                    "Cannot delete checklist: {$documentsCount} document(s) exist. " .
                    "Please remove documents first or deactivate the checklist instead."
                );
            }

            // Log activity before deletion
            activity()
                ->performedOn($documentChecklist)
                ->causedBy(auth()->user())
                ->log('Document checklist deleted');

            $documentChecklist->delete();

            return redirect()->route('admin.document-checklists.index')
                ->with('success', 'Document checklist deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete document checklist: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of a document checklist.
     */
    public function toggleStatus(DocumentChecklist $documentChecklist)
    {
        $this->authorize('update', $documentChecklist);

        try {
            $documentChecklist->update(['is_active' => !$documentChecklist->is_active]);

            $status = $documentChecklist->is_active ? 'activated' : 'deactivated';

            activity()
                ->performedOn($documentChecklist)
                ->causedBy(auth()->user())
                ->log("Document checklist {$status}");

            return back()->with('success', "Document checklist {$status} successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * Reorder document checklists via AJAX.
     */
    public function reorder(Request $request)
    {
        $this->authorize('update', DocumentChecklist::class);

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:document_checklists,id',
            'items.*.display_order' => 'required|integer|min:0',
        ]);

        try {
            foreach ($validated['items'] as $item) {
                DocumentChecklist::where('id', $item['id'])
                    ->update(['display_order' => $item['display_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document checklists reordered successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder: ' . $e->getMessage()
            ], 500);
        }
    }
}
