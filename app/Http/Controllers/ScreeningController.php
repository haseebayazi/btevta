<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateScreening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScreeningController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CandidateScreening::class);

        // FIXED: Optimized N+1 query by using join instead of nested whereHas
        $query = CandidateScreening::with('candidate');

        // AUDIT FIX: Apply campus filtering for campus admin users
        $user = Auth::user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
        }

        $screenings = $query->when($request->search, function($q) use ($request) {
                // Escape special LIKE characters to prevent SQL LIKE injection
                $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
                $q->join('candidates', 'candidate_screenings.candidate_id', '=', 'candidates.id')
                  ->where(function($sq) use ($escapedSearch) {
                      $sq->where('candidates.name', 'like', '%'.$escapedSearch.'%')
                         ->orWhere('candidates.btevta_id', 'like', '%'.$escapedSearch.'%');
                  })
                  ->select('candidate_screenings.*');
            })
            ->latest('candidate_screenings.created_at')
            ->paginate(15);

        return view('screening.index', compact('screenings'));
    }

    public function pending()
    {
        $this->authorize('viewAny', CandidateScreening::class);

        // AUDIT FIX: Apply campus filtering for campus admin users
        $query = Candidate::where('status', 'screening');

        $user = Auth::user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        // AUDIT FIX: Added pagination to prevent memory issues with large datasets
        $candidates = $query->withCount('screenings')
            ->having('screenings_count', '<', 3)
            ->latest()
            ->paginate(20);

        return view('screening.pending', compact('candidates'));
    }

    public function create()
    {
        $this->authorize('create', CandidateScreening::class);

        // AUDIT FIX: Apply campus filtering and limit dropdown results for performance
        $query = Candidate::whereIn('status', ['new', 'screening'])
            ->select('id', 'name', 'btevta_id');

        $user = Auth::user();
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $candidates = $query->orderBy('name')
            ->limit(200)
            ->get();

        return view('screening.create', compact('candidates'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'screening_type' => 'required|string',
            'screened_at' => 'required|date',
            'call_duration' => 'nullable|integer|min:1',
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string',
            'evidence_path' => 'nullable|string',
        ]);

        try {
            $validated['screened_by'] = auth()->id();
            $validated['created_by'] = auth()->id();

            CandidateScreening::create($validated);

            activity()
                ->causedBy(auth()->user())
                ->log('Screening record created');

            return redirect()->route('screening.index')->with('success', 'Screening record created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create screening record: ' . $e->getMessage());
        }
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
     * Updates existing call screening record instead of creating new ones.
     * Respects the max 3 attempts limit.
     */
    public function logCall(Request $request, Candidate $candidate)
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'screened_at' => 'required|date',
            'call_duration' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:1000',
            'answered' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Get or create call screening for this candidate
            $screening = $candidate->screenings()
                ->where('screening_type', CandidateScreening::TYPE_CALL)
                ->whereIn('status', [
                    CandidateScreening::STATUS_PENDING,
                    CandidateScreening::STATUS_IN_PROGRESS
                ])
                ->first();

            if (!$screening) {
                // Create new call screening if none exists
                $screening = CandidateScreening::create([
                    'candidate_id' => $candidate->id,
                    'screening_type' => CandidateScreening::TYPE_CALL,
                    'status' => CandidateScreening::STATUS_IN_PROGRESS,
                    'screened_by' => auth()->id(),
                    'screened_at' => $validated['screened_at'],
                    'call_count' => 0,
                ]);
            }

            // Check if max attempts reached
            if ($screening->max_calls_reached) {
                DB::rollBack();
                return back()->with('error', 'Maximum call attempts (3) already reached for this candidate.');
            }

            // Record the call attempt
            $answered = $validated['answered'] ?? false;
            $screening->recordCallAttempt(
                $validated['call_duration'],
                $answered,
                $validated['remarks'] ?? null
            );

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'call_number' => $screening->call_count,
                    'answered' => $answered,
                    'duration' => $validated['call_duration'],
                ])
                ->log('Call attempt logged for screening');

            DB::commit();

            $message = "Call #{$screening->call_count} logged successfully!";
            if ($screening->max_calls_reached && !$answered) {
                $message .= " Maximum attempts reached.";
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to log call: ' . $e->getMessage());
        }
    }

    /**
     * Record screening outcome for a candidate.
     * Uses the model's markAsPassed/markAsFailed methods to ensure proper
     * auto-progression logic (all 3 screenings must pass for REGISTERED status).
     */
    public function recordOutcome(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'screening_type' => 'nullable|in:desk,call,physical,document,medical',
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string|max:1000',
            'evidence' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        try {
            DB::beginTransaction();

            // Get the screening record for this type, or latest if not specified
            $screeningType = $validated['screening_type'] ?? null;

            if ($screeningType) {
                $screening = $candidate->screenings()
                    ->where('screening_type', $screeningType)
                    ->latest()
                    ->first();
            } else {
                $screening = $candidate->screenings()->latest()->first();
            }

            if ($screening) {
                $this->authorize('update', $screening);
            } else {
                $this->authorize('create', CandidateScreening::class);

                // Create new screening record if none exists
                $screeningType = $screeningType ?? CandidateScreening::TYPE_DESK;
                $screening = CandidateScreening::create([
                    'candidate_id' => $candidate->id,
                    'screening_type' => $screeningType,
                    'status' => CandidateScreening::STATUS_PENDING,
                    'screened_by' => auth()->id(),
                    'screened_at' => now(),
                ]);
            }

            // Handle evidence file upload with validation
            if ($request->hasFile('evidence')) {
                $screening->uploadEvidence($request->file('evidence'));
            }

            // Use the model's methods for proper auto-progression
            // These methods handle the status transitions correctly:
            // - markAsPassed() calls checkAndUpdateCandidateStatus() which checks if ALL 3 screenings passed
            // - markAsFailed() immediately rejects the candidate
            $remarks = $validated['remarks'] ?? null;
            $status = $validated['status'];

            if ($status === CandidateScreening::STATUS_PASSED) {
                $screening->markAsPassed($remarks);

                // Check if this was the final screening that triggered auto-progression
                $candidate->refresh();
                $message = 'Screening marked as passed.';
                if ($candidate->status === 'registered') {
                    $message .= ' All screenings complete - candidate moved to REGISTERED status.';
                } else {
                    // Show which screenings are still pending
                    $requiredTypes = [
                        CandidateScreening::TYPE_DESK,
                        CandidateScreening::TYPE_CALL,
                        CandidateScreening::TYPE_PHYSICAL
                    ];
                    $passedTypes = $candidate->screenings()
                        ->whereIn('screening_type', $requiredTypes)
                        ->where('status', CandidateScreening::STATUS_PASSED)
                        ->pluck('screening_type')
                        ->toArray();
                    $pendingTypes = array_diff($requiredTypes, $passedTypes);

                    if (!empty($pendingTypes)) {
                        $pendingLabels = array_map(function($type) {
                            return CandidateScreening::getScreeningTypes()[$type] ?? $type;
                        }, $pendingTypes);
                        $message .= ' Pending: ' . implode(', ', $pendingLabels);
                    }
                }
            } elseif ($status === CandidateScreening::STATUS_FAILED) {
                $screening->markAsFailed($remarks);
                $message = 'Screening failed - candidate has been REJECTED.';
            } elseif ($status === CandidateScreening::STATUS_DEFERRED) {
                $nextDate = $request->input('next_date', now()->addDays(7));
                $screening->defer($nextDate, $remarks);
                $message = 'Screening deferred.';
            } else {
                // For other statuses (pending, in_progress, cancelled), just update the record
                $screening->status = $status;
                if ($remarks) {
                    $screening->remarks = $remarks;
                }
                $screening->updated_by = auth()->id();
                $screening->save();
                $message = 'Screening status updated.';
            }

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'screening_type' => $screening->screening_type,
                    'screening_status' => $status,
                    'candidate_status' => $candidate->fresh()->status,
                ])
                ->log('Screening outcome recorded');

            DB::commit();

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to record outcome: ' . $e->getMessage());
        }
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
}