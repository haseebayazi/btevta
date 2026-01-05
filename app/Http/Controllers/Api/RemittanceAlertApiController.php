<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\RemittanceAlert;
use App\Services\RemittanceAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemittanceAlertApiController extends Controller
{
    protected $alertService;

    public function __construct(RemittanceAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Get all alerts (paginated)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RemittanceAlert::class);

        $query = RemittanceAlert::with(['candidate', 'remittance'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'unresolved') {
                $query->where('is_resolved', false);
            } elseif ($request->status === 'resolved') {
                $query->where('is_resolved', true);
            }
        } else {
            // Default: show only unresolved
            $query->where('is_resolved', false);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('alert_type', $request->type);
        }

        // Filter by candidate
        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        $perPage = $request->input('per_page', 20);
        $alerts = $query->paginate($perPage);

        return response()->json($alerts);
    }

    /**
     * Get alert by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $alert = RemittanceAlert::with(['candidate', 'remittance', 'resolvedBy'])
            ->find($id);

        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $this->authorize('view', $alert);

        // Removed auto-mark as read (violates HTTP GET semantics)
        // Use the dedicated markAsRead() endpoint instead

        return response()->json($alert);
    }

    /**
     * Get unread alert count
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unreadCount(Request $request)
    {
        $this->authorize('viewAny', RemittanceAlert::class);

        $candidateId = $request->input('candidate_id');
        $count = $this->alertService->getUnresolvedAlertsCount($candidateId);

        return response()->json(['count' => $count]);
    }

    /**
     * Get alert statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $this->authorize('viewAny', RemittanceAlert::class);

        $stats = $this->alertService->getAlertStatistics();

        return response()->json($stats);
    }

    /**
     * Mark alert as read
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $alert = RemittanceAlert::find($id);

        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $this->authorize('view', $alert);

        $alert->markAsRead();

        return response()->json([
            'message' => 'Alert marked as read',
            'alert' => $alert,
        ]);
    }

    /**
     * Resolve alert
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resolve(Request $request, $id)
    {
        $alert = RemittanceAlert::find($id);

        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $this->authorize('resolve', $alert);

        $notes = $request->input('resolution_notes');
        $alert->resolve(Auth::id(), $notes);

        return response()->json([
            'message' => 'Alert resolved successfully',
            'alert' => $alert,
        ]);
    }

    /**
     * Dismiss alert
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function dismiss($id)
    {
        $alert = RemittanceAlert::find($id);

        if (!$alert) {
            return response()->json(['error' => 'Alert not found'], 404);
        }

        $this->authorize('resolve', $alert);

        $alert->resolve(Auth::id(), 'Dismissed via API');

        return response()->json([
            'message' => 'Alert dismissed successfully',
            'alert' => $alert,
        ]);
    }

    /**
     * Get alerts by candidate ID
     *
     * @param int $candidateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCandidate($candidateId)
    {
        // AUDIT FIX: Authorize view on the specific candidate to prevent cross-tenant data leakage
        $candidate = Candidate::findOrFail($candidateId);
        $this->authorize('view', $candidate);

        $alerts = RemittanceAlert::where('candidate_id', $candidateId)
            ->with(['remittance'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'candidate_id' => $candidateId,
            'alerts' => $alerts,
            'summary' => [
                'total' => $alerts->count(),
                'unresolved' => $alerts->where('is_resolved', false)->count(),
                'critical' => $alerts->where('severity', 'critical')->where('is_resolved', false)->count(),
            ],
        ]);
    }
}
