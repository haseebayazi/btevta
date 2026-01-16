<?php
// ============================================
// File: app/Http/Controllers/CorrespondenceController.php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\Campus;
use App\Models\Oep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CorrespondenceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::with(['campus', 'oep'])->latest();

        // AUDIT FIX: Apply campus/OEP filtering for non-admin users
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }
        }

        if ($request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $correspondences = $query->paginate(20);

        return view('correspondence.index', compact('correspondences'));
    }

    public function create()
    {
        $this->authorize('create', Correspondence::class);

        // PERFORMANCE: Use cached dropdown data
        $campuses = Cache::remember('active_campuses', 86400, function () {
            return Campus::where('is_active', true)->select('id', 'name')->get();
        });

        $oeps = Cache::remember('active_oeps', 86400, function () {
            return Oep::where('is_active', true)->select('id', 'name', 'code')->get();
        });

        return view('correspondence.create', compact('campuses', 'oeps'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Correspondence::class);

        $validated = $request->validate([
            'reference_number' => 'required|unique:correspondences,reference_number',
            'date' => 'required|date',
            'subject' => 'required|string|max:500',
            'type' => 'required|in:incoming,outgoing',
            'sender' => 'required|string|max:255',
            'recipient' => 'required|string|max:255',
            'organization_type' => 'required|in:btevta,oep,embassy,campus,government,other',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'summary' => 'nullable|string',
            'file' => 'required|file|max:10240|mimes:pdf',
            'requires_reply' => 'boolean',
            'reply_deadline' => 'nullable|date|after:date',
        ]);

        try {
            $validated['file_path'] = $request->file('file')->store('correspondence', 'public');

            $correspondence = Correspondence::create($validated);

            activity()
                ->performedOn($correspondence)
                ->causedBy(auth()->user())
                ->log('Correspondence recorded');

            return redirect()->route('correspondence.index')
                ->with('success', 'Correspondence recorded successfully!');
        } catch (\Exception $e) {
            // SECURITY: Log exception details, show generic message to user
            \Log::error('Correspondence creation failed', ['error' => $e->getMessage(), 'user_id' => auth()->id()]);
            return back()->withInput()->with('error', 'Failed to record correspondence. Please try again or contact support.');
        }
    }

    public function show(Correspondence $correspondence)
    {
        $this->authorize('view', $correspondence);

        return view('correspondence.show', compact('correspondence'));
    }

    public function pendingReply()
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::where('requires_reply', true)
            ->where('replied', false);

        // AUDIT FIX: Apply campus/OEP filtering for non-admin users
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }
        }

        $correspondences = $query->latest()->paginate(20);

        return view('correspondence.pending-reply', compact('correspondences'));
    }

    public function markReplied(Request $request, Correspondence $correspondence)
    {
        $this->authorize('update', $correspondence);

        // FIXED: Added validation
        $request->validate([
            'reply_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $correspondence->replied = true;
            $correspondence->replied_at = now();
            if ($request->filled('reply_notes')) {
                $correspondence->reply_notes = $request->reply_notes;
            }
            $correspondence->save();

            activity()
                ->performedOn($correspondence)
                ->causedBy(auth()->user())
                ->log('Correspondence marked as replied');

            return back()->with('success', 'Marked as replied!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark as replied: ' . $e->getMessage());
        }
    }

    public function register()
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::with(['campus', 'oep']);

        // AUDIT FIX: Apply campus/OEP filtering for non-admin users
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }
        }

        // FIXED: Changed from get() to paginate() to prevent loading all records
        $correspondences = $query->latest()->paginate(50); // Show 50 records per page for register view

        return view('correspondence.register', compact('correspondences'));
    }

    /**
     * Communication summary report with outgoing/incoming ratio
     */
    public function summary(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'organization_type' => 'nullable|string',
            'campus_id' => 'nullable|exists:campuses,id',
        ]);

        try {
            $query = Correspondence::query();

            // Apply date filters
            if (!empty($validated['start_date'])) {
                $query->whereDate('date', '>=', $validated['start_date']);
            }
            if (!empty($validated['end_date'])) {
                $query->whereDate('date', '<=', $validated['end_date']);
            }
            if (!empty($validated['organization_type'])) {
                $query->where('organization_type', $validated['organization_type']);
            }
            if (!empty($validated['campus_id'])) {
                $query->where('campus_id', $validated['campus_id']);
            }

            // AUDIT FIX: Apply campus/OEP filtering for non-admin users
            $user = Auth::user();
            if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
                if ($user->isCampusAdmin() && $user->campus_id) {
                    $query->where('campus_id', $user->campus_id);
                } elseif ($user->isOep() && $user->oep_id) {
                    $query->where('oep_id', $user->oep_id);
                }
            }

            // Calculate summary statistics
            $incoming = (clone $query)->where('type', 'incoming')->count();
            $outgoing = (clone $query)->where('type', 'outgoing')->count();
            $total = $incoming + $outgoing;

            // By organization type
            $byOrganization = (clone $query)
                ->selectRaw('organization_type, type, count(*) as count')
                ->groupBy('organization_type', 'type')
                ->get()
                ->groupBy('organization_type');

            // By month (last 12 months)
            $byMonth = Correspondence::selectRaw('DATE_FORMAT(date, "%Y-%m") as month, type, count(*) as count')
                ->where('date', '>=', now()->subMonths(12))
                ->when(auth()->user()->role === 'campus_admin', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                })
                ->groupBy('month', 'type')
                ->orderBy('month')
                ->get()
                ->groupBy('month');

            // Pending replies
            $pendingReplies = Correspondence::where('requires_reply', true)
                ->where('replied', false)
                ->when(auth()->user()->role === 'campus_admin', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                })
                ->count();

            // Overdue replies (past deadline)
            $overdueReplies = Correspondence::where('requires_reply', true)
                ->where('replied', false)
                ->whereNotNull('reply_deadline')
                ->where('reply_deadline', '<', now())
                ->when(auth()->user()->role === 'campus_admin', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                })
                ->count();

            // Average response time (in days)
            $avgResponseTime = Correspondence::whereNotNull('replied_at')
                ->selectRaw('AVG(DATEDIFF(replied_at, created_at)) as avg_days')
                ->when(auth()->user()->role === 'campus_admin', function($q) {
                    $q->where('campus_id', auth()->user()->campus_id);
                })
                ->value('avg_days');

            $summary = [
                'total' => $total,
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'ratio' => $outgoing > 0 ? round($incoming / $outgoing, 2) : ($incoming > 0 ? 'All Incoming' : 'N/A'),
                'by_organization' => $byOrganization,
                'by_month' => $byMonth,
                'pending_replies' => $pendingReplies,
                'overdue_replies' => $overdueReplies,
                'avg_response_time' => $avgResponseTime ? round($avgResponseTime, 1) : 0,
            ];

            $campuses = Cache::remember('active_campuses', 86400, function () {
                return Campus::where('is_active', true)->select('id', 'name')->get();
            });

            $organizationTypes = [
                'btevta' => 'BTEVTA',
                'oep' => 'OEP',
                'embassy' => 'Embassy',
                'campus' => 'Campus',
                'government' => 'Government',
                'other' => 'Other',
            ];

            return view('correspondence.reports.summary', compact('summary', 'campuses', 'organizationTypes', 'validated'));
        } catch (\Exception $e) {
            \Log::error('Correspondence summary failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to generate summary report. Please try again.');
        }
    }

    /**
     * AUDIT FIX: Added missing CRUD methods for Route::resource()
     */

    /**
     * Show the form for editing a correspondence.
     */
    public function edit(Correspondence $correspondence)
    {
        $campuses = Cache::remember('active_campuses', 86400, function () {
            return Campus::where('is_active', true)->select('id', 'name')->get();
        });

        $candidates = Candidate::select('id', 'name', 'btevta_id')->orderBy('name')->get();

        return view('correspondence.edit', compact('correspondence', 'campuses', 'candidates'));
    }

    /**
     * Update the specified correspondence.
     */
    public function update(Request $request, Correspondence $correspondence)
    {
        $validated = $request->validate([
            'file_reference_number' => 'required|string|max:100',
            'correspondence_type' => 'required|in:incoming,outgoing',
            'sender' => 'required|string|max:255',
            'recipient' => 'required|string|max:255',
            'subject' => 'required|string|max:500',
            'description' => 'nullable|string|max:5000',
            'correspondence_date' => 'required|date',
            'priority_level' => 'nullable|in:low,normal,high,urgent',
            'candidate_id' => 'nullable|exists:candidates,id',
            'requires_reply' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        $correspondence->update($validated);

        activity()
            ->performedOn($correspondence)
            ->causedBy(auth()->user())
            ->log('Correspondence updated');

        return redirect()->route('correspondence.show', $correspondence)
            ->with('success', 'Correspondence updated successfully!');
    }

    /**
     * Remove the specified correspondence (soft delete).
     */
    public function destroy(Correspondence $correspondence)
    {
        $correspondence->delete();

        activity()
            ->performedOn($correspondence)
            ->causedBy(auth()->user())
            ->log('Correspondence deleted');

        return redirect()->route('correspondence.index')
            ->with('success', 'Correspondence deleted successfully!');
    }

    /**
     * Full-text search across correspondence records
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = $request->get('q');

        if (empty($query)) {
            return redirect()->route('correspondence.index')
                ->with('info', 'Please enter a search query.');
        }

        // Escape special LIKE characters to prevent SQL injection
        $escapedQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);

        // Build search query
        $results = Correspondence::where(function($q) use ($escapedQuery) {
            $q->where('subject', 'LIKE', "%{$escapedQuery}%")
              ->orWhere('reference_number', 'LIKE', "%{$escapedQuery}%")
              ->orWhere('sender', 'LIKE', "%{$escapedQuery}%")
              ->orWhere('recipient', 'LIKE', "%{$escapedQuery}%")
              ->orWhere('content', 'LIKE', "%{$escapedQuery}%")
              ->orWhere('notes', 'LIKE', "%{$escapedQuery}%");
        });

        // Apply campus/OEP filtering for non-admin users
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $results->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $results->where('oep_id', $user->oep_id);
            }
        }

        $results = $results->with(['campus', 'oep', 'creator'])
            ->latest()
            ->paginate(20);

        // Highlight search terms in results (optional, for frontend use)
        $highlightedQuery = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');

        return view('correspondence.search-results', compact('results', 'query', 'highlightedQuery'));
    }

    /**
     * Pendency analytics dashboard
     */
    public function pendencyReport()
    {
        $this->authorize('viewAny', Correspondence::class);

        // Base query
        $baseQuery = Correspondence::query();

        // Apply campus/OEP filtering for non-admin users
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector() && !$user->isViewer()) {
            if ($user->isCampusAdmin() && $user->campus_id) {
                $baseQuery->where('campus_id', $user->campus_id);
            } elseif ($user->isOep() && $user->oep_id) {
                $baseQuery->where('oep_id', $user->oep_id);
            }
        }

        // Basic statistics
        $stats = [
            'total_correspondence' => (clone $baseQuery)->count(),
            'total_pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'total_replied' => (clone $baseQuery)->where('status', 'replied')->count(),
            'total_closed' => (clone $baseQuery)->where('status', 'closed')->count(),
        ];

        // Pending by type
        $stats['pending_by_type'] = (clone $baseQuery)
            ->where('status', 'pending')
            ->select('type', \DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        // Average response time (for replied correspondence)
        $avgResponseTime = (clone $baseQuery)
            ->whereNotNull('response_date')
            ->select(\DB::raw('AVG(DATEDIFF(response_date, created_at)) as avg_days'))
            ->value('avg_days');

        $stats['avg_response_time_days'] = $avgResponseTime ? round($avgResponseTime, 1) : 0;

        // Overdue correspondence (pending beyond due date)
        $stats['overdue'] = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        // Overdue list (detailed)
        $stats['overdue_list'] = (clone $baseQuery)
            ->where('status', 'pending')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with(['campus', 'oep', 'creator'])
            ->orderBy('due_date', 'asc')
            ->limit(20)
            ->get()
            ->map(function($correspondence) {
                $daysPastDue = now()->diffInDays($correspondence->due_date);
                return [
                    'correspondence' => $correspondence,
                    'days_past_due' => $daysPastDue,
                    'severity' => $daysPastDue > 14 ? 'critical' : ($daysPastDue > 7 ? 'high' : 'moderate'),
                ];
            });

        // By organization type
        $stats['by_org_type'] = (clone $baseQuery)
            ->select('organization_type', \DB::raw('count(*) as count'))
            ->groupBy('organization_type')
            ->get()
            ->pluck('count', 'organization_type');

        // Monthly trend (last 6 months)
        $monthlyTrend = (clone $baseQuery)
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                \DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                \DB::raw('count(*) as total'),
                \DB::raw('SUM(CASE WHEN status = "replied" THEN 1 ELSE 0 END) as replied'),
                \DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $stats['monthly_trend'] = $monthlyTrend;

        // Response rate (percentage of correspondence with responses)
        $totalWithDueDate = (clone $baseQuery)->whereNotNull('due_date')->count();
        if ($totalWithDueDate > 0) {
            $respondedOnTime = (clone $baseQuery)
                ->whereNotNull('due_date')
                ->whereNotNull('response_date')
                ->whereRaw('response_date <= due_date')
                ->count();

            $stats['on_time_response_rate'] = round(($respondedOnTime / $totalWithDueDate) * 100, 1);
        } else {
            $stats['on_time_response_rate'] = 0;
        }

        // Campus breakdown (for admins only)
        if ($user->role === 'admin' || $user->role === 'project_director') {
            $stats['by_campus'] = Correspondence::join('campuses', 'correspondences.campus_id', '=', 'campuses.id')
                ->select('campuses.name as campus_name', \DB::raw('count(*) as count'))
                ->groupBy('campuses.id', 'campuses.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
        }

        // OEP breakdown (for admins only)
        if ($user->role === 'admin' || $user->role === 'project_director') {
            $stats['by_oep'] = Correspondence::join('oeps', 'correspondences.oep_id', '=', 'oeps.id')
                ->select('oeps.name as oep_name', \DB::raw('count(*) as count'))
                ->groupBy('oeps.id', 'oeps.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get();
        }

        // Recent correspondence activity
        $stats['recent_activity'] = (clone $baseQuery)
            ->with(['campus', 'oep', 'creator'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('correspondence.pendency-report', compact('stats'));
    }
}