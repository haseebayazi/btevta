<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Batch;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrainingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TrainingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrainingService();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    // =========================================================================
    // BATCH TRAINING
    // =========================================================================

    #[Test]
    public function it_can_start_batch_training()
    {
        $batch = Batch::factory()->create(['status' => 'planned']);
        $candidates = Candidate::factory()->count(5)->create([
            'batch_id' => $batch->id,
            'status' => 'registered',
        ]);

        $result = $this->service->startBatchTraining(
            $batch->id,
            '2024-06-01',
            '2024-08-31'
        );

        $this->assertEquals('ongoing', $result->status);
        $this->assertEquals('2024-06-01', $result->start_date->format('Y-m-d'));

        // All candidates should be updated
        foreach ($candidates as $candidate) {
            $candidate->refresh();
            $this->assertEquals('training', $candidate->status);
        }
    }

    #[Test]
    public function starting_batch_training_is_transactional()
    {
        $batch = Batch::factory()->create(['status' => 'planned']);

        // This should fail but roll back all changes
        try {
            $this->service->startBatchTraining($batch->id, 'invalid-date', '2024-08-31');
        } catch (\Exception $e) {
            // Expected
        }

        $batch->refresh();
        $this->assertEquals('planned', $batch->status);
    }

    // =========================================================================
    // ATTENDANCE
    // =========================================================================

    #[Test]
    public function it_can_record_attendance()
    {
        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();

        $attendance = $this->service->recordAttendance([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'date' => '2024-06-15',
            'status' => 'present',
            'session_type' => 'theory',
        ]);

        $this->assertInstanceOf(TrainingAttendance::class, $attendance);
        $this->assertEquals('present', $attendance->status);
    }

    #[Test]
    public function it_can_record_bulk_attendance()
    {
        $batch = Batch::factory()->create();
        $candidates = Candidate::factory()->count(5)->create(['batch_id' => $batch->id]);

        $attendanceData = [];
        foreach ($candidates as $index => $candidate) {
            $attendanceData[$candidate->id] = $index % 2 == 0 ? 'present' : 'absent';
        }

        $records = $this->service->recordBatchAttendance(
            $batch->id,
            '2024-06-15',
            $attendanceData
        );

        $this->assertCount(5, $records);
    }

    #[Test]
    public function it_calculates_attendance_statistics()
    {
        $candidate = Candidate::factory()->create();

        // Record 10 days: 7 present, 2 absent, 1 late
        for ($i = 1; $i <= 7; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => 'present',
            ]);
        }
        TrainingAttendance::factory()->create([
            'candidate_id' => $candidate->id,
            'date' => '2024-06-08',
            'status' => 'absent',
        ]);
        TrainingAttendance::factory()->create([
            'candidate_id' => $candidate->id,
            'date' => '2024-06-09',
            'status' => 'absent',
        ]);
        TrainingAttendance::factory()->create([
            'candidate_id' => $candidate->id,
            'date' => '2024-06-10',
            'status' => 'late',
        ]);

        $stats = $this->service->getAttendanceStatistics($candidate->id);

        $this->assertEquals(10, $stats['total_sessions']);
        $this->assertEquals(7, $stats['present']);
        $this->assertEquals(2, $stats['absent']);
        $this->assertEquals(1, $stats['late']);
        $this->assertEquals(70.0, $stats['percentage']);
    }

    #[Test]
    public function low_attendance_marks_candidate_at_risk()
    {
        $candidate = Candidate::factory()->create(['training_status' => 'ongoing']);

        // Record poor attendance (below 80%)
        for ($i = 1; $i <= 10; $i++) {
            $this->service->recordAttendance([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => $i <= 6 ? 'present' : 'absent', // 60% attendance
            ]);
        }

        $candidate->refresh();
        $this->assertEquals('at_risk', $candidate->training_status);
    }

    // =========================================================================
    // ASSESSMENTS
    // =========================================================================

    #[Test]
    public function it_can_record_assessment()
    {
        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();

        $assessment = $this->service->recordAssessment([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'midterm',
            'assessment_date' => '2024-07-01',
            'total_score' => 75,
            'max_score' => 100,
            'pass_score' => 60,
        ]);

        $this->assertInstanceOf(TrainingAssessment::class, $assessment);
        $this->assertEquals('pass', $assessment->result);
    }

    #[Test]
    public function failed_assessment_marks_candidate_at_risk()
    {
        $candidate = Candidate::factory()->create(['training_status' => 'ongoing']);
        $batch = Batch::factory()->create();

        $this->service->recordAssessment([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'midterm',
            'assessment_date' => '2024-07-01',
            'total_score' => 45,
            'max_score' => 100,
            'pass_score' => 60,
        ]);

        $candidate->refresh();
        $this->assertEquals('at_risk', $candidate->training_status);
    }

    #[Test]
    public function passed_final_assessment_updates_status_to_completed()
    {
        $candidate = Candidate::factory()->create(['training_status' => 'ongoing']);
        $batch = Batch::factory()->create();

        // Need to record good attendance first
        for ($i = 1; $i <= 10; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => 'present',
            ]);
        }

        $this->service->recordAssessment([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'final',
            'assessment_date' => '2024-08-01',
            'total_score' => 85,
            'max_score' => 100,
            'pass_score' => 60,
        ]);

        $candidate->refresh();
        $this->assertEquals('completed', $candidate->training_status);
    }

    // =========================================================================
    // CERTIFICATE GENERATION
    // =========================================================================

    #[Test]
    public function it_generates_certificate_for_eligible_candidate()
    {
        $candidate = Candidate::factory()->create(['training_status' => 'completed']);

        // Good attendance
        for ($i = 1; $i <= 10; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => 'present',
            ]);
        }

        // Passed final assessment
        TrainingAssessment::factory()->create([
            'candidate_id' => $candidate->id,
            'assessment_type' => 'final',
            'result' => 'pass',
        ]);

        $certificate = $this->service->generateCertificate($candidate->id);

        $this->assertInstanceOf(TrainingCertificate::class, $certificate);
        $this->assertNotNull($certificate->certificate_number);
    }

    #[Test]
    public function it_rejects_certificate_for_low_attendance()
    {
        $candidate = Candidate::factory()->create();

        // Poor attendance (60%)
        for ($i = 1; $i <= 10; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => $i <= 6 ? 'present' : 'absent',
            ]);
        }

        TrainingAssessment::factory()->create([
            'candidate_id' => $candidate->id,
            'assessment_type' => 'final',
            'result' => 'pass',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('minimum attendance requirement');

        $this->service->generateCertificate($candidate->id);
    }

    #[Test]
    public function it_rejects_certificate_without_final_assessment()
    {
        $candidate = Candidate::factory()->create();

        // Good attendance
        for ($i = 1; $i <= 10; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => 'present',
            ]);
        }

        // Only midterm assessment
        TrainingAssessment::factory()->create([
            'candidate_id' => $candidate->id,
            'assessment_type' => 'midterm',
            'result' => 'pass',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not passed final assessment');

        $this->service->generateCertificate($candidate->id);
    }

    #[Test]
    public function it_rejects_certificate_for_failed_final_assessment()
    {
        $candidate = Candidate::factory()->create();

        // Good attendance
        for ($i = 1; $i <= 10; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => 'present',
            ]);
        }

        // Failed final assessment
        TrainingAssessment::factory()->create([
            'candidate_id' => $candidate->id,
            'assessment_type' => 'final',
            'result' => 'fail',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not passed final assessment');

        $this->service->generateCertificate($candidate->id);
    }

    #[Test]
    public function it_rejects_certificate_for_at_risk_candidate()
    {
        $candidate = Candidate::factory()->create(['training_status' => 'at_risk']);

        // Good attendance
        for ($i = 1; $i <= 10; $i++) {
            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => "2024-06-{$i}",
                'status' => 'present',
            ]);
        }

        TrainingAssessment::factory()->create([
            'candidate_id' => $candidate->id,
            'assessment_type' => 'final',
            'result' => 'pass',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('at-risk');

        $this->service->generateCertificate($candidate->id);
    }

    // =========================================================================
    // BATCH STATISTICS
    // =========================================================================

    #[Test]
    public function it_calculates_batch_statistics()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(3)->create([
            'batch_id' => $batch->id,
            'training_status' => 'completed',
        ]);
        Candidate::factory()->count(5)->create([
            'batch_id' => $batch->id,
            'training_status' => 'ongoing',
        ]);
        Candidate::factory()->count(2)->create([
            'batch_id' => $batch->id,
            'training_status' => 'at_risk',
        ]);

        $stats = $this->service->getBatchStatistics($batch->id);

        $this->assertEquals(10, $stats['total_candidates']);
        $this->assertEquals(3, $stats['completed']);
        $this->assertEquals(5, $stats['ongoing']);
        $this->assertEquals(2, $stats['at_risk']);
        $this->assertEquals(30.0, $stats['completion_rate']);
    }

    // =========================================================================
    // AT-RISK CANDIDATES
    // =========================================================================

    #[Test]
    public function it_returns_at_risk_candidates()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(3)->create([
            'batch_id' => $batch->id,
            'training_status' => 'at_risk',
        ]);
        Candidate::factory()->count(5)->create([
            'batch_id' => $batch->id,
            'training_status' => 'ongoing',
        ]);

        $atRisk = $this->service->getAtRiskCandidates($batch->id);

        $this->assertCount(3, $atRisk);
    }

    #[Test]
    public function at_risk_candidates_include_attendance_data()
    {
        $candidate = Candidate::factory()->create(['training_status' => 'at_risk']);

        TrainingAttendance::factory()->count(5)->create([
            'candidate_id' => $candidate->id,
            'status' => 'present',
        ]);

        $atRisk = $this->service->getAtRiskCandidates();

        $this->assertArrayHasKey('attendance', $atRisk->first());
        $this->assertEquals(5, $atRisk->first()['attendance']['total_sessions']);
    }
}
