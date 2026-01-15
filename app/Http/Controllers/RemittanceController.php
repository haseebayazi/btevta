<?php

namespace App\Http\Controllers;

use App\Models\Remittance;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\RemittanceBeneficiary;
use App\Models\RemittanceReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RemittanceController extends Controller
{
    /**
     * Display a listing of remittances.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Remittance::class);

        $query = Remittance::with(['candidate', 'departure', 'recordedBy'])
            ->orderBy('transfer_date', 'desc');

        // Apply filters
        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('purpose')) {
            $query->where('primary_purpose', $request->purpose);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        // Role-based filtering
        $user = Auth::user();
        if ($user->role === 'oep') {
            $query->whereHas('candidate', function($q) use ($user) {
                $q->where('oep_id', $user->oep_id);
            });
        } elseif ($user->role === 'campus_admin') {
            $query->whereHas('candidate', function($q) use ($user) {
                $q->where('campus_id', $user->campus_id);
            });
        } elseif ($user->role === 'candidate') {
            $query->whereHas('candidate', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $remittances = $query->paginate(20);

        // AUDIT FIX: Apply role-based filtering to statistics as well
        $statsQuery = Remittance::query();
        if ($user->role === 'oep') {
            $statsQuery->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
        } elseif ($user->role === 'campus_admin') {
            $statsQuery->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
        } elseif ($user->role === 'candidate') {
            $statsQuery->whereHas('candidate', fn($q) => $q->where('user_id', $user->id));
        }

        $totalCount = (clone $statsQuery)->count();
        $withProof = (clone $statsQuery)->where('has_proof', true)->count();

        $stats = [
            'total_count' => $totalCount,
            'total_amount' => (clone $statsQuery)->sum('amount'),
            'avg_amount' => (clone $statsQuery)->avg('amount'),
            'with_proof' => $withProof,
            'proof_rate' => $totalCount > 0 ? round(($withProof / $totalCount) * 100, 2) : 0,
        ];

        return view('remittances.index', compact('remittances', 'stats'));
    }

    /**
     * Show the form for creating a new remittance.
     */
    public function create()
    {
        $this->authorize('create', Remittance::class);

        // AUDIT FIX: Filter candidates by campus/OEP for non-admin users
        $candidatesQuery = Candidate::whereHas('departure')
            ->with('departure');

        $user = Auth::user();
        if ($user->role === 'oep' && $user->oep_id) {
            $candidatesQuery->where('oep_id', $user->oep_id);
        } elseif ($user->role === 'campus_admin' && $user->campus_id) {
            $candidatesQuery->where('campus_id', $user->campus_id);
        }

        $candidates = $candidatesQuery->orderBy('name')->get();

        $beneficiaries = [];

        return view('remittances.create', compact('candidates', 'beneficiaries'));
    }

    /**
     * Store a newly created remittance.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Remittance::class);

        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'departure_id' => 'nullable|exists:departures,id',
            'transaction_reference' => 'required|string|unique:remittances,transaction_reference',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'amount_foreign' => 'nullable|numeric|min:0',
            'foreign_currency' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'transfer_date' => 'required|date',
            'transfer_method' => 'nullable|string',
            'sender_name' => 'required|string',
            'sender_location' => 'nullable|string',
            'receiver_name' => 'required|string',
            'receiver_account' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'primary_purpose' => 'required|string',
            'purpose_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['recorded_by'] = Auth::id();
        $validated['status'] = 'pending';

        // Check if this is the first remittance for this candidate
        $isFirst = !Remittance::where('candidate_id', $validated['candidate_id'])->exists();
        $validated['is_first_remittance'] = $isFirst;

        $remittance = Remittance::create($validated);

        // Calculate month number if departure exists
        if ($remittance->departure_id) {
            $monthNumber = $remittance->calculateMonthNumber();
            $remittance->update(['month_number' => $monthNumber]);
        }

        return redirect()
            ->route('remittances.show', $remittance)
            ->with('success', 'Remittance recorded successfully.');
    }

    /**
     * Display the specified remittance.
     */
    public function show(Remittance $remittance)
    {
        $this->authorize('view', $remittance);

        $remittance->load(['candidate', 'departure', 'recordedBy', 'verifiedBy', 'receipts.uploadedBy', 'usageBreakdown']);

        return view('remittances.show', compact('remittance'));
    }

    /**
     * Show the form for editing the specified remittance.
     */
    public function edit(Remittance $remittance)
    {
        $this->authorize('update', $remittance);

        $candidates = Candidate::whereHas('departure')
            ->with('departure')
            ->orderBy('name')
            ->get();

        $beneficiaries = RemittanceBeneficiary::where('candidate_id', $remittance->candidate_id)
            ->active()
            ->get();

        return view('remittances.edit', compact('remittance', 'candidates', 'beneficiaries'));
    }

    /**
     * Update the specified remittance.
     */
    public function update(Request $request, Remittance $remittance)
    {
        $this->authorize('update', $remittance);

        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'departure_id' => 'nullable|exists:departures,id',
            'transaction_reference' => 'required|string|unique:remittances,transaction_reference,' . $remittance->id,
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'amount_foreign' => 'nullable|numeric|min:0',
            'foreign_currency' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'transfer_date' => 'required|date',
            'transfer_method' => 'nullable|string',
            'sender_name' => 'required|string',
            'sender_location' => 'nullable|string',
            'receiver_name' => 'required|string',
            'receiver_account' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'primary_purpose' => 'required|string',
            'purpose_description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $remittance->update($validated);

        // Recalculate month number if departure changed
        if ($remittance->departure_id) {
            $monthNumber = $remittance->calculateMonthNumber();
            $remittance->update(['month_number' => $monthNumber]);
        }

        return redirect()
            ->route('remittances.show', $remittance)
            ->with('success', 'Remittance updated successfully.');
    }

    /**
     * Remove the specified remittance.
     */
    public function destroy(Remittance $remittance)
    {
        $this->authorize('delete', $remittance);

        $remittance->delete();

        return redirect()
            ->route('remittances.index')
            ->with('success', 'Remittance deleted successfully.');
    }

    /**
     * Verify the remittance.
     */
    public function verify(Request $request, $id)
    {
        $remittance = Remittance::findOrFail($id);
        $this->authorize('verify', $remittance);

        $remittance->markAsVerified(Auth::id());

        return back()->with('success', 'Remittance verified successfully.');
    }

    /**
     * Upload receipt for remittance.
     */
    public function uploadReceipt(Request $request, $id)
    {
        $remittance = Remittance::findOrFail($id);
        $this->authorize('uploadReceipt', $remittance);

        $request->validate([
            'receipt' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|string',
        ]);

        $file = $request->file('receipt');
        $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('remittances/receipts', $fileName, 'public');

        RemittanceReceipt::create([
            'remittance_id' => $remittance->id,
            'uploaded_by' => Auth::id(),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $request->document_type,
        ]);

        $remittance->update(['has_proof' => true]);

        return back()->with('success', 'Receipt uploaded successfully.');
    }

    /**
     * Delete receipt.
     */
    public function deleteReceipt($id)
    {
        $this->authorize('deleteReceipt', Remittance::class);

        $receipt = RemittanceReceipt::findOrFail($id);
        $receipt->delete();

        return back()->with('success', 'Receipt deleted successfully.');
    }

    /**
     * Export remittances to CSV.
     * PHASE 7 FIX: Implemented export functionality.
     */
    public function export(Request $request, $format = 'csv')
    {
        $this->authorize('export', Remittance::class);

        // Validate export parameters
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'candidate_id' => 'nullable|exists:candidates,id',
            'status' => 'nullable|string|in:pending,verified,rejected',
        ]);

        try {
            // Build query with filters
            $query = Remittance::with(['candidate', 'candidate.trade', 'beneficiary'])
                // AUDIT FIX: Changed remittance_date to transfer_date to match database schema
                ->when($request->from_date, fn($q) => $q->whereDate('transfer_date', '>=', $request->from_date))
                ->when($request->to_date, fn($q) => $q->whereDate('transfer_date', '<=', $request->to_date))
                ->when($request->candidate_id, fn($q) => $q->where('candidate_id', $request->candidate_id))
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->orderBy('transfer_date', 'desc');

            // Role-based filtering
            if (auth()->user()->role === 'campus_admin' && auth()->user()->campus_id) {
                $query->whereHas('candidate', fn($q) => $q->where('campus_id', auth()->user()->campus_id));
            }

            $remittances = $query->get();

            // Generate CSV
            $filename = 'remittances-export-' . now()->format('Y-m-d-His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($remittances) {
                $file = fopen('php://output', 'w');

                // CSV Header
                fputcsv($file, [
                    'TheLeap ID',
                    'Candidate Name',
                    'Trade',
                    'Remittance Date',
                    'Amount (PKR)',
                    'Amount (SAR)',
                    'Exchange Rate',
                    'Transaction Reference',
                    'Bank Name',
                    'Beneficiary Name',
                    'Status',
                    'Verified At',
                ]);

                // Data rows
                foreach ($remittances as $remittance) {
                    fputcsv($file, [
                        $remittance->candidate->btevta_id ?? 'N/A',
                        $remittance->candidate->name ?? 'N/A',
                        $remittance->candidate->trade->name ?? 'N/A',
                        $remittance->transfer_date ? $remittance->transfer_date->format('Y-m-d') : 'N/A',
                        number_format($remittance->amount_pkr ?? 0, 2),
                        number_format($remittance->amount_sar ?? 0, 2),
                        $remittance->exchange_rate ?? 'N/A',
                        $remittance->transaction_reference ?? 'N/A',
                        $remittance->bank_name ?? 'N/A',
                        $remittance->beneficiary->name ?? 'N/A',
                        ucfirst($remittance->status ?? 'pending'),
                        $remittance->verified_at ? $remittance->verified_at->format('Y-m-d H:i') : '',
                    ]);
                }

                fclose($file);
            };

            // Log export activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'count' => $remittances->count(),
                    'filters' => $request->only(['from_date', 'to_date', 'candidate_id', 'status']),
                ])
                ->log('Exported remittance records');

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to export remittances: ' . $e->getMessage());
        }
    }
}
