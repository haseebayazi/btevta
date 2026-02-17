<?php

namespace Tests\Feature;

use App\Enums\CandidateStatus;
use App\Enums\VisaStage;
use App\Enums\VisaStageResult;
use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use App\Events\TrainingCompleted;
use App\Listeners\HandleTrainingCompleted;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Training;
use App\Models\User;
use App\Models\VisaProcess;
use App\ValueObjects\VisaStageDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisaProcessingEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $candidate;
    protected VisaProcess $visaProcess;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'force_password_change' => false,
        ]);
        $this->candidate = Candidate::factory()->create([
            'status' => CandidateStatus::VISA_PROCESS->value,
        ]);
        $this->visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $this->candidate->id,
            'overall_status' => 'initiated',
            'interview_status' => 'pending',
            'trade_test_status' => 'pending',
            'takamol_status' => 'pending',
            'medical_status' => 'pending',
            'biometric_status' => 'pending',
            'visa_status' => 'pending',
        ]);
    }

    // =========================================================================
    // Hierarchical Dashboard Tests
    // =========================================================================

    public function test_admin_can_access_hierarchical_dashboard(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('visa-processing.hierarchical-dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.hierarchical-dashboard');
        $response->assertViewHas('dashboard');
    }

    public function test_hierarchical_dashboard_shows_category_counts(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('visa-processing.hierarchical-dashboard'));

        $response->assertStatus(200);
        $dashboard = $response->viewData('dashboard');
        $this->assertArrayHasKey('counts', $dashboard);
        $this->assertArrayHasKey('items', $dashboard);
        $this->assertArrayHasKey('scheduled', $dashboard['counts']);
        $this->assertArrayHasKey('pending', $dashboard['counts']);
        $this->assertArrayHasKey('passed', $dashboard['counts']);
        $this->assertArrayHasKey('failed', $dashboard['counts']);
    }

    public function test_unauthenticated_user_redirected_from_hierarchical_dashboard(): void
    {
        $response = $this->get(route('visa-processing.hierarchical-dashboard'));
        $response->assertRedirect('/login');
    }

    // =========================================================================
    // Stage Details Tests
    // =========================================================================

    public function test_admin_can_view_stage_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('visa-processing.stage-details', [$this->visaProcess, 'interview']));

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.stage-details');
        $response->assertViewHas('visaProcess');
        $response->assertViewHas('stage', 'interview');
        $response->assertViewHas('details');
    }

    public function test_invalid_stage_returns_404(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('visa-processing.stage-details', [$this->visaProcess, 'invalid_stage']));

        $response->assertStatus(404);
    }

    // =========================================================================
    // Schedule Stage Tests
    // =========================================================================

    public function test_admin_can_schedule_interview(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('visa-processing.update-stage', [$this->visaProcess, 'interview']), [
                'action' => 'schedule',
                'appointment_date' => now()->addDays(7)->format('Y-m-d'),
                'appointment_time' => '10:00',
                'center' => 'Test Center Lahore',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->visaProcess->refresh();
        $this->assertEquals('scheduled', $this->visaProcess->interview_status);
        $this->assertNotNull($this->visaProcess->interview_details);
        $this->assertEquals('Test Center Lahore', $this->visaProcess->interview_details['center']);
    }

    public function test_schedule_validation_requires_future_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('visa-processing.update-stage', [$this->visaProcess, 'interview']), [
                'action' => 'schedule',
                'appointment_date' => '2020-01-01',
                'appointment_time' => '10:00',
                'center' => 'Test Center',
            ]);

        $response->assertSessionHasErrors('appointment_date');
    }

    // =========================================================================
    // Record Result Tests
    // =========================================================================

    public function test_admin_can_record_stage_result_with_evidence(): void
    {
        Storage::fake('private');

        // First schedule the interview
        $this->visaProcess->update([
            'interview_status' => 'scheduled',
            'interview_details' => [
                'appointment_date' => now()->format('Y-m-d'),
                'appointment_time' => '10:00',
                'center' => 'Test Center',
                'result_status' => 'scheduled',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('visa-processing.update-stage', [$this->visaProcess, 'interview']), [
                'action' => 'result',
                'result_status' => 'pass',
                'notes' => 'Excellent performance',
                'evidence' => UploadedFile::fake()->create('result.pdf', 500, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->visaProcess->refresh();
        $details = VisaStageDetails::fromArray($this->visaProcess->interview_details);
        $this->assertEquals('pass', $details->resultStatus);
        $this->assertEquals('Excellent performance', $details->notes);
        $this->assertTrue($details->hasEvidence());
    }

    // =========================================================================
    // Visa Application Status Tests
    // =========================================================================

    public function test_admin_can_update_visa_application_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('visa-processing.update-visa-application', $this->visaProcess), [
                'application_status' => 'applied',
                'notes' => 'Application submitted',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->visaProcess->refresh();
        $this->assertEquals(VisaApplicationStatus::APPLIED, $this->visaProcess->visa_application_status);
    }

    public function test_visa_confirmed_updates_candidate_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('visa-processing.update-visa-application', $this->visaProcess), [
                'application_status' => 'applied',
                'issued_status' => 'confirmed',
                'notes' => 'Visa confirmed',
            ]);

        $response->assertRedirect();

        $this->candidate->refresh();
        $this->assertEquals(CandidateStatus::VISA_APPROVED->value, $this->candidate->status);
    }

    // =========================================================================
    // Training → Visa Transition Tests
    // =========================================================================

    public function test_training_completed_listener_creates_visa_process(): void
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::TRAINING->value,
            'training_status' => 'completed',
        ]);

        $training = Training::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
        ]);

        $event = new TrainingCompleted($training, $candidate);
        $listener = new HandleTrainingCompleted();
        $listener->handle($event);

        $candidate->refresh();
        $this->assertEquals(CandidateStatus::VISA_PROCESS->value, $candidate->status);

        $visaProcess = VisaProcess::where('candidate_id', $candidate->id)->first();
        $this->assertNotNull($visaProcess);
        $this->assertEquals(VisaStage::INITIATED->value, $visaProcess->overall_status);
    }

    public function test_training_completed_listener_does_not_duplicate_visa_process(): void
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::TRAINING->value,
            'training_status' => 'completed',
        ]);

        // Create existing visa process
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'overall_status' => 'initiated',
        ]);

        $training = Training::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
        ]);

        $event = new TrainingCompleted($training, $candidate);
        $listener = new HandleTrainingCompleted();
        $listener->handle($event);

        $this->assertEquals(1, VisaProcess::where('candidate_id', $candidate->id)->count());
    }

    public function test_training_completed_listener_skips_non_training_status(): void
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::VISA_PROCESS->value,
            'training_status' => 'completed',
        ]);

        $training = Training::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
        ]);

        $event = new TrainingCompleted($training, $candidate);
        $listener = new HandleTrainingCompleted();
        $listener->handle($event);

        // Should NOT create visa process because candidate is already in visa_process
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::VISA_PROCESS->value, $candidate->status);
    }

    // =========================================================================
    // VisaProcess Model Enhancement Tests
    // =========================================================================

    public function test_model_casts_json_details_to_array(): void
    {
        $this->visaProcess->update([
            'interview_details' => [
                'appointment_date' => '2026-03-15',
                'center' => 'Test Center',
            ],
        ]);

        $this->visaProcess->refresh();
        $this->assertIsArray($this->visaProcess->interview_details);
        $this->assertEquals('Test Center', $this->visaProcess->interview_details['center']);
    }

    public function test_model_details_object_accessors(): void
    {
        $this->visaProcess->update([
            'interview_details' => [
                'appointment_date' => '2026-03-15',
                'appointment_time' => '10:00',
                'center' => 'Test Center',
                'result_status' => 'pass',
            ],
        ]);

        $this->visaProcess->refresh();
        $details = $this->visaProcess->interview_details_object;

        $this->assertInstanceOf(VisaStageDetails::class, $details);
        $this->assertEquals('2026-03-15', $details->appointmentDate);
        $this->assertTrue($details->isPassed());
    }

    public function test_model_stages_overview(): void
    {
        $overview = $this->visaProcess->getStagesOverview();

        $this->assertArrayHasKey('interview', $overview);
        $this->assertArrayHasKey('trade_test', $overview);
        $this->assertArrayHasKey('takamol', $overview);
        $this->assertArrayHasKey('medical', $overview);
        $this->assertArrayHasKey('biometric', $overview);
        $this->assertArrayHasKey('visa_application', $overview);

        foreach ($overview as $stage) {
            $this->assertArrayHasKey('name', $stage);
            $this->assertArrayHasKey('status', $stage);
            $this->assertArrayHasKey('details', $stage);
            $this->assertArrayHasKey('icon', $stage);
            $this->assertInstanceOf(VisaStageDetails::class, $stage['details']);
        }
    }

    public function test_model_hierarchical_status(): void
    {
        $status = $this->visaProcess->getHierarchicalStatus();

        $this->assertArrayHasKey('scheduled', $status);
        $this->assertArrayHasKey('done', $status);
        $this->assertArrayHasKey('passed', $status);
        $this->assertArrayHasKey('failed', $status);
        $this->assertArrayHasKey('pending', $status);
    }

    public function test_model_casts_visa_application_status(): void
    {
        $this->visaProcess->update([
            'visa_application_status' => 'applied',
        ]);

        $this->visaProcess->refresh();
        $this->assertEquals(VisaApplicationStatus::APPLIED, $this->visaProcess->visa_application_status);
    }

    public function test_model_casts_visa_issued_status(): void
    {
        $this->visaProcess->update([
            'visa_issued_status' => 'confirmed',
        ]);

        $this->visaProcess->refresh();
        $this->assertEquals(VisaIssuedStatus::CONFIRMED, $this->visaProcess->visa_issued_status);
    }
}
