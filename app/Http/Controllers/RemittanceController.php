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

        // Statistics
        $stats = [
            'total_count' => Remittance::count(),
            'total_amount' => Remittance::sum('amount'),
            'avg_amount' => Remittance::avg('amount'),
            'with_proof' => Remittance::where('has_proof', true)->count(),
            'proof_rate' => Remittance::count() > 0
                ? round((Remittance::where('has_proof', true)->count() / Remittance::count()) * 100, 2)
                : 0,
        ];

        return view('remittances.index', compact('remittances', 'stats'));
    }

    /**
     * Show the form for creating a new remittance.
     */
    public function create()
    {
        $candidates = Candidate::whereHas('departure')
            ->with('departure')
            ->orderBy('full_name')
            ->get();

        $beneficiaries = [];

        return view('remittances.create', compact('candidates', 'beneficiaries'));
    }

    /**
     * Store a newly created remittance.
     */
    public function store(Request $request)
    {
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
        $remittance->load(['candidate', 'departure', 'recordedBy', 'verifiedBy', 'receipts.uploadedBy', 'usageBreakdown']);

        return view('remittances.show', compact('remittance'));
    }

    /**
     * Show the form for editing the specified remittance.
     */
    public function edit(Remittance $remittance)
    {
        $candidates = Candidate::whereHas('departure')
            ->with('departure')
            ->orderBy('full_name')
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

        $remittance->markAsVerified(Auth::id());

        return back()->with('success', 'Remittance verified successfully.');
    }

    /**
     * Upload receipt for remittance.
     */
    public function uploadReceipt(Request $request, $id)
    {
        $remittance = Remittance::findOrFail($id);

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
        $receipt = RemittanceReceipt::findOrFail($id);
        $receipt->delete();

        return back()->with('success', 'Receipt deleted successfully.');
    }

    /**
     * Export remittances.
     */
    public function export(Request $request, $format = 'excel')
    {
        // Implementation for export will be added in Phase 2
        return back()->with('info', 'Export functionality coming soon.');
    }
}
