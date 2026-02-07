<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Training;
use App\Models\TrainingAssessment;
use App\Enums\TrainingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrainingDualStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campus = Campus::factory()->create();
        $this->batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'active',
        ]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    // =========================================================================
    // DUAL STATUS DASHBOARD
    // =========================================================================

    #[Test]
    public function dual_status_dashboard_is_accessible()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('training.dual-status-dashboard', $this->batch));

        $response->assertStatus(200);
        $response->assertViewIs('training.dual-status-dashboard');
        $response->assertViewHas('batch');
        $response->assertViewHas('summary');
    }

    #[Test]
    public function dual_status_dashboard_shows_correct_counts()
    {
        $candidate1 = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
        ]);
        Training::factory()->create([
            'candidate_id' => $candidate1->id,
            'batch_id' => $this->batch->id,
            'technical_training_status' => TrainingProgress::COMPLETED,
            'soft_skills_status' => TrainingProgress::IN_PROGRESS,
        ]);

        $candidate2 = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
        ]);
        Training::factory()->create([
            'candidate_id' => $candidate2->id,
            'batch_id' => $this->batch->id,
            'technical_training_status' => TrainingProgress::IN_PROGRESS,
            'soft_skills_status' => TrainingProgress::NOT_STARTED,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('training.dual-status-dashboard', $this->batch));

        $response->assertStatus(200);
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_candidates'] === 2
                && $summary['technical']['completed'] === 1
                && $summary['technical']['in_progress'] === 1
                && $summary['soft_skills']['in_progress'] === 1;
        });
    }

    // =========================================================================
    // CANDIDATE PROGRESS
    // =========================================================================

    #[Test]
    public function candidate_progress_page_is_accessible()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
        ]);
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('training.candidate-progress', $training));

        $response->assertStatus(200);
        $response->assertViewIs('training.candidate-progress');
        $response->assertViewHas('training');
        $response->assertViewHas('progress');
    }

    // =========================================================================
    // TYPED ASSESSMENT
    // =========================================================================

    #[Test]
    public function can_record_typed_technical_assessment()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
        ]);
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('training.store-typed-assessment', $training), [
                'candidate_id' => $candidate->id,
                'assessment_type' => 'midterm',
                'training_type' => 'technical',
                'score' => 75,
                'max_score' => 100,
                'notes' => 'Good performance',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('training_assessments', [
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'assessment_type' => 'midterm',
            'training_type' => 'technical',
            'result' => 'pass',
        ]);
    }

    #[Test]
    public function can_record_typed_soft_skills_assessment()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
        ]);
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('training.store-typed-assessment', $training), [
                'candidate_id' => $candidate->id,
                'assessment_type' => 'final',
                'training_type' => 'soft_skills',
                'score' => 60,
                'max_score' => 100,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('training_assessments', [
            'training_id' => $training->id,
            'assessment_type' => 'final',
            'training_type' => 'soft_skills',
        ]);
    }

    #[Test]
    public function typed_assessment_validation_rejects_invalid_data()
    {
        $candidate = Candidate::factory()->create();
        $training = Training::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($this->admin)
            ->post(route('training.store-typed-assessment', $training), [
                'candidate_id' => $candidate->id,
                // missing required fields
            ]);

        $response->assertSessionHasErrors(['assessment_type', 'training_type', 'score', 'max_score']);
    }

    // =========================================================================
    // COMPLETE TRAINING TYPE
    // =========================================================================

    #[Test]
    public function can_complete_technical_training_type()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
        ]);
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
        ]);

        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'midterm',
            'training_type' => 'technical',
            'score' => 70,
            'total_score' => 70,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        TrainingAssessment::factory()->create([
            'training_id' => $training->id,
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'training_type' => 'technical',
            'score' => 80,
            'total_score' => 80,
            'max_score' => 100,
            'result' => 'pass',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('training.complete-training-type', $training), [
                'training_type' => 'technical',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $training->refresh();
        $this->assertEquals(TrainingProgress::COMPLETED, $training->technical_training_status);
    }

    #[Test]
    public function cannot_complete_training_type_without_assessments()
    {
        $candidate = Candidate::factory()->create();
        $training = Training::factory()->inProgress()->create([
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('training.complete-training-type', $training), [
                'training_type' => 'technical',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function unauthenticated_users_cannot_access_dual_status_dashboard()
    {
        $response = $this->get(route('training.dual-status-dashboard', $this->batch));

        $response->assertRedirect(route('login'));
    }
}
