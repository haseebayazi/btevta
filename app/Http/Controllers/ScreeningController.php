<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateScreening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScreeningController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', CandidateScreening::class);

        // FIXED: Optimized N+1 query by using join instead of nested whereHas
        $screenings = CandidateScreening::with('candidate')
            ->when($request->search, function($q) use ($request) {
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

        $candidates = Candidate::where('status', 'screening')
            ->withCount('screenings')
            ->having('screenings_count', '<', 3)
            ->latest()
            ->get();
        
        return view('screening.pending', compact('candidates'));
    }

    public function create()
    {
        $this->authorize('create', CandidateScreening::class);

        $candidates = Candidate::where('status', 'listed')
            ->select('id', 'name', 'btevta_id')
            ->orderBy('name')
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
            $candidate = Candidate::findOrFail($candidateId);
            $screening = $candidate->screenings()->latest()->first();

            if (!$screening) {
                return back()->with('error', 'No screening record found for this candidate!');
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

    public function logCall(Request $request, Candidate $candidate)
    {
        $this->authorize('create', CandidateScreening::class);

        $validated = $request->validate([
            'screened_at' => 'required|date',
            'call_duration' => 'required|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        try {
            $validated['candidate_id'] = $candidate->id;
            $validated['screening_type'] = 'call';
            $validated['status'] = 'in_progress';
            $validated['screened_by'] = auth()->id();
            $validated['created_by'] = auth()->id();

            CandidateScreening::create($validated);

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->log('Call logged for screening');

            return back()->with('success', 'Call logged successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to log call: ' . $e->getMessage());
        }
    }

    public function recordOutcome(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $screening = $candidate->screenings()->latest()->first();

            if ($screening) {
                $this->authorize('update', $screening);
                $validated['updated_by'] = auth()->id();
                $screening->update($validated);
            } else {
                $this->authorize('create', CandidateScreening::class);
                $validated['candidate_id'] = $candidate->id;
                $validated['screening_type'] = 'desk';
                $validated['screened_by'] = auth()->id();
                $validated['screened_at'] = now();
                $validated['created_by'] = auth()->id();
                $screening = CandidateScreening::create($validated);
            }

            // Update candidate status if passed
            if ($validated['status'] === 'passed') {
                $candidate->update(['status' => 'registered']);
            } elseif ($validated['status'] === 'failed') {
                $candidate->update(['status' => 'rejected']);
            }

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties(['screening_status' => $validated['status']])
                ->log('Screening outcome recorded');

            DB::commit();

            return back()->with('success', 'Screening outcome recorded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to record outcome: ' . $e->getMessage());
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

            $screenings = CandidateScreening::with('candidate')
                ->when($escapedSearch, function($q) use ($escapedSearch) {
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
}