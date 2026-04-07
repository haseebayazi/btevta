<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Correspondence;
use App\Models\Oep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class CorrespondenceController extends Controller
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $user  = Auth::user();
        $query = Correspondence::with(['campus', 'oep', 'createdBy'])->latest();

        $this->applyUserScope($query, $user);

        if ($request->filled('organization_type')) {
            $query->where('organization_type', $request->organization_type);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $correspondences = $query->paginate(20);

        return view('correspondence.index', compact('correspondences'));
    }

    // ─── Create / Store ───────────────────────────────────────────────────────

    public function create()
    {
        $this->authorize('create', Correspondence::class);

        $campuses = Cache::remember('active_campuses', 86400, fn () =>
            Campus::where('is_active', true)->select('id', 'name')->get()
        );

        $oeps = Cache::remember('active_oeps', 86400, fn () =>
            Oep::where('is_active', true)->select('id', 'name', 'code')->get()
        );

        $types             = Correspondence::getDirectionTypes();
        $organizationTypes = Correspondence::getOrganizationTypes();
        $priorities        = Correspondence::getPriorities();

        return view('correspondence.create', compact('campuses', 'oeps', 'types', 'organizationTypes', 'priorities'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Correspondence::class);

        $validated = $request->validate([
            'type'              => 'required|in:incoming,outgoing',
            'organization_type' => 'required|in:btevta,oep,embassy,campus,government,private,ngo,internal,other',
            'subject'           => 'required|string|max:500',
            'sender'            => 'required|string|max:255',
            'recipient'         => 'required|string|max:255',
            'sent_at'           => 'required|date',
            'campus_id'         => 'nullable|exists:campuses,id',
            'oep_id'            => 'nullable|exists:oeps,id',
            'candidate_id'      => 'nullable|exists:candidates,id',
            'message'           => 'nullable|string',
            'description'       => 'nullable|string',
            'priority_level'    => 'nullable|in:low,normal,high,urgent',
            'requires_reply'    => 'boolean',
            'due_date'          => 'nullable|date|after:sent_at',
            'file'              => 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,png',
        ]);

        try {
            if ($request->hasFile('file')) {
                $validated['attachment_path'] = $request->file('file')
                    ->store('correspondence', 'private');
            }

            $correspondence = Correspondence::create($validated);

            activity()
                ->performedOn($correspondence)
                ->causedBy(auth()->user())
                ->log('Correspondence recorded');

            return redirect()->route('correspondence.show', $correspondence)
                ->with('success', 'Correspondence recorded successfully.');
        } catch (\Exception $e) {
            \Log::error('Correspondence creation failed', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to record correspondence. Please try again or contact support.');
        }
    }

    // ─── Show ─────────────────────────────────────────────────────────────────

    public function show(Correspondence $correspondence)
    {
        $this->authorize('view', $correspondence);

        $correspondence->load(['campus', 'oep', 'candidate', 'createdBy', 'assignee']);

        return view('correspondence.show', compact('correspondence'));
    }

    // ─── Edit / Update ────────────────────────────────────────────────────────

    public function edit(Correspondence $correspondence)
    {
        $this->authorize('update', $correspondence);

        $campuses = Cache::remember('active_campuses', 86400, fn () =>
            Campus::where('is_active', true)->select('id', 'name')->get()
        );

        $oeps = Cache::remember('active_oeps', 86400, fn () =>
            Oep::where('is_active', true)->select('id', 'name', 'code')->get()
        );

        $candidates        = Candidate::select('id', 'name', 'btevta_id')->orderBy('name')->get();
        $types             = Correspondence::getDirectionTypes();
        $organizationTypes = Correspondence::getOrganizationTypes();
        $priorities        = Correspondence::getPriorities();

        return view('correspondence.edit', compact(
            'correspondence', 'campuses', 'oeps', 'candidates',
            'types', 'organizationTypes', 'priorities'
        ));
    }

    public function update(Request $request, Correspondence $correspondence)
    {
        $this->authorize('update', $correspondence);

        $validated = $request->validate([
            'type'              => 'required|in:incoming,outgoing',
            'organization_type' => 'nullable|in:btevta,oep,embassy,campus,government,private,ngo,internal,other',
            'subject'           => 'required|string|max:500',
            'sender'            => 'required|string|max:255',
            'recipient'         => 'required|string|max:255',
            'sent_at'           => 'required|date',
            'campus_id'         => 'nullable|exists:campuses,id',
            'oep_id'            => 'nullable|exists:oeps,id',
            'candidate_id'      => 'nullable|exists:candidates,id',
            'message'           => 'nullable|string',
            'description'       => 'nullable|string|max:5000',
            'priority_level'    => 'nullable|in:low,normal,high,urgent',
            'requires_reply'    => 'boolean',
            'due_date'          => 'nullable|date',
            'status'            => 'nullable|in:pending,in_progress,replied,closed',
        ]);

        $correspondence->update($validated);

        activity()
            ->performedOn($correspondence)
            ->causedBy(auth()->user())
            ->log('Correspondence updated');

        return redirect()->route('correspondence.show', $correspondence)
            ->with('success', 'Correspondence updated successfully.');
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function destroy(Correspondence $correspondence)
    {
        $this->authorize('delete', $correspondence);

        $correspondence->delete();

        activity()
            ->performedOn($correspondence)
            ->causedBy(auth()->user())
            ->log('Correspondence deleted');

        return redirect()->route('correspondence.index')
            ->with('success', 'Correspondence deleted successfully.');
    }

    // ─── Pending Reply ────────────────────────────────────────────────────────

    public function pendingReply()
    {
        $this->authorize('viewAny', Correspondence::class);

        $user  = Auth::user();
        $query = Correspondence::pendingReply()->with(['campus', 'oep']);

        $this->applyUserScope($query, $user);

        $correspondences = $query->latest()->paginate(20);

        return view('correspondence.pending-reply', compact('correspondences'));
    }

    public function markReplied(Request $request, Correspondence $correspondence)
    {
        $this->authorize('update', $correspondence);

        $request->validate([
            'reply_notes' => 'nullable|string|max:2000',
        ]);

        try {
            $correspondence->update([
                'replied'    => true,
                'replied_at' => now(),
                'status'     => Correspondence::STATUS_REPLIED,
                'notes'      => $request->filled('reply_notes')
                    ? $request->reply_notes
                    : $correspondence->notes,
            ]);

            activity()
                ->performedOn($correspondence)
                ->causedBy(auth()->user())
                ->log('Correspondence marked as replied');

            return back()->with('success', 'Marked as replied.');
        } catch (\Exception $e) {
            \Log::error('markReplied failed', ['error' => $e->getMessage(), 'id' => $correspondence->id]);

            return back()->with('error', 'Failed to mark as replied. Please try again.');
        }
    }

    // ─── Register ─────────────────────────────────────────────────────────────

    public function register()
    {
        $this->authorize('viewAny', Correspondence::class);

        $user  = Auth::user();
        $query = Correspondence::with(['campus', 'oep', 'createdBy']);

        $this->applyUserScope($query, $user);

        $correspondences = $query->latest()->paginate(50);

        return view('correspondence.register', compact('correspondences'));
    }

    // ─── Summary Report ───────────────────────────────────────────────────────

    public function summary(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $validated = $request->validate([
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'organization_type' => 'nullable|string',
            'campus_id'         => 'nullable|exists:campuses,id',
        ]);

        try {
            $user  = Auth::user();
            $query = Correspondence::query();

            if (!empty($validated['start_date'])) {
                $query->whereDate('sent_at', '>=', $validated['start_date']);
            }
            if (!empty($validated['end_date'])) {
                $query->whereDate('sent_at', '<=', $validated['end_date']);
            }
            if (!empty($validated['organization_type'])) {
                $query->where('organization_type', $validated['organization_type']);
            }
            if (!empty($validated['campus_id'])) {
                $query->where('campus_id', $validated['campus_id']);
            }

            $this->applyUserScope($query, $user);

            $incoming = (clone $query)->where('type', 'incoming')->count();
            $outgoing = (clone $query)->where('type', 'outgoing')->count();
            $total    = $incoming + $outgoing;

            $byOrganization = (clone $query)
                ->selectRaw('organization_type, type, count(*) as count')
                ->groupBy('organization_type', 'type')
                ->get()
                ->groupBy('organization_type');

            $byMonth = (clone $query)
                ->selectRaw('DATE_FORMAT(sent_at, "%Y-%m") as month, type, count(*) as count')
                ->where('sent_at', '>=', now()->subMonths(12))
                ->groupBy('month', 'type')
                ->orderBy('month')
                ->get()
                ->groupBy('month');

            $pendingReplies = (clone $query)
                ->where('requires_reply', true)
                ->where('replied', false)
                ->count();

            $overdueReplies = (clone $query)
                ->where('requires_reply', true)
                ->where('replied', false)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count();

            $avgResponseTime = (clone $query)
                ->whereNotNull('replied_at')
                ->selectRaw('AVG(' . $this->dateDiffDays('replied_at', 'sent_at') . ') as avg_days')
                ->value('avg_days');

            $summary = [
                'total'            => $total,
                'incoming'         => $incoming,
                'outgoing'         => $outgoing,
                'ratio'            => $outgoing > 0
                    ? round($incoming / $outgoing, 2)
                    : ($incoming > 0 ? 'All Incoming' : 'N/A'),
                'by_organization'  => $byOrganization,
                'by_month'         => $byMonth,
                'pending_replies'  => $pendingReplies,
                'overdue_replies'  => $overdueReplies,
                'avg_response_time' => $avgResponseTime ? round($avgResponseTime, 1) : 0,
            ];

            $campuses = Cache::remember('active_campuses', 86400, fn () =>
                Campus::where('is_active', true)->select('id', 'name')->get()
            );

            $organizationTypes = Correspondence::getOrganizationTypes();

            return view('correspondence.reports.summary', compact(
                'summary', 'campuses', 'organizationTypes', 'validated'
            ));
        } catch (\Exception $e) {
            \Log::error('Correspondence summary failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to generate summary report. Please try again.');
        }
    }

    // ─── Search ───────────────────────────────────────────────────────────────

    public function search(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $rawQuery = $request->get('q', '');

        if (empty(trim($rawQuery))) {
            return redirect()->route('correspondence.index')
                ->with('info', 'Please enter a search query.');
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $rawQuery);
        $user    = Auth::user();

        $results = Correspondence::where(function ($q) use ($escaped) {
            $q->where('subject', 'LIKE', "%{$escaped}%")
              ->orWhere('file_reference_number', 'LIKE', "%{$escaped}%")
              ->orWhere('sender', 'LIKE', "%{$escaped}%")
              ->orWhere('recipient', 'LIKE', "%{$escaped}%")
              ->orWhere('message', 'LIKE', "%{$escaped}%")
              ->orWhere('notes', 'LIKE', "%{$escaped}%")
              ->orWhere('description', 'LIKE', "%{$escaped}%");
        });

        $this->applyUserScope($results, $user);

        $results = $results->with(['campus', 'oep', 'createdBy'])
            ->latest()
            ->paginate(20);

        $query            = $rawQuery;
        $highlightedQuery = htmlspecialchars($rawQuery, ENT_QUOTES, 'UTF-8');

        return view('correspondence.search-results', compact('results', 'query', 'highlightedQuery'));
    }

    // ─── Pendency Report ──────────────────────────────────────────────────────

    public function pendencyReport()
    {
        $this->authorize('viewAny', Correspondence::class);

        $user      = Auth::user();
        $baseQuery = Correspondence::query();

        $this->applyUserScope($baseQuery, $user);

        $stats = [
            'total_correspondence' => (clone $baseQuery)->count(),
            'total_pending'        => (clone $baseQuery)->where('status', Correspondence::STATUS_PENDING)->count(),
            'total_replied'        => (clone $baseQuery)->where('status', Correspondence::STATUS_REPLIED)->count(),
            'total_closed'         => (clone $baseQuery)->where('status', Correspondence::STATUS_CLOSED)->count(),
        ];

        $stats['pending_by_type'] = (clone $baseQuery)
            ->where('status', Correspondence::STATUS_PENDING)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        $avgResponseTime = (clone $baseQuery)
            ->whereNotNull('replied_at')
            ->selectRaw('AVG(' . $this->dateDiffDays('replied_at', 'sent_at') . ') as avg_days')
            ->value('avg_days');

        $stats['avg_response_time_days'] = $avgResponseTime ? round($avgResponseTime, 1) : 0;

        $stats['overdue'] = (clone $baseQuery)->overdue()->count();

        $stats['overdue_list'] = (clone $baseQuery)
            ->overdue()
            ->with(['campus', 'oep', 'createdBy'])
            ->orderBy('due_date')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                $daysPastDue = now()->diffInDays($c->due_date);

                return [
                    'correspondence' => $c,
                    'days_past_due'  => $daysPastDue,
                    'severity'       => $daysPastDue > 14 ? 'critical' : ($daysPastDue > 7 ? 'high' : 'moderate'),
                ];
            });

        $stats['by_org_type'] = (clone $baseQuery)
            ->selectRaw('organization_type, count(*) as count')
            ->groupBy('organization_type')
            ->get()
            ->pluck('count', 'organization_type');

        // Monthly trend (last 6 months)
        $stats['monthly_trend'] = (clone $baseQuery)
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw(
                'DATE_FORMAT(created_at, "%Y-%m") as month,' .
                'count(*) as total,' .
                'SUM(CASE WHEN status = "replied" THEN 1 ELSE 0 END) as replied,' .
                'SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending'
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // On-time response rate
        $totalWithDueDate = (clone $baseQuery)->whereNotNull('due_date')->count();

        if ($totalWithDueDate > 0) {
            $respondedOnTime = (clone $baseQuery)
                ->whereNotNull('due_date')
                ->whereNotNull('replied_at')
                ->whereRaw('replied_at <= due_date')
                ->count();

            $stats['on_time_response_rate'] = round(($respondedOnTime / $totalWithDueDate) * 100, 1);
        } else {
            $stats['on_time_response_rate'] = 0;
        }

        // Campus / OEP breakdowns (admins only)
        if (in_array($user->role, ['admin', 'super_admin', 'project_director'])) {
            $stats['by_campus'] = Correspondence::join('campuses', 'correspondences.campus_id', '=', 'campuses.id')
                ->selectRaw('campuses.name as campus_name, count(*) as count')
                ->groupBy('campuses.id', 'campuses.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $stats['by_oep'] = Correspondence::join('oeps', 'correspondences.oep_id', '=', 'oeps.id')
                ->selectRaw('oeps.name as oep_name, count(*) as count')
                ->groupBy('oeps.id', 'oeps.name')
                ->orderByDesc('count')
                ->limit(10)
                ->get();
        }

        $stats['recent_activity'] = (clone $baseQuery)
            ->with(['campus', 'oep', 'createdBy'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('correspondence.pendency-report', compact('stats'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Return a SQL fragment for computing the difference in days between two
     * datetime columns, compatible with both MySQL (DATEDIFF) and SQLite
     * (julianday arithmetic).
     */
    private function dateDiffDays(string $col1, string $col2): string
    {
        if (\DB::connection()->getDriverName() === 'sqlite') {
            return "CAST(julianday({$col1}) - julianday({$col2}) AS INTEGER)";
        }

        return "DATEDIFF({$col1}, {$col2})";
    }

    /**
     * Constrain a query to records visible to the authenticated user based on
     * their role (campus admin sees only their campus; OEP sees only their OEP).
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
