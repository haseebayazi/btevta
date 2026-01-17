<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Http\Resources\ComplaintResource;
use App\Services\ComplaintService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ComplaintApiController extends Controller
{
    protected ComplaintService $complaintService;

    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
    }

    /**
     * List all complaints with filters
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Complaint::class);

        $query = Complaint::with(['candidate', 'campus', 'oep', 'assignee']);

        // Apply campus filtering for campus admin users
        $user = $request->user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('candidate', fn($cq) => $cq->where('campus_id', $user->campus_id))
                  ->orWhere('assigned_to', $user->id);
            });
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('complaint_category')) {
            $query->where('complaint_category', $request->complaint_category);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('sla_breached')) {
            $query->where('sla_breached', $request->sla_breached === 'true' || $request->sla_breached === '1');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('registered_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('registered_at', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function($q) use ($search) {
                $q->where('complaint_reference', 'LIKE', "%{$search}%")
                  ->orWhere('complainant_name', 'LIKE', "%{$search}%")
                  ->orWhere('subject', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $complaints = $query->latest('registered_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ComplaintResource::collection($complaints),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }

    /**
     * Show specific complaint
     */
    public function show(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::with(['candidate', 'campus', 'oep', 'assignee', 'escalatedToUser'])->findOrFail($id);

        $this->authorize('view', $complaint);

        // Get SLA status
        $slaStatus = $this->complaintService->checkSLAStatus($id);

        return response()->json([
            'success' => true,
            'data' => array_merge(
                (new ComplaintResource($complaint))->toArray($request),
                ['sla_status' => $slaStatus]
            ),
        ]);
    }

    /**
     * Create complaint
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Complaint::class);

        $validated = $request->validate([
            'candidate_id' => 'nullable|exists:candidates,id',
            'complainant_name' => 'required|string|max:255',
            'complainant_contact' => 'required|string|max:20',
            'complainant_email' => 'nullable|email|max:255',
            'complaint_category' => 'required|in:screening,registration,training,visa,salary,conduct,accommodation,health,discrimination,other',
            'subject' => 'required|string|max:500',
            'description' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent,critical',
        ]);

        $complaint = $this->complaintService->registerComplaint($validated);

        return response()->json([
            'success' => true,
            'message' => 'Complaint registered successfully',
            'data' => new ComplaintResource($complaint->load(['candidate', 'campus', 'oep', 'assignee'])),
        ], 201);
    }

    /**
     * Update complaint
     */
    public function update(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'priority' => 'sometimes|in:low,normal,high,urgent,critical',
            'status' => 'sometimes|in:registered,assigned,in_progress,resolved,closed',
            'assigned_to' => 'sometimes|exists:users,id',
        ]);

        if (isset($validated['priority'])) {
            $this->complaintService->updatePriority($id, $validated['priority']);
        }

        if (isset($validated['status'])) {
            $this->complaintService->updateStatus($id, $validated['status']);
        }

        if (isset($validated['assigned_to'])) {
            $this->complaintService->assignComplaint($id, $validated['assigned_to']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Complaint updated successfully',
            'data' => new ComplaintResource($complaint->fresh()->load(['candidate', 'campus', 'oep', 'assignee'])),
        ]);
    }

    /**
     * Assign complaint
     */
    public function assign(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $this->complaintService->assignComplaint($id, $validated['assigned_to'], $validated['remarks'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Complaint assigned successfully',
            'data' => new ComplaintResource($complaint->fresh()->load(['candidate', 'campus', 'oep', 'assignee'])),
        ]);
    }

    /**
     * Escalate complaint
     */
    public function escalate(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'escalation_reason' => 'required|string',
        ]);

        $this->complaintService->escalateComplaint($id, $validated['escalation_reason']);

        return response()->json([
            'success' => true,
            'message' => 'Complaint escalated successfully',
            'data' => new ComplaintResource($complaint->fresh()->load(['candidate', 'campus', 'oep', 'assignee', 'escalatedToUser'])),
        ]);
    }

    /**
     * Resolve complaint
     */
    public function resolve(Request $request, $id): JsonResponse
    {
        $complaint = Complaint::findOrFail($id);

        $this->authorize('update', $complaint);

        $validated = $request->validate([
            'resolution_details' => 'required|string',
            'action_taken' => 'nullable|string',
            'resolution_category' => 'nullable|in:accepted,rejected,partial',
        ]);

        $this->complaintService->resolveComplaint($id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Complaint resolved successfully',
            'data' => new ComplaintResource($complaint->fresh()->load(['candidate', 'campus', 'oep', 'assignee'])),
        ]);
    }

    /**
     * Get complaint statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Complaint::class);

        $filters = [];
        if ($request->filled('from_date')) {
            $filters['from_date'] = $request->from_date;
        }
        if ($request->filled('to_date')) {
            $filters['to_date'] = $request->to_date;
        }

        $stats = $this->complaintService->getStatistics($filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get overdue complaints
     */
    public function overdue(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Complaint::class);

        $filters = [];
        if ($request->filled('category')) {
            $filters['category'] = $request->category;
        }
        if ($request->filled('priority')) {
            $filters['priority'] = $request->priority;
        }

        $overdueComplaints = $this->complaintService->getOverdueComplaints($filters);

        return response()->json([
            'success' => true,
            'data' => $overdueComplaints,
        ]);
    }
}
