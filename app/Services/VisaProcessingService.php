<?php

namespace App\Services;

use App\Models\VisaProcess;
use App\Models\Candidate;
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
                'status' => 'interview',
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
        Candidate::find($candidateId)->update(['status' => 'interview_scheduled']);

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

        // Update candidate status based on result
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
     */
    private function generateEtimadAppointmentId()
    {
        return 'ETM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
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
            'status' => $stage,
            'current_stage' => $stage,
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
        $visaProcess->update(['current_stage' => $stage]);
    }

    /**
     * Complete visa process
     */
    private function completeVisaProcess($visaProcess)
    {
        $visaProcess->update([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        $visaProcess->candidate->update(['status' => 'visa_completed']);
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
            'interview_stage' => (clone $query)->where('current_stage', 'interview')->count(),
            'takamol_stage' => (clone $query)->where('current_stage', 'takamol')->count(),
            'medical_stage' => (clone $query)->where('current_stage', 'medical')->count(),
            'biometrics_stage' => (clone $query)->where('current_stage', 'biometrics')->count(),
            'visa_submission_stage' => (clone $query)->where('current_stage', 'visa_submission')->count(),
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
            ->whereIn('current_stage', ['medical', 'biometrics'])
            ->where('status', '!=', 'completed')
            ->get();
    }

    /**
     * Generate visa processing report
     */
    public function generateReport($filters = [])
    {
        $query = VisaProcess::with(['candidate.oep', 'candidate.trade', 'candidate.campus']);

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

        if (!empty($filters['stage'])) {
            $query->where('current_stage', $filters['stage']);
        }

        $processes = $query->get();

        return [
            'data' => $processes,
            'statistics' => $this->getStatistics($filters),
            'by_oep' => $this->groupByOEP($processes),
            'by_stage' => $this->groupByStage($processes),
        ];
    }

    /**
     * Group processes by OEP
     */
    private function groupByOEP($processes)
    {
        return $processes->groupBy('candidate.oep.name')->map(function($group) {
            return [
                'count' => $group->count(),
                'completed' => $group->where('status', 'completed')->count(),
                'in_progress' => $group->where('status', '!=', 'completed')->count(),
            ];
        });
    }

    /**
     * Group processes by stage
     */
    private function groupByStage($processes)
    {
        return $processes->groupBy('current_stage')->map(function($group) {
            return [
                'count' => $group->count(),
                'average_days' => $group->avg(function($process) {
                    return Carbon::parse($process->created_at)->diffInDays(now());
                }),
            ];
        });
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
}