<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Models\VisaProcess;
use App\Enums\CandidateStatus;
use App\Enums\TrainingStatus;
use App\Enums\VisaStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class TrainingService
{
    /**
     * Training modules structure
     */
    const MODULES = [
        'orientation' => 'Orientation & Safety',
        'technical_theory' => 'Technical Theory',
        'practical_workshop' => 'Practical Workshop',
        'soft_skills' => 'Soft Skills & Communication',
        'cultural_orientation' => 'Cultural Orientation',
        'language_training' => 'Language Training',
        'final_assessment' => 'Final Assessment',
    ];

    /**
     * Assessment types
     */
    const ASSESSMENT_TYPES = [
        'initial' => 'Initial Assessment',
        'midterm' => 'Midterm Assessment',
        'practical' => 'Practical Assessment',
        'final' => 'Final Assessment',
    ];

    /**
     * Configurable attendance threshold (percentage).
     * Can be overridden via config('training.attendance_threshold')
     */
    const DEFAULT_ATTENDANCE_THRESHOLD = 80;

    /**
     * Configurable passing score (percentage).
     * Can be overridden via config('training.passing_score')
     */
    const DEFAULT_PASSING_SCORE = 60;

    /**
     * Get the configured attendance threshold.
     */
    public function getAttendanceThreshold(): int
    {
        return config('training.attendance_threshold', self::DEFAULT_ATTENDANCE_THRESHOLD);
    }

    /**
     * Get the configured passing score.
     */
    public function getPassingScore(): int
    {
        return config('training.passing_score', self::DEFAULT_PASSING_SCORE);
    }

    /**
     * Get all training modules
     */
    public function getModules(): array
    {
        return self::MODULES;
    }

    /**
     * Get assessment types
     */
    public function getAssessmentTypes(): array
    {
        return self::ASSESSMENT_TYPES;
    }

    /**
     * Start training for a batch
     */
    public function startBatchTraining($batchId, $startDate, $endDate, $trainerId = null)
    {
        $batch = Batch::findOrFail($batchId);

        // AUDIT FIX: Wrap multi-step operation in database transaction
        DB::beginTransaction();
        try {
            $batch->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'ongoing',
            ]);

            // Update all candidates in batch
            $batch->candidates()->update([
                'training_start_date' => $startDate,
                'training_end_date' => $endDate,
                'training_status' => TrainingStatus::IN_PROGRESS->value,
                'status' => CandidateStatus::TRAINING->value,
            ]);

            // Log activity
            activity()
                ->performedOn($batch)
                ->causedBy(auth()->user())
                ->log("Training started for batch {$batch->batch_code}");

            DB::commit();
            return $batch;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Record attendance for a session
     */
    public function recordAttendance($data)
    {
        $attendance = TrainingAttendance::create([
            'candidate_id' => $data['candidate_id'],
            'batch_id' => $data['batch_id'] ?? null,
            'date' => $data['date'],
            'status' => $data['status'], // present, absent, late, leave
            'session_type' => $data['session_type'] ?? 'theory', // theory, practical, assessment
            'trainer_id' => $data['trainer_id'] ?? auth()->id(),
            'detailed_remarks' => $data['remarks'] ?? null,
            'leave_type' => $data['leave_type'] ?? null, // sick, casual, emergency
        ]);

        // Update candidate training status if too many absences
        $this->checkAttendanceThreshold($data['candidate_id']);

        return $attendance;
    }

    /**
     * Bulk record attendance for entire batch
     */
    public function recordBatchAttendance($batchId, $date, $attendanceData)
    {
        $batch = Batch::with('candidates')->findOrFail($batchId);
        $records = [];

        DB::beginTransaction();
        try {
            foreach ($batch->candidates as $candidate) {
                $status = $attendanceData[$candidate->id] ?? 'absent';
                
                $records[] = TrainingAttendance::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $batchId,
                    'date' => $date,
                    'status' => $status,
                    'trainer_id' => auth()->id(),
                ]);
            }

            DB::commit();
            return $records;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if attendance threshold is breached
     */
    private function checkAttendanceThreshold($candidateId, $threshold = 80)
    {
        $candidate = Candidate::findOrFail($candidateId);
        $attendanceStats = $this->getAttendanceStatistics($candidateId);

        if ($attendanceStats['percentage'] < $threshold) {
            // Mark candidate as at-risk using dedicated columns (not training_status enum)
            // 'at_risk' is tracked via at_risk_reason/at_risk_since columns, not enum
            $candidate->update([
                'at_risk_reason' => "Attendance below threshold: {$attendanceStats['percentage']}% (required: {$threshold}%)",
                'at_risk_since' => now(),
            ]);

            // Log warning
            activity()
                ->performedOn($candidate)
                ->log("Attendance below threshold: {$attendanceStats['percentage']}%");
        }
    }

    /**
     * Get attendance statistics for a candidate
     */
    public function getAttendanceStatistics($candidateId, $fromDate = null, $toDate = null): array
    {
        $query = TrainingAttendance::where('candidate_id', $candidateId);

        if ($fromDate) {
            $query->whereDate('date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('date', '<=', $toDate);
        }

        $total = $query->count();
        $present = (clone $query)->where('status', 'present')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $leave = (clone $query)->where('status', 'leave')->count();

        return [
            'total_sessions' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'leave' => $leave,
            'percentage' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get batch attendance summary
     */
    public function getBatchAttendanceSummary($batchId, $fromDate = null, $toDate = null): array
    {
        $batch = Batch::with('candidates')->findOrFail($batchId);
        
        $summary = [];
        foreach ($batch->candidates as $candidate) {
            $summary[] = [
                'candidate' => $candidate,
                'statistics' => $this->getAttendanceStatistics($candidate->id, $fromDate, $toDate),
            ];
        }

        return [
            'batch' => $batch,
            'attendance' => $summary,
            'batch_average' => $this->calculateBatchAverageAttendance($summary),
        ];
    }

    /**
     * Calculate batch average attendance
     */
    private function calculateBatchAverageAttendance(array $summary): float
    {
        if (empty($summary)) {
            return 0;
        }

        $totalPercentage = array_sum(array_column(array_column($summary, 'statistics'), 'percentage'));
        return round($totalPercentage / count($summary), 2);
    }

    /**
     * Record assessment
     */
    public function recordAssessment($data)
    {
        $assessment = TrainingAssessment::create([
            'candidate_id' => $data['candidate_id'],
            'batch_id' => $data['batch_id'] ?? null,
            'assessment_type' => $data['assessment_type'], // initial, midterm, practical, final
            'assessment_date' => $data['assessment_date'],
            'theoretical_score' => $data['theoretical_score'] ?? null,
            'practical_score' => $data['practical_score'] ?? null,
            'total_score' => $data['total_score'],
            'max_score' => $data['max_score'] ?? 100,
            'pass_score' => $data['pass_score'] ?? 60,
            'result' => $data['result'] ?? ($data['total_score'] >= ($data['pass_score'] ?? 60) ? 'pass' : 'fail'),
            'trainer_id' => $data['trainer_id'] ?? auth()->id(),
            'assessment_location' => $data['assessment_location'] ?? null,
            'remedial_needed' => $data['remedial_needed'] ?? false,
            'remarks' => $data['remarks'] ?? null,
        ]);

        // Update candidate training status
        $this->updateCandidateAssessmentStatus($data['candidate_id'], $data['assessment_type'], $assessment->result);

        return $assessment;
    }

    /**
     * Update candidate status based on assessment
     */
    private function updateCandidateAssessmentStatus($candidateId, $assessmentType, $result)
    {
        $candidate = Candidate::findOrFail($candidateId);

        if ($assessmentType === 'final') {
            if ($result === 'pass') {
                $candidate->update([
                    'training_status' => TrainingStatus::COMPLETED->value,
                    'status' => CandidateStatus::VISA_PROCESS->value,
                    'at_risk_reason' => null, // Clear at-risk flag on completion
                    'at_risk_since' => null,
                ]);

                // Generate certificate
                $this->generateCertificate($candidateId);
            } else {
                $candidate->update([
                    'training_status' => TrainingStatus::FAILED->value,
                ]);
            }
        } elseif ($result === 'fail') {
            // Mark as at-risk using dedicated columns
            $candidate->update([
                'at_risk_reason' => "Failed {$assessmentType} assessment",
                'at_risk_since' => now(),
            ]);
        }
    }

    /**
     * Generate training certificate
     */
    public function generateCertificate($candidateId, $issueDate = null)
    {
        $candidate = Candidate::with(['batch', 'trade', 'campus'])->findOrFail($candidateId);

        // AUDIT FIX: Check attendance percentage before issuing certificate
        $attendanceStats = $this->getAttendanceStatistics($candidateId);
        $minAttendance = config('training.minimum_attendance', 80);

        if ($attendanceStats['percentage'] < $minAttendance) {
            throw new \Exception(
                "Candidate does not meet minimum attendance requirement. " .
                "Required: {$minAttendance}%, Actual: {$attendanceStats['percentage']}%"
            );
        }

        // Check if candidate is at-risk due to attendance issues
        // at_risk is tracked via at_risk_reason column, not training_status enum
        if ($candidate->training_status === Candidate::TRAINING_DROPPED ||
            !empty($candidate->at_risk_reason)) {
            throw new \Exception('Candidate is at-risk or dropped from training and cannot receive certificate');
        }

        // Check if candidate passed all assessments
        $finalAssessment = TrainingAssessment::where('candidate_id', $candidateId)
            ->where('assessment_type', 'final')
            ->where('result', 'pass')
            ->first();

        if (!$finalAssessment) {
            throw new \Exception('Candidate has not passed final assessment');
        }

        // Generate certificate number
        $certificateNumber = $this->generateCertificateNumber($candidate);

        // Create certificate record
        $certificate = TrainingCertificate::create([
            'candidate_id' => $candidateId,
            'certificate_number' => $certificateNumber,
            'issue_date' => $issueDate ?? now(),
            'issued_by' => auth()->id(),
            'trainer_id' => $finalAssessment->trainer_id,
        ]);

        // Generate PDF certificate
        $pdfPath = $this->generateCertificatePDF($candidate, $certificate);
        
        $certificate->update([
            'certificate_path' => $pdfPath,
        ]);

        return $certificate;
    }

    /**
     * Generate certificate number
     */
    private function generateCertificateNumber($candidate)
    {
        $year = date('Y');
        $campusCode = $candidate->campus ? $candidate->campus->code : 'CMP';
        $tradeCode = $candidate->trade ? $candidate->trade->code : 'TRD';
        
        $lastCert = TrainingCertificate::where('certificate_number', 'like', "CERT-{$year}-{$campusCode}-{$tradeCode}-%")
            ->orderBy('certificate_number', 'desc')
            ->first();
        
        if ($lastCert) {
            $lastSequence = (int) substr($lastCert->certificate_number, -4);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }
        
        return sprintf('CERT-%s-%s-%s-%04d', $year, $campusCode, $tradeCode, $sequence);
    }

    /**
     * Generate certificate PDF
     */
    private function generateCertificatePDF($candidate, $certificate)
    {
        $data = [
            'candidate' => $candidate,
            'certificate' => $certificate,
            'batch' => $candidate->batch,
            'trade' => $candidate->trade,
            'campus' => $candidate->campus,
        ];

        // Generate PDF (using a view template)
        $pdf = PDF::loadView('certificates.training-certificate', $data);
        
        $filename = "certificate_{$certificate->certificate_number}.pdf";
        $path = "certificates/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());
        
        return $path;
    }

    /**
     * Get training statistics for a batch
     */
    public function getBatchStatistics($batchId): array
    {
        $batch = Batch::with('candidates')->findOrFail($batchId);
        
        $totalCandidates = $batch->candidates->count();
        $completed = $batch->candidates->where('training_status', 'completed')->count();
        $ongoing = $batch->candidates->where('training_status', 'in_progress')->count();
        $failed = $batch->candidates->where('training_status', 'failed')->count();
        // at_risk is tracked via at_risk_reason column, not training_status enum
        $atRisk = $batch->candidates->whereNotNull('at_risk_reason')->count();

        // Assessment statistics
        $assessments = TrainingAssessment::whereIn('candidate_id', $batch->candidates->pluck('id'))->get();
        
        $assessmentStats = [];
        foreach (self::ASSESSMENT_TYPES as $type => $label) {
            $typeAssessments = $assessments->where('assessment_type', $type);
            $assessmentStats[$type] = [
                'total' => $typeAssessments->count(),
                'pass' => $typeAssessments->where('result', 'pass')->count(),
                'fail' => $typeAssessments->where('result', 'fail')->count(),
                'average_score' => $typeAssessments->avg('total_score'),
            ];
        }

        return [
            'batch' => $batch,
            'total_candidates' => $totalCandidates,
            'completed' => $completed,
            'ongoing' => $ongoing,
            'failed' => $failed,
            'at_risk' => $atRisk,
            'completion_rate' => $totalCandidates > 0 ? round(($completed / $totalCandidates) * 100, 2) : 0,
            'assessment_statistics' => $assessmentStats,
            'average_attendance' => $this->getBatchAverageAttendance($batchId),
            'certificates_issued' => TrainingCertificate::whereIn('candidate_id', $batch->candidates->pluck('id'))->count(),
        ];
    }

    /**
     * Get batch average attendance
     */
    private function getBatchAverageAttendance($batchId): float
    {
        $batch = Batch::with('candidates')->findOrFail($batchId);
        
        $summary = [];
        foreach ($batch->candidates as $candidate) {
            $stats = $this->getAttendanceStatistics($candidate->id);
            $summary[] = $stats;
        }

        if (empty($summary)) {
            return 0;
        }

        $totalPercentage = array_sum(array_column($summary, 'percentage'));
        return round($totalPercentage / count($summary), 2);
    }

    /**
     * Get trainer performance metrics
     */
    public function getTrainerPerformance($trainerId, $fromDate = null, $toDate = null): array
    {
        $query = TrainingAssessment::where('trainer_id', $trainerId);

        if ($fromDate) {
            $query->whereDate('assessment_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('assessment_date', '<=', $toDate);
        }

        $assessments = $query->get();
        $totalAssessments = $assessments->count();

        return [
            'trainer_id' => $trainerId,
            'total_assessments' => $totalAssessments,
            'total_students' => $assessments->unique('candidate_id')->count(),
            'pass_rate' => $totalAssessments > 0 ? round(($assessments->where('result', 'pass')->count() / $totalAssessments) * 100, 2) : 0,
            'average_score' => round($assessments->avg('total_score'), 2),
            'by_assessment_type' => $assessments->groupBy('assessment_type')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'pass_count' => $group->where('result', 'pass')->count(),
                    'average_score' => round($group->avg('total_score'), 2),
                ];
            }),
        ];
    }

    /**
     * Get campus training performance comparison
     */
    public function getCampusPerformanceComparison($campusIds = null, $fromDate = null, $toDate = null): array
    {
        $query = Batch::with(['candidates', 'campus']);

        if ($campusIds) {
            $query->whereIn('campus_id', $campusIds);
        }

        if ($fromDate) {
            $query->whereDate('start_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('end_date', '<=', $toDate);
        }

        $batches = $query->get();
        
        $comparison = [];
        foreach ($batches->groupBy('campus_id') as $campusId => $campusBatches) {
            $campus = $campusBatches->first()->campus;
            
            $allCandidates = $campusBatches->flatMap(function($batch) {
                return $batch->candidates;
            });

            $comparison[$campusId] = [
                'campus_name' => $campus->name ?? 'Unknown',
                'total_batches' => $campusBatches->count(),
                'total_candidates' => $allCandidates->count(),
                'completed' => $allCandidates->where('training_status', 'completed')->count(),
                'completion_rate' => $allCandidates->count() > 0 
                    ? round(($allCandidates->where('training_status', 'completed')->count() / $allCandidates->count()) * 100, 2) 
                    : 0,
            ];
        }

        return $comparison;
    }

    /**
     * Generate training report
     */
    public function generateReport(array $filters = []): array
    {
        $query = Batch::with(['candidates', 'campus', 'trade']);

        // Apply filters
        if (!empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        if (!empty($filters['trade_id'])) {
            $query->where('trade_id', $filters['trade_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('start_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('end_date', '<=', $filters['to_date']);
        }

        $batches = $query->get();

        $report = [
            'summary' => [
                'total_batches' => $batches->count(),
                'total_candidates' => $batches->sum(function($batch) {
                    return $batch->candidates->count();
                }),
                'completed_batches' => $batches->where('status', 'completed')->count(),
                'ongoing_batches' => $batches->where('status', 'ongoing')->count(),
            ],
            'batches' => $batches->map(function($batch) {
                return array_merge(
                    $batch->toArray(),
                    ['statistics' => $this->getBatchStatistics($batch->id)]
                );
            }),
            'by_campus' => $this->getCampusPerformanceComparison(
                $filters['campus_id'] ?? null,
                $filters['from_date'] ?? null,
                $filters['to_date'] ?? null
            ),
        ];

        return $report;
    }

    /**
     * Get at-risk candidates
     * Candidates are considered at-risk if they have an at_risk_reason set
     */
    public function getAtRiskCandidates($batchId = null)
    {
        $query = Candidate::whereNotNull('at_risk_reason')
            ->with(['batch', 'trade', 'campus']);

        if ($batchId) {
            $query->where('batch_id', $batchId);
        }

        return $query->get()->map(function($candidate) {
            return [
                'candidate' => $candidate,
                'attendance' => $this->getAttendanceStatistics($candidate->id),
                'assessments' => TrainingAssessment::where('candidate_id', $candidate->id)
                    ->orderBy('assessment_date', 'desc')
                    ->get(),
            ];
        });
    }

    /**
     * Schedule makeup session for candidate
     */
    public function scheduleMakeupSession($candidateId, $data)
    {
        // Record makeup session attendance
        return $this->recordAttendance([
            'candidate_id' => $candidateId,
            'batch_id' => $data['batch_id'],
            'date' => $data['date'],
            'status' => 'present',
            'session_type' => 'makeup',
            'trainer_id' => $data['trainer_id'] ?? auth()->id(),
            'remarks' => $data['remarks'] ?? 'Makeup session',
        ]);
    }

    // ==================== PHASE 4 IMPROVEMENTS ====================

    /**
     * Assign candidates to a batch with capacity enforcement.
     * Uses database transaction to prevent race conditions.
     *
     * @param int $batchId Batch ID
     * @param array $candidateIds Array of candidate IDs
     * @return array Results with success/failure counts
     * @throws \Exception If batch not found
     */
    public function assignCandidatesToBatch($batchId, array $candidateIds)
    {
        $batch = Batch::lockForUpdate()->findOrFail($batchId);
        $results = [
            'success' => [],
            'failed' => [],
            'already_assigned' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($candidateIds as $candidateId) {
                $candidate = Candidate::find($candidateId);

                if (!$candidate) {
                    $results['failed'][] = [
                        'id' => $candidateId,
                        'reason' => 'Candidate not found',
                    ];
                    continue;
                }

                // Check if already in this batch
                if ($candidate->batch_id === $batchId) {
                    $results['already_assigned'][] = $candidateId;
                    continue;
                }

                // Check batch capacity
                $currentCount = Candidate::where('batch_id', $batchId)->count();
                if ($currentCount >= $batch->capacity) {
                    $results['failed'][] = [
                        'id' => $candidateId,
                        'reason' => 'Batch is at full capacity',
                    ];
                    continue;
                }

                // Assign to batch
                $candidate->update([
                    'batch_id' => $batchId,
                    'status' => CandidateStatus::TRAINING->value,
                    'training_status' => TrainingStatus::IN_PROGRESS->value,
                    'training_start_date' => $batch->start_date ?? now(),
                ]);

                $results['success'][] = $candidateId;

                activity()
                    ->performedOn($candidate)
                    ->causedBy(auth()->user())
                    ->withProperties(['batch_id' => $batchId])
                    ->log('Assigned to training batch');
            }

            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete training for a candidate.
     * Validates all requirements before transitioning to visa_process status.
     *
     * @param int $candidateId Candidate ID
     * @param string|null $remarks Optional remarks
     * @return Candidate Updated candidate
     * @throws \Exception If requirements not met
     */
    public function completeTraining($candidateId, $remarks = null)
    {
        $candidate = Candidate::with(['batch', 'certificate'])->findOrFail($candidateId);

        // Validate candidate is in training
        if ($candidate->status !== CandidateStatus::TRAINING->value) {
            throw new \Exception("Candidate is not in training. Current status: {$candidate->status}");
        }

        // Check attendance threshold
        $attendanceStats = $this->getAttendanceStatistics($candidateId);
        $threshold = $this->getAttendanceThreshold();

        if ($attendanceStats['percentage'] < $threshold) {
            throw new \Exception(
                "Attendance ({$attendanceStats['percentage']}%) is below required threshold ({$threshold}%)"
            );
        }

        // Check final assessment passed
        $finalAssessment = TrainingAssessment::where('candidate_id', $candidateId)
            ->where('assessment_type', 'final')
            ->where('result', 'pass')
            ->first();

        if (!$finalAssessment) {
            throw new \Exception('Candidate has not passed final assessment');
        }

        // Check or generate certificate
        $certificate = $candidate->certificate ?? $this->generateCertificate($candidateId);

        DB::beginTransaction();
        try {
            // Update candidate status
            $candidate->update([
                'status' => CandidateStatus::VISA_PROCESS->value,
                'training_status' => TrainingStatus::COMPLETED->value,
                'training_end_date' => now(),
                'at_risk_reason' => null, // Clear at-risk flag
                'at_risk_since' => null,
                'remarks' => $remarks ? ($candidate->remarks . "\n" . $remarks) : $candidate->remarks,
            ]);

            // AUDIT FIX: Auto-create VisaProcess record when training completes
            // This ensures the visa workflow can begin immediately
            $visaProcess = VisaProcess::firstOrCreate(
                ['candidate_id' => $candidate->id],
                [
                    'overall_status' => VisaStage::INITIATED->value,
                    'interview_status' => 'pending',
                    'trade_test_status' => 'pending',
                    'takamol_status' => 'pending',
                    'medical_status' => 'pending',
                    'biometric_status' => 'pending',
                    'visa_status' => 'pending',
                    'initiated_at' => now(),
                    'created_by' => auth()->id(),
                ]
            );

            activity()
                ->performedOn($candidate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'attendance_percentage' => $attendanceStats['percentage'],
                    'final_assessment_score' => $finalAssessment->total_score,
                    'certificate_number' => $certificate->certificate_number,
                    'visa_process_id' => $visaProcess->id,
                ])
                ->log('Training completed - visa process initiated');

            DB::commit();
            return $candidate->fresh(['visaProcess']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate attendance report for a batch or candidate.
     *
     * @param array $filters Filters (batch_id, candidate_id, from_date, to_date)
     * @return array Report data
     */
    public function generateAttendanceReport(array $filters = [])
    {
        $batchId = $filters['batch_id'] ?? null;
        $candidateId = $filters['candidate_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if ($candidateId) {
            // Single candidate report
            $candidate = Candidate::with(['batch', 'trade', 'campus'])->findOrFail($candidateId);
            $stats = $this->getAttendanceStatistics($candidateId, $fromDate, $toDate);

            $records = TrainingAttendance::where('candidate_id', $candidateId)
                ->when($fromDate, fn($q) => $q->whereDate('date', '>=', $fromDate))
                ->when($toDate, fn($q) => $q->whereDate('date', '<=', $toDate))
                ->orderBy('date', 'desc')
                ->get();

            return [
                'type' => 'individual',
                'candidate' => $candidate,
                'statistics' => $stats,
                'records' => $records,
                'threshold' => $this->getAttendanceThreshold(),
                'meets_threshold' => $stats['percentage'] >= $this->getAttendanceThreshold(),
            ];
        }

        if ($batchId) {
            // Batch report
            return $this->getBatchAttendanceSummary($batchId, $fromDate, $toDate);
        }

        // All batches summary
        $batches = Batch::where('status', 'ongoing')->get();
        $report = [];

        foreach ($batches as $batch) {
            $summary = $this->getBatchAttendanceSummary($batch->id, $fromDate, $toDate);
            $report[] = [
                'batch' => $batch,
                'average_attendance' => $summary['batch_average'],
                'candidates_below_threshold' => collect($summary['attendance'])
                    ->filter(fn($item) => $item['statistics']['percentage'] < $this->getAttendanceThreshold())
                    ->count(),
            ];
        }

        return [
            'type' => 'summary',
            'batches' => $report,
            'threshold' => $this->getAttendanceThreshold(),
        ];
    }

    /**
     * Generate assessment report for a batch or candidate.
     *
     * @param array $filters Filters (batch_id, candidate_id, assessment_type, from_date, to_date)
     * @return array Report data
     */
    public function generateAssessmentReport(array $filters = [])
    {
        $query = TrainingAssessment::with(['candidate', 'candidate.batch', 'candidate.trade']);

        if (!empty($filters['batch_id'])) {
            $query->where('batch_id', $filters['batch_id']);
        }

        if (!empty($filters['candidate_id'])) {
            $query->where('candidate_id', $filters['candidate_id']);
        }

        if (!empty($filters['assessment_type'])) {
            $query->where('assessment_type', $filters['assessment_type']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('assessment_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('assessment_date', '<=', $filters['to_date']);
        }

        $assessments = $query->orderBy('assessment_date', 'desc')->get();

        // Calculate statistics
        $stats = [
            'total' => $assessments->count(),
            'passed' => $assessments->where('result', 'pass')->count(),
            'failed' => $assessments->where('result', 'fail')->count(),
            'average_score' => round($assessments->avg('total_score'), 2),
            'highest_score' => $assessments->max('total_score'),
            'lowest_score' => $assessments->min('total_score'),
        ];

        // Group by type
        $byType = [];
        foreach (self::ASSESSMENT_TYPES as $type => $label) {
            $typeAssessments = $assessments->where('assessment_type', $type);
            $byType[$type] = [
                'label' => $label,
                'count' => $typeAssessments->count(),
                'passed' => $typeAssessments->where('result', 'pass')->count(),
                'average' => round($typeAssessments->avg('total_score'), 2),
            ];
        }

        return [
            'filters' => $filters,
            'statistics' => $stats,
            'by_type' => $byType,
            'assessments' => $assessments,
            'passing_score' => $this->getPassingScore(),
        ];
    }

    /**
     * Get batch performance data for analytics.
     *
     * @param int $batchId Batch ID
     * @return array Performance data
     */
    public function getBatchPerformance($batchId)
    {
        $batch = Batch::with(['candidates', 'campus', 'trade'])->findOrFail($batchId);
        $candidates = $batch->candidates;

        // Collect performance data for each candidate
        $candidatePerformance = [];
        foreach ($candidates as $candidate) {
            $attendance = $this->getAttendanceStatistics($candidate->id);
            $assessments = TrainingAssessment::where('candidate_id', $candidate->id)
                ->orderBy('assessment_date')
                ->get();

            $candidatePerformance[] = [
                'candidate' => $candidate,
                'attendance_percentage' => $attendance['percentage'],
                'meets_attendance_threshold' => $attendance['percentage'] >= $this->getAttendanceThreshold(),
                'assessments' => $assessments,
                'average_assessment_score' => round($assessments->avg('total_score'), 2),
                'final_assessment' => $assessments->where('assessment_type', 'final')->first(),
                'training_status' => $candidate->training_status,
                'has_certificate' => TrainingCertificate::where('candidate_id', $candidate->id)->exists(),
            ];
        }

        // Calculate batch-level metrics
        $metrics = [
            'total_candidates' => $candidates->count(),
            'average_attendance' => round(collect($candidatePerformance)->avg('attendance_percentage'), 2),
            'average_assessment_score' => round(collect($candidatePerformance)->avg('average_assessment_score'), 2),
            'completed' => $candidates->where('training_status', 'completed')->count(),
            'in_progress' => $candidates->where('training_status', 'in_progress')->count(),
            'at_risk' => $candidates->whereNotNull('at_risk_reason')->count(),
            'failed' => $candidates->where('training_status', 'failed')->count(),
            'certificates_issued' => collect($candidatePerformance)->where('has_certificate', true)->count(),
            'below_attendance_threshold' => collect($candidatePerformance)->where('meets_attendance_threshold', false)->count(),
        ];

        return [
            'batch' => $batch,
            'metrics' => $metrics,
            'candidates' => $candidatePerformance,
            'thresholds' => [
                'attendance' => $this->getAttendanceThreshold(),
                'passing_score' => $this->getPassingScore(),
            ],
        ];
    }

    /**
     * Validate certificate generation requirements.
     *
     * @param int $candidateId Candidate ID
     * @return array Validation result with status and issues
     */
    public function validateCertificateRequirements($candidateId)
    {
        $candidate = Candidate::with(['batch'])->findOrFail($candidateId);
        $issues = [];
        $canGenerate = true;

        // Check training status
        if ($candidate->status !== 'training' && $candidate->training_status !== 'completed') {
            $issues[] = "Candidate is not in training (status: {$candidate->status})";
            $canGenerate = false;
        }

        // Check attendance
        $attendance = $this->getAttendanceStatistics($candidateId);
        $threshold = $this->getAttendanceThreshold();

        if ($attendance['percentage'] < $threshold) {
            $issues[] = "Attendance ({$attendance['percentage']}%) is below threshold ({$threshold}%)";
            $canGenerate = false;
        }

        // Check final assessment
        $finalAssessment = TrainingAssessment::where('candidate_id', $candidateId)
            ->where('assessment_type', 'final')
            ->first();

        if (!$finalAssessment) {
            $issues[] = 'Final assessment not completed';
            $canGenerate = false;
        } elseif ($finalAssessment->result !== 'pass') {
            $issues[] = "Final assessment not passed (result: {$finalAssessment->result}, score: {$finalAssessment->total_score})";
            $canGenerate = false;
        }

        // Check if certificate already exists
        $existingCertificate = TrainingCertificate::where('candidate_id', $candidateId)->first();
        if ($existingCertificate) {
            $issues[] = "Certificate already issued: {$existingCertificate->certificate_number}";
            // Don't block generation, just warn
        }

        return [
            'can_generate' => $canGenerate,
            'issues' => $issues,
            'attendance' => $attendance,
            'final_assessment' => $finalAssessment,
            'existing_certificate' => $existingCertificate,
        ];
    }
}