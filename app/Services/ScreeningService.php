<?php

namespace App\Services;

use App\Models\CandidateScreening;
use App\Models\Candidate;
use App\Models\Undertaking;
use App\Enums\CandidateStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScreeningService
{
    /**
     * Generate undertaking content
     */
    public function generateUndertakingContent($candidate): string
    {
        return "
            UNDERTAKING

            I, {$candidate->name}, S/O / D/O {$candidate->father_name}, 
            CNIC: {$candidate->formatted_cnic}, resident of {$candidate->address}, 
            District {$candidate->district}, hereby undertake that:

            1. All information provided by me during the screening and registration process is true and correct.
            2. I have not concealed any information that may affect my candidature.
            3. I will abide by all rules and regulations of the training program.
            4. I understand that any false information may lead to cancellation of my candidature.
            5. I will maintain discipline during the training period.
            6. I will not engage in any political or illegal activities.
            7. I will respect the cultural norms of the host country upon deployment.
            8. I understand that the training and deployment are subject to successful completion of all requirements.
            9. I will return to Pakistan upon completion of my contract period.
            10. I will maintain regular contact with my family and the relevant authorities.

            Date: " . now()->format('d-m-Y') . "
            
            Candidate Signature: _________________
            
            Witness 1: _________________
            Name:
            CNIC:
            
            Witness 2: _________________
            Name:
            CNIC:
        ";
    }

    /**
     * Get call logs for a screening
     *
     * AUDIT FIX: Replaced fragile string parsing with proper field-based approach.
     * Previously parsed remarks text looking for 'Call' which was unreliable.
     * Now uses the dedicated call_1/2/3 fields from the 3-call workflow.
     */
    public function getCallLogs($screening): array
    {
        $logs = [];

        // Build logs from the dedicated call fields (3-call workflow)
        for ($i = 1; $i <= 3; $i++) {
            $atField = "call_{$i}_at";
            $outcomeField = "call_{$i}_outcome";
            $notesField = "call_{$i}_notes";
            $byField = "call_{$i}_by";

            if ($screening->$atField) {
                $logs[] = [
                    'call_number' => $i,
                    'timestamp' => Carbon::parse($screening->$atField),
                    'outcome' => $screening->$outcomeField ?? 'unknown',
                    'notes' => $screening->$notesField ?? '',
                    'by_user_id' => $screening->$byField ?? null,
                    'details' => sprintf(
                        'Call %d: %s at %s',
                        $i,
                        self::CALL_OUTCOMES[$screening->$outcomeField] ?? $screening->$outcomeField ?? 'Unknown',
                        Carbon::parse($screening->$atField)->format('Y-m-d H:i')
                    ),
                ];
            }
        }

        return $logs;
    }

    /**
     * Generate screening report
     */
    public function generateReport($filters = []): array
    {
        $query = CandidateScreening::query();

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('screened_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('screened_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['screening_type'])) {
            $query->where('screening_type', $filters['screening_type']);
        }

        $total = $query->count();
        $passed = clone $query;
        $failed = clone $query;

        return [
            'total_screenings' => $total,
            'passed' => $passed->where('status', 'passed')->count(),
            'failed' => $failed->where('status', 'failed')->count(),
            // BUG FIX: Correct clone syntax
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'by_type' => $this->getScreeningsByType($filters),
            'by_screener' => $this->getScreeningsByScreener($filters),
            'average_call_attempts' => $this->calculateAverageCallAttempts($filters),
            'daily_stats' => $this->getDailyStats($filters),
        ];
    }

    /**
     * Get screenings grouped by type
     */
    protected function getScreeningsByType($filters = []): \Illuminate\Support\Collection
    {
        return CandidateScreening::select('screening_type', DB::raw('count(*) as count'))
            ->when(!empty($filters['from_date']), function($q) use ($filters) {
                $q->whereDate('screened_at', '>=', $filters['from_date']);
            })
            ->groupBy('screening_type')
            ->pluck('count', 'screening_type');
    }

    /**
     * Get screenings grouped by screener
     */
    protected function getScreeningsByScreener($filters = []): \Illuminate\Support\Collection
    {
        return CandidateScreening::select('screened_by', DB::raw('count(*) as count'))
            ->with('screener:id,name')
            ->when(!empty($filters['from_date']), function($q) use ($filters) {
                $q->whereDate('screened_at', '>=', $filters['from_date']);
            })
            ->whereNotNull('screened_by')
            ->groupBy('screened_by')
            ->get()
            ->map(function($item) {
                return [
                    'screener' => $item->screener->name ?? 'Unknown',
                    'count' => $item->count
                ];
            });
    }

    /**
     * Calculate average call attempts
     */
    protected function calculateAverageCallAttempts($filters = []): float
    {
        $avg = CandidateScreening::where('screening_type', 'call')
            ->when(!empty($filters['from_date']), function($q) use ($filters) {
                $q->whereDate('screened_at', '>=', $filters['from_date']);
            })
            ->avg('call_count');
        
        return round($avg ?: 0, 2);
    }

    /**
     * Get daily statistics
     */
    protected function getDailyStats($filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $fromDate = $filters['from_date'] ?? Carbon::now()->subDays(30);
        $toDate = $filters['to_date'] ?? Carbon::now();

        return CandidateScreening::select(
                DB::raw('DATE(screened_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('SUM(CASE WHEN status = "passed" THEN 1 ELSE 0 END) as passed'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->whereBetween('screened_at', [$fromDate, $toDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Auto-schedule next screening
     */
    public function scheduleNextScreening($candidate, $completedType): void
    {
        $sequence = [
            'desk' => 'call',
            'call' => 'physical',
            'physical' => null
        ];

        $nextType = $sequence[$completedType] ?? null;

        if ($nextType) {
            CandidateScreening::create([
                'candidate_id' => $candidate->id,
                'screening_type' => $nextType,
                'status' => 'pending',
                'remarks' => 'Auto-scheduled after ' . $completedType . ' screening'
            ]);
        }
    }

    /**
     * Check screening eligibility
     */
    public function checkEligibility($candidateId, $screeningType): array
    {
        $candidate = Candidate::find($candidateId);

        if (!$candidate) {
            return ['eligible' => false, 'reason' => 'Candidate not found'];
        }

        // Check prerequisites
        $prerequisites = [
            'desk' => [],
            'call' => ['desk'],
            'physical' => ['desk', 'call'],
            'medical' => ['desk', 'call', 'physical'],
        ];

        $required = $prerequisites[$screeningType] ?? [];

        foreach ($required as $type) {
            $passed = CandidateScreening::where('candidate_id', $candidateId)
                ->where('screening_type', $type)
                ->where('status', 'passed')
                ->exists();

            if (!$passed) {
                return [
                    'eligible' => false,
                    'reason' => "Prerequisite {$type} screening not completed"
                ];
            }
        }

        return ['eligible' => true];
    }

    // =========================================================================
    // 3-CALL WORKFLOW METHODS
    // =========================================================================

    /**
     * Call stage labels for display
     */
    const CALL_STAGES = [
        'pending' => 'Pending First Call',
        'call_1_document' => 'Call 1: Document Verification',
        'call_2_registration' => 'Call 2: Registration Reminder',
        'call_3_confirmation' => 'Call 3: Confirmation',
        'completed' => 'Completed',
        'unreachable' => 'Unreachable',
    ];

    /**
     * Call outcomes
     */
    const CALL_OUTCOMES = [
        'answered' => 'Answered',
        'no_answer' => 'No Answer',
        'busy' => 'Busy',
        'wrong_number' => 'Wrong Number',
        'switched_off' => 'Phone Switched Off',
        'not_reachable' => 'Not Reachable',
    ];

    /**
     * Record a call attempt in the 3-call workflow.
     *
     * @param CandidateScreening $screening
     * @param int $callNumber (1, 2, or 3)
     * @param array $callData ['outcome' => string, 'response' => string, 'notes' => string]
     * @return CandidateScreening
     */
    public function recordCallAttempt(CandidateScreening $screening, int $callNumber, array $callData): CandidateScreening
    {
        if ($callNumber < 1 || $callNumber > 3) {
            throw new \InvalidArgumentException('Call number must be 1, 2, or 3');
        }

        $prefix = "call_{$callNumber}";
        $userId = auth()->id();

        // Update call fields
        $screening->{$prefix . '_at'} = now();
        $screening->{$prefix . '_outcome'} = $callData['outcome'];
        $screening->{$prefix . '_response'} = $callData['response'] ?? null;
        $screening->{$prefix . '_notes'} = $callData['notes'] ?? null;
        $screening->{$prefix . '_by'} = $userId;

        // Increment call attempts
        $screening->total_call_attempts = ($screening->total_call_attempts ?? 0) + 1;

        // Update call stage based on outcome
        $screening->call_stage = $this->determineNextCallStage($screening, $callNumber, $callData);

        // Handle callback scheduling
        if (($callData['response'] ?? '') === 'callback_requested' && !empty($callData['callback_at'])) {
            $screening->callback_scheduled_at = $callData['callback_at'];
            $screening->callback_reason = $callData['callback_reason'] ?? 'Candidate requested callback';
        }

        // Handle appointment scheduling (Call 3)
        if ($callNumber === 3 && ($callData['response'] ?? '') === 'confirmed') {
            $screening->registration_appointment_at = $callData['appointment_at'] ?? now()->addDays(2);
            $screening->registration_appointment_campus = $callData['appointment_campus'] ?? null;
        }

        // Determine final outcome
        $screening->final_outcome = $this->determineFinalOutcome($screening, $callNumber, $callData);

        $screening->save();

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($screening)
            ->withProperties([
                'call_number' => $callNumber,
                'outcome' => $callData['outcome'],
                'response' => $callData['response'] ?? null,
            ])
            ->log("Recorded call {$callNumber} attempt");

        return $screening;
    }

    /**
     * Determine the next call stage based on call outcome.
     */
    protected function determineNextCallStage(CandidateScreening $screening, int $callNumber, array $callData): string
    {
        $outcome = $callData['outcome'];
        $response = $callData['response'] ?? null;

        // If unreachable after max attempts
        if (in_array($outcome, ['wrong_number', 'switched_off']) && $screening->total_call_attempts >= 5) {
            return 'unreachable';
        }

        // If candidate not interested at any stage
        if ($response === 'not_interested') {
            return 'completed';
        }

        // If answered, progress to next stage
        if ($outcome === 'answered') {
            return match($callNumber) {
                1 => 'call_2_registration',
                2 => 'call_3_confirmation',
                3 => 'completed',
                default => $screening->call_stage,
            };
        }

        // If not answered, stay at current stage (will retry)
        return match($callNumber) {
            1 => 'call_1_document',
            2 => 'call_2_registration',
            3 => 'call_3_confirmation',
            default => $screening->call_stage,
        };
    }

    /**
     * Determine final outcome based on call workflow.
     */
    protected function determineFinalOutcome(CandidateScreening $screening, int $callNumber, array $callData): string
    {
        $response = $callData['response'] ?? null;
        $outcome = $callData['outcome'];

        // Explicit not interested
        if ($response === 'not_interested') {
            return 'not_interested';
        }

        // Cancelled appointment
        if ($response === 'cancelled') {
            return 'not_interested';
        }

        // Wrong number or unreachable after multiple attempts
        if ($outcome === 'wrong_number') {
            return 'unreachable';
        }

        if (in_array($outcome, ['switched_off', 'not_reachable']) && $screening->total_call_attempts >= 5) {
            return 'unreachable';
        }

        // Successfully confirmed
        if ($callNumber === 3 && $response === 'confirmed') {
            return 'registered';
        }

        // Rescheduled
        if ($response === 'rescheduled') {
            return 'postponed';
        }

        return 'pending';
    }

    /**
     * Get candidates pending callback.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingCallbacks(): \Illuminate\Database\Eloquent\Collection
    {
        return CandidateScreening::with('candidate')
            ->whereNotNull('callback_scheduled_at')
            ->where('callback_scheduled_at', '<=', now())
            ->whereIn('call_stage', ['call_1_document', 'call_2_registration', 'call_3_confirmation'])
            ->orderBy('callback_scheduled_at')
            ->get();
    }

    /**
     * Get candidates at each call stage.
     *
     * @return array
     */
    public function getCallStageStatistics(): array
    {
        return CandidateScreening::select('call_stage', DB::raw('COUNT(*) as count'))
            ->groupBy('call_stage')
            ->pluck('count', 'call_stage')
            ->toArray();
    }

    /**
     * Get call success rates by stage.
     *
     * @return array
     */
    public function getCallSuccessRates(): array
    {
        $stats = [];

        for ($i = 1; $i <= 3; $i++) {
            $total = CandidateScreening::whereNotNull("call_{$i}_at")->count();
            $answered = CandidateScreening::where("call_{$i}_outcome", 'answered')->count();

            $stats["call_{$i}"] = [
                'total' => $total,
                'answered' => $answered,
                'success_rate' => $total > 0 ? round(($answered / $total) * 100, 1) : 0,
            ];
        }

        return $stats;
    }

    /**
     * Get candidates pending registration appointment.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingAppointments(): \Illuminate\Database\Eloquent\Collection
    {
        return CandidateScreening::with('candidate')
            ->whereNotNull('registration_appointment_at')
            ->where('registration_appointment_at', '>=', now())
            ->where('final_outcome', '!=', 'registered')
            ->orderBy('registration_appointment_at')
            ->get();
    }

    /**
     * Get today's scheduled appointments.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodaysAppointments(): \Illuminate\Database\Eloquent\Collection
    {
        return CandidateScreening::with('candidate')
            ->whereNotNull('registration_appointment_at')
            ->whereDate('registration_appointment_at', today())
            ->orderBy('registration_appointment_at')
            ->get();
    }

    /**
     * Get response rate analytics.
     *
     * @param array $filters
     * @return array
     */
    public function getResponseRateAnalytics(array $filters = []): array
    {
        $query = CandidateScreening::query();

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $total = $query->count();
        $completed = (clone $query)->where('call_stage', 'completed')->count();
        $unreachable = (clone $query)->where('call_stage', 'unreachable')->count();
        $registered = (clone $query)->where('final_outcome', 'registered')->count();
        $notInterested = (clone $query)->where('final_outcome', 'not_interested')->count();

        return [
            'total_screenings' => $total,
            'completed' => $completed,
            'unreachable' => $unreachable,
            'registered' => $registered,
            'not_interested' => $notInterested,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'registration_rate' => $total > 0 ? round(($registered / $total) * 100, 1) : 0,
            'unreachable_rate' => $total > 0 ? round(($unreachable / $total) * 100, 1) : 0,
            'call_success_rates' => $this->getCallSuccessRates(),
        ];
    }

    /**
     * Bulk update call stage for candidates.
     *
     * @param array $screeningIds
     * @param string $stage
     * @return int Number of records updated
     */
    public function bulkUpdateCallStage(array $screeningIds, string $stage): int
    {
        return CandidateScreening::whereIn('id', $screeningIds)
            ->update(['call_stage' => $stage]);
    }

    /**
     * Get candidates who need follow-up calls.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCandidatesNeedingFollowUp(): \Illuminate\Database\Eloquent\Collection
    {
        return CandidateScreening::with('candidate')
            ->whereIn('call_stage', ['call_1_document', 'call_2_registration', 'call_3_confirmation'])
            ->where(function($query) {
                // No call in the last 24 hours
                $query->where('call_1_at', '<', now()->subDay())
                    ->orWhereNull('call_1_at');
            })
            ->where('final_outcome', 'pending')
            ->orderBy('created_at')
            ->get();
    }
}