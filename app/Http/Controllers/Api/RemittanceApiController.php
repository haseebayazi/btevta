<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remittance;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RemittanceApiController extends Controller
{
    /**
     * Get all remittances (paginated)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Remittance::class);

        $query = Remittance::with(['candidate', 'departure', 'recordedBy']);

        // Apply filters
        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        // Role-based filtering
        $user = Auth::user();
        if ($user->role === 'candidate') {
            $query->whereHas('candidate', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'campus_admin') {
            // Campus admins can only see remittances for candidates at their campus
            $query->whereHas('candidate', function($q) use ($user) {
                $q->where('campus_id', $user->campus_id);
            });
        }

        $perPage = $request->input('per_page', 20);
        $remittances = $query->orderBy('transfer_date', 'desc')->paginate($perPage);

        return response()->json($remittances);
    }

    /**
     * Get remittance by ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $remittance = Remittance::with([
            'candidate',
            'departure',
            'recordedBy',
            'verifiedBy',
            'receipts.uploadedBy',
            'usageBreakdown'
        ])->find($id);

        if (!$remittance) {
            return response()->json(['error' => 'Remittance not found'], 404);
        }

        $this->authorize('view', $remittance);

        return response()->json($remittance);
    }

    /**
     * Get remittances by candidate ID
     * AUDIT FIX: Added proper candidate-level authorization to prevent cross-campus data access
     *
     * @param int $candidateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCandidate($candidateId)
    {
        $this->authorize('viewAny', Remittance::class);

        $candidate = Candidate::find($candidateId);

        if (!$candidate) {
            return response()->json(['error' => 'Candidate not found'], 404);
        }

        // AUDIT FIX: Verify user has access to this specific candidate
        // This prevents cross-campus data leakage
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector()) {
            // Campus admins can only view remittances for their campus
            if ($user->role === 'campus_admin' && $user->campus_id !== $candidate->campus_id) {
                return response()->json([
                    'error' => 'Unauthorized: You do not have access to this candidate\'s remittances'
                ], 403);
            }
            // OEP users can only view remittances for their OEP's candidates
            if ($user->role === 'oep' && $user->oep_id !== $candidate->oep_id) {
                return response()->json([
                    'error' => 'Unauthorized: You do not have access to this candidate\'s remittances'
                ], 403);
            }
        }

        $remittances = Remittance::where('candidate_id', $candidateId)
            ->with(['departure', 'recordedBy'])
            ->orderBy('transfer_date', 'desc')
            ->get();

        return response()->json([
            'candidate' => $candidate,
            'remittances' => $remittances,
            'summary' => [
                'total_count' => $remittances->count(),
                'total_amount' => $remittances->sum('amount'),
                'average_amount' => $remittances->avg('amount'),
                'latest_remittance' => $remittances->first(),
            ],
        ]);
    }

    /**
     * Create a new remittance
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Remittance::class);

        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
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

        return response()->json([
            'message' => 'Remittance created successfully',
            'remittance' => $remittance->load(['candidate', 'departure']),
        ], 201);
    }

    /**
     * Update a remittance
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $remittance = Remittance::find($id);

        if (!$remittance) {
            return response()->json(['error' => 'Remittance not found'], 404);
        }

        $this->authorize('update', $remittance);

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'sometimes|required|exists:candidates,id',
            'departure_id' => 'nullable|exists:departures,id',
            'transaction_reference' => 'sometimes|required|string|unique:remittances,transaction_reference,' . $id,
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'transfer_date' => 'sometimes|required|date',
            'sender_name' => 'sometimes|required|string',
            'receiver_name' => 'sometimes|required|string',
            'primary_purpose' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // AUDIT FIX: Validate candidate reassignment based on user role
        if (isset($validated['candidate_id']) && $validated['candidate_id'] != $remittance->candidate_id) {
            $user = Auth::user();
            $newCandidate = \App\Models\Candidate::find($validated['candidate_id']);

            if (!$newCandidate) {
                return response()->json(['error' => 'Target candidate not found'], 404);
            }

            // Campus admins can only reassign to candidates in their own campus
            if ($user->role === 'campus_admin' && $user->campus_id) {
                if ($newCandidate->campus_id != $user->campus_id) {
                    return response()->json([
                        'error' => 'You cannot reassign remittance to a candidate outside your campus'
                    ], 403);
                }
            }

            // OEP users can only reassign to candidates in their own OEP
            if ($user->role === 'oep' && $user->oep_id) {
                if ($newCandidate->oep_id != $user->oep_id) {
                    return response()->json([
                        'error' => 'You cannot reassign remittance to a candidate outside your OEP'
                    ], 403);
                }
            }
        }

        $remittance->update($validated);

        // Recalculate month number if departure changed
        if ($remittance->departure_id) {
            $monthNumber = $remittance->calculateMonthNumber();
            $remittance->update(['month_number' => $monthNumber]);
        }

        return response()->json([
            'message' => 'Remittance updated successfully',
            'remittance' => $remittance->load(['candidate', 'departure']),
        ]);
    }

    /**
     * Delete a remittance
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $remittance = Remittance::find($id);

        if (!$remittance) {
            return response()->json(['error' => 'Remittance not found'], 404);
        }

        $this->authorize('delete', $remittance);

        $remittance->delete();

        return response()->json(['message' => 'Remittance deleted successfully']);
    }

    /**
     * Search remittances
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Remittance::class);

        $query = Remittance::with(['candidate', 'departure']);

        // Search by transaction reference
        if ($request->filled('transaction_reference')) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedRef = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->transaction_reference);
            $query->where('transaction_reference', 'like', '%' . $escapedRef . '%');
        }

        // Search by candidate name or CNIC
        if ($request->filled('candidate')) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedCandidate = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->candidate);
            $query->whereHas('candidate', function($q) use ($escapedCandidate) {
                $q->where('full_name', 'like', '%' . $escapedCandidate . '%')
                  ->orWhere('cnic', 'like', '%' . $escapedCandidate . '%');
            });
        }

        // Search by amount range
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        $results = $query->orderBy('transfer_date', 'desc')->limit(50)->get();

        return response()->json([
            'count' => $results->count(),
            'results' => $results,
        ]);
    }

    /**
     * Get remittance statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics()
    {
        $this->authorize('viewAny', Remittance::class);

        $stats = [
            'total_remittances' => Remittance::count(),
            'total_amount' => Remittance::sum('amount'),
            'average_amount' => Remittance::avg('amount'),
            'total_candidates' => Remittance::distinct('candidate_id')->count(),
            'with_proof' => Remittance::where('has_proof', true)->count(),
            'proof_compliance_rate' => Remittance::count() > 0
                ? round((Remittance::where('has_proof', true)->count() / Remittance::count()) * 100, 2)
                : 0,
            'by_status' => Remittance::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'current_year' => [
                'count' => Remittance::where('year', date('Y'))->count(),
                'amount' => Remittance::where('year', date('Y'))->sum('amount'),
            ],
            'current_month' => [
                'count' => Remittance::where('year', date('Y'))
                    ->where('month', date('n'))->count(),
                'amount' => Remittance::where('year', date('Y'))
                    ->where('month', date('n'))->sum('amount'),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Verify a remittance
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify($id)
    {
        $remittance = Remittance::find($id);

        if (!$remittance) {
            return response()->json(['error' => 'Remittance not found'], 404);
        }

        $this->authorize('verify', $remittance);

        // Prevent duplicate verification
        if ($remittance->status === 'verified') {
            return response()->json([
                'error' => 'Remittance is already verified',
                'verified_by' => $remittance->verifiedBy?->name,
                'verified_date' => $remittance->proof_verified_date,
            ], 400);
        }

        $remittance->markAsVerified(Auth::id());

        return response()->json([
            'message' => 'Remittance verified successfully',
            'remittance' => $remittance,
        ]);
    }
}
