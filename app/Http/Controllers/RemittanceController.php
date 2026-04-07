<?php

namespace App\Http\Controllers;

use App\Models\Remittance;
use App\Models\RemittanceReceipt;
use App\Models\Candidate;
use App\Models\RemittanceBeneficiary;
use App\Services\RemittanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RemittanceController extends Controller
{
    protected RemittanceService $remittanceService;

    public function __construct(RemittanceService $remittanceService)
    {
        $this->remittanceService = $remittanceService;
    }

    /**
     * Display a listing of remittances with statistics.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Remittance::with(['candidate', 'campus', 'departure', 'verifiedBy', 'recordedBy']);

        // Campus filtering for campus admin
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        // Apply filters
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('campus_id') && ($user->isSuperAdmin() || $user->isProjectDirector())) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('purpose')) {
            $query->where('primary_purpose', $request->purpose);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate(DB::raw('COALESCE(transfer_date, transaction_date)'), '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate(DB::raw('COALESCE(transfer_date, transaction_date)'), '<=', $request->date_to);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        // Build stats from a base query (before pagination)
        $statsQuery = clone $query;
        $totalCount = (clone $statsQuery)->count();
        $totalAmount = (clone $statsQuery)->sum('amount');
        $withProof = (clone $statsQuery)->where(function ($q) {
            $q->where('has_proof', true)->orWhereNotNull('proof_document_path');
        })->count();
        $proofRate = $totalCount > 0 ? round(($withProof / $totalCount) * 100) : 0;
        $avgAmount = $totalCount > 0 ? round($totalAmount / $totalCount, 2) : 0;

        $stats = [
            'total_count'  => $totalCount,
            'total_amount' => $totalAmount,
            'avg_amount'   => $avgAmount,
            'proof_rate'   => $proofRate,
            'with_proof'   => $withProof,
        ];

        $remittances = $query->latest()->paginate(20)->withQueryString();

        return view('remittances.index', compact('remittances', 'stats'));
    }

    /**
     * Show the form for creating a new remittance.
     */
    public function create(Request $request)
    {
        $candidateId = $request->get('candidate_id');
        $candidate = $candidateId ? Candidate::findOrFail($candidateId) : null;

        // Show departed/post-departure candidates who can send remittances
        $candidates = Candidate::with(['departure'])
            ->whereHas('departure')
            ->orderBy('name')
            ->get();

        return view('remittances.create', compact('candidate', 'candidates'));
    }

    /**
     * Store a newly created remittance.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_id'        => 'required|exists:candidates,id',
            'departure_id'        => 'nullable|exists:departures,id',
            'transaction_reference' => 'nullable|string|unique:remittances,transaction_reference',
            'transfer_date'       => 'required|date|before_or_equal:today',
            'transfer_method'     => 'nullable|string',
            'primary_purpose'     => 'required|string',
            'purpose_description' => 'nullable|string',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'nullable|string|max:10',
            'amount_foreign'      => 'nullable|numeric|min:0',
            'foreign_currency'    => 'nullable|string|max:10',
            'exchange_rate'       => 'nullable|numeric|min:0',
            'sender_name'         => 'required|string|max:255',
            'sender_location'     => 'nullable|string|max:255',
            'receiver_name'       => 'required|string|max:255',
            'receiver_account'    => 'nullable|string|max:255',
            'bank_name'           => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
            'proof_document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Get candidate's campus
        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $validated['campus_id']   = $candidate->campus_id;
        $validated['recorded_by'] = $request->user()->id;
        $validated['currency']    = $validated['currency'] ?? 'PKR';

        // Derive year/month from transfer_date
        $transferDate = \Carbon\Carbon::parse($validated['transfer_date']);
        $validated['year']       = $transferDate->year;
        $validated['month']      = $transferDate->month;
        $validated['quarter']    = $transferDate->quarter;
        $validated['month_year'] = $transferDate->format('Y-m');

        // Also store as transaction_date for v3 compatibility
        $validated['transaction_date'] = $validated['transfer_date'];

        // Calculate PKR amount if exchange rate provided
        if (!empty($validated['exchange_rate']) && !empty($validated['amount_foreign'])) {
            $validated['amount_in_pkr'] = $validated['amount_foreign'] * $validated['exchange_rate'];
        }

        // Handle proof document upload
        $proofFile = $request->hasFile('proof_document') ? $request->file('proof_document') : null;
        if ($proofFile) {
            $proofPath = $proofFile->store('remittances/proofs', 'public');
            $validated['proof_document_path'] = $proofPath;
            $validated['proof_document_type'] = $proofFile->getClientOriginalExtension();
            $validated['proof_document_size'] = $proofFile->getSize();
            $validated['has_proof']           = true;
        }

        // Generate reference if missing
        if (empty($validated['transaction_reference'])) {
            $validated['transaction_reference'] = $this->remittanceService->generateTransactionReference();
        }

        $remittance = Remittance::create($validated);

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Remittance record created successfully.');
    }

    /**
     * Display the specified remittance.
     */
    public function show(Remittance $remittance)
    {
        $this->authorize('view', $remittance);
        $remittance->load(['candidate', 'campus', 'departure', 'verifiedBy', 'recordedBy', 'receipts.uploadedBy', 'usageBreakdown']);
        return view('remittances.show', compact('remittance'));
    }

    /**
     * Show the form for editing the remittance.
     */
    public function edit(Remittance $remittance)
    {
        $this->authorize('update', $remittance);
        $remittance->load(['candidate', 'departure']);

        $candidates = Candidate::with(['departure'])
            ->whereHas('departure')
            ->orderBy('name')
            ->get();

        $beneficiaries = RemittanceBeneficiary::where('candidate_id', $remittance->candidate_id)->get();

        return view('remittances.edit', compact('remittance', 'candidates', 'beneficiaries'));
    }

    /**
     * Update the specified remittance.
     */
    public function update(Request $request, Remittance $remittance)
    {
        $this->authorize('update', $remittance);

        $validated = $request->validate([
            'candidate_id'        => 'required|exists:candidates,id',
            'departure_id'        => 'nullable|exists:departures,id',
            'transaction_reference' => 'nullable|string|unique:remittances,transaction_reference,' . $remittance->id,
            'transfer_date'       => 'required|date|before_or_equal:today',
            'transfer_method'     => 'nullable|string',
            'primary_purpose'     => 'required|string',
            'purpose_description' => 'nullable|string',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'nullable|string|max:10',
            'amount_foreign'      => 'nullable|numeric|min:0',
            'foreign_currency'    => 'nullable|string|max:10',
            'exchange_rate'       => 'nullable|numeric|min:0',
            'sender_name'         => 'required|string|max:255',
            'sender_location'     => 'nullable|string|max:255',
            'receiver_name'       => 'required|string|max:255',
            'receiver_account'    => 'nullable|string|max:255',
            'bank_name'           => 'nullable|string|max:255',
            'notes'               => 'nullable|string',
            'proof_document'      => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Re-derive campus
        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $validated['campus_id'] = $candidate->campus_id;

        // Derive year/month from transfer_date
        $transferDate = \Carbon\Carbon::parse($validated['transfer_date']);
        $validated['year']       = $transferDate->year;
        $validated['month']      = $transferDate->month;
        $validated['quarter']    = $transferDate->quarter;
        $validated['month_year'] = $transferDate->format('Y-m');
        $validated['transaction_date'] = $validated['transfer_date'];

        // Recalculate PKR amount
        if (!empty($validated['exchange_rate']) && !empty($validated['amount_foreign'])) {
            $validated['amount_in_pkr'] = $validated['amount_foreign'] * $validated['exchange_rate'];
        }

        // Handle new proof document
        if ($request->hasFile('proof_document')) {
            if ($remittance->proof_document_path) {
                Storage::disk('public')->delete($remittance->proof_document_path);
            }
            $proofFile = $request->file('proof_document');
            $proofPath = $proofFile->store('remittances/proofs', 'public');
            $validated['proof_document_path'] = $proofPath;
            $validated['proof_document_type'] = $proofFile->getClientOriginalExtension();
            $validated['proof_document_size'] = $proofFile->getSize();
            $validated['has_proof']           = true;
        }

        $remittance->update($validated);

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Remittance updated successfully.');
    }

    /**
     * Remove the specified remittance (soft delete).
     */
    public function destroy(Remittance $remittance)
    {
        $this->authorize('delete', $remittance);
        $remittance->delete();

        return redirect()->route('remittances.index')
            ->with('success', 'Remittance deleted successfully.');
    }

    /**
     * Verify a remittance.
     */
    public function verify(Request $request, Remittance $remittance)
    {
        $this->authorize('verify', $remittance);
        $request->validate([
            'verification_notes' => 'nullable|string',
        ]);

        $this->remittanceService->verifyRemittance(
            $remittance,
            $request->user()->id,
            $request->verification_notes
        );

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Remittance verified successfully.');
    }

    /**
     * Reject a remittance verification.
     */
    public function reject(Request $request, Remittance $remittance)
    {
        $this->authorize('verify', $remittance);
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $this->remittanceService->rejectRemittance(
            $remittance,
            $request->user()->id,
            $request->rejection_reason
        );

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Remittance rejected.');
    }

    /**
     * Upload a proof receipt document.
     */
    public function uploadReceipt(Request $request, Remittance $remittance)
    {
        $this->authorize('update', $remittance);
        $request->validate([
            'receipt'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|string',
        ]);

        $file = $request->file('receipt');
        $path = $file->store('remittances/receipts', 'public');

        RemittanceReceipt::create([
            'remittance_id' => $remittance->id,
            'uploaded_by'   => $request->user()->id,
            'file_name'     => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_type'     => $file->getClientOriginalExtension(),
            'file_size'     => $file->getSize(),
            'document_type' => $request->document_type,
        ]);

        // Mark remittance as having proof
        $remittance->update(['has_proof' => true]);

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Receipt uploaded successfully.');
    }

    /**
     * Delete a proof receipt document.
     */
    public function deleteReceipt(Request $request, RemittanceReceipt $receipt)
    {
        $this->authorize('update', $receipt->remittance);

        // Delete file from storage
        Storage::disk('public')->delete($receipt->file_path);
        $receipt->delete();

        // Update has_proof if no more receipts
        $remittance = $receipt->remittance;
        if ($remittance && $remittance->receipts()->count() === 0 && !$remittance->proof_document_path) {
            $remittance->update(['has_proof' => false]);
        }

        return redirect()->back()->with('success', 'Receipt deleted.');
    }

    /**
     * Export remittances.
     */
    public function export(Request $request, string $format)
    {
        $user = $request->user();
        $query = Remittance::with(['candidate', 'campus'])
            ->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $remittances = $query->limit(config('remittance.export_limit', 10000))->get();

        if ($format === 'csv') {
            return $this->exportCsv($remittances);
        }

        // Default: redirect to reports for other formats
        return redirect()->route('remittance.reports.dashboard')
            ->with('info', 'Use the Reports section for PDF and Excel exports.');
    }

    /**
     * Get remittances by candidate.
     */
    public function byCandidate(Request $request, Candidate $candidate)
    {
        $remittances = $this->remittanceService->getCandidateRemittances(
            $candidate->id,
            $request->only(['verification_status', 'status'])
        );

        return view('remittances.by-candidate', compact('candidate', 'remittances'));
    }

    /**
     * Display remittance statistics dashboard.
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        $filters = [];

        if ($user->isCampusAdmin() && $user->campus_id) {
            $filters['campus_id'] = $user->campus_id;
        } elseif ($request->filled('campus_id')) {
            $filters['campus_id'] = $request->campus_id;
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date']   = $request->end_date;
        }

        $statistics = $this->remittanceService->getStatistics($filters);

        return view('remittances.statistics', compact('statistics'));
    }

    /**
     * Download the main proof document.
     */
    public function downloadProof(Remittance $remittance)
    {
        $this->authorize('view', $remittance);

        if (!$remittance->hasProof()) {
            abort(404, 'Proof document not found.');
        }

        return Storage::disk('public')->download(
            $remittance->proof_document_path,
            'remittance_proof_' . $remittance->transaction_reference . '.' . $remittance->proof_document_type
        );
    }

    /**
     * Export remittances as CSV.
     */
    protected function exportCsv($remittances)
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="remittances_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($remittances) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Reference', 'Date', 'Candidate', 'CNIC', 'Amount', 'Currency',
                'Transfer Method', 'Purpose', 'Sender', 'Receiver', 'Status', 'Verification',
            ]);

            foreach ($remittances as $r) {
                fputcsv($handle, [
                    $r->transaction_reference,
                    $r->transfer_date?->format('Y-m-d') ?? $r->transaction_date?->format('Y-m-d'),
                    $r->candidate?->full_name,
                    $r->candidate?->cnic,
                    $r->amount,
                    $r->currency,
                    $r->transfer_method,
                    $r->primary_purpose,
                    $r->sender_name,
                    $r->receiver_name,
                    $r->status,
                    $r->verification_status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
