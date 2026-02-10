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

        $candidates = $query->latest()->paginate(20);

        // Get dropdown data - fetching fresh to avoid cache issues
        $campuses = Campus::where('is_active', true)->select('id', 'name')->get();
        $trades = Trade::where('is_active', true)->select('id', 'name', 'code')->get();

        return view('candidates.index', compact('candidates', 'campuses', 'trades'));
    }

    public function create()
    {
        $this->authorize('create', Candidate::class);

        // Get dropdown data - fetching fresh to avoid cache issues
        $campuses = Campus::where('is_active', true)->select('id', 'name')->get();
        $trades = Trade::where('is_active', true)->select('id', 'name', 'code')->get();
        $oeps = Oep::where('is_active', true)->select('id', 'name', 'code')->get();

        // Get batches - include planned and active, fresh query (no cache)
        $batches = Batch::with('trade:id,name')
            ->whereIn('status', ['planned', 'active'])
            ->select('id', 'batch_code', 'name', 'trade_id', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

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

        // Ensure address is never null (fallback for nullable migration compatibility)
        $validated['address'] = $validated['address'] ?? '';

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

        // Get dropdown data - fetching fresh to avoid cache issues
        $campuses = Campus::where('is_active', true)->select('id', 'name')->get();
        $trades = Trade::where('is_active', true)->select('id', 'name', 'code')->get();
        $oeps = Oep::where('is_active', true)->select('id', 'name', 'code')->get();

        // Get batches - include planned and active, plus the candidate's current batch if different
        $batches = Batch::with('trade:id,name')
            ->where(function($query) use ($candidate) {
                $query->whereIn('status', ['planned', 'active']);
                if ($candidate->batch_id) {
                    $query->orWhere('id', $candidate->batch_id);
                }
            })
            ->select('id', 'batch_code', 'name', 'trade_id', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('candidates.edit', compact('candidate', 'campuses', 'trades', 'oeps', 'batches'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $this->authorize('update', $candidate);

        $validated = $request->validate([
            // btevta_id is auto-generated and disabled in form, so not validated here
            'cnic' => 'required|digits:13|unique:candidates,cnic,' . $candidate->id,
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
            'status' => 'nullable|in:new,screening,registered,training,visa_process,ready,departed,rejected,dropped',
            'remarks' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($candidate->photo_path) {
                Storage::disk('public')->delete($candidate->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('candidates/photos', 'public');
        }

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

        // AUDIT FIX: Calculate remittance statistics in a single query to avoid N+1
        $remittanceStats = \DB::table('remittances')
            ->where('candidate_id', $candidate->id)
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(amount), 0) as total_amount,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN has_proof = 1 THEN 1 ELSE 0 END) as with_proof,
                MAX(transfer_date) as last_transfer_date
            ')
            ->first();

        // Get last remittance details separately (single query)
        $lastRemittance = $candidate->remittances()->latest('transfer_date')->first();

        // Get unresolved alerts count (single query)
        $unresolvedAlerts = \App\Models\RemittanceAlert::where('candidate_id', $candidate->id)
            ->where('is_resolved', false)
            ->count();

        $remittanceStats = [
            'total_count' => $remittanceStats->total_count ?? 0,
            'total_amount' => $remittanceStats->total_amount ?? 0,
            'last_remittance' => $lastRemittance,
            'pending_count' => $remittanceStats->pending_count ?? 0,
            'with_proof' => $remittanceStats->with_proof ?? 0,
            'unresolved_alerts' => $unresolvedAlerts,
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
            'status' => 'required|in:' . implode(',', array_keys(Candidate::getStatuses())),
            'remarks' => 'nullable|string'
        ]);

        try {
            // Use the model's updateStatus method which validates state transitions
            // This prevents invalid transitions like new → training (skipping screening)
            $candidate->updateStatus($request->status, $request->remarks);

            return back()->with('success', 'Status updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Invalid status transition: ' . $e->getMessage());
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
                'TheLeap ID', 'Application ID', 'CNIC', 'Name', 'Father Name',
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

    /**
     * Check for potential duplicate candidates based on phone, email, or name.
     * Used for real-time duplicate warning during registration.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkDuplicates(Request $request)
    {
        $this->authorize('viewAny', Candidate::class);

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'name' => 'nullable|string|max:255',
            'exclude_id' => 'nullable|integer|exists:candidates,id',
        ]);

        $duplicates = Candidate::findPotentialDuplicates(
            $request->phone,
            $request->email,
            $request->name,
            $request->exclude_id
        );

        $result = $duplicates->map(function ($item) {
            return [
                'id' => $item['candidate']->id,
                'btevta_id' => $item['candidate']->btevta_id,
                'name' => $item['candidate']->name,
                'status' => $item['candidate']->status,
                'match_type' => $item['match_type'],
                'confidence' => $item['confidence'],
            ];
        });

        return response()->json([
            'has_duplicates' => $duplicates->isNotEmpty(),
            'count' => $duplicates->count(),
            'duplicates' => $result,
        ]);
    }

    /**
     * Validate CNIC format and checksum.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateCnic(Request $request)
    {
        $request->validate([
            'cnic' => 'required|string',
            'exclude_id' => 'nullable|integer|exists:candidates,id',
        ]);

        $cnic = preg_replace('/[^0-9]/', '', $request->cnic);

        $response = [
            'cnic' => $cnic,
            'is_valid_format' => strlen($cnic) === 13,
            'is_valid_checksum' => Candidate::validateCnicChecksum($cnic),
            'is_unique' => true,
            'existing_candidate' => null,
        ];

        // Check uniqueness
        $query = Candidate::where('cnic', $cnic);
        if ($request->exclude_id) {
            $query->where('id', '!=', $request->exclude_id);
        }
        $existing = $query->first();

        if ($existing) {
            $response['is_unique'] = false;
            $response['existing_candidate'] = [
                'id' => $existing->id,
                'btevta_id' => $existing->btevta_id,
                'name' => $existing->name,
                'status' => $existing->status,
            ];
        }

        $response['is_valid'] = $response['is_valid_format']
            && $response['is_valid_checksum']
            && $response['is_unique'];

        return response()->json($response);
    }

    /**
     * Validate Pakistan phone number format.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validatePhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = $request->phone;
        $isValidFormat = Candidate::validatePakistanPhone($phone);

        return response()->json([
            'phone' => $phone,
            'is_valid_format' => $isValidFormat,
            'message' => $isValidFormat
                ? 'Valid Pakistan phone number format'
                : 'Please enter a valid Pakistan phone number (e.g., 03XX-XXXXXXX or +92-3XX-XXXXXXX)',
        ]);
    }

    /**
     * Serve candidate photo (fallback for when storage link doesn't exist)
     *
     * @param Candidate $candidate
     * @return \Illuminate\Http\Response
     */
    public function photo(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        if (!$candidate->photo_path) {
            abort(404, 'No photo available');
        }

        // Check if file exists on public disk
        if (!Storage::disk('public')->exists($candidate->photo_path)) {
            abort(404, 'Photo file not found');
        }

        $file = Storage::disk('public')->get($candidate->photo_path);
        $mimeType = Storage::disk('public')->mimeType($candidate->photo_path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }
}