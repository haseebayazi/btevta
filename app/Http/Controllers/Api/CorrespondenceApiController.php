<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Correspondence;
use App\Http\Resources\CorrespondenceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CorrespondenceApiController extends Controller
{
    /**
     * List all correspondence with filters
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::with(['campus', 'oep', 'creator']);

        // Apply campus/OEP filtering for non-admin users
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('oep_id')) {
            $query->where('oep_id', $request->oep_id);
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
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('sender', 'LIKE', "%{$search}%")
                  ->orWhere('recipient', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $correspondences = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CorrespondenceResource::collection($correspondences),
            'meta' => [
                'current_page' => $correspondences->currentPage(),
                'last_page' => $correspondences->lastPage(),
                'per_page' => $correspondences->perPage(),
                'total' => $correspondences->total(),
            ],
        ]);
    }

    /**
     * Show specific correspondence
     */
    public function show(Request $request, $id): JsonResponse
    {
        $correspondence = Correspondence::with(['campus', 'oep', 'creator'])->findOrFail($id);

        $this->authorize('view', $correspondence);

        return response()->json([
            'success' => true,
            'data' => new CorrespondenceResource($correspondence),
        ]);
    }

    /**
     * Create correspondence
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Correspondence::class);

        $validated = $request->validate([
            'organization_type' => 'required|in:government,embassy,private,ngo,internal',
            'type' => 'required|in:incoming,outgoing',
            'subject' => 'required|string|max:500',
            'sender' => 'required|string|max:255',
            'recipient' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'date_received' => 'required_if:type,incoming|date',
            'date_sent' => 'required_if:type,outgoing|date',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'content' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'status' => 'nullable|in:pending,replied,closed',
            'due_date' => 'nullable|date',
        ]);

        $correspondence = Correspondence::create(array_merge($validated, [
            'created_by' => $request->user()->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Correspondence created successfully',
            'data' => new CorrespondenceResource($correspondence->load(['campus', 'oep', 'creator'])),
        ], 201);
    }

    /**
     * Update correspondence
     */
    public function update(Request $request, $id): JsonResponse
    {
        $correspondence = Correspondence::findOrFail($id);

        $this->authorize('update', $correspondence);

        $validated = $request->validate([
            'organization_type' => 'sometimes|in:government,embassy,private,ngo,internal',
            'type' => 'sometimes|in:incoming,outgoing',
            'subject' => 'sometimes|string|max:500',
            'sender' => 'sometimes|string|max:255',
            'recipient' => 'sometimes|string|max:255',
            'reference_number' => 'nullable|string|max:100',
            'date_received' => 'sometimes|date',
            'date_sent' => 'sometimes|date',
            'content' => 'sometimes|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'status' => 'nullable|in:pending,replied,closed',
            'due_date' => 'nullable|date',
            'response_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $correspondence->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Correspondence updated successfully',
            'data' => new CorrespondenceResource($correspondence->load(['campus', 'oep', 'creator'])),
        ]);
    }

    /**
     * Delete correspondence
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $correspondence = Correspondence::findOrFail($id);

        $this->authorize('delete', $correspondence);

        $correspondence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Correspondence deleted successfully',
        ]);
    }

    /**
     * Get correspondence statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Correspondence::class);

        $baseQuery = Correspondence::query();

        // Apply campus/OEP filtering
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $baseQuery->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $baseQuery->where('oep_id', $user->oep_id);
            }
        }

        // Apply date filters
        if ($request->filled('from_date')) {
            $baseQuery->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $baseQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'replied' => (clone $baseQuery)->where('status', 'replied')->count(),
            'closed' => (clone $baseQuery)->where('status', 'closed')->count(),
            'overdue' => (clone $baseQuery)->where('status', 'pending')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
            'by_type' => (clone $baseQuery)->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type'),
            'by_organization_type' => (clone $baseQuery)->select('organization_type', DB::raw('count(*) as count'))
                ->groupBy('organization_type')
                ->get()
                ->pluck('count', 'organization_type'),
        ];

        // Average response time
        $avgResponseTime = (clone $baseQuery)
            ->whereNotNull('response_date')
            ->select(DB::raw('AVG(DATEDIFF(response_date, created_at)) as avg_days'))
            ->value('avg_days');

        $stats['avg_response_time_days'] = $avgResponseTime ? round($avgResponseTime, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get pending correspondence
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::with(['campus', 'oep', 'creator'])
            ->where('status', 'pending');

        // Apply filtering
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }
        }

        $correspondences = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => CorrespondenceResource::collection($correspondences),
            'meta' => [
                'current_page' => $correspondences->currentPage(),
                'last_page' => $correspondences->lastPage(),
                'per_page' => $correspondences->perPage(),
                'total' => $correspondences->total(),
            ],
        ]);
    }
}
