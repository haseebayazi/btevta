<?php
// ============================================
// FILE: app/Http/Controllers/ScreeningController.php
// REPLACE ENTIRE FILE WITH THIS VERSION
// ============================================

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateScreening;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function index(Request $request)
    {
        $screenings = CandidateScreening::with('candidate')
            ->when($request->search, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('btevta_id', 'like', '%'.$request->search.'%')
            ))
            ->latest()
            ->paginate(15);
        
        return view('screening.index', compact('screenings'));
    }

    public function pending()
    {
        $candidates = Candidate::where('status', 'screening')
            ->withCount('screenings')
            ->having('screenings_count', '<', 3)
            ->latest()
            ->get();
        
        return view('screening.pending', compact('candidates'));
    }

    public function create()
    {
        $candidates = Candidate::where('status', 'listed')
            ->select('id', 'name', 'btevta_id')
            ->orderBy('name')
            ->get();
        
        return view('screening.create', compact('candidates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'screening_type' => 'required|string',
            'screened_at' => 'required|date',
            'call_duration' => 'nullable|integer|min:1',
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string',
            'evidence_path' => 'nullable|string',
        ]);

        $validated['screened_by'] = auth()->id();
        $validated['created_by'] = auth()->id();

        CandidateScreening::create($validated);

        return redirect()->route('screening.index')->with('success', 'Screening record created successfully!');
    }

    public function edit($candidateId)
    {
        $candidate = Candidate::findOrFail($candidateId);
        $screening = $candidate->screenings()->latest()->first();
        
        if (!$screening) {
            return back()->with('error', 'No screening record found for this candidate!');
        }
        
        return view('screening.edit', compact('candidate', 'screening'));
    }

    public function update(Request $request, $candidateId)
    {
        $candidate = Candidate::findOrFail($candidateId);
        $screening = $candidate->screenings()->latest()->firstOrFail();

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

        return redirect()->route('screening.index')->with('success', 'Screening record updated successfully!');
    }

    public function logCall(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'screened_at' => 'required|date',
            'call_duration' => 'required|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        $validated['candidate_id'] = $candidate->id;
        $validated['screening_type'] = 'call';
        $validated['status'] = 'in_progress';
        $validated['screened_by'] = auth()->id();
        $validated['created_by'] = auth()->id();

        CandidateScreening::create($validated);

        return back()->with('success', 'Call logged successfully!');
    }

    public function recordOutcome(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,passed,failed,deferred,cancelled',
            'remarks' => 'nullable|string',
        ]);

        $screening = $candidate->screenings()->latest()->first();

        if ($screening) {
            $validated['updated_by'] = auth()->id();
            $screening->update($validated);
        } else {
            $validated['candidate_id'] = $candidate->id;
            $validated['screening_type'] = 'desk';
            $validated['screened_by'] = auth()->id();
            $validated['screened_at'] = now();
            $validated['created_by'] = auth()->id();
            CandidateScreening::create($validated);
        }

        // Update candidate status if passed
        if ($validated['status'] === 'passed') {
            $candidate->update(['status' => 'registered']);
        } elseif ($validated['status'] === 'failed') {
            $candidate->update(['status' => 'rejected']);
        }

        return back()->with('success', 'Screening outcome recorded successfully!');
    }

    public function export(Request $request)
    {
        $screenings = CandidateScreening::with('candidate')
            ->when($request->search, fn($q) => $q->whereHas('candidate', fn($sq) => 
                $sq->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('btevta_id', 'like', '%'.$request->search.'%')
            ))
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
        
        return response()->stream($callback, 200, $headers);
    }
}