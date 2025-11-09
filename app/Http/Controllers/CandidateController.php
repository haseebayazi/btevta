<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::with(['trade', 'campus', 'batch', 'oep']);

        // Role-based filtering
        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('trade_id')) {
            $query->where('trade_id', $request->trade_id);
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        $candidates = $query->latest()->paginate(20);

        $campuses = Campus::where('is_active', true)->get();
        $trades = Trade::where('is_active', true)->get();
        $batches = Batch::where('status', 'active')->get();

        return view('candidates.index', compact('candidates', 'campuses', 'trades', 'batches'));
    }

    public function create()
    {
        $campuses = Campus::where('is_active', true)->get();
        $trades = Trade::where('is_active', true)->get();
        $oeps = Oep::where('is_active', true)->get();

        return view('candidates.create', compact('campuses', 'trades', 'oeps'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'btevta_id' => 'required|unique:candidates,btevta_id',
            'cnic' => 'required|digits:13|unique:candidates,cnic',
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'district' => 'required|string|max:100',
            'tehsil' => 'nullable|string|max:100',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('candidates/photos', 'public');
        }

        $validated['status'] = 'new';

        $candidate = Candidate::create($validated);

        activity()
            ->performedOn($candidate)
            ->log('Candidate created');

        return redirect()->route('candidates.show', $candidate)
            ->with('success', 'Candidate registered successfully!');
    }

    public function show(Candidate $candidate)
    {
        $candidate->load([
            'trade', 
            'campus', 
            'batch', 
            'oep',
            'screenings',
            'documents',
            'nextOfKin',
            'undertakings',
            'attendances',
            'assessments',
            'certificate',
            'visaProcess',
            'departure',
            'complaints'
        ]);

        return view('candidates.show', compact('candidate'));
    }

    public function edit(Candidate $candidate)
    {
        $campuses = Campus::where('is_active', true)->get();
        $trades = Trade::where('is_active', true)->get();
        $oeps = Oep::where('is_active', true)->get();

        return view('candidates.edit', compact('candidate', 'campuses', 'trades', 'oeps'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'btevta_id' => 'required|unique:candidates,btevta_id,' . $candidate->id,
            'cnic' => 'required|digits:13|unique:candidates,cnic,' . $candidate->id,
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'district' => 'required|string|max:100',
            'tehsil' => 'nullable|string|max:100',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'oep_id' => 'nullable|exists:oeps,id',
            'remarks' => 'nullable|string',
        ]);

        $candidate->update($validated);

        activity()
            ->performedOn($candidate)
            ->log('Candidate updated');

        return redirect()->route('candidates.show', $candidate)
            ->with('success', 'Candidate updated successfully!');
    }

    public function destroy(Candidate $candidate)
    {
        // Soft delete
        $candidate->delete();

        activity()
            ->performedOn($candidate)
            ->log('Candidate deleted');

        return redirect()->route('candidates.index')
            ->with('success', 'Candidate deleted successfully!');
    }

    public function profile(Candidate $candidate)
    {
        $candidate->load([
            'trade', 
            'campus', 
            'batch', 
            'oep',
            'screenings' => function($q) {
                $q->orderBy('call_date', 'desc');
            },
            'documents',
            'nextOfKin',
            'visaProcess',
            'departure'
        ]);

        return view('candidates.profile', compact('candidate'));
    }

    public function timeline(Candidate $candidate)
    {
        $activities = activity()
            ->forSubject($candidate)
            ->latest()
            ->get();

        return view('candidates.timeline', compact('candidate', 'activities'));
    }

    public function updateStatus(Request $request, Candidate $candidate)
    {
        $request->validate([
            'status' => 'required|in:listed,screening,registered,training,visa_processing,departed,rejected',
            'remarks' => 'nullable|string'
        ]);

        $oldStatus = $candidate->status;
        $candidate->status = $request->status;
        $candidate->remarks = $request->remarks;
        $candidate->save();

        activity()
            ->performedOn($candidate)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'remarks' => $request->remarks
            ])
            ->log('Status changed');

        return back()->with('success', 'Status updated successfully!');
    }

    public function assignCampus(Request $request, Candidate $candidate)
    {
        $request->validate([
            'campus_id' => 'required|exists:campuses,id'
        ]);

        $campus = Campus::findOrFail($request->campus_id);
        $candidate->campus_id = $campus->id;
        $candidate->save();

        activity()
            ->performedOn($candidate)
            ->withProperties(['campus' => $campus->name])
            ->log('Campus assigned');

        return back()->with('success', 'Campus assigned successfully!');
    }

    public function assignOep(Request $request, Candidate $candidate)
    {
        $request->validate([
            'oep_id' => 'required|exists:oeps,id'
        ]);

        $oep = Oep::findOrFail($request->oep_id);
        $candidate->oep_id = $oep->id;
        $candidate->save();

        activity()
            ->performedOn($candidate)
            ->withProperties(['oep' => $oep->name])
            ->log('OEP assigned');

        return back()->with('success', 'OEP assigned successfully!');
    }

    public function uploadPhoto(Request $request, Candidate $candidate)
    {
        $request->validate([
            'photo' => 'required|image|max:2048|mimes:jpg,jpeg,png'
        ]);

        // Delete old photo if exists
        if ($candidate->photo_path) {
            Storage::disk('public')->delete($candidate->photo_path);
        }

        // Store new photo
        $path = $request->file('photo')->store('candidates/photos', 'public');
        $candidate->photo_path = $path;
        $candidate->save();

        activity()
            ->performedOn($candidate)
            ->log('Photo uploaded');

        return back()->with('success', 'Photo uploaded successfully!');
    }

    public function export(Request $request)
    {
        try {
            $query = Candidate::with(['trade', 'campus', 'batch', 'oep']);

            // Apply same filters as index
            if (auth()->user()->role === 'campus_admin') {
                $query->where('campus_id', auth()->user()->campus_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('campus_id')) {
                $query->where('campus_id', $request->campus_id);
            }

            if ($request->filled('trade_id')) {
                $query->where('trade_id', $request->trade_id);
            }

            $candidates = $query->with(['trade', 'campus', 'batch', 'oep'])->get();

            // Create Excel export
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Headers
            $headers = [
                'BTEVTA ID', 'Application ID', 'CNIC', 'Name', 'Father Name',
                'Date of Birth', 'Gender', 'Phone', 'Email', 'Address',
                'District', 'Tehsil', 'Trade', 'Campus', 'Batch', 'OEP', 'Status'
            ];
            $sheet->fromArray($headers, null, 'A1');

            // Data
            $row = 2;
            foreach ($candidates as $candidate) {
                // FIXED: Using null coalescing operators to prevent crashes
                $data = [
                    $candidate->btevta_id,
                    $candidate->application_id,
                    $candidate->cnic,
                    $candidate->name,
                    $candidate->father_name,
                    $candidate->date_of_birth?->format('Y-m-d') ?? 'N/A',
                    $candidate->gender ?? 'N/A',
                    $candidate->phone,
                    $candidate->email ?? 'N/A',
                    $candidate->address,
                    $candidate->district,
                    $candidate->tehsil ?? 'N/A',
                    $candidate->trade?->name ?? 'N/A',  // FIXED: Null coalescing
                    $candidate->campus?->name ?? 'N/A', // FIXED: Null coalescing
                    $candidate->batch?->batch_number ?? 'N/A', // FIXED: Null coalescing
                    $candidate->oep?->name ?? 'N/A', // FIXED: Null coalescing
                    $candidate->status_label // FIXED: Now defined in model
                ];
                $sheet->fromArray($data, null, 'A' . $row);
                $row++;
            }

            // Style header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            ];
            $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

            // Auto-size columns
            foreach (range('A', 'Q') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Generate file
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $filename = 'candidates_export_' . date('Y-m-d_His') . '.xlsx';
            $tempDir = storage_path('app/temp');
            
            // FIXED: Ensure temp directory exists with error handling
            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true)) {
                    throw new \Exception('Failed to create temp directory');
                }
            }
            
            $tempFile = $tempDir . '/' . $filename;
            $writer->save($tempFile);

            activity()
                ->withProperties(['filename' => $filename])
                ->log('Candidates exported to Excel');

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            activity()
                ->withProperties(['error' => $e->getMessage()])
                ->log('Export failed');
            
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function apiSearch(Request $request)
    {
        $query = Candidate::query();

        if ($request->filled('term')) {
            $query->search($request->term);
        }

        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        $candidates = $query->limit(10)->get(['id', 'btevta_id', 'name', 'cnic', 'status']);

        return response()->json($candidates);
    }
    public function apiSearch(Request $request)
    {
        $search = $request->query('q');
        
        $candidates = Candidate::where('name', 'like', "%{$search}%")
            ->orWhere('btevta_id', 'like', "%{$search}%")
            ->select('id', 'name', 'btevta_id', 'status')
            ->limit(20)
            ->get();
        
        return response()->json($candidates);
    }
}