<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Batch;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
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
                'training_status' => 'ongoing',
                'status' => 'in_training',
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
            $candidate->update([
                'training_status' => 'at_risk',
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
                    'training_status' => 'completed',
                    'status' => 'training_completed',
                ]);

                // Generate certificate
                $this->generateCertificate($candidateId);
            } else {
                $candidate->update([
                    'training_status' => 'failed',
                ]);
            }
        } elseif ($result === 'fail') {
            $candidate->update([
                'training_status' => 'at_risk',
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
        if ($candidate->training_status === Candidate::TRAINING_DROPPED ||
            $candidate->training_status === 'at_risk') {
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
        $ongoing = $batch->candidates->where('training_status', 'ongoing')->count();
        $failed = $batch->candidates->where('training_status', 'failed')->count();
        $atRisk = $batch->candidates->where('training_status', 'at_risk')->count();

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
     */
    public function getAtRiskCandidates($batchId = null)
    {
        $query = Candidate::where('training_status', 'at_risk')
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
}