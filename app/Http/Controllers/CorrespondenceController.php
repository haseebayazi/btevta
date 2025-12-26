<?php
// ============================================
// File: app/Http/Controllers/CorrespondenceController.php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\Campus;
use App\Models\Oep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CorrespondenceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $query = Correspondence::with(['campus', 'oep'])->latest();

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

        $correspondences = Correspondence::where('requires_reply', true)
            ->where('replied', false)
            ->latest()
            ->paginate(20);

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

        // FIXED: Changed from get() to paginate() to prevent loading all records
        $correspondences = Correspondence::with(['campus', 'oep'])
            ->latest()
            ->paginate(50); // Show 50 records per page for register view

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

            // Filter by campus for campus admins
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
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
}