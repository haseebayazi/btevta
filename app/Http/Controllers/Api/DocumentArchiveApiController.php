<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentArchive;
use App\Http\Resources\DocumentArchiveResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentArchiveApiController extends Controller
{
    /**
     * List all documents with filters
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $query = DocumentArchive::with(['candidate', 'campus', 'uploadedBy']);

        // Apply campus filtering for campus admin users
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        // Filters
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('has_expiry')) {
            $query->whereNotNull('expiry_date');
        }

        if ($request->filled('expiring_soon')) {
            $days = $request->get('expiry_days', 30);
            $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function($q) use ($search) {
                $q->where('document_name', 'LIKE', "%{$search}%")
                  ->orWhere('document_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('tags', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $documents = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DocumentArchiveResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Show specific document
     */
    public function show(Request $request, $id): JsonResponse
    {
        $document = DocumentArchive::with(['candidate', 'campus', 'uploadedBy', 'versions'])->findOrFail($id);

        $this->authorize('view', $document);

        return response()->json([
            'success' => true,
            'data' => new DocumentArchiveResource($document),
        ]);
    }

    /**
     * Get documents for a specific candidate
     */
    public function byCandidate(Request $request, $candidateId): JsonResponse
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $documents = DocumentArchive::where('candidate_id', $candidateId)
            ->with(['campus', 'uploadedBy'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => DocumentArchiveResource::collection($documents),
        ]);
    }

    /**
     * Get document statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $baseQuery = DocumentArchive::query();

        // Apply campus filtering
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $baseQuery->where('campus_id', $user->campus_id);
        }

        // Apply date filters
        if ($request->filled('from_date')) {
            $baseQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $baseQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $stats = [
            'total_documents' => (clone $baseQuery)->count(),
            'by_type' => (clone $baseQuery)->select('document_type', DB::raw('count(*) as count'))
                ->groupBy('document_type')
                ->get()
                ->pluck('count', 'document_type'),
            'by_category' => (clone $baseQuery)->select('category', DB::raw('count(*) as count'))
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
            'by_status' => (clone $baseQuery)->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'expiring_soon_30_days' => (clone $baseQuery)
                ->whereBetween('expiry_date', [now(), now()->addDays(30)])
                ->count(),
            'expiring_soon_60_days' => (clone $baseQuery)
                ->whereBetween('expiry_date', [now(), now()->addDays(60)])
                ->count(),
            'expired' => (clone $baseQuery)
                ->where('expiry_date', '<', now())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get expiring documents
     */
    public function expiring(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $days = $request->get('days', 30);

        $query = DocumentArchive::with(['candidate', 'campus', 'uploadedBy'])
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);

        // Apply campus filtering
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $documents = $query->orderBy('expiry_date', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => DocumentArchiveResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
                'expiry_window_days' => $days,
            ],
        ]);
    }

    /**
     * Get expired documents
     */
    public function expired(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $query = DocumentArchive::with(['candidate', 'campus', 'uploadedBy'])
            ->where('expiry_date', '<', now());

        // Apply campus filtering
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $documents = $query->orderBy('expiry_date', 'asc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => DocumentArchiveResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Search documents
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DocumentArchive::class);

        $validated = $request->validate([
            'q' => 'required|string|min:2',
            'document_type' => 'nullable|string',
            'category' => 'nullable|string',
            'candidate_id' => 'nullable|exists:candidates,id',
        ]);

        $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $validated['q']);

        $query = DocumentArchive::with(['candidate', 'campus', 'uploadedBy'])
            ->where(function($q) use ($search) {
                $q->where('document_name', 'LIKE', "%{$search}%")
                  ->orWhere('document_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('tags', 'LIKE', "%{$search}%");
            });

        // Apply filters
        if (isset($validated['document_type'])) {
            $query->where('document_type', $validated['document_type']);
        }

        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        if (isset($validated['candidate_id'])) {
            $query->where('candidate_id', $validated['candidate_id']);
        }

        // Apply campus filtering
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $documents = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => DocumentArchiveResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
            'query' => $validated['q'],
        ]);
    }

    /**
     * Download document
     */
    public function download(Request $request, $id): JsonResponse
    {
        $document = DocumentArchive::findOrFail($id);

        $this->authorize('view', $document);

        if (!Storage::disk('private')->exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found',
            ], 404);
        }

        // Log access
        activity()
            ->performedOn($document)
            ->causedBy($request->user())
            ->log('Document accessed via API');

        // Return file URL or download token
        // Note: In a real implementation, you might return a temporary signed URL
        return response()->json([
            'success' => true,
            'data' => [
                'document_id' => $document->id,
                'document_name' => $document->document_name,
                'file_type' => $document->file_type,
                'file_size' => $document->file_size,
                'download_url' => route('secure-file.download', ['path' => $document->file_path]),
            ],
        ]);
    }
}
