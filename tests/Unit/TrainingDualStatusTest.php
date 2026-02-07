<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Enums\TrainingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrainingDualStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    // =========================================================================
    // DUAL STATUS - BASIC MODEL
    // =========================================================================

    #[Test]
    public function training_model_starts_with_not_started_status()
    {
        $candidate = Candidate::factory()->create();
        $training = Training::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertEquals(TrainingProgress::NOT_STARTED, $training->technical_training_status);
        $this->assertEquals(TrainingProgress::NOT_STARTED, $training->soft_skills_status);
        $this->assertEquals('not_started', $training->status);
    }

    #[Test]
    public function training_completion_percentage_starts_at_zero()
    {
        $training = Training::factory()->create();

        $this->assertEquals(0, $training->completion_percentage);
    }

    #[Test]
    public function training_completion_percentage_shows_50_when_one_complete()
    {
        $training = Training::factory()->technicalComplete()->create();

        $this->assertEquals(75, $training->completion_percentage); // 50 tech + 25 soft in progress
    }

    #[Test]
    public function training_completion_percentage_shows_100_when_both_complete()
    {
        $training = Training::factory()->completed()->create();

        $this->assertEquals(100, $training->completion_percentage);
    }

    #[Test]
    public function is_both_complete_returns_false_when_partially_done()
    {
        $training = Training::factory()->technicalComplete()->create();

        $this->assertFalse($training->isBothComplete());
    }

    #[Test]
    public function is_both_complete_returns_true_when_fully_done()
    {
        $training = Training::factory()->completed()->create();

        $this->assertTrue($training->isBothComplete());
    }

    // =========================================================================
    // DUAL STATUS - TECHNICAL TRAINING
    // =========================================================================

    #[Test]
    public function start_technical_training_updates_status()
    {
        $training = Training::factory()->create();

        $training->startTechnicalTraining();
        $training->refresh();

        $this->assertEquals(TrainingProgress::IN_PROGRESS, $training->technical_training_status);
        $this->assertEquals('in_progress', $training->status);
    }

    #[Test]
    public function start_technical_training_is_idempotent()
    {
        $training = Training::factory()->inProgress()->create();
        $training->startTechnicalTraining();

        $this->assertEquals(TrainingProgress::IN_PROGRESS, $training->technical_training_status);
    }

    #[Test]
    public function complete_technical_training_requires_passed_assessments()
    {
        $training = Training::factory()->inProgress()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('technical assessments');
        $training->completeTechnicalTraining();
    }

    #[Test]
    public function complete_technical_training_with_valid_assessments()
    {
        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
        ]);

        // Create required technical assessments
        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'midterm',
            'training_type' => 'technical',
            'score' => 75,
            'total_score' => 75,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'final',
            'training_type' => 'technical',
            'score' => 80,
            'total_score' => 80,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $training->completeTechnicalTraining();
        $training->refresh();

        $this->assertEquals(TrainingProgress::COMPLETED, $training->technical_training_status);
        $this->assertNotNull($training->technical_completed_at);
    }

    // =========================================================================
    // DUAL STATUS - SOFT SKILLS TRAINING
    // =========================================================================

    #[Test]
    public function start_soft_skills_training_updates_status()
    {
        $training = Training::factory()->create();

        $training->startSoftSkillsTraining();
        $training->refresh();

        $this->assertEquals(TrainingProgress::IN_PROGRESS, $training->soft_skills_status);
    }

    #[Test]
    public function complete_soft_skills_training_requires_passed_assessments()
    {
        $training = Training::factory()->inProgress()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('soft skills assessments');
        $training->completeSoftSkillsTraining();
    }

    #[Test]
    public function complete_soft_skills_training_with_valid_assessments()
    {
        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
        ]);

        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'final',
            'training_type' => 'soft_skills',
            'score' => 70,
            'total_score' => 70,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $training->completeSoftSkillsTraining();
        $training->refresh();

        $this->assertEquals(TrainingProgress::COMPLETED, $training->soft_skills_status);
        $this->assertNotNull($training->soft_skills_completed_at);
    }

    // =========================================================================
    // DUAL STATUS - OVERALL COMPLETION
    // =========================================================================

    #[Test]
    public function completing_both_tracks_fires_training_completed_event()
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\TrainingCompleted::class]);

        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();
        $training = Training::factory()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'technical_training_status' => TrainingProgress::COMPLETED,
            'technical_completed_at' => now(),
            'soft_skills_status' => TrainingProgress::IN_PROGRESS,
            'status' => 'in_progress',
        ]);

        // Add soft skills final assessment
        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'final',
            'training_type' => 'soft_skills',
            'score' => 65,
            'total_score' => 65,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $training->completeSoftSkillsTraining();

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\TrainingCompleted::class);
    }

    #[Test]
    public function completing_both_tracks_sets_completed_status()
    {
        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();
        $training = Training::factory()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'technical_training_status' => TrainingProgress::COMPLETED,
            'technical_completed_at' => now(),
            'soft_skills_status' => TrainingProgress::IN_PROGRESS,
            'status' => 'in_progress',
        ]);

        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'final',
            'training_type' => 'soft_skills',
            'score' => 72,
            'total_score' => 72,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $training->completeSoftSkillsTraining();
        $training->refresh();

        $this->assertEquals('completed', $training->status);
        $this->assertNotNull($training->completed_at);
    }

    // =========================================================================
    // ASSESSMENT GRADING
    // =========================================================================

    #[Test]
    public function grade_a_for_score_90_or_above()
    {
        $this->assertEquals('A', TrainingAssessment::calculateGrade(95, 100));
        $this->assertEquals('A', TrainingAssessment::calculateGrade(90, 100));
    }

    #[Test]
    public function grade_b_for_score_80_to_89()
    {
        $this->assertEquals('B', TrainingAssessment::calculateGrade(85, 100));
        $this->assertEquals('B', TrainingAssessment::calculateGrade(80, 100));
    }

    #[Test]
    public function grade_c_for_score_70_to_79()
    {
        $this->assertEquals('C', TrainingAssessment::calculateGrade(75, 100));
    }

    #[Test]
    public function grade_d_for_score_50_to_69()
    {
        $this->assertEquals('D', TrainingAssessment::calculateGrade(60, 100));
        $this->assertEquals('D', TrainingAssessment::calculateGrade(50, 100));
    }

    #[Test]
    public function grade_f_for_score_below_50()
    {
        $this->assertEquals('F', TrainingAssessment::calculateGrade(40, 100));
        $this->assertEquals('F', TrainingAssessment::calculateGrade(0, 100));
    }

    #[Test]
    public function grade_auto_calculated_on_save()
    {
        $candidate = Candidate::factory()->create();
        $batch = Batch::factory()->create();
        $training = Training::factory()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
        ]);

        $assessment = TrainingAssessment::create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => 'midterm',
            'training_type' => 'technical',
            'score' => 85,
            'max_score' => 100,
            'total_score' => 85,
            'assessment_date' => now(),
            'result' => 'pass',
        ]);

        $this->assertEquals('B', $assessment->grade);
    }

    // =========================================================================
    // FIND OR CREATE
    // =========================================================================

    #[Test]
    public function find_or_create_for_candidate_creates_new_record()
    {
        $candidate = Candidate::factory()->create(['batch_id' => Batch::factory()->create()->id]);

        $training = Training::findOrCreateForCandidate($candidate);

        $this->assertInstanceOf(Training::class, $training);
        $this->assertEquals($candidate->id, $training->candidate_id);
        $this->assertEquals($candidate->batch_id, $training->batch_id);
    }

    #[Test]
    public function find_or_create_for_candidate_returns_existing_record()
    {
        $candidate = Candidate::factory()->create();
        $existing = Training::factory()->create(['candidate_id' => $candidate->id]);

        $training = Training::findOrCreateForCandidate($candidate);

        $this->assertEquals($existing->id, $training->id);
    }

    // =========================================================================
    // ENUM ENHANCEMENTS
    // =========================================================================

    #[Test]
    public function training_progress_enum_has_icon_method()
    {
        $this->assertEquals('fas fa-clock', TrainingProgress::NOT_STARTED->icon());
        $this->assertEquals('fas fa-spinner fa-spin', TrainingProgress::IN_PROGRESS->icon());
        $this->assertEquals('fas fa-check-circle', TrainingProgress::COMPLETED->icon());
    }

    // =========================================================================
    // CANDIDATE RELATIONSHIP
    // =========================================================================

    #[Test]
    public function candidate_has_training_relationship()
    {
        $candidate = Candidate::factory()->create();
        $training = Training::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertInstanceOf(Training::class, $candidate->training);
        $this->assertEquals($training->id, $candidate->training->id);
    }
}
