<?php

namespace App\Http\Controllers;

use App\Models\RemittanceAlert;
use App\Services\RemittanceAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemittanceAlertController extends Controller
{
    protected $alertService;

    public function __construct(RemittanceAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Display a listing of alerts
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RemittanceAlert::class);

        $query = RemittanceAlert::with(['candidate', 'remittance', 'resolvedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'unresolved') {
                $query->where('is_resolved', false);
            } elseif ($request->status === 'resolved') {
                $query->where('is_resolved', true);
            }
        } else {
            // Default: show only unresolved alerts
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

        // Filter by read status
        if ($request->filled('read')) {
            $query->where('is_read', $request->read === 'read');
        }

        $alerts = $query->paginate(20);

        // Get statistics
        $stats = $this->alertService->getAlertStatistics();

        return view('remittances.alerts.index', compact('alerts', 'stats'));
    }

    /**
     * Display the specified alert
     */
    public function show($id)
    {
        $alert = RemittanceAlert::with(['candidate', 'remittance', 'resolvedBy'])
            ->findOrFail($id);

        $this->authorize('view', $alert);

        // Mark as read when viewed
        if (!$alert->is_read) {
            $alert->markAsRead();
        }

        return view('remittances.alerts.show', compact('alert'));
    }

    /**
     * Mark alert as read
     */
    public function markAsRead($id)
    {
        $alert = RemittanceAlert::findOrFail($id);
        $this->authorize('markAsRead', $alert);

        $alert->markAsRead();

        return back()->with('success', 'Alert marked as read.');
    }

    /**
     * Mark all alerts as read
     */
    public function markAllAsRead()
    {
        $this->authorize('markAllAsRead', RemittanceAlert::class);

        RemittanceAlert::where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All alerts marked as read.');
    }

    /**
     * Resolve alert
     */
    public function resolve(Request $request, $id)
    {
        $alert = RemittanceAlert::findOrFail($id);
        $this->authorize('resolve', $alert);

        $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $alert->resolve(Auth::id(), $request->resolution_notes);

        return back()->with('success', 'Alert resolved successfully.');
    }

    /**
     * Manually generate alerts
     */
    public function generateAlerts()
    {
        // FIXED: Use proper authorization instead of manual role check
        $this->authorize('generateAlerts', RemittanceAlert::class);

        $result = $this->alertService->generateAllAlerts();

        return back()->with('success', "Generated {$result['total_generated']} new alerts.");
    }

    /**
     * Auto-resolve alerts
     */
    public function autoResolve()
    {
        // FIXED: Use proper authorization instead of manual role check
        $this->authorize('autoResolve', RemittanceAlert::class);

        $resolved = $this->alertService->autoResolveAlerts();

        return back()->with('success', "Auto-resolved {$resolved} alerts.");
    }

    /**
     * Get unread alert count (AJAX)
     */
    public function unreadCount()
    {
        $this->authorize('getUnreadCount', RemittanceAlert::class);

        $count = $this->alertService->getUnresolvedAlertsCount();

        return response()->json(['count' => $count]);
    }

    /**
     * Dismiss alert (mark as resolved without notes)
     */
    public function dismiss($id)
    {
        $alert = RemittanceAlert::findOrFail($id);
        $this->authorize('dismiss', $alert);

        $alert->resolve(Auth::id(), 'Dismissed by user');

        return back()->with('success', 'Alert dismissed.');
    }

    /**
     * Bulk actions on alerts
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('bulkAction', RemittanceAlert::class);

        $request->validate([
            'alert_ids' => 'required|array',
            'alert_ids.*' => 'exists:remittance_alerts,id',
            'action' => 'required|in:mark_read,resolve,dismiss',
        ]);

        $alertIds = $request->alert_ids;
        $action = $request->action;

        switch ($action) {
            case 'mark_read':
                RemittanceAlert::whereIn('id', $alertIds)->update(['is_read' => true]);
                $message = 'Selected alerts marked as read.';
                break;

            case 'resolve':
                foreach ($alertIds as $id) {
                    $alert = RemittanceAlert::find($id);
                    if ($alert) {
                        $alert->resolve(Auth::id(), 'Bulk resolved');
                    }
                }
                $message = 'Selected alerts resolved.';
                break;

            case 'dismiss':
                foreach ($alertIds as $id) {
                    $alert = RemittanceAlert::find($id);
                    if ($alert) {
                        $alert->resolve(Auth::id(), 'Bulk dismissed');
                    }
                }
                $message = 'Selected alerts dismissed.';
                break;

            default:
                return back()->with('error', 'Invalid action.');
        }

        return back()->with('success', $message);
    }
}
