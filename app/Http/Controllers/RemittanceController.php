<?php

namespace App\Http\Controllers;

use App\Models\Remittance;
use App\Models\Candidate;
use App\Services\RemittanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RemittanceController extends Controller
{
    protected RemittanceService $remittanceService;

    public function __construct(RemittanceService $remittanceService)
    {
        $this->remittanceService = $remittanceService;
    }

    /**
     * Display a listing of remittances
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

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $remittances = $query->latest()->paginate(20);

        return view('remittances.index', compact('remittances'));
    }

    /**
     * Show the form for creating a new remittance
     */
    public function create(Request $request)
    {
        $candidateId = $request->get('candidate_id');
        $candidate = $candidateId ? Candidate::findOrFail($candidateId) : null;

        return view('remittances.create', compact('candidate'));
    }

    /**
     * Store a newly created remittance
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'departure_id' => 'nullable|exists:departures,id',
            'transaction_reference' => 'nullable|unique:remittances,transaction_reference',
            'transaction_type' => 'required|string',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'transfer_method' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'swift_code' => 'nullable|string',
            'iban' => 'nullable|string',
            'purpose' => 'required|string',
            'description' => 'nullable|string',
            'month_year' => 'nullable|string',
            'proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Get candidate's campus
        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $validated['campus_id'] = $candidate->campus_id;
        $validated['recorded_by'] = $request->user()->id;

        $proofFile = $request->hasFile('proof_document') ? $request->file('proof_document') : null;

        $remittance = $this->remittanceService->createRemittance($validated, $proofFile);

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Remittance record created successfully.');
    }

    /**
     * Display the specified remittance
     */
    public function show(Remittance $remittance)
    {
        $remittance->load(['candidate', 'campus', 'departure', 'verifiedBy', 'recordedBy']);
        $this->authorize('view', $remittance);
        return view('remittances.show', compact('remittance'));
    }

    /**
     * Show the form for editing the remittance
     */
    public function edit(Remittance $remittance)
    {
        $remittance->load(['candidate']);

        return view('remittances.edit', compact('remittance'));
    }

    /**
     * Update the specified remittance
     */
    public function update(Request $request, Remittance $remittance)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'transfer_method' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'account_number' => 'nullable|string',
            'swift_code' => 'nullable|string',
            'iban' => 'nullable|string',
            'purpose' => 'required|string',
            'description' => 'nullable|string',
            'month_year' => 'nullable|string',
            'proof_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $proofFile = $request->hasFile('proof_document') ? $request->file('proof_document') : null;

        $this->remittanceService->updateRemittance($remittance, $validated, $proofFile);

        return redirect()->route('remittances.show', $remittance)
            ->with('success', 'Remittance updated successfully.');
    }

    /**
     * Verify a remittance
     */
    public function verify(Request $request, Remittance $remittance)
    {
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
     * Reject a remittance verification
     */
    public function reject(Request $request, Remittance $remittance)
    {
        $request->validate([
            'rejection_reason' => 'required|string',
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
     * Download proof document
     */
    public function downloadProof(Remittance $remittance)
    {
        if (!$remittance->hasProof()) {
            abort(404, 'Proof document not found.');
        }

        return Storage::disk('public')->download(
            $remittance->proof_document_path,
            'remittance_proof_' . $remittance->transaction_reference . '.' . $remittance->proof_document_type
        );
    }

    /**
     * Display remittance statistics dashboard
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        $filters = [];

        // Campus filtering
        if ($user->isCampusAdmin() && $user->campus_id) {
            $filters['campus_id'] = $user->campus_id;
        } elseif ($request->filled('campus_id')) {
            $filters['campus_id'] = $request->campus_id;
        }

        // Date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date'] = $request->end_date;
        }

        $statistics = $this->remittanceService->getStatistics($filters);

        return view('remittances.statistics', compact('statistics'));
    }

    /**
     * Get remittances by candidate
     */
    public function byCandidate(Request $request, Candidate $candidate)
    {
        $remittances = $this->remittanceService->getCandidateRemittances(
            $candidate->id,
            $request->only(['verification_status', 'status'])
        );

        return view('remittances.by-candidate', compact('candidate', 'remittances'));
    }
}
