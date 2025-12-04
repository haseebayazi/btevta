<?php

namespace App\Http\Controllers;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Display activity logs
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $query = Activity::with(['causer', 'subject'])
            ->latest();

        // Search by description
        if ($request->filled('search')) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function($q) use ($escapedSearch) {
                $q->where('description', 'like', "%{$escapedSearch}%")
                  ->orWhere('log_name', 'like', "%{$escapedSearch}%");
            });
        }

        // Filter by log name (type)
        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        // Filter by causer (user)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        // Filter by subject type
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $activities = $query->paginate(50);

        // Get filter options
        $logNames = Activity::select('log_name')
            ->distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name');

        $subjectTypes = Activity::select('subject_type')
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->map(function($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type)
                ];
            });

        $users = DB::table('users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('activity-logs.index', compact('activities', 'logNames', 'subjectTypes', 'users'));
    }

    /**
     * Show single activity log details
     */
    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);

        $activity->load(['causer', 'subject']);

        return view('activity-logs.show', compact('activity'));
    }

    /**
     * Get statistics
     */
    public function statistics(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $fromDate = $request->input('from_date', now()->subDays(30));
        $toDate = $request->input('to_date', now());

        $stats = [
            'total_activities' => Activity::whereBetween('created_at', [$fromDate, $toDate])->count(),

            'by_log_name' => Activity::whereBetween('created_at', [$fromDate, $toDate])
                ->select('log_name', DB::raw('count(*) as count'))
                ->groupBy('log_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),

            'by_user' => Activity::whereBetween('created_at', [$fromDate, $toDate])
                ->join('users', 'activity_log.causer_id', '=', 'users.id')
                ->where('activity_log.causer_type', 'App\\Models\\User')
                ->select('users.name', DB::raw('count(*) as count'))
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),

            'by_subject_type' => Activity::whereBetween('created_at', [$fromDate, $toDate])
                ->select('subject_type', DB::raw('count(*) as count'))
                ->groupBy('subject_type')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    $item->subject_type = class_basename($item->subject_type);
                    return $item;
                }),

            'recent_activities' => Activity::with(['causer'])
                ->latest()
                ->limit(10)
                ->get(),
        ];

        return view('activity-logs.statistics', compact('stats', 'fromDate', 'toDate'));
    }

    /**
     * Export activity logs
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $query = Activity::with(['causer', 'subject']);

        // Apply same filters as index
        if ($request->filled('search')) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where(function($q) use ($escapedSearch) {
                $q->where('description', 'like', "%{$escapedSearch}%")
                  ->orWhere('log_name', 'like', "%{$escapedSearch}%");
            });
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Create CSV
        $filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($query) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, ['ID', 'Log Name', 'Description', 'Causer', 'Subject Type', 'Subject ID', 'Created At']);

            // Use chunking to prevent memory exhaustion on large exports
            $query->chunk(1000, function($activities) use ($file) {
                foreach ($activities as $activity) {
                    fputcsv($file, [
                        $activity->id,
                        $activity->log_name,
                        $activity->description,
                        $activity->causer ? $activity->causer->name : 'System',
                        class_basename($activity->subject_type),
                        $activity->subject_id,
                        $activity->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete old activity logs
     */
    public function clean(Request $request)
    {
        $this->authorize('delete', Activity::class);

        $request->validate([
            'days' => 'required|integer|min:30|max:365'
        ]);

        $days = $request->input('days', 90);
        $date = now()->subDays($days);

        $deleted = Activity::where('created_at', '<', $date)->delete();

        return back()->with('success', "Deleted {$deleted} activity logs older than {$days} days.");
    }
}
