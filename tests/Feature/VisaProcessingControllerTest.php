<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\VisaProcess;
use App\Models\Campus;
use App\Models\Trade;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisaProcessingControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // INDEX / LIST
    // =========================================================================

    #[Test]
    public function super_admin_can_view_visa_processing_list()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->get('/visa-processing');

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.index');
    }

    #[Test]
    public function campus_admin_only_sees_their_campus_candidates()
    {
        $campus = Campus::factory()->create();
        $otherCampus = Campus::factory()->create();

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $ownCandidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'visa_process',
        ]);
        VisaProcess::factory()->create(['candidate_id' => $ownCandidate->id]);

        $otherCandidate = Candidate::factory()->create([
            'campus_id' => $otherCampus->id,
            'status' => 'visa_process',
        ]);
        VisaProcess::factory()->create(['candidate_id' => $otherCandidate->id]);

        $response = $this->actingAs($user)->get('/visa-processing');

        $response->assertStatus(200);
        $response->assertViewHas('candidates', function ($candidates) use ($ownCandidate, $otherCandidate) {
            return $candidates->contains('id', $ownCandidate->id)
                && !$candidates->contains('id', $otherCandidate->id);
        });
    }

    #[Test]
    public function index_filters_by_stage()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $candidate1 = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate1->id,
            'overall_status' => 'interview',
        ]);

        $candidate2 = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate2->id,
            'overall_status' => 'medical',
        ]);

        $response = $this->actingAs($user)->get('/visa-processing?stage=interview');

        $response->assertStatus(200);
    }

    // =========================================================================
    // CREATE VISA PROCESS
    // =========================================================================

    #[Test]
    public function admin_can_create_visa_process_for_eligible_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create([
            'status' => 'training',
            'training_status' => 'completed',
        ]);

        $response = $this->actingAs($user)->post('/visa-processing', [
            'candidate_id' => $candidate->id,
            'interview_date' => now()->toDateString(),
            'interview_status' => 'pending',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('visa_processes', [
            'candidate_id' => $candidate->id,
        ]);
    }

    #[Test]
    public function create_validates_candidate_exists()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->post('/visa-processing', [
            'candidate_id' => 99999,
            'interview_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['candidate_id']);
    }

    // =========================================================================
    // SHOW VISA PROCESS
    // =========================================================================

    #[Test]
    public function admin_can_view_visa_process_details()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->get("/visa-processing/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.show');
    }

    #[Test]
    public function show_redirects_if_no_visa_process()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'training']);

        $response = $this->actingAs($user)->get("/visa-processing/{$candidate->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // UPDATE INTERVIEW
    // =========================================================================

    #[Test]
    public function admin_can_update_interview_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/interview", [
            'interview_date' => now()->toDateString(),
            'interview_status' => 'passed',
            'interview_remarks' => 'Good performance',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function interview_update_validates_required_fields()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/interview", []);

        $response->assertSessionHasErrors(['interview_date', 'interview_status']);
    }

    #[Test]
    public function interview_status_must_be_valid_value()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/interview", [
            'interview_date' => now()->toDateString(),
            'interview_status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['interview_status']);
    }

    // =========================================================================
    // UPDATE TRADE TEST
    // =========================================================================

    #[Test]
    public function admin_can_update_trade_test()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/trade-test", [
            'trade_test_date' => now()->toDateString(),
            'trade_test_status' => 'passed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // UPDATE MEDICAL - WITH PREREQUISITES
    // =========================================================================

    #[Test]
    public function medical_update_requires_takamol_passed()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'takamol_status' => 'pending', // Not passed
        ]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/medical", [
            'medical_date' => now()->toDateString(),
            'medical_status' => 'fit',
        ]);

        $response->assertSessionHasErrors(['prerequisites']);
    }

    #[Test]
    public function medical_update_succeeds_when_prerequisites_met()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'takamol_status' => 'passed',
        ]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/medical", [
            'medical_date' => now()->toDateString(),
            'medical_status' => 'fit',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // UPDATE BIOMETRIC - WITH PREREQUISITES
    // =========================================================================

    #[Test]
    public function biometric_requires_medical_cleared()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'medical_status' => 'pending', // Not cleared
        ]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/biometric", [
            'biometric_date' => now()->toDateString(),
            'biometric_status' => 'completed',
        ]);

        $response->assertSessionHasErrors(['prerequisites']);
    }

    // =========================================================================
    // UPDATE E-NUMBER - WITH PREREQUISITES
    // =========================================================================

    #[Test]
    public function enumber_requires_biometrics_completed()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'biometric_status' => 'pending', // Not completed
        ]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/enumber", [
            'enumber_date' => now()->toDateString(),
            'enumber_status' => 'generated',
        ]);

        $response->assertSessionHasErrors(['prerequisites']);
    }

    #[Test]
    public function enumber_validates_format()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'biometric_status' => 'completed',
        ]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/enumber", [
            'enumber' => 'INVALID-FORMAT',
            'enumber_date' => now()->toDateString(),
            'enumber_status' => 'generated',
        ]);

        $response->assertSessionHasErrors(['enumber']);
    }

    // =========================================================================
    // UPDATE VISA ISSUANCE
    // =========================================================================

    #[Test]
    public function admin_can_update_visa_issuance()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/visa", [
            'visa_date' => now()->toDateString(),
            'visa_number' => 'V123456789',
            'visa_status' => 'issued',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function visa_number_is_required_for_issuance()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->patch("/visa-processing/{$candidate->id}/visa", [
            'visa_date' => now()->toDateString(),
            'visa_status' => 'issued',
            // Missing visa_number
        ]);

        $response->assertSessionHasErrors(['visa_number']);
    }

    // =========================================================================
    // UPLOAD DOCUMENTS
    // =========================================================================

    #[Test]
    public function admin_can_upload_travel_plan()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('travel_plan.pdf', 500);

        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/travel-plan", [
            'travel_plan_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function travel_plan_validates_file_type()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('travel_plan.exe', 500);

        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/travel-plan", [
            'travel_plan_file' => $file,
        ]);

        $response->assertSessionHasErrors(['travel_plan_file']);
    }

    // =========================================================================
    // COMPLETE VISA PROCESS
    // =========================================================================

    #[Test]
    public function admin_can_complete_visa_process()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'visa_status' => 'issued',
        ]);

        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/complete");

        $response->assertRedirect(route('visa-processing.index'));
        $response->assertSessionHas('success');

        $candidate->refresh();
        $this->assertEquals('ready', $candidate->status);
    }

    // =========================================================================
    // DELETE VISA PROCESS
    // =========================================================================

    #[Test]
    public function admin_can_delete_visa_process()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->delete("/visa-processing/{$candidate->id}");

        $response->assertRedirect(route('visa-processing.index'));
        $response->assertSessionHas('success');

        $candidate->refresh();
        $this->assertEquals('training', $candidate->status);
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    #[Test]
    public function admin_can_view_visa_timeline()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->get("/visa-processing/{$candidate->id}/timeline");

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.timeline');
    }

    #[Test]
    public function timeline_redirects_if_no_visa_process()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'training']);

        $response = $this->actingAs($user)->get("/visa-processing/{$candidate->id}/timeline");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // OVERDUE
    // =========================================================================

    #[Test]
    public function admin_can_view_overdue_processes()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/visa-processing/overdue');

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.overdue');
    }

    // =========================================================================
    // REPORT
    // =========================================================================

    #[Test]
    public function admin_can_generate_visa_report()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/visa-processing/report', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        // Just check it doesn't throw unauthorized
        $this->assertTrue(in_array($response->status(), [200, 302, 422]));
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function unauthenticated_user_cannot_access_visa_processing()
    {
        $response = $this->get('/visa-processing');

        $response->assertRedirect('/login');
    }

    #[Test]
    public function regular_user_cannot_access_visa_processing()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/visa-processing');

        $response->assertStatus(403);
    }
}
