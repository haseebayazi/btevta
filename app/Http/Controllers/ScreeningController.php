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
            'screening_date' => 'required|date',
            'call_duration' => 'required|integer|min:1',
            'call_notes' => 'nullable|string',
            'screening_outcome' => 'required|in:pass,fail,pending',
            'remarks' => 'nullable|string',
        ]);
        
        $validated['screened_by'] = auth()->id();
        $validated['screened_at'] = now();
        
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
            'screening_date' => 'required|date',
            'call_duration' => 'required|integer|min:1',
            'call_notes' => 'nullable|string',
            'screening_outcome' => 'required|in:pass,fail,pending',
            'remarks' => 'nullable|string',
        ]);
        
        $screening->update($validated);
        
        return redirect()->route('screening.index')->with('success', 'Screening record updated successfully!');
    }

    public function logCall(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'call_date' => 'required|date',
            'call_duration' => 'required|integer|min:1',
            'call_notes' => 'nullable|string',
        ]);
        
        $validated['candidate_id'] = $candidate->id;
        $validated['screened_by'] = auth()->id();
        $validated['screened_at'] = now();
        
        CandidateScreening::create($validated);
        
        return back()->with('success', 'Call logged successfully!');
    }

    public function recordOutcome(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'screening_outcome' => 'required|in:pass,fail,pending',
            'remarks' => 'nullable|string',
        ]);
        
        $screening = $candidate->screenings()->latest()->first();
        
        if ($screening) {
            $screening->update($validated);
        } else {
            $validated['candidate_id'] = $candidate->id;
            $validated['screened_by'] = auth()->id();
            $validated['screened_at'] = now();
            CandidateScreening::create($validated);
        }
        
        // Update candidate status if passed
        if ($validated['screening_outcome'] === 'pass') {
            $candidate->update(['status' => 'registered']);
        } elseif ($validated['screening_outcome'] === 'fail') {
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
            fputcsv($file, ['Candidate ID', 'Name', 'Screening Date', 'Outcome', 'Duration (mins)', 'Remarks']);
            
            foreach ($screenings as $screening) {
                fputcsv($file, [
                    $screening->candidate->btevta_id,
                    $screening->candidate->name,
                    $screening->screened_at,
                    $screening->screening_outcome,
                    $screening->call_duration,
                    $screening->remarks,
                ]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}