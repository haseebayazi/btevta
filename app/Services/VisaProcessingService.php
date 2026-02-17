<?php

namespace App\Services;

use App\Models\VisaProcess;
use App\Models\Candidate;
use App\Models\Campus;
use App\Enums\CandidateStatus;
use App\Enums\VisaStage;
use App\Enums\VisaStageResult;
use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use App\ValueObjects\VisaStageDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VisaProcessingService
{
    /**
     * Visa processing stages
     */
    const STAGES = [
        'interview' => 'Interview',
        'trade_test' => 'Trade Test',
        'takamol' => 'Takamol Test',
        'medical' => 'Medical (GAMCA)',
        'biometrics' => 'Biometrics (Etimad)',
        'visa_applied' => 'Visa Applied',
        'ptn_issuance' => 'PTN Issuance',
        'ticket' => 'Ticket Booking',
        'completed' => 'Completed'
    ];

    /**
     * Get all visa processing stages
     */
    public function getStages()
    {
        return self::STAGES;
    }

    /**
     * Generate E-Number for candidate
     */
    public function generateEnumber($candidate)
    {
        // E-Number format: OEP-YEAR-SEQUENCE
        $year = date('Y');
        $oep = $candidate->oep ? $candidate->oep->code : 'OEP';
        
        $lastEnumber = VisaProcess::where('enumber', 'like', "{$oep}-{$year}-%")
            ->orderBy('enumber', 'desc')
            ->first();
        
        if ($lastEnumber) {
            $lastSequence = (int) substr($lastEnumber->enumber, -4);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('%s-%s-%04d', $oep, $year, $sequence);
    }

    /**
     * Generate PTN (Protector Number)
     */
    public function generatePTN($candidate)
    {
        // PTN format: PTN-YEAR-TRADE-SEQUENCE
        $year = date('Y');
        $tradeCode = $candidate->trade ? $candidate->trade->code : 'TRD';
        
        $lastPTN = VisaProcess::where('ptn_number', 'like', "PTN-{$year}-{$tradeCode}-%")
            ->orderBy('ptn_number', 'desc')
            ->first();
        
        if ($lastPTN) {
            $lastSequence = (int) substr($lastPTN->ptn_number, -5);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('PTN-%s-%s-%05d', $year, $tradeCode, $sequence);
    }

    /**
     * Schedule interview for candidate
     */
    public function scheduleInterview($candidateId, $data)
    {
        // Validate candidate exists first
        $candidate = Candidate::find($candidateId);
        if (!$candidate) {
            throw new \Exception("Candidate not found");
        }

        $visaProcess = VisaProcess::firstOrCreate(
            ['candidate_id' => $candidateId],
            [
                'overall_status' => 'interview',
                'interview_date' => $data['interview_date'],
                'interview_status' => 'scheduled',
                'interview_remarks' => $data['interview_notes'] ?? $data['interview_remarks'] ?? null,
            ]
        );

        if (!$visaProcess->wasRecentlyCreated) {
            $visaProcess->update([
                'interview_date' => $data['interview_date'],
                'interview_status' => 'scheduled',
                'interview_remarks' => $data['interview_notes'] ?? $data['interview_remarks'] ?? null,
            ]);
        }

        // Update candidate status
        $candidate->update(['status' => 'interview_scheduled']);

        return $visaProcess->fresh();
    }

    /**
     * Record interview result
     */
    public function recordInterviewResult($visaProcessId, $result, $remarks = null)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'interview_status' => $result,
            'interview_remarks' => $remarks,
        ]);

        // Update candidate status based on result with NULL CHECK
        if (!$visaProcess->candidate) {
            throw new \Exception("Visa process {$visaProcessId} has no associated candidate");
        }

        if ($result === 'pass') {
            $visaProcess->candidate->update(['status' => 'interview_passed']);
            $this->moveToNextStage($visaProcess, 'trade_test');
        } else {
            $visaProcess->candidate->update(['status' => 'interview_failed']);
        }

        return $visaProcess->fresh();
    }

    /**
     * Schedule Takamol test
     */
    public function scheduleTakamol($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'takamol_date' => $data['test_date'] ?? $data['booking_date'] ?? null,
            'takamol_status' => 'scheduled',
        ]);

        $this->updateStage($visaProcess, 'takamol');

        return $visaProcess->fresh();
    }

    /**
     * Upload Takamol result
     */
    public function uploadTakamolResult($visaProcessId, $file, $result, $score = null)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        // Store file
        $path = $file->store('visa/takamol', 'public');
        
        $visaProcess->update([
            'takamol_status' => $result,
        ]);

        if ($result === 'pass') {
            $this->moveToNextStage($visaProcess, 'medical');
        }

        return $visaProcess->fresh();
    }

    /**
     * Schedule GAMCA medical
     */
    public function scheduleGAMCA($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'medical_date' => $data['test_date'] ?? $data['booking_date'] ?? null,
            'medical_status' => 'scheduled',
        ]);

        $this->updateStage($visaProcess, 'medical');

        return $visaProcess->fresh();
    }

    /**
     * Upload GAMCA certificate
     */
    public function uploadGAMCACertificate($visaProcessId, $file, $result, $expiryDate = null)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        // Store file
        $path = $file->store('visa/gamca', 'public');
        
        $visaProcess->update([
            'medical_status' => $result,
        ]);

        if ($result === 'fit') {
            $this->moveToNextStage($visaProcess, 'biometrics');
        }

        return $visaProcess->fresh();
    }

    /**
     * Schedule Etimad biometrics appointment
     */
    public function scheduleEtimad($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $appointmentId = $data['appointment_id'] ?? $this->generateEtimadAppointmentId();

        $visaProcess->update([
            'etimad_appointment_id' => $appointmentId,
            'biometric_date' => $data['appointment_date'] ?? null,
            'biometric_status' => 'scheduled',
        ]);

        $this->updateStage($visaProcess, 'biometrics');

        return $visaProcess->fresh();
    }

    /**
     * Generate Etimad appointment ID
     * AUDIT FIX: Replaced uniqid() with cryptographically secure random bytes
     * uniqid() is time-based and predictable, making IDs guessable
     */
    private function generateEtimadAppointmentId()
    {
        // Use cryptographically secure random bytes instead of predictable uniqid()
        $randomPart = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
        return 'ETM-' . date('Ymd') . '-' . $randomPart;
    }

    /**
     * Record biometrics completion
     */
    public function recordBiometricsCompletion($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'biometric_completed' => true,
            'biometric_status' => 'completed',
        ]);

        $this->moveToNextStage($visaProcess, 'visa_applied');

        return $visaProcess->fresh();
    }

    /**
     * Record visa document submission
     */
    public function recordVisaSubmission($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'visa_status' => 'applied',
            'overall_status' => 'visa_applied',
        ]);

        return $visaProcess->fresh();
    }

    /**
     * Record PTN issuance
     */
    public function recordPTNIssuance($visaProcessId, $ptnNumber = null, $issueDate = null)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        if (!$ptnNumber) {
            $ptnNumber = $this->generatePTN($visaProcess->candidate);
        }
        
        $visaProcess->update([
            'ptn_number' => $ptnNumber,
            'ptn_issue_date' => $issueDate ?? now(),
            'visa_status' => 'approved',
        ]);

        $this->moveToNextStage($visaProcess, 'ptn_issuance');

        return $visaProcess;
    }

    /**
     * Upload travel plan/ticket
     */
    public function uploadTravelPlan($visaProcessId, $file, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        // Store file
        $path = $file->store('visa/travel', 'public');
        
        $visaProcess->update([
            'travel_plan_path' => $path,
        ]);

        $this->moveToNextStage($visaProcess, 'ticket');
        $this->completeVisaProcess($visaProcess->id);

        return $visaProcess;
    }

    /**
     * Move to next stage
     */
    private function moveToNextStage($visaProcess, $stage)
    {
        $visaProcess->update([
            'overall_status' => $stage,
        ]);

        // Log stage transition
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log("Visa process moved to stage: {$stage}");
    }

    /**
     * Update current stage
     */
    private function updateStage($visaProcess, $stage)
    {
        $visaProcess->update(['overall_status' => $stage]);
    }

    /**
     * Calculate visa processing timeline
     */
    public function calculateTimeline($visaProcessId)
    {
        $visaProcess = VisaProcess::with('candidate')->findOrFail($visaProcessId);

        $timeline = [];
        $stages = [
            'interview_date' => 'Interview',
            'trade_test_date' => 'Trade Test',
            'takamol_date' => 'Takamol Test',
            'medical_date' => 'Medical (GAMCA)',
            'biometric_date' => 'Biometrics',
            'visa_date' => 'Visa Applied',
            'ticket_date' => 'Ticket Booking',
        ];

        $startDate = $visaProcess->created_at;

        foreach ($stages as $field => $label) {
            if ($visaProcess->$field) {
                $date = Carbon::parse($visaProcess->$field);
                $daysFromStart = $startDate->diffInDays($date);

                $timeline[] = [
                    'stage' => $label,
                    'date' => $date->format('Y-m-d'),
                    'days_from_start' => $daysFromStart,
                    'status' => 'completed',
                ];
            }
        }

        return [
            'timeline' => $timeline,
            'total_days' => $startDate->diffInDays(now()),
            'completed_stages' => count($timeline),
            'total_stages' => count($stages),
        ];
    }

    /**
     * Get visa processing statistics
     */
    public function getStatistics($filters = [])
    {
        $query = VisaProcess::query();

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['oep_id'])) {
            $query->whereHas('candidate', function($q) use ($filters) {
                $q->where('oep_id', $filters['oep_id']);
            });
        }

        $total = $query->count();

        return [
            'total_processes' => $total,
            'completed' => (clone $query)->where('overall_status', 'completed')->count(),
            'in_progress' => (clone $query)->where('overall_status', '!=', 'completed')->count(),
            'interview_stage' => (clone $query)->where('overall_status', 'interview')->count(),
            'takamol_stage' => (clone $query)->where('overall_status', 'takamol')->count(),
            'medical_stage' => (clone $query)->where('overall_status', 'medical')->count(),
            'biometrics_stage' => (clone $query)->where('overall_status', 'biometrics')->count(),
            'visa_applied_stage' => (clone $query)->where('overall_status', 'visa_applied')->count(),
            'ptn_issued' => (clone $query)->whereNotNull('ptn_number')->count(),
            'average_processing_days' => $this->calculateAverageProcessingDays($query),
        ];
    }

    /**
     * Calculate average processing days
     */
    private function calculateAverageProcessingDays($query)
    {
        $completed = (clone $query)
            ->where('status', 'completed')
            ->whereNotNull('completion_date')
            ->get();

        if ($completed->isEmpty()) {
            return 0;
        }

        $totalDays = 0;
        foreach ($completed as $process) {
            $totalDays += Carbon::parse($process->created_at)->diffInDays($process->completion_date);
        }

        return round($totalDays / $completed->count(), 1);
    }

    /**
     * Get pending medical/biometric candidates
     */
    public function getPendingMedicalBiometric()
    {
        return VisaProcess::with('candidate')
            ->whereIn('overall_status', ['medical', 'biometrics'])
            ->get();
    }

    /**
     * Check document expiry alerts
     */
    public function getExpiringDocuments($days = 30)
    {
        // Note: gamca_expiry_date and passport_expiry_date columns don't exist in visa_processes table
        // This method returns empty collection until schema is updated
        return collect();
    }

    /**
     * Create new visa process for candidate
     * CRITICAL FIX: This method was missing, causing store() to fail
     */
    public function createVisaProcess($candidateId, $data)
    {
        // Use DB::transaction() closure to properly support nested transactions/savepoints
        return DB::transaction(function () use ($candidateId, $data) {
            // Create visa process record
            $visaProcess = VisaProcess::create([
                'candidate_id' => $candidateId,
                'interview_date' => $data['interview_date'] ?? null,
                'interview_status' => $data['interview_status'] ?? 'pending',
                'interview_remarks' => $data['interview_remarks'] ?? null,
                'overall_status' => VisaStage::INITIATED->value,
            ]);

            // Update candidate status to VISA_PROCESS
            $candidate = Candidate::findOrFail($candidateId);
            $candidate->update(['status' => CandidateStatus::VISA_PROCESS->value]);

            // Log activity
            activity()
                ->performedOn($visaProcess)
                ->causedBy(auth()->user())
                ->log('Visa process initiated');

            return $visaProcess;
        });
    }

    /**
     * Update visa process
     * CRITICAL FIX: This method was missing, causing update() to fail
     */
    public function updateVisaProcess($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update($data);

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Visa process updated');

        return $visaProcess;
    }

    /**
     * Update interview stage
     * CRITICAL FIX: This method was missing, causing updateInterview() to fail
     */
    public function updateInterview($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'interview_date' => $data['interview_date'],
            'interview_status' => $data['interview_status'],
            'interview_remarks' => $data['interview_remarks'] ?? null,
            'interview_completed' => $data['interview_status'] === 'passed',
        ]);

        // Update overall status if interview passed
        if ($data['interview_status'] === 'passed') {
            $visaProcess->update(['overall_status' => 'interview_completed']);
        }

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Interview details updated');

        return $visaProcess;
    }

    /**
     * Update trade test stage
     * CRITICAL FIX: This method was missing, causing updateTradeTest() to fail
     */
    public function updateTradeTest($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'trade_test_date' => $data['trade_test_date'],
            'trade_test_status' => $data['trade_test_status'],
            'trade_test_remarks' => $data['trade_test_remarks'] ?? null,
            'trade_test_completed' => $data['trade_test_status'] === 'passed',
        ]);

        // Update overall status if trade test passed
        if ($data['trade_test_status'] === 'passed') {
            $visaProcess->update(['overall_status' => 'trade_test_completed']);
        }

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Trade test details updated');

        return $visaProcess;
    }

    /**
     * Update Takamol stage
     * CRITICAL FIX: This method was missing, causing updateTakamol() to fail
     */
    public function updateTakamol($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'takamol_date' => $data['takamol_date'],
            'takamol_status' => $data['takamol_status'],
            'takamol_remarks' => $data['takamol_remarks'] ?? null,
        ]);

        // Update overall status if Takamol completed
        if ($data['takamol_status'] === 'completed') {
            $visaProcess->update(['overall_status' => 'takamol_completed']);
        }

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Takamol details updated');

        return $visaProcess;
    }

    /**
     * Update medical stage
     * CRITICAL FIX: This method was missing, causing updateMedical() to fail
     */
    public function updateMedical($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'medical_date' => $data['medical_date'],
            'medical_status' => $data['medical_status'],
            'medical_remarks' => $data['medical_remarks'] ?? null,
            'medical_completed' => $data['medical_status'] === 'fit',
        ]);

        // Update overall status if medical fit
        if ($data['medical_status'] === 'fit') {
            $visaProcess->update(['overall_status' => 'medical_completed']);
        }

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Medical details updated');

        return $visaProcess;
    }

    /**
     * Update biometric stage
     * CRITICAL FIX: This method was missing, causing updateBiometric() to fail
     */
    public function updateBiometric($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'biometric_date' => $data['biometric_date'],
            'biometric_status' => $data['biometric_status'],
            'biometric_remarks' => $data['biometric_remarks'] ?? null,
            'biometric_completed' => $data['biometric_status'] === 'completed',
        ]);

        // Update overall status if biometric completed
        if ($data['biometric_status'] === 'completed') {
            $visaProcess->update(['overall_status' => 'biometric_completed']);
        }

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Biometric details updated');

        return $visaProcess;
    }

    /**
     * Update visa issuance
     * CRITICAL FIX: This method was missing, causing updateVisa() to fail
     */
    public function updateVisaIssuance($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'visa_date' => $data['visa_date'],
            'visa_number' => $data['visa_number'],
            'visa_status' => $data['visa_status'],
            'visa_remarks' => $data['visa_remarks'] ?? null,
            'visa_issued' => $data['visa_status'] === 'issued',
        ]);

        // Update overall status if visa issued
        if ($data['visa_status'] === 'issued') {
            $visaProcess->update(['overall_status' => 'visa_issued']);
        }

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Visa issuance details updated');

        return $visaProcess;
    }

    /**
     * Upload ticket
     * CRITICAL FIX: This method was missing, causing uploadTicket() to fail
     */
    public function uploadTicket($visaProcessId, $file, $ticketDate)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        // Store ticket file
        $path = $file->store('visa/tickets', 'public');

        $visaProcess->update([
            'ticket_path' => $path,
            'ticket_date' => $ticketDate,
            'ticket_uploaded' => true,
        ]);

        // Update overall status
        $visaProcess->update(['overall_status' => 'ticket_uploaded']);

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Ticket uploaded');

        return $visaProcess;
    }

    /**
     * Get visa processing timeline
     * CRITICAL FIX: This method was missing, causing timeline() to fail
     */
    public function getTimeline($visaProcessId)
    {
        $visaProcess = VisaProcess::with('candidate')->findOrFail($visaProcessId);

        $timeline = [];

        // Interview stage
        if ($visaProcess->interview_date) {
            $timeline[] = [
                'stage' => 'Interview',
                'date' => $visaProcess->interview_date,
                'status' => $visaProcess->interview_status,
                'completed' => $visaProcess->interview_completed,
                'remarks' => $visaProcess->interview_remarks,
            ];
        }

        // Trade test stage
        if ($visaProcess->trade_test_date) {
            $timeline[] = [
                'stage' => 'Trade Test',
                'date' => $visaProcess->trade_test_date,
                'status' => $visaProcess->trade_test_status,
                'completed' => $visaProcess->trade_test_completed,
                'remarks' => $visaProcess->trade_test_remarks,
            ];
        }

        // Takamol stage
        if ($visaProcess->takamol_date) {
            $timeline[] = [
                'stage' => 'Takamol',
                'date' => $visaProcess->takamol_date,
                'status' => $visaProcess->takamol_status,
                'completed' => $visaProcess->takamol_status === 'completed',
                'remarks' => $visaProcess->takamol_remarks ?? null,
            ];
        }

        // Medical stage
        if ($visaProcess->medical_date) {
            $timeline[] = [
                'stage' => 'Medical (GAMCA)',
                'date' => $visaProcess->medical_date,
                'status' => $visaProcess->medical_status,
                'completed' => $visaProcess->medical_completed,
                'remarks' => $visaProcess->medical_remarks ?? null,
            ];
        }

        // Biometric stage
        if ($visaProcess->biometric_date) {
            $timeline[] = [
                'stage' => 'Biometric',
                'date' => $visaProcess->biometric_date,
                'status' => $visaProcess->biometric_status,
                'completed' => $visaProcess->biometric_completed,
                'remarks' => $visaProcess->biometric_remarks ?? null,
            ];
        }

        // Visa issuance
        if ($visaProcess->visa_date) {
            $timeline[] = [
                'stage' => 'Visa Issuance',
                'date' => $visaProcess->visa_date,
                'status' => $visaProcess->visa_status,
                'completed' => $visaProcess->visa_issued,
                'remarks' => $visaProcess->visa_remarks ?? null,
                'visa_number' => $visaProcess->visa_number,
            ];
        }

        // Ticket upload
        if ($visaProcess->ticket_date) {
            $timeline[] = [
                'stage' => 'Ticket',
                'date' => $visaProcess->ticket_date,
                'status' => 'uploaded',
                'completed' => $visaProcess->ticket_uploaded,
                'remarks' => null,
            ];
        }

        return $timeline;
    }

    /**
     * Get overdue visa processes
     * CRITICAL FIX: This method was missing, causing overdue() to fail
     */
    public function getOverdueProcesses()
    {
        $thresholdDays = 90; // Consider process overdue after 90 days
        $thresholdDate = Carbon::now()->subDays($thresholdDays);

        return Candidate::with(['visaProcess', 'trade', 'campus', 'oep'])
            ->whereHas('visaProcess', function($query) use ($thresholdDate) {
                $query->where('created_at', '<', $thresholdDate)
                      ->where('overall_status', '!=', 'completed')
                      ->whereNull('deleted_at');
            })
            ->where('status', 'visa_processing')
            ->get();
    }

    /**
     * Complete visa process - make public
     * CRITICAL FIX: Changed from private to public, was being called from controller
     */
    public function completeVisaProcess($visaProcessId)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'overall_status' => 'completed',
        ]);

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Visa process completed');

        return $visaProcess;
    }

    /**
     * Delete visa process
     * CRITICAL FIX: This method was missing, causing destroy() to fail
     */
    public function deleteVisaProcess($visaProcessId)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        // Soft delete
        $visaProcess->delete();

        // Log activity
        activity()
            ->performedOn($visaProcess)
            ->causedBy(auth()->user())
            ->log('Visa process deleted');

        return true;
    }

    /**
     * Generate visa processing report (updated signature)
     * CRITICAL FIX: Updated to match controller usage
     */
    public function generateReport($startDate, $endDate, $campusId = null)
    {
        $query = VisaProcess::with(['candidate.trade', 'candidate.campus', 'candidate.oep'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Filter by campus if specified
        if ($campusId) {
            $query->whereHas('candidate', function($q) use ($campusId) {
                $q->where('campus_id', $campusId);
            });
        }

        $processes = $query->get();

        // Calculate statistics
        $stats = [
            'total' => $processes->count(),
            'completed' => $processes->where('overall_status', 'completed')->count(),
            'in_progress' => $processes->where('overall_status', '!=', 'completed')->count(),
            'interview_stage' => $processes->where('overall_status', 'interview_completed')->count(),
            'trade_test_stage' => $processes->where('overall_status', 'trade_test_completed')->count(),
            'takamol_stage' => $processes->where('overall_status', 'takamol_completed')->count(),
            'medical_stage' => $processes->where('overall_status', 'medical_completed')->count(),
            'biometric_stage' => $processes->where('overall_status', 'biometric_completed')->count(),
            'visa_issued' => $processes->where('visa_issued', true)->count(),
            'ticket_uploaded' => $processes->where('ticket_uploaded', true)->count(),
        ];

        return [
            'processes' => $processes,
            'statistics' => $stats,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    // =========================================================================
    // Module 5 Enhancement: Stage Detail Management
    // =========================================================================

    /**
     * Schedule stage appointment with details
     */
    public function scheduleStage(
        VisaProcess $visaProcess,
        string $stage,
        string $date,
        string $time,
        string $center
    ): void {
        $validStages = ['interview', 'trade_test', 'takamol', 'medical', 'biometric'];
        if (!in_array($stage, $validStages)) {
            throw new \Exception("Invalid stage: {$stage}");
        }

        $this->validateStagePrerequisites($visaProcess, $stage);

        $visaProcess->scheduleStageAppointment($stage, $date, $time, $center);
    }

    /**
     * Record stage result with details
     */
    public function recordStageResultWithDetails(
        VisaProcess $visaProcess,
        string $stage,
        string $resultStatus,
        ?string $notes = null,
        $evidenceFile = null
    ): void {
        DB::transaction(function () use ($visaProcess, $stage, $resultStatus, $notes, $evidenceFile) {
            $evidencePath = null;

            // Upload evidence if provided
            if ($evidenceFile) {
                $evidencePath = $visaProcess->uploadStageEvidence($stage, $evidenceFile);
            }

            // Record the result
            $visaProcess->recordStageResult($stage, $resultStatus, $notes, $evidencePath);

            // Handle failed/refused results
            $result = VisaStageResult::from($resultStatus);
            if ($result->isTerminal()) {
                $visaProcess->update([
                    'failed_at' => now(),
                    'failed_stage' => $stage,
                    'failure_reason' => $notes ?? "Failed at {$stage}",
                ]);

                // Update candidate status to rejected
                $visaProcess->candidate->update(['status' => CandidateStatus::REJECTED->value]);
            }
        });
    }

    /**
     * Update visa application status
     */
    public function updateVisaApplicationStatus(
        VisaProcess $visaProcess,
        string $applicationStatus,
        ?string $issuedStatus = null,
        ?string $notes = null,
        $evidenceFile = null
    ): void {
        DB::transaction(function () use ($visaProcess, $applicationStatus, $issuedStatus, $notes, $evidenceFile) {
            $evidencePath = null;

            if ($evidenceFile) {
                $evidencePath = $visaProcess->uploadStageEvidence('visa_application', $evidenceFile);
            }

            $details = VisaStageDetails::fromArray($visaProcess->visa_application_details);
            $visaProcess->visa_application_details = $details->withResult(
                $applicationStatus,
                $notes,
                $evidencePath
            )->toArray();

            $visaProcess->visa_application_status = VisaApplicationStatus::from($applicationStatus);

            if ($issuedStatus) {
                $visaProcess->visa_issued_status = VisaIssuedStatus::from($issuedStatus);
            }

            // If visa confirmed, update candidate status
            if ($issuedStatus === 'confirmed') {
                $visaProcess->visa_status = 'approved';
                $visaProcess->visa_issued = true;
                $visaProcess->candidate->update(['status' => CandidateStatus::VISA_APPROVED->value]);
            } elseif ($applicationStatus === 'refused' || $issuedStatus === 'refused') {
                $visaProcess->update([
                    'failed_at' => now(),
                    'failed_stage' => 'visa_application',
                    'failure_reason' => $notes ?? 'Visa application refused',
                ]);
                $visaProcess->candidate->update(['status' => CandidateStatus::REJECTED->value]);
            }

            $visaProcess->save();

            activity()
                ->performedOn($visaProcess)
                ->causedBy(auth()->user())
                ->withProperties([
                    'application_status' => $applicationStatus,
                    'issued_status' => $issuedStatus,
                ])
                ->log('Visa application status updated');
        });
    }

    /**
     * Get hierarchical dashboard data
     */
    public function getHierarchicalDashboard(?int $campusId = null): array
    {
        $query = VisaProcess::with(['candidate.campus', 'candidate.trade'])
            ->whereNotIn('overall_status', ['completed', 'cancelled']);

        if ($campusId) {
            $query->whereHas('candidate', fn($q) => $q->where('campus_id', $campusId));
        }

        $processes = $query->get();

        $dashboard = [
            'scheduled' => collect(),
            'done' => collect(),
            'passed' => collect(),
            'failed' => collect(),
            'pending' => collect(),
        ];

        foreach ($processes as $process) {
            $hierarchical = $process->getHierarchicalStatus();

            foreach (['scheduled', 'done', 'passed', 'failed', 'pending'] as $category) {
                foreach ($hierarchical[$category] as $stage => $stageData) {
                    $dashboard[$category]->push([
                        'visa_process_id' => $process->id,
                        'candidate' => $process->candidate,
                        'stage' => $stage,
                        'stage_name' => $stageData['name'],
                        'details' => $stageData['details'],
                        'icon' => $stageData['icon'],
                    ]);
                }
            }
        }

        return [
            'counts' => [
                'scheduled' => $dashboard['scheduled']->count(),
                'done' => $dashboard['done']->count(),
                'passed' => $dashboard['passed']->count(),
                'failed' => $dashboard['failed']->count(),
                'pending' => $dashboard['pending']->count(),
            ],
            'items' => $dashboard,
        ];
    }

    /**
     * Get stages requiring evidence
     */
    public function getStagesMissingEvidence(VisaProcess $visaProcess): array
    {
        $stages = ['interview', 'trade_test', 'takamol', 'medical', 'biometric'];
        $missing = [];

        foreach ($stages as $stage) {
            $details = VisaStageDetails::fromArray($visaProcess->{"{$stage}_details"});

            if ($details->hasResult() && !$details->hasEvidence()) {
                $missing[] = [
                    'stage' => $stage,
                    'result' => $details->resultStatus,
                ];
            }
        }

        return $missing;
    }

    /**
     * Validate stage prerequisites for enhanced stages
     */
    protected function validateStagePrerequisites(VisaProcess $visaProcess, string $stage): void
    {
        $errors = [];

        switch ($stage) {
            case 'trade_test':
                $interviewDetails = VisaStageDetails::fromArray($visaProcess->interview_details);
                if (!$interviewDetails->isPassed() && $visaProcess->interview_status !== 'passed') {
                    $errors[] = 'Interview must be passed before Trade Test';
                }
                break;

            case 'takamol':
                if ($visaProcess->interview_status !== 'passed' && !VisaStageDetails::fromArray($visaProcess->interview_details)->isPassed()) {
                    $errors[] = 'Interview must be passed before Takamol';
                }
                break;

            case 'medical':
                if ($visaProcess->interview_status !== 'passed' && !VisaStageDetails::fromArray($visaProcess->interview_details)->isPassed()) {
                    $errors[] = 'Interview must be passed first';
                }
                break;

            case 'biometric':
                if ($visaProcess->medical_status !== 'fit' && $visaProcess->medical_status !== 'completed') {
                    $medicalDetails = VisaStageDetails::fromArray($visaProcess->medical_details);
                    if (!$medicalDetails->isPassed()) {
                        $errors[] = 'Medical examination must be cleared before biometrics';
                    }
                }
                break;
        }

        if (!empty($errors)) {
            throw new \Exception(implode('; ', $errors));
        }
    }
}