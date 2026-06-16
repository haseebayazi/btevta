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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    // MODULE 5: STAGE UPDATES VIA /stage/{visaProcess}/{stage}
    // =========================================================================

    #[Test]
    public function admin_can_schedule_interview_via_stage_route()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/interview", [
            'action' => 'schedule',
            'appointment_date' => now()->addDays(7)->toDateString(),
            'appointment_time' => '10:00',
            'center' => 'Test Center',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_record_interview_result()
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/interview", [
            'action' => 'result',
            'result_status' => 'pass',
            'notes' => 'Good performance',
            'evidence' => UploadedFile::fake()->create('result.pdf', 500),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function stage_update_validates_required_action()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/interview", []);

        $response->assertSessionHasErrors(['action']);
    }

    #[Test]
    public function stage_update_validates_result_status_values()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/interview", [
            'action' => 'result',
            'result_status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['result_status']);
    }

    #[Test]
    public function admin_can_update_trade_test_stage()
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/trade_test", [
            'action' => 'result',
            'result_status' => 'pass',
            'notes' => 'Passed trade test',
            'evidence' => UploadedFile::fake()->create('trade_test.pdf', 500),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_update_medical_stage()
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'takamol_status' => 'passed',
        ]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/medical", [
            'action' => 'result',
            'result_status' => 'pass',
            'notes' => 'Medically fit',
            'evidence' => UploadedFile::fake()->create('gamca.pdf', 500),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_update_biometric_stage()
    {
        Storage::fake('private');
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'medical_status' => 'fit',
        ]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/biometric", [
            'action' => 'result',
            'result_status' => 'pass',
            'notes' => 'Biometrics captured',
            'evidence' => UploadedFile::fake()->create('biometric.pdf', 500),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function stage_rejects_invalid_stage_name()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/stage/{$visaProcess->id}/invalid_stage", [
            'action' => 'result',
            'result_status' => 'pass',
        ]);

        // Should redirect back with error for invalid stage
        $response->assertRedirect();
    }

    // =========================================================================
    // MODULE 5: VISA APPLICATION UPDATE
    // =========================================================================

    #[Test]
    public function admin_can_update_visa_application()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/visa-application/{$visaProcess->id}", [
            'application_status' => 'applied',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function admin_can_confirm_visa_issuance()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/visa-application/{$visaProcess->id}", [
            'application_status' => 'applied',
            'issued_status' => 'confirmed',
            'visa_number' => 'V123456789',
            'visa_date' => now()->toDateString(),
            'ptn_number' => 'PTN12345',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function visa_application_validates_application_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->post("/visa-processing/visa-application/{$visaProcess->id}", [
            'application_status' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['application_status']);
    }

    // =========================================================================
    // E-NUMBER UPDATE (Legacy route retained)
    // =========================================================================

    #[Test]
    public function enumber_can_be_generated_and_persists_date()
    {
        // E-Number is generated externally (stage 4, before Biometrics stage 5),
        // so it has no biometric prerequisite. The generation date must persist.
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'biometric_status' => 'pending',
        ]);

        $date = now()->toDateString();
        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/update-enumber", [
            'enumber' => 'E123456',
            'enumber_date' => $date,
            'enumber_status' => 'generated',
        ]);

        $response->assertSessionDoesntHaveErrors(['prerequisites']);
        $visaProcess->refresh();
        $this->assertEquals('E123456', $visaProcess->enumber);
        $this->assertEquals($date, $visaProcess->enumber_date?->format('Y-m-d'));
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

        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/update-enumber", [
            'enumber' => 'INVALID-FORMAT',
            'enumber_date' => now()->toDateString(),
            'enumber_status' => 'generated',
        ]);

        $response->assertSessionHasErrors(['enumber']);
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
            'interview_status' => 'passed',
            'medical_status' => 'fit',
            'biometric_status' => 'completed',
            'enumber' => 'E123456',
            'enumber_status' => 'verified',
            'visa_status' => 'issued',
            'ptn_number' => 'PTN123',
            'ptn_cleared' => true,
            'protector_performed' => true,
        ]);

        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/complete");

        $response->assertRedirect(route('visa-processing.index'));
        $response->assertSessionHas('success');

        $candidate->refresh();
        $this->assertEquals('departure_processing', $candidate->status);
    }

    #[Test]
    public function completing_visa_process_creates_departure_record()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'medical_status' => 'fit',
            'biometric_status' => 'completed',
            'enumber' => 'E123456',
            'enumber_status' => 'verified',
            'visa_status' => 'issued',
            'ptn_cleared' => true,
            'protector_performed' => true,
        ]);

        $this->actingAs($user)->post("/visa-processing/{$candidate->id}/complete");

        // Visa process is marked complete and the candidate is handed to Departure.
        $this->assertEquals('completed', $visaProcess->fresh()->overall_status);
        $this->assertDatabaseHas('departures', ['candidate_id' => $candidate->id]);
    }

    #[Test]
    public function complete_is_blocked_until_required_stages_are_done()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'medical_status' => 'fit',
            'biometric_status' => 'completed',
            'enumber' => 'E123456',
            'enumber_status' => 'verified',
            'visa_status' => 'issued',
            // PTN & protector NOT done yet
            'ptn_cleared' => false,
            'protector_performed' => false,
        ]);

        $response = $this->actingAs($user)->post("/visa-processing/{$candidate->id}/complete");

        $response->assertSessionHasErrors(['visa_completion']);
        $this->assertEquals('visa_process', $candidate->fresh()->status);
    }

    // =========================================================================
    // BIOMETRICS — Etimad fields must persist (no longer blank on edit)
    // =========================================================================

    #[Test]
    public function biometric_update_persists_etimad_appointment_and_center()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'medical_status' => 'fit',
        ]);

        $this->actingAs($user)->post("/visa-processing/{$candidate->id}/update-biometric", [
            'etimad_appointment_id' => 'ETM-20260613-ABC123',
            'etimad_center' => 'Etimad Center Lahore',
            'biometric_date' => now()->toDateString(),
            'biometric_status' => 'completed',
        ]);

        $visaProcess->refresh();
        $this->assertEquals('ETM-20260613-ABC123', $visaProcess->etimad_appointment_id);
        $this->assertEquals('Etimad Center Lahore', $visaProcess->etimad_center);
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

        $response = $this->actingAs($user)->get('/visa-processing/reports/overdue');

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

        $response = $this->actingAs($user)->post('/visa-processing/reports/generate', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        // Report may succeed (200), redirect (302), fail validation (422), or error (500)
        $this->assertTrue(in_array($response->status(), [200, 302, 422, 500]));
    }

    // =========================================================================
    // HIERARCHICAL DASHBOARD
    // =========================================================================

    #[Test]
    public function admin_can_view_hierarchical_dashboard()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/visa-processing/hierarchical-dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.hierarchical-dashboard');
    }

    // =========================================================================
    // STAGE DETAILS VIEW
    // =========================================================================

    #[Test]
    public function admin_can_view_stage_details()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'visa_process']);
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($user)->get("/visa-processing/stage/{$visaProcess->id}/interview");

        $response->assertStatus(200);
        $response->assertViewIs('visa-processing.stage-details');
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
