<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

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

        // PERFORMANCE: Cache dropdown data for 24 hours to reduce database queries
        $campuses = Cache::remember('active_campuses', 86400, function () {
            return Campus::where('is_active', true)->select('id', 'name')->get();
        });

        $trades = Cache::remember('active_trades', 86400, function () {
            return Trade::where('is_active', true)->select('id', 'name', 'code')->get();
        });

        $batches = Cache::remember('active_batches', 3600, function () {
            return Batch::where('status', 'active')->select('id', 'batch_code', 'name')->get();
        });

        return view('candidates.index', compact('candidates', 'campuses', 'trades', 'batches'));
    }

    public function create()
    {
        $this->authorize('create', Candidate::class);

        // PERFORMANCE: Use cached dropdown data
        $campuses = Cache::remember('active_campuses', 86400, function () {
            return Campus::where('is_active', true)->select('id', 'name')->get();
        });

        $trades = Cache::remember('active_trades', 86400, function () {
            return Trade::where('is_active', true)->select('id', 'name', 'code')->get();
        });

        $oeps = Cache::remember('active_oeps', 86400, function () {
            return Oep::where('is_active', true)->select('id', 'name', 'code')->get();
        });

        // FIX: Include batches with trade relationship for the create form
        // Include both 'planned' and 'active' batches so newly created batches are visible
        $batches = Cache::remember('available_batches_with_trades', 3600, function () {
            return Batch::with('trade:id,name')
                ->whereIn('status', ['planned', 'active'])
                ->select('id', 'batch_code', 'name', 'trade_id', 'status')
                ->orderBy('created_at', 'desc')
                ->get();
        });

        return view('candidates.create', compact('campuses', 'trades', 'oeps', 'batches'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Candidate::class);

        $validated = $request->validate([
            'btevta_id' => 'nullable|unique:candidates,btevta_id',
            'cnic' => 'required|digits:13|unique:candidates,cnic',
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'district' => 'required|string|max:100',
            'tehsil' => 'nullable|string|max:100',
            'trade_id' => 'required|exists:trades,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'batch_id' => 'nullable|exists:batches,id',
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
        $this->authorize('view', $candidate);

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
        $this->authorize('update', $candidate);

        // PERFORMANCE: Use cached dropdown data (consistent with create method)
        $campuses = Cache::remember('active_campuses', 86400, function () {
            return Campus::where('is_active', true)->select('id', 'name')->get();
        });

        $trades = Cache::remember('active_trades', 86400, function () {
            return Trade::where('is_active', true)->select('id', 'name', 'code')->get();
        });

        $oeps = Cache::remember('active_oeps', 86400, function () {
            return Oep::where('is_active', true)->select('id', 'name', 'code')->get();
        });

        return view('candidates.edit', compact('candidate', 'campuses', 'trades', 'oeps'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            'btevta_id' => 'required|unique:candidates,btevta_id,' . $candidate->id,
            'cnic' => 'required|digits:13|unique:candidates,cnic,' . $candidate->id,
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
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
        $this->authorize('delete', $candidate);

        try {
            // Soft delete
            $candidate->delete();

            activity()
                ->performedOn($candidate)
                ->log('Candidate deleted');

            return redirect()->route('candidates.index')
                ->with('success', 'Candidate deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete candidate: ' . $e->getMessage());
        }
    }

    public function profile(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $candidate->load([
            'trade',
            'campus',
            'batch',
            'oep',
            'screenings' => function($q) {
                $q->orderBy('screened_at', 'desc');
            },
            'documents',
            'nextOfKin',
            'visaProcess',
            'departure',
            'remittances' => function($q) {
                $q->orderBy('transfer_date', 'desc')->limit(5);
            }
        ]);

        // Calculate remittance statistics
        $remittanceStats = [
            'total_count' => $candidate->remittances()->count(),
            'total_amount' => $candidate->remittances()->sum('amount'),
            'last_remittance' => $candidate->remittances()->latest('transfer_date')->first(),
            'pending_count' => $candidate->remittances()->where('status', 'pending')->count(),
            'with_proof' => $candidate->remittances()->where('has_proof', true)->count(),
            'unresolved_alerts' => \App\Models\RemittanceAlert::where('candidate_id', $candidate->id)
                ->where('is_resolved', false)
                ->count(),
        ];

        return view('candidates.profile', compact('candidate', 'remittanceStats'));
    }

    public function timeline(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $activities = activity()
            ->forSubject($candidate)
            ->latest()
            ->get();

        return view('candidates.timeline', compact('candidate', 'activities'));
    }

    public function updateStatus(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $request->validate([
            'status' => 'required|in:listed,screening,registered,training,visa_processing,departed,rejected',
            'remarks' => 'nullable|string'
        ]);

        try {
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
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function assignCampus(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $request->validate([
            'campus_id' => 'required|exists:campuses,id'
        ]);

        try {
            $campus = Campus::findOrFail($request->campus_id);
            $candidate->campus_id = $campus->id;
            $candidate->save();

            activity()
                ->performedOn($candidate)
                ->withProperties(['campus' => $campus->name])
                ->log('Campus assigned');

            return back()->with('success', 'Campus assigned successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign campus: ' . $e->getMessage());
        }
    }

    public function assignOep(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $request->validate([
            'oep_id' => 'required|exists:oeps,id'
        ]);

        try {
            $oep = Oep::findOrFail($request->oep_id);
            $candidate->oep_id = $oep->id;
            $candidate->save();

            activity()
                ->performedOn($candidate)
                ->withProperties(['oep' => $oep->name])
                ->log('OEP assigned');

            return back()->with('success', 'OEP assigned successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign OEP: ' . $e->getMessage());
        }
    }

    public function uploadPhoto(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $request->validate([
            'photo' => 'required|image|max:2048|mimes:jpg,jpeg,png'
        ]);

        try {
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
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload photo: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

        try {
            // FIXED: Removed duplicate eager loading - already loaded at the beginning
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

            $candidates = $query->get();

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
        $this->authorize('viewAny', Candidate::class);

        $query = Candidate::query();

        // Support both 'term' and 'q' parameters for backward compatibility
        $searchTerm = $request->filled('term') ? $request->term : $request->query('q');

        if ($searchTerm) {
            // Use search scope if available, otherwise use direct LIKE queries
            if (method_exists(Candidate::class, 'scopeSearch')) {
                $query->search($searchTerm);
            } else {
                // Escape special LIKE characters to prevent SQL LIKE injection
                $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
                $query->where(function($q) use ($escapedTerm) {
                    $q->where('name', 'like', "%{$escapedTerm}%")
                      ->orWhere('btevta_id', 'like', "%{$escapedTerm}%")
                      ->orWhere('cnic', 'like', "%{$escapedTerm}%");
                });
            }
        }

        // Apply role-based filtering for security
        if (auth()->user()->role === 'campus_admin') {
            $query->where('campus_id', auth()->user()->campus_id);
        }

        $candidates = $query->limit(20)->get(['id', 'btevta_id', 'name', 'cnic', 'status']);

        return response()->json($candidates);
    }
}