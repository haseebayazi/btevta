<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\Country;
use App\Http\Requests\InitialScreeningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScreeningController extends Controller
{
    /**
     * Display a listing of screenings.
     *
     * @deprecated WASL v3: This method is part of the legacy 3-call screening system.
     *             Use initialScreeningDashboard() for the new Module 2 Initial Screening workflow.
     * @see \App\Http\Controllers\ScreeningController::initialScreeningDashboard()
     */
    public function index(Request $request)
    {
        // WASL v3: Redirect to Module 2 Initial Screening Dashboard
        return redirect()->route('screening.initial-dashboard')
            ->with('info', 'The legacy screening list has been replaced with Initial Screening. Please use the Initial Screening dashboard.');
    }

    /**
     * Display pending screenings.
     *
     * @deprecated WASL v3: This method is part of the legacy 3-call screening system.
     *             Use initialScreeningDashboard() for the new Module 2 Initial Screening workflow.
     * @see \App\Http\Controllers\ScreeningController::initialScreeningDashboard()
     */
    public function pending()
    {
        // WASL v3: Redirect to Module 2 Initial Screening Dashboard
        return redirect()->route('screening.initial-dashboard')
            ->with('info', 'Pending screenings are now managed in Initial Screening. Please use the Initial Screening dashboard.');
    }

    /**
     * Show the form for creating a new screening.
     *
     * @deprecated WASL v3: This method is part of the legacy 3-call screening system.
     *             Use initialScreening() for the new Module 2 Initial Screening workflow.
     * @see \App\Http\Controllers\ScreeningController::initialScreening()
     */
    public function create()
    {
        // WASL v3: Redirect to Module 2 Initial Screening Dashboard
        // Users should select a candidate from the dashboard to start Initial Screening
        return redirect()->route('screening.initial-dashboard')
            ->with('info', 'To screen a candidate, please select them from the Initial Screening dashboard and click "Screen".');
    }

    /**
     * Store a newly created screening in storage.
     *
     * @deprecated WASL v3: This method is part of the legacy 3-call screening system.
     *             Use storeInitialScreening() for the new Module 2 Initial Screening workflow.
     * @see \App\Http\Controllers\ScreeningController::storeInitialScreening()
     */
    public function store(Request $request)
    {
        // WASL v3: Redirect to Module 2 Initial Screening Dashboard
        // Legacy screening creation is no longer supported
        return redirect()->route('screening.initial-dashboard')
            ->with('warning', 'The legacy screening system has been deprecated. Please use Initial Screening instead.');
    }

    public function edit($candidateId)
    {
        try {
            $candidate = Candidate::with('screenings')->findOrFail($candidateId);
            $screening = $candidate->screenings()->latest()->first();

            if (!$screening) {
                // No existing screening, create a new one for this candidate
                return redirect()->route('screening.create', ['candidate_id' => $candidate->id])
                    ->with('info', 'No existing screening record. Please create a new one.');
            }

            $this->authorize('update', $screening);

            return view('screening.edit', compact('candidate', 'screening'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load screening: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $candidateId)
    {
        try {
            $candidate = Candidate::findOrFail($candidateId);
            $screening = $candidate->screenings()->latest()->firstOrFail();

            $this->authorize('update', $screening);

            $validated = $request->validate([
                'screening_type' => 'required|string',
                'screened_at' => 'required|date',
                'call_duration' => 'nullable|integer|min:1',
                'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
                'remarks' => 'nullable|string',
                'evidence_path' => 'nullable|string',
            ]);

            $validated['updated_by'] = auth()->id();
            $screening->update($validated);

            activity()
                ->performedOn($screening)
                ->causedBy(auth()->user())
                ->log('Screening record updated');

            return redirect()->route('screening.index')->with('success', 'Screening record updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update screening: ' . $e->getMessage());
        }
    }

    /**
     * Log a call attempt for a candidate's call screening.
     *
     * @deprecated WASL v3: This method is part of the legacy 3-call screening system.
     *             Module 2 Initial Screening uses a single-review approach with storeInitialScreening().
     * @see \App\Http\Controllers\ScreeningController::storeInitialScreening()
     */
    public function logCall(Request $request, Candidate $candidate)
    {
        // WASL v3: Redirect to Module 2 Initial Screening
        // The legacy 3-call system has been replaced with single-review Initial Screening
        return redirect()->route('candidates.initial-screening', $candidate)
            ->with('warning', 'The legacy call logging system has been deprecated. Please use Initial Screening instead.');
    }

    /**
     * Record screening outcome for a candidate.
     *
     * @deprecated WASL v3: This method is part of the legacy 3-call screening system.
     *             Module 2 Initial Screening uses storeInitialScreening() with outcomes:
     *             'screened', 'pending', or 'deferred'.
     * @see \App\Http\Controllers\ScreeningController::storeInitialScreening()
     */
    public function recordOutcome(Request $request, Candidate $candidate)
    {
        // WASL v3: Redirect to Module 2 Initial Screening
        // The legacy outcome recording has been replaced with Initial Screening workflow
        return redirect()->route('candidates.initial-screening', $candidate)
            ->with('warning', 'The legacy screening outcome system has been deprecated. Please use Initial Screening instead.');
    }

    /**
     * Get screening progress for a candidate.
     * Returns the status of all required screenings (desk, call, physical).
     */
    public function progress(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $progress = CandidateScreening::getScreeningProgress($candidate);

        if (request()->wantsJson()) {
            return response()->json([
                'candidate' => [
                    'id' => $candidate->id,
                    'btevta_id' => $candidate->btevta_id,
                    'name' => $candidate->name,
                    'status' => $candidate->status,
                ],
                'progress' => $progress,
            ]);
        }

        return view('screening.progress', compact('candidate', 'progress'));
    }

    /**
     * Upload evidence file for a screening.
     */
    public function uploadEvidence(Request $request, Candidate $candidate)
    {
        $request->validate([
            'screening_type' => 'required|in:desk,call,physical,document,medical',
            'evidence' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        try {
            $screening = $candidate->screenings()
                ->where('screening_type', $request->screening_type)
                ->latest()
                ->first();

            if (!$screening) {
                return back()->with('error', 'No screening record found for this type. Please create a screening first.');
            }

            $this->authorize('update', $screening);

            $path = $screening->uploadEvidence($request->file('evidence'));

            return back()->with('success', 'Evidence uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload evidence: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', CandidateScreening::class);

        // FIXED: Added validation for export parameters
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,in_progress,passed,failed,deferred,cancelled',
        ]);

        try {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedSearch = $request->search ? str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search) : null;

            $query = CandidateScreening::with('candidate');

            // AUDIT FIX: Apply campus filtering for campus admin users
            $user = Auth::user();
            if ($user->isCampusAdmin() && $user->campus_id) {
                $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            }

            $screenings = $query->when($escapedSearch, function($q) use ($escapedSearch) {
                    $q->join('candidates', 'candidate_screenings.candidate_id', '=', 'candidates.id')
                      ->where(function($sq) use ($escapedSearch) {
                          $sq->where('candidates.name', 'like', '%'.$escapedSearch.'%')
                             ->orWhere('candidates.btevta_id', 'like', '%'.$escapedSearch.'%');
                      })
                      ->select('candidate_screenings.*');
                })
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->get();

            // Export logic - customize based on your export needs
            $filename = 'screening-records-' . now()->format('Y-m-d-His') . '.csv';
            $headers = array(
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            );

            $callback = function() use ($screenings) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Candidate ID', 'Name', 'Screening Type', 'Screening Date', 'Status', 'Duration (secs)', 'Remarks']);

                foreach ($screenings as $screening) {
                    fputcsv($file, [
                        $screening->candidate->btevta_id ?? 'N/A',
                        $screening->candidate->name ?? 'N/A',
                        $screening->screening_type ?? 'N/A',
                        $screening->screened_at ? $screening->screened_at->format('Y-m-d H:i:s') : 'N/A',
                        $screening->status ?? 'N/A',
                        $screening->call_duration ?? 'N/A',
                        $screening->remarks ?? '',
                    ]);
                }
                fclose($file);
            };

            activity()
                ->causedBy(auth()->user())
                ->log('Exported screening records');

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export screenings: ' . $e->getMessage());
        }
    }

    /**
     * AUDIT FIX: Added missing destroy method for Route::resource()
     * Remove the specified screening record (soft delete).
     */
    public function destroy(CandidateScreening $screening)
    {
        $screening->delete();

        activity()
            ->performedOn($screening)
            ->causedBy(auth()->user())
            ->log('Screening record deleted');

        return redirect()->route('screening.index')
            ->with('success', 'Screening record deleted successfully!');
    }

    /**
     * Screening dashboard with analytics and statistics.
     *
     * @deprecated WASL v3: This dashboard is part of the legacy 3-call screening system.
     *             Use initialScreeningDashboard() for the new Module 2 Initial Screening workflow.
     * @see \App\Http\Controllers\ScreeningController::initialScreeningDashboard()
     */
    public function dashboard()
    {
        // WASL v3: Redirect to Module 2 Initial Screening Dashboard
        return redirect()->route('screening.initial-dashboard')
            ->with('info', 'The legacy screening dashboard has been replaced with Initial Screening.');
    }

    /**
     * MODULE 2: Display Initial Screening form for a candidate
     */
    public function initialScreening(Candidate $candidate)
    {
        $this->authorize('create', CandidateScreening::class);

        // Check if candidate is in correct status (Module 1 statuses: new, listed, pre_departure_docs, or screening)
        // 'new' is included for backward compatibility with legacy workflow
        if (!in_array($candidate->status, ['new', 'listed', 'pre_departure_docs', 'screening'])) {
            return back()->with('error', 'Candidate must be in Module 1 (Listed or Pre-Departure Documents) before screening.');
        }

        // Verify all mandatory pre-departure documents are completed AND verified
        if (!$candidate->hasCompletedAndVerifiedPreDepartureDocuments()) {
            // Check if documents are uploaded but not verified
            if ($candidate->hasCompletedPreDepartureDocuments()) {
                return back()->with('error', 'All mandatory documents have been uploaded but are pending verification. Please verify documents before screening.');
            }
            // Documents are missing
            $missingDocs = $candidate->getMissingMandatoryDocuments();
            $missingNames = $missingDocs->pluck('name')->implode(', ');
            return back()->with('error', 'Candidate must have all mandatory documents uploaded and verified before screening. Missing: ' . $missingNames);
        }

        $countries = Country::destinations()->active()->orderBy('name')->get();
        $existingScreening = $candidate->screenings()
            ->where('screening_type', 'initial')
            ->latest()
            ->first();

        return view('screening.initial-screening', compact('candidate', 'countries', 'existingScreening'));
    }

    /**
     * MODULE 2: Store Initial Screening result
     */
    public function storeInitialScreening(InitialScreeningRequest $request, Candidate $candidate)
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Create or update screening record
            $screening = $candidate->screenings()->updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'screening_type' => 'initial'
                ],
                [
                    'consent_for_work' => $validated['consent_for_work'],
                    'placement_interest' => $validated['placement_interest'],
                    'target_country_id' => $validated['target_country_id'] ?? null,
                    'screening_status' => $validated['screening_status'],
                    'remarks' => $validated['notes'] ?? null,
                    'screened_by' => auth()->id(),
                    'screened_at' => now(),
                    'reviewer_id' => auth()->id(),
                    'reviewed_at' => now(),
                ]
            );

            // Handle evidence upload
            if ($request->hasFile('evidence')) {
                $screening->uploadEvidence($request->file('evidence'));
            }

            // Process outcome
            if ($validated['screening_status'] === 'screened') {
                $screening->markAsScreened($validated['notes'] ?? null);
                $message = 'Candidate screened successfully. Ready for Registration.';
            } elseif ($validated['screening_status'] === 'deferred') {
                $screening->markAsDeferred($validated['notes'] ?? 'Deferred');
                $message = 'Candidate screening deferred.';
            } else {
                $message = 'Screening saved as pending.';
            }

            DB::commit();

            return redirect()->route('candidates.show', $candidate)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to save screening: ' . $e->getMessage());
        }
    }

    /**
     * MODULE 2: Initial Screening Dashboard
     */
    public function initialScreeningDashboard()
    {
        $this->authorize('viewAny', CandidateScreening::class);

        $user = auth()->user();

        // Base query with campus filtering
        $baseQuery = Candidate::query();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $baseQuery->where('campus_id', $user->campus_id);
        }

        // Statistics - includes all Module 1 statuses (new, listed, pre_departure_docs, screening)
        $stats = [
            'pending' => (clone $baseQuery)->whereIn('status', ['new', 'listed', 'pre_departure_docs', 'screening'])->count(),
            'screened' => (clone $baseQuery)->where('status', 'screened')->count(),
            'deferred' => (clone $baseQuery)->where('status', 'deferred')->count(),
            'total_this_month' => CandidateScreening::whereMonth('reviewed_at', now()->month)
                ->whereNotNull('reviewed_at')
                ->when($user->isCampusAdmin() && $user->campus_id, function($q) use ($user) {
                    $q->whereHas('candidate', fn($cq) => $cq->where('campus_id', $user->campus_id));
                })
                ->count(),
        ];

        // Pending candidates for screening - show all candidates from Module 1 with document status
        // Module 1 includes: new, listed, pre_departure_docs statuses (candidates who have/are uploading documents)
        // 'new' is included for backward compatibility with legacy workflow
        $pendingCandidates = (clone $baseQuery)
            ->whereIn('status', ['new', 'listed', 'pre_departure_docs', 'screening'])
            ->with(['campus', 'trade', 'oep', 'preDepartureDocuments'])
            ->latest()
            ->get();

        // Add document completion status to each candidate
        $pendingCandidates = $pendingCandidates->map(function ($candidate) {
            $candidate->document_status = $candidate->getPreDepartureDocumentStatus();
            // Check if all mandatory documents are verified (ready for screening)
            $candidate->ready_for_screening = $candidate->hasCompletedAndVerifiedPreDepartureDocuments();
            return $candidate;
        });

        // Paginate the collection
        $page = request()->get('page', 1);
        $perPage = 20;
        $pendingCandidates = new \Illuminate\Pagination\LengthAwarePaginator(
            $pendingCandidates->forPage($page, $perPage),
            $pendingCandidates->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Recently screened
        $recentlyScreened = (clone $baseQuery)
            ->where('status', 'screened')
            ->with(['campus', 'trade'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('screening.initial-screening-dashboard', compact('stats', 'pendingCandidates', 'recentlyScreened'));
    }
}