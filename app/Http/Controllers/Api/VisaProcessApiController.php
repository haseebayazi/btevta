<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisaProcess;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * AUDIT FIX: API-001 - Added missing VisaProcess API endpoints
 */
class VisaProcessApiController extends Controller
{
    /**
     * Get paginated list of visa processes
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', VisaProcess::class);

        $query = VisaProcess::with(['candidate:id,name,btevta_id,cnic', 'visaPartner:id,name']);

        // Filter by campus (for campus admins)
        if (auth()->user()->isCampusAdmin()) {
            $query->whereHas('candidate', function ($q) {
                $q->where('campus_id', auth()->user()->campus_id);
            });
        }

        // Filter by overall status
        if ($request->filled('status')) {
            $query->where('overall_status', $request->status);
        }

        // Filter by interview status
        if ($request->filled('interview_status')) {
            $query->where('interview_status', $request->interview_status);
        }

        // Filter by visa status
        if ($request->filled('visa_status')) {
            $query->where('visa_status', $request->visa_status);
        }

        $perPage = min($request->input('per_page', 20), 100);
        $visaProcesses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $visaProcesses,
        ]);
    }

    /**
     * Get single visa process details
     */
    public function show(int $id): JsonResponse
    {
        $visaProcess = VisaProcess::with(['candidate', 'visaPartner'])->findOrFail($id);
        $this->authorize('view', $visaProcess);

        return response()->json([
            'success' => true,
            'data' => $visaProcess,
        ]);
    }

    /**
     * Create visa process for candidate
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', VisaProcess::class);

        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|exists:candidates,id',
            'visa_partner_id' => 'nullable|exists:visa_partners,id',
            'interview_date' => 'nullable|date',
            'interview_status' => 'nullable|in:pending,passed,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check candidate is eligible for visa processing
        $candidate = Candidate::findOrFail($request->candidate_id);
        if (!in_array($candidate->status, [Candidate::STATUS_TRAINING, Candidate::STATUS_VISA_PROCESS])) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate must complete training before visa processing',
            ], 422);
        }

        // Check if visa process already exists
        if ($candidate->visaProcess) {
            return response()->json([
                'success' => false,
                'message' => 'Visa process already exists for this candidate',
            ], 422);
        }

        $data = $validator->validated();
        $data['overall_status'] = 'interview';
        $data['created_by'] = auth()->id();

        // AUDIT FIX: Wrap visa process creation and status update in transaction
        try {
            DB::beginTransaction();

            $visaProcess = VisaProcess::create($data);

            // Update candidate status
            $candidate->update(['status' => Candidate::STATUS_VISA_PROCESS]);

            DB::commit();

            activity()
                ->performedOn($visaProcess)
                ->causedBy(auth()->user())
                ->log('Visa process created via API');

            return response()->json([
                'success' => true,
                'message' => 'Visa process created successfully',
                'data' => $visaProcess,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create visa process: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update visa process
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $visaProcess = VisaProcess::findOrFail($id);
        $this->authorize('update', $visaProcess);

        $validator = Validator::make($request->all(), [
            'interview_date' => 'nullable|date',
            'interview_status' => 'nullable|in:pending,passed,failed',
            'interview_remarks' => 'nullable|string|max:1000',
            'takamol_date' => 'nullable|date',
            'takamol_status' => 'nullable|in:pending,passed,failed',
            'medical_date' => 'nullable|date',
            'medical_status' => 'nullable|in:pending,fit,unfit',
            'biometric_date' => 'nullable|date',
            'biometric_status' => 'nullable|in:pending,completed,failed',
            'enumber' => 'nullable|string|max:50',
            'enumber_status' => 'nullable|in:pending,generated,verified',
            'visa_status' => 'nullable|in:pending,issued,rejected',
            'visa_date' => 'nullable|date',
            'visa_number' => 'nullable|string|max:50',
            'ticket_date' => 'nullable|date',
            'overall_status' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $visaProcess->update($validator->validated());

        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Visa process updated via API');

        return response()->json([
            'success' => true,
            'message' => 'Visa process updated successfully',
            'data' => $visaProcess->fresh(),
        ]);
    }

    /**
     * Get visa process statistics
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', VisaProcess::class);

        $stats = VisaProcess::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN overall_status = "interview" THEN 1 ELSE 0 END) as interview_stage,
            SUM(CASE WHEN overall_status = "takamol" THEN 1 ELSE 0 END) as takamol_stage,
            SUM(CASE WHEN overall_status = "medical" THEN 1 ELSE 0 END) as medical_stage,
            SUM(CASE WHEN overall_status = "biometric" THEN 1 ELSE 0 END) as biometric_stage,
            SUM(CASE WHEN overall_status = "visa" THEN 1 ELSE 0 END) as visa_stage,
            SUM(CASE WHEN overall_status = "completed" THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN visa_status = "issued" THEN 1 ELSE 0 END) as visas_issued,
            SUM(CASE WHEN interview_status = "passed" THEN 1 ELSE 0 END) as interviews_passed
        ')->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get visa process by candidate
     */
    public function byCandidate(int $candidateId): JsonResponse
    {
        $candidate = Candidate::findOrFail($candidateId);
        $this->authorize('view', $candidate);

        $visaProcess = VisaProcess::where('candidate_id', $candidateId)->first();

        return response()->json([
            'success' => true,
            'data' => $visaProcess,
        ]);
    }
}
