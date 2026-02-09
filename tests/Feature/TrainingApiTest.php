<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Feature tests for training API endpoints.
 */
class TrainingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Trade $trade;
    protected Campus $campus;
    protected Batch $batch;
    protected TrainingService $trainingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->trade = Trade::factory()->create();
        $this->campus = Campus::factory()->create();
        $this->batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 30,
        ]);
        $this->trainingService = app(TrainingService::class);
    }

    // ==================== BATCH ENROLLMENT ====================

    #[Test]
    public function it_assigns_candidates_to_batch()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
        ]);

        $candidateIds = $candidates->pluck('id')->toArray();

        $result = $this->trainingService->assignCandidatesToBatch(
            $this->batch->id,
            $candidateIds
        );

        $this->assertArrayHasKey('assigned', $result);
        $this->assertEquals(5, count($result['assigned']));

        foreach ($candidates as $candidate) {
            $candidate->refresh();
            $this->assertEquals($this->batch->id, $candidate->batch_id);
        }
    }

    #[Test]
    public function it_respects_batch_capacity()
    {
        $batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 3,
        ]);

        // Already have 2 candidates
        Candidate::factory()->count(2)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $batch->id,
            'trade_id' => $this->trade->id,
        ]);

        // Try to add 3 more
        $newCandidates = Candidate::factory()->count(3)->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
        ]);

        $result = $this->trainingService->assignCandidatesToBatch(
            $batch->id,
            $newCandidates->pluck('id')->toArray()
        );

        // Only 1 should be assigned (capacity is 3, already have 2)
        $this->assertEquals(1, count($result['assigned']));
        $this->assertEquals(2, count($result['failed']));
    }

    #[Test]
    public function it_skips_already_assigned_candidates()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $this->trainingService->assignCandidatesToBatch(
            $this->batch->id,
            [$candidate->id]
        );

        $this->assertEmpty($result['assigned']);
    }

    // ==================== ATTENDANCE ====================

    #[Test]
    public function it_records_attendance()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        $attendance = $this->trainingService->recordAttendance([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present',
        ]);

        $this->assertNotNull($attendance);
        $this->assertDatabaseHas('training_attendances', [
            'candidate_id' => $candidate->id,
            'status' => 'present',
        ]);
    }

    #[Test]
    public function it_calculates_attendance_percentage()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Create 10 attendance records: 8 present, 2 absent
        for ($i = 0; $i < 10; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 8 ? 'present' : 'absent',
            ]);
        }

        $percentage = $candidate->getAttendancePercentage();
        $this->assertEquals(80, $percentage);
    }

    #[Test]
    public function it_generates_attendance_report()
    {
        $candidates = Candidate::factory()->count(3)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        foreach ($candidates as $candidate) {
            for ($i = 0; $i < 5; $i++) {
                TrainingAttendance::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $this->batch->id,
                    'date' => now()->subDays($i),
                    'status' => 'present',
                ]);
            }
        }

        $report = $this->trainingService->generateAttendanceReport([
            'batch_id' => $this->batch->id,
        ]);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('candidates', $report);
        $this->assertEquals(3, count($report['candidates']));
    }

    // ==================== ASSESSMENTS ====================

    #[Test]
    public function it_records_assessment()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        $assessment = $this->trainingService->recordAssessment([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'practical',
            'assessment_date' => now()->format('Y-m-d'),
            'total_score' => 75,
            'score' => 75,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $this->assertNotNull($assessment);
        $this->assertDatabaseHas('training_assessments', [
            'candidate_id' => $candidate->id,
            'score' => 75,
            'result' => 'pass',
        ]);
    }

    #[Test]
    public function it_calculates_average_score()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'practical',
            'assessment_date' => now(),
            'score' => 70,
            'total_score' => 70,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'practical',
            'assessment_date' => now(),
            'score' => 80,
            'total_score' => 80,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $average = $candidate->getAverageAssessmentScore();
        $this->assertEquals(75, $average);
    }

    #[Test]
    public function it_validates_passing_all_assessments()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'practical',
            'assessment_date' => now(),
            'score' => 70,
            'total_score' => 70,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'assessment_date' => now(),
            'score' => 50,
            'total_score' => 50,
            'max_score' => 100,
            'result' => 'fail',
        ]);

        $this->assertFalse($candidate->hasPassedAllAssessments());
    }

    #[Test]
    public function it_generates_assessment_report()
    {
        $candidates = Candidate::factory()->count(3)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        foreach ($candidates as $candidate) {
            TrainingAssessment::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'assessment_type' => 'final',
                'assessment_date' => now(),
                'score' => rand(60, 100),
                'total_score' => rand(60, 100),
                'max_score' => 100,
                'result' => 'pass',
            ]);
        }

        $report = $this->trainingService->generateAssessmentReport([
            'batch_id' => $this->batch->id,
        ]);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('candidates', $report);
    }

    // ==================== CERTIFICATES ====================

    #[Test]
    public function it_issues_certificate()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Add required attendance
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => 'present',
            ]);
        }

        // Add final assessment
        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'assessment_date' => now(),
            'score' => 75,
            'total_score' => 75,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $certificate = $this->trainingService->generateCertificate($candidate->id);

        $this->assertNotNull($certificate);
        $this->assertNotNull($candidate->fresh()->certificate);
    }

    #[Test]
    public function it_validates_certificate_requirements()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        $validation = $this->trainingService->validateCertificateRequirements($candidate->id);

        $this->assertFalse($validation['can_generate']);
        $this->assertNotEmpty($validation['issues']);
    }

    // ==================== TRAINING COMPLETION ====================

    #[Test]
    public function it_completes_training()
    {
        $candidate = $this->createTrainingReadyCandidate();

        $result = $this->trainingService->completeTraining($candidate->id);

        $this->assertNotNull($result);

        $candidate->refresh();
        $this->assertEquals(Candidate::STATUS_VISA_PROCESS, $candidate->status);
        $this->assertEquals(Candidate::TRAINING_COMPLETED, $candidate->training_status);
    }

    #[Test]
    public function it_rejects_completion_without_attendance()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Add only 50% attendance
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 50 ? 'present' : 'absent',
            ]);
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Attendance');
        $this->trainingService->completeTraining($candidate->id);
    }

    #[Test]
    public function it_rejects_completion_without_final_assessment()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Add 100% attendance
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => 'present',
            ]);
        }

        // No final assessment
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('final assessment');
        $this->trainingService->completeTraining($candidate->id);
    }

    // ==================== BATCH PERFORMANCE ====================

    #[Test]
    public function it_returns_batch_performance()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        foreach ($candidates as $candidate) {
            for ($i = 0; $i < 10; $i++) {
                TrainingAttendance::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $this->batch->id,
                    'date' => now()->subDays($i),
                    'status' => 'present',
                ]);
            }

            TrainingAssessment::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'assessment_type' => 'final',
                'assessment_date' => now(),
                'score' => rand(60, 100),
                'total_score' => rand(60, 100),
                'max_score' => 100,
                'result' => 'pass',
            ]);
        }

        $performance = $this->trainingService->getBatchPerformance($this->batch->id);

        $this->assertArrayHasKey('batch', $performance);
        $this->assertArrayHasKey('candidates_count', $performance);
        $this->assertArrayHasKey('average_attendance', $performance);
        $this->assertArrayHasKey('average_score', $performance);
        $this->assertEquals(5, $performance['candidates_count']);
    }

    // ==================== HELPER METHODS ====================

    protected function createTrainingReadyCandidate(): Candidate
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Add 90%+ attendance
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 85 ? 'present' : 'absent',
            ]);
        }

        // Add final assessment
        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'assessment_date' => now(),
            'score' => 75,
            'total_score' => 75,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        // Add certificate
        TrainingCertificate::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'certificate_number' => 'CERT-' . $candidate->id,
            'issue_date' => now(),
            'trade_id' => $this->trade->id,
        ]);

        return $candidate;
    }
}
