<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Campus;
use App\Events\CandidateStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkOperationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Bulk update candidate status
     * AUDIT FIX: Added proper policy-based authorization
     */
    public function updateStatus(Request $request)
    {
        // AUDIT FIX: Proper authorization check using policy
        $this->authorize('bulkUpdateStatus', Candidate::class);

        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'exists:candidates,id',
            'status' => 'required|string|in:' . implode(',', array_keys(Candidate::STATUSES)),
        ]);

        $candidateIds = $request->candidate_ids;
        $newStatus = $request->status;
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $id) {
                $candidate = Candidate::find($id);

                if (!$candidate) {
                    $failedCount++;
                    continue;
                }

                // Check if user has permission for this candidate
                if (!$this->canModifyCandidate($candidate)) {
                    $errors[] = "No permission to modify {$candidate->name}";
                    $failedCount++;
                    continue;
                }

                // Validate state transition
                $transitionCheck = $candidate->canTransitionTo($newStatus);
                if (!$transitionCheck['can_transition']) {
                    $errorMsg = "{$candidate->name}: Cannot transition from {$candidate->status} to {$newStatus}";
                    if (!empty($transitionCheck['issues'])) {
                        $errorMsg .= " - " . implode(', ', $transitionCheck['issues']);
                    }
                    $errors[] = $errorMsg;
                    $failedCount++;
                    continue;
                }

                $oldStatus = $candidate->status;
                $candidate->updateStatus($newStatus);

                // Broadcast the update
                event(new CandidateStatusUpdated($candidate, $oldStatus, $newStatus));

                $successCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} candidates updated successfully" .
                            ($failedCount > 0 ? ", {$failedCount} failed" : ''),
                'updated' => $successCount,
                'failed' => $failedCount,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk status update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign candidates to batch
     * AUDIT FIX: Added proper policy-based authorization
     */
    public function assignToBatch(Request $request)
    {
        // AUDIT FIX: Proper authorization check using policy
        $this->authorize('bulkAssignBatch', Candidate::class);

        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'exists:candidates,id',
            'batch_id' => 'required|exists:batches,id',
        ]);

        $batch = Batch::findOrFail($request->batch_id);
        $candidateIds = $request->candidate_ids;
        $successCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $id) {
                $candidate = Candidate::find($id);

                if (!$candidate || !$this->canModifyCandidate($candidate)) {
                    $errors[] = "Cannot modify candidate ID {$id}";
                    continue;
                }

                // Check batch capacity
                if ($batch->candidates()->count() >= $batch->capacity) {
                    $errors[] = "Batch {$batch->name} is at full capacity";
                    break;
                }

                $candidate->update(['batch_id' => $batch->id]);
                $successCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} candidates assigned to {$batch->name}",
                'updated' => $successCount,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Batch assignment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign candidates to campus
     * AUDIT FIX: Added proper policy-based authorization
     */
    public function assignToCampus(Request $request)
    {
        // AUDIT FIX: Proper authorization check using policy
        $this->authorize('bulkAssignCampus', Candidate::class);

        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'exists:candidates,id',
            'campus_id' => 'required|exists:campuses,id',
        ]);

        $campus = Campus::findOrFail($request->campus_id);
        $candidateIds = $request->candidate_ids;
        $successCount = 0;

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $id) {
                $candidate = Candidate::find($id);

                if (!$candidate || !$this->canModifyCandidate($candidate)) {
                    continue;
                }

                $candidate->update([
                    'campus_id' => $campus->id,
                    'batch_id' => null, // Clear batch when changing campus
                ]);
                $successCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$successCount} candidates assigned to {$campus->name}",
                'updated' => $successCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Campus assignment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk export candidates
     */
    public function export(Request $request)
    {
        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:500',
            'candidate_ids.*' => 'exists:candidates,id',
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $candidates = Candidate::with(['campus', 'trade', 'batch', 'oep'])
            ->whereIn('id', $request->candidate_ids)
            ->get();

        // Filter by user permissions
        $candidates = $candidates->filter(fn($c) => $this->canViewCandidate($c));

        if ($candidates->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No candidates available for export',
            ], 400);
        }

        // Generate export based on format
        $format = $request->format;

        return response()->json([
            'success' => true,
            'message' => "Export prepared for {$candidates->count()} candidates",
            'download_url' => route('candidates.export.download', [
                'ids' => implode(',', $candidates->pluck('id')->toArray()),
                'format' => $format,
            ]),
        ]);
    }

    /**
     * Bulk delete candidates (soft delete)
     * AUDIT FIX: Added proper policy-based authorization
     */
    public function delete(Request $request)
    {
        // AUDIT FIX: Proper authorization check using policy
        $this->authorize('bulkDelete', Candidate::class);

        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:50',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        $candidateIds = $request->candidate_ids;
        $successCount = 0;

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $id) {
                $candidate = Candidate::find($id);

                if (!$candidate) {
                    continue;
                }

                // Don't delete departed candidates
                if ($candidate->status === 'departed') {
                    continue;
                }

                $candidate->delete();
                $successCount++;
            }

            DB::commit();

            activity()
                ->causedBy(auth()->user())
                ->log("Bulk deleted {$successCount} candidates");

            return response()->json([
                'success' => true,
                'message' => "{$successCount} candidates deleted successfully",
                'deleted' => $successCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Bulk delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send bulk notifications
     * AUDIT FIX: Implemented actual notification sending
     */
    public function sendNotification(Request $request)
    {
        // AUDIT FIX: Proper authorization check using policy
        $this->authorize('bulkNotify', Candidate::class);

        $request->validate([
            'candidate_ids' => 'required|array|min:1|max:100',
            'candidate_ids.*' => 'exists:candidates,id',
            'notification_type' => 'required|in:sms,email,both',
            'message' => 'required|string|max:500',
            'subject' => 'required_if:notification_type,email,both|string|max:200',
        ]);

        $candidates = Candidate::whereIn('id', $request->candidate_ids)->get();

        // AUDIT FIX: Filter by user's campus if not super admin
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isProjectDirector()) {
            if ($user->campus_id) {
                $candidates = $candidates->filter(fn($c) => $c->campus_id === $user->campus_id);
            }
        }

        $sent = 0;
        $failed = 0;
        $errors = [];
        $notificationService = app(\App\Services\NotificationService::class);

        foreach ($candidates as $candidate) {
            try {
                // Prepare notification data
                $notificationData = [
                    'subject' => $request->subject ?? 'BTEVTA Notification',
                    'message' => $request->message,
                    'candidate_name' => $candidate->name,
                    'candidate_id' => $candidate->btevta_id,
                ];

                $channels = [];
                if ($request->notification_type === 'email' || $request->notification_type === 'both') {
                    if ($candidate->email) {
                        $channels[] = 'email';
                    }
                }
                if ($request->notification_type === 'sms' || $request->notification_type === 'both') {
                    if ($candidate->phone) {
                        $channels[] = 'sms';
                    }
                }

                if (!empty($channels)) {
                    $notificationService->send(
                        $candidate,
                        'bulk_notification',
                        $notificationData,
                        $channels
                    );
                    $sent++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Failed for {$candidate->name}: {$e->getMessage()}";
                Log::warning('Bulk notification failed', [
                    'candidate_id' => $candidate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log the bulk operation
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'type' => $request->notification_type,
                'sent' => $sent,
                'failed' => $failed,
                'candidate_count' => $candidates->count(),
            ])
            ->log('Bulk notifications sent');

        return response()->json([
            'success' => $sent > 0,
            'message' => "{$sent} notifications sent" . ($failed > 0 ? ", {$failed} failed" : ''),
            'sent' => $sent,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 10), // Limit errors returned
        ]);
    }

    /**
     * Check if user can modify the candidate
     */
    private function canModifyCandidate(Candidate $candidate): bool
    {
        $user = auth()->user();

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->role === 'campus_admin' && $user->campus_id === $candidate->campus_id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can view the candidate
     */
    private function canViewCandidate(Candidate $candidate): bool
    {
        $user = auth()->user();

        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return true;
        }

        if ($user->campus_id === $candidate->campus_id) {
            return true;
        }

        if ($user->oep_id === $candidate->oep_id) {
            return true;
        }

        return false;
    }
}
