<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CorrespondenceResource;
use App\Models\Correspondence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorrespondenceApiController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Correspondence::class);

        $user  = $request->user();
        $query = Correspondence::with(['campus', 'oep', 'creator']);

        $this->applyUserScope($query, $user);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // `type` is the DB column (incoming|outgoing)
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

        if ($request->filled('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhere('file_reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('sender', 'LIKE', "%{$search}%")
                  ->orWhere('recipient', 'LIKE', "%{$search}%")
                  ->orWhere('message', 'LIKE', "%{$search}%");
            });
        }

        $correspondences = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => CorrespondenceResource::collection($correspondences),
            'meta'    => [
                'current_page' => $correspondences->currentPage(),
                'last_page'    => $correspondences->lastPage(),
                'per_page'     => $correspondences->perPage(),
                'total'        => $correspondences->total(),
            ],
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(Request $request, $id): JsonResponse
    {
        $correspondence = Correspondence::with(['campus', 'oep', 'creator'])->findOrFail($id);

        $this->authorize('view', $correspondence);

        return response()->json([
            'success' => true,
            'data'    => new CorrespondenceResource($correspondence),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    /**
     * Accepts the API field names expected by tests and maps them to canonical
     * DB column names before persisting.
     *
     * API field  → DB column
     * ---------    ---------
     * type        → type              (same)
     * content     → message
     * priority    → priority_level
     * reference_number → file_reference_number
     * date_received / date_sent → sent_at
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Correspondence::class);

        $validated = $request->validate([
            'organization_type' => 'required|in:btevta,oep,embassy,campus,government,private,ngo,internal,other',
            'type'              => 'required|in:incoming,outgoing',
            'subject'           => 'required|string|max:500',
            'sender'            => 'required|string|max:255',
            'recipient'         => 'required|string|max:255',
            'content'           => 'required|string',
            'reference_number'  => 'nullable|string|max:100',
            'date_received'     => 'required_if:type,incoming|nullable|date',
            'date_sent'         => 'required_if:type,outgoing|nullable|date',
            'campus_id'         => 'nullable|exists:campuses,id',
            'oep_id'            => 'nullable|exists:oeps,id',
            'priority'          => 'nullable|in:low,normal,high,urgent',
            'status'            => 'nullable|in:pending,in_progress,replied,closed',
            'due_date'          => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        $payload = $this->mapApiToDb($validated, $request->user()->id);

        $correspondence = Correspondence::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Correspondence created successfully',
            'data'    => new CorrespondenceResource($correspondence->load(['campus', 'oep', 'creator'])),
        ], 201);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function update(Request $request, $id): JsonResponse
    {
        $correspondence = Correspondence::findOrFail($id);

        $this->authorize('update', $correspondence);

        $validated = $request->validate([
            'organization_type' => 'sometimes|in:btevta,oep,embassy,campus,government,private,ngo,internal,other',
            'type'              => 'sometimes|in:incoming,outgoing',
            'subject'           => 'sometimes|string|max:500',
            'sender'            => 'sometimes|string|max:255',
            'recipient'         => 'sometimes|string|max:255',
            'content'           => 'sometimes|string',
            'reference_number'  => 'nullable|string|max:100',
            'date_received'     => 'sometimes|nullable|date',
            'date_sent'         => 'sometimes|nullable|date',
            'priority'          => 'nullable|in:low,normal,high,urgent',
            'status'            => 'nullable|in:pending,in_progress,replied,closed',
            'due_date'          => 'nullable|date',
            'response_date'     => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        $payload = $this->mapApiToDb($validated);

        $correspondence->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Correspondence updated successfully',
            'data'    => new CorrespondenceResource($correspondence->load(['campus', 'oep', 'creator'])),
        ]);
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

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

    // ─── Statistics ───────────────────────────────────────────────────────────

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Correspondence::class);

        $user      = $request->user();
        $baseQuery = Correspondence::query();

        $this->applyUserScope($baseQuery, $user);

        if ($request->filled('from_date')) {
            $baseQuery->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $baseQuery->whereDate('created_at', '<=', $request->to_date);
        }

        $stats = [
            'total'   => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', Correspondence::STATUS_PENDING)->count(),
            'replied' => (clone $baseQuery)->where('status', Correspondence::STATUS_REPLIED)->count(),
            'closed'  => (clone $baseQuery)->where('status', Correspondence::STATUS_CLOSED)->count(),
            'overdue' => (clone $baseQuery)->overdue()->count(),

            'by_type' => (clone $baseQuery)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type'),

            'by_organization_type' => (clone $baseQuery)
                ->selectRaw('organization_type, count(*) as count')
                ->groupBy('organization_type')
                ->get()
                ->pluck('count', 'organization_type'),
        ];

        $avgResponseTime = (clone $baseQuery)
            ->whereNotNull('replied_at')
            ->selectRaw('AVG(DATEDIFF(replied_at, sent_at)) as avg_days')
            ->value('avg_days');

        $stats['avg_response_time_days'] = $avgResponseTime ? round($avgResponseTime, 1) : 0;

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    // ─── Pending ──────────────────────────────────────────────────────────────

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Correspondence::class);

        $user  = $request->user();
        $query = Correspondence::with(['campus', 'oep', 'creator'])
            ->where('status', Correspondence::STATUS_PENDING);

        $this->applyUserScope($query, $user);

        $correspondences = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => CorrespondenceResource::collection($correspondences),
            'meta'    => [
                'current_page' => $correspondences->currentPage(),
                'last_page'    => $correspondences->lastPage(),
                'per_page'     => $correspondences->perPage(),
                'total'        => $correspondences->total(),
            ],
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Map API request field names to canonical DB column names.
     */
    private function mapApiToDb(array $validated, ?int $createdBy = null): array
    {
        $map = [];

        // Pass-through fields (API name = DB column name)
        $passThrough = [
            'organization_type', 'type', 'subject', 'sender', 'recipient',
            'campus_id', 'oep_id', 'status', 'due_date', 'notes',
        ];
        foreach ($passThrough as $field) {
            if (array_key_exists($field, $validated)) {
                $map[$field] = $validated[$field];
            }
        }

        // API alias → DB column
        if (array_key_exists('content', $validated)) {
            $map['message'] = $validated['content'];
        }
        if (array_key_exists('priority', $validated)) {
            $map['priority_level'] = $validated['priority'];
        }
        if (array_key_exists('reference_number', $validated)) {
            $map['file_reference_number'] = $validated['reference_number'];
        }
        if (array_key_exists('response_date', $validated)) {
            $map['replied_at'] = $validated['response_date'];
        }

        // date_received / date_sent both map to sent_at
        if (!empty($validated['date_received'])) {
            $map['sent_at'] = $validated['date_received'];
        } elseif (!empty($validated['date_sent'])) {
            $map['sent_at'] = $validated['date_sent'];
        }

        if ($createdBy !== null) {
            $map['created_by'] = $createdBy;
        }

        return $map;
    }

    /**
     * Constrain query to records visible to the given user.
     */
    private function applyUserScope($query, $user): void
    {
        if ($user->isSuperAdmin() || $user->isProjectDirector() || $user->isViewer()) {
            return;
        }

        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        } elseif ($user->isOep() && $user->oep_id) {
            $query->where('oep_id', $user->oep_id);
        }
    }
}
