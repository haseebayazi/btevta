<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remittance;
use App\Models\Candidate;
use App\Services\RemittanceService;
use App\Http\Resources\RemittanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RemittanceApiController extends Controller
{
    protected RemittanceService $remittanceService;

    public function __construct(RemittanceService $remittanceService)
    {
        $this->remittanceService = $remittanceService;
    }

    /**
     * Display a listing of remittances
     */
    public function index(Request $request): JsonResponse
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

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        $perPage = $request->get('per_page', 20);
        $remittances = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => RemittanceResource::collection($remittances),
            'meta' => [
                'current_page' => $remittances->currentPage(),
                'last_page' => $remittances->lastPage(),
                'per_page' => $remittances->perPage(),
                'total' => $remittances->total(),
            ],
        ]);
    }

    /**
     * Store a newly created remittance
     */
    public function store(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'message' => 'Remittance created successfully',
            'data' => new RemittanceResource($remittance->load(['candidate', 'campus', 'verifiedBy', 'recordedBy'])),
        ], 201);
    }

    /**
     * Display the specified remittance
     */
    public function show(Request $request, $id): JsonResponse
    {
        $remittance = Remittance::with(['candidate', 'campus', 'departure', 'verifiedBy', 'recordedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new RemittanceResource($remittance),
        ]);
    }

    /**
     * Update the specified remittance
     */
    public function update(Request $request, $id): JsonResponse
    {
        $remittance = Remittance::findOrFail($id);

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

        $remittance = $this->remittanceService->updateRemittance($remittance, $validated, $proofFile);

        return response()->json([
            'success' => true,
            'message' => 'Remittance updated successfully',
            'data' => new RemittanceResource($remittance->load(['candidate', 'campus', 'verifiedBy', 'recordedBy'])),
        ]);
    }

    /**
     * Verify a remittance
     */
    public function verify(Request $request, $id): JsonResponse
    {
        $remittance = Remittance::findOrFail($id);

        $request->validate([
            'verification_notes' => 'nullable|string',
        ]);

        $remittance = $this->remittanceService->verifyRemittance(
            $remittance,
            $request->user()->id,
            $request->verification_notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Remittance verified successfully',
            'data' => new RemittanceResource($remittance->load(['candidate', 'campus', 'verifiedBy', 'recordedBy'])),
        ]);
    }

    /**
     * Reject a remittance
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $remittance = Remittance::findOrFail($id);

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $remittance = $this->remittanceService->rejectRemittance(
            $remittance,
            $request->user()->id,
            $request->rejection_reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Remittance rejected',
            'data' => new RemittanceResource($remittance->load(['candidate', 'campus', 'verifiedBy', 'recordedBy'])),
        ]);
    }

    /**
     * Get remittance statistics
     */
    public function statistics(Request $request): JsonResponse
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

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get remittances by candidate
     */
    public function byCandidate(Request $request, $candidateId): JsonResponse
    {
        $candidate = Candidate::findOrFail($candidateId);

        $remittances = $this->remittanceService->getCandidateRemittances(
            $candidate->id,
            $request->only(['verification_status', 'status'])
        );

        return response()->json([
            'success' => true,
            'data' => RemittanceResource::collection($remittances),
            'meta' => [
                'candidate_id' => $candidate->id,
                'candidate_name' => $candidate->name,
                'total_remittances' => $remittances->count(),
            ],
        ]);
    }

    /**
     * Get pending verifications
     */
    public function pendingVerifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $campusId = null;

        if ($user->isCampusAdmin() && $user->campus_id) {
            $campusId = $user->campus_id;
        }

        $remittances = $this->remittanceService->getPendingVerifications($campusId);

        return response()->json([
            'success' => true,
            'data' => RemittanceResource::collection($remittances),
            'meta' => [
                'current_page' => $remittances->currentPage(),
                'last_page' => $remittances->lastPage(),
                'per_page' => $remittances->perPage(),
                'total' => $remittances->total(),
            ],
        ]);
    }

    /**
     * Download proof document
     */
    public function downloadProof(Request $request, $id): JsonResponse
    {
        $remittance = Remittance::findOrFail($id);

        if (!$remittance->hasProof()) {
            return response()->json([
                'success' => false,
                'message' => 'Proof document not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'remittance_id' => $remittance->id,
                'transaction_reference' => $remittance->transaction_reference,
                'proof_url' => $remittance->proof_url,
                'file_type' => $remittance->proof_document_type,
                'file_size' => $remittance->proof_document_size,
            ],
        ]);
    }

    /**
     * Delete a remittance
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $remittance = Remittance::findOrFail($id);

        // Check if already verified - cannot delete verified remittances
        if ($remittance->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete verified remittance',
            ], 403);
        }

        // Delete proof document if exists
        if ($remittance->hasProof()) {
            $this->remittanceService->deleteProof($remittance);
        }

        $remittance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Remittance deleted successfully',
        ]);
    }

    /**
     * Search remittances
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $user = $request->user();
        $searchTerm = $request->get('q');

        // Escape special characters for SQL LIKE
        $escapedQuery = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);

        $query = Remittance::with(['candidate', 'campus', 'departure', 'verifiedBy', 'recordedBy'])
            ->where(function($q) use ($escapedQuery) {
                $q->where('transaction_reference', 'LIKE', "%{$escapedQuery}%")
                  ->orWhere('purpose', 'LIKE', "%{$escapedQuery}%")
                  ->orWhere('description', 'LIKE', "%{$escapedQuery}%")
                  ->orWhere('bank_name', 'LIKE', "%{$escapedQuery}%")
                  ->orWhereHas('candidate', function($candidateQuery) use ($escapedQuery) {
                      $candidateQuery->where('name', 'LIKE', "%{$escapedQuery}%")
                          ->orWhere('passport_number', 'LIKE', "%{$escapedQuery}%");
                  });
            });

        // Campus filtering for campus admin
        if ($user->isCampusAdmin() && $user->campus_id) {
            $query->where('campus_id', $user->campus_id);
        }

        $perPage = $request->get('per_page', 20);
        $remittances = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => RemittanceResource::collection($remittances),
            'meta' => [
                'current_page' => $remittances->currentPage(),
                'last_page' => $remittances->lastPage(),
                'per_page' => $remittances->perPage(),
                'total' => $remittances->total(),
                'search_query' => $searchTerm,
            ],
        ]);
    }
}
