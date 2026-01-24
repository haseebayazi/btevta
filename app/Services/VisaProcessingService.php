<?php

namespace App\Services;

use App\Models\VisaProcess;
use App\Models\Candidate;
use App\Enums\CandidateStatus;
use App\Enums\VisaStage;
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
        'visa_submission' => 'Visa Document Submission',
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
        $visaProcess = VisaProcess::firstOrCreate(
            ['candidate_id' => $candidateId],
            [
                'overall_status' => 'interview',
                'interview_date' => $data['interview_date'],
                'interview_location' => $data['interview_location'] ?? null,
                'interview_notes' => $data['interview_notes'] ?? null,
            ]
        );

        if (!$visaProcess->wasRecentlyCreated) {
            $visaProcess->update([
                'interview_date' => $data['interview_date'],
                'interview_location' => $data['interview_location'] ?? null,
                'interview_notes' => $data['interview_notes'] ?? null,
            ]);
        }

        // Update candidate status
        // Update candidate status with NULL CHECK
        $candidate = Candidate::find($candidateId);
        if (!$candidate) {
            throw new \Exception("Candidate not found with ID: {$candidateId}");
        }
        $candidate->update(['status' => 'interview_scheduled']);

        return $visaProcess;
    }

    /**
     * Record interview result
     */
    public function recordInterviewResult($visaProcessId, $result, $remarks = null)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);

        $visaProcess->update([
            'interview_result' => $result,
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

        return $visaProcess;
    }

    /**
     * Schedule Takamol test
     */
    public function scheduleTakamol($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        $visaProcess->update([
            'takamol_booking_date' => $data['booking_date'],
            'takamol_test_date' => $data['test_date'] ?? null,
            'takamol_center' => $data['center'] ?? null,
        ]);

        $this->updateStage($visaProcess, 'takamol');

        return $visaProcess;
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
            'takamol_result' => $result,
            'takamol_score' => $score,
            'takamol_certificate_path' => $path,
        ]);

        if ($result === 'pass') {
            $this->moveToNextStage($visaProcess, 'medical');
        }

        return $visaProcess;
    }

    /**
     * Schedule GAMCA medical
     */
    public function scheduleGAMCA($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        $visaProcess->update([
            'gamca_booking_date' => $data['booking_date'],
            'gamca_test_date' => $data['test_date'] ?? null,
            'gamca_center' => $data['center'] ?? null,
            'gamca_barcode' => $data['barcode'] ?? null,
        ]);

        $this->updateStage($visaProcess, 'medical');

        return $visaProcess;
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
            'gamca_result' => $result,
            'gamca_certificate_path' => $path,
            'gamca_expiry_date' => $expiryDate,
        ]);

        if ($result === 'fit') {
            $this->moveToNextStage($visaProcess, 'biometrics');
        }

        return $visaProcess;
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
            'etimad_appointment_date' => $data['appointment_date'],
            'etimad_center' => $data['center'] ?? null,
        ]);

        $this->updateStage($visaProcess, 'biometrics');

        return $visaProcess;
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
            'biometrics_completed' => true,
            'biometrics_completion_date' => $data['completion_date'] ?? now(),
            'biometrics_remarks' => $data['remarks'] ?? null,
        ]);

        $this->moveToNextStage($visaProcess, 'visa_submission');

        return $visaProcess;
    }

    /**
     * Record visa document submission
     */
    public function recordVisaSubmission($visaProcessId, $data)
    {
        $visaProcess = VisaProcess::findOrFail($visaProcessId);
        
        $visaProcess->update([
            'visa_submission_date' => $data['submission_date'],
            'visa_application_number' => $data['application_number'] ?? null,
            'embassy' => $data['embassy'] ?? null,
        ]);

        $this->updateStage($visaProcess, 'visa_submission');

        return $visaProcess;
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
            'flight_number' => $data['flight_number'] ?? null,
            'departure_date' => $data['departure_date'] ?? null,
            'arrival_date' => $data['arrival_date'] ?? null,
        ]);

        $this->moveToNextStage($visaProcess, 'ticket');
        $this->completeVisaProcess($visaProcess);

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
            'takamol_test_date' => 'Takamol Test',
            'gamca_test_date' => 'Medical (GAMCA)',
            'etimad_appointment_date' => 'Biometrics',
            'visa_submission_date' => 'Visa Submission',
            'ptn_issue_date' => 'PTN Issuance',
            'departure_date' => 'Departure',
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
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'in_progress' => (clone $query)->where('status', '!=', 'completed')->count(),
            'interview_stage' => (clone $query)->where('overall_status', 'interview')->count(),
            'takamol_stage' => (clone $query)->where('overall_status', 'takamol')->count(),
            'medical_stage' => (clone $query)->where('overall_status', 'medical')->count(),
            'biometrics_stage' => (clone $query)->where('overall_status', 'biometrics')->count(),
            'visa_submission_stage' => (clone $query)->where('overall_status', 'visa_submission')->count(),
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
            ->where('status', '!=', 'completed')
            ->get();
    }

    /**
     * Check document expiry alerts
     */
    public function getExpiringDocuments($days = 30)
    {
        $alertDate = Carbon::now()->addDays($days);

        return VisaProcess::with('candidate')
            ->where(function($query) use ($alertDate) {
                $query->where('gamca_expiry_date', '<=', $alertDate)
                      ->orWhere('passport_expiry_date', '<=', $alertDate);
            })
            ->get();
    }

    /**
     * Create new visa process for candidate
     * CRITICAL FIX: This method was missing, causing store() to fail
     */
    public function createVisaProcess($candidateId, $data)
    {
        DB::beginTransaction();
        try {
            // Create visa process record
            $visaProcess = VisaProcess::create([
                'candidate_id' => $candidateId,
                'interview_date' => $data['interview_date'] ?? null,
                'interview_status' => $data['interview_status'] ?? 'pending',
                'interview_remarks' => $data['interview_remarks'] ?? null,
                'overall_status' => VisaStage::INITIATED->value,
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

            DB::commit();
            return $visaProcess;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
}