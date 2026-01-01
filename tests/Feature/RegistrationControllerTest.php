<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function admin_can_view_registration_list()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/registration');

        $response->assertStatus(200);
        $response->assertViewIs('registration.index');
    }

    /** @test */
    public function campus_admin_sees_only_their_campus_candidates()
    {
        $campus = Campus::factory()->create();
        $otherCampus = Campus::factory()->create();

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $ownCandidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'screening_passed',
        ]);

        $otherCandidate = Candidate::factory()->create([
            'campus_id' => $otherCampus->id,
            'status' => 'screening_passed',
        ]);

        $response = $this->actingAs($user)->get('/registration');

        $response->assertStatus(200);
        $response->assertViewHas('candidates', function ($candidates) use ($ownCandidate, $otherCandidate) {
            return $candidates->contains('id', $ownCandidate->id)
                && !$candidates->contains('id', $otherCandidate->id);
        });
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function admin_can_view_candidate_registration_details()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->get("/registration/{$candidate->id}");

        $response->assertStatus(200);
        $response->assertViewIs('registration.show');
        $response->assertViewHas('candidate');
    }

    // =========================================================================
    // UPLOAD DOCUMENT
    // =========================================================================

    /** @test */
    public function admin_can_upload_document()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $file = UploadedFile::fake()->create('cnic.pdf', 500);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/documents", [
            'document_type' => 'cnic',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('registration_documents', [
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ]);
    }

    /** @test */
    public function document_upload_validates_file_type()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $file = UploadedFile::fake()->create('document.exe', 500);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/documents", [
            'document_type' => 'cnic',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    /** @test */
    public function document_upload_validates_document_type()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $file = UploadedFile::fake()->create('document.pdf', 500);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/documents", [
            'document_type' => 'invalid_type',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['document_type']);
    }

    /** @test */
    public function document_upload_validates_file_size()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $file = UploadedFile::fake()->create('document.pdf', 6000); // 6MB, exceeds 5120KB limit

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/documents", [
            'document_type' => 'cnic',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    // =========================================================================
    // DELETE DOCUMENT
    // =========================================================================

    /** @test */
    public function admin_can_delete_document()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $document = RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($user)->delete("/registration/documents/{$document->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('registration_documents', ['id' => $document->id]);
    }

    /** @test */
    public function campus_admin_cannot_delete_other_campus_document()
    {
        $campus = Campus::factory()->create();
        $otherCampus = Campus::factory()->create();

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $candidate = Candidate::factory()->create([
            'campus_id' => $otherCampus->id,
        ]);

        $document = RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($user)->delete("/registration/documents/{$document->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // SAVE NEXT OF KIN
    // =========================================================================

    /** @test */
    public function admin_can_save_next_of_kin()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/next-of-kin", [
            'name' => 'John Father',
            'relationship' => 'Father',
            'cnic' => '3520112345678',
            'phone' => '03001234567',
            'address' => '123 Main Street, Lahore',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('next_of_kins', [
            'candidate_id' => $candidate->id,
            'name' => 'John Father',
        ]);
    }

    /** @test */
    public function next_of_kin_validates_required_fields()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/next-of-kin", []);

        $response->assertSessionHasErrors(['name', 'relationship', 'cnic', 'phone', 'address']);
    }

    /** @test */
    public function next_of_kin_validates_cnic_format()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/next-of-kin", [
            'name' => 'John Father',
            'relationship' => 'Father',
            'cnic' => '123', // Invalid
            'phone' => '03001234567',
            'address' => '123 Main Street',
        ]);

        $response->assertSessionHasErrors(['cnic']);
    }

    // =========================================================================
    // SAVE UNDERTAKING
    // =========================================================================

    /** @test */
    public function admin_can_save_undertaking()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/undertaking", [
            'undertaking_type' => 'employment',
            'content' => 'I hereby agree to all terms and conditions of the employment program.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('undertakings', [
            'candidate_id' => $candidate->id,
            'undertaking_type' => 'employment',
        ]);
    }

    /** @test */
    public function undertaking_validates_type()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/undertaking", [
            'undertaking_type' => 'invalid_type',
            'content' => 'Content here',
        ]);

        $response->assertSessionHasErrors(['undertaking_type']);
    }

    // =========================================================================
    // COMPLETE REGISTRATION
    // =========================================================================

    /** @test */
    public function registration_fails_without_required_documents()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        // No documents uploaded

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function registration_fails_without_next_of_kin()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        // Upload required documents
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'education',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'photo',
        ]);

        // No next of kin

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function registration_fails_without_undertaking()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        // Upload required documents
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'education',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'photo',
        ]);

        // Add next of kin
        NextOfKin::factory()->create(['candidate_id' => $candidate->id]);

        // No undertaking

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function registration_succeeds_with_all_requirements()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        // Upload required documents
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'education',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'photo',
        ]);

        // Add next of kin
        NextOfKin::factory()->create(['candidate_id' => $candidate->id]);

        // Add undertaking
        Undertaking::factory()->create([
            'candidate_id' => $candidate->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/complete");

        $response->assertRedirect(route('registration.index'));
        $response->assertSessionHas('success');

        $candidate->refresh();
        $this->assertEquals('registered', $candidate->status);
    }

    /** @test */
    public function registration_fails_with_expired_documents()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        // Upload required documents with expired one
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'expiry_date' => now()->subDays(10), // Expired
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'education',
        ]);
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'photo',
        ]);

        NextOfKin::factory()->create(['candidate_id' => $candidate->id]);
        Undertaking::factory()->create([
            'candidate_id' => $candidate->id,
            'is_completed' => true,
        ]);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/complete");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // =========================================================================
    // VERIFY DOCUMENT
    // =========================================================================

    /** @test */
    public function admin_can_verify_document()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $document = RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post("/registration/documents/{$document->id}/verify", [
            'verification_remarks' => 'Document verified successfully',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals('verified', $document->status);
    }

    /** @test */
    public function regular_user_cannot_verify_document()
    {
        $user = User::factory()->create(['role' => 'user']);
        $candidate = Candidate::factory()->create();
        $document = RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($user)->post("/registration/documents/{$document->id}/verify");

        $response->assertStatus(403);
    }

    // =========================================================================
    // REJECT DOCUMENT
    // =========================================================================

    /** @test */
    public function admin_can_reject_document()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $document = RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post("/registration/documents/{$document->id}/reject", [
            'rejection_reason' => 'Document is blurry and unreadable',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertEquals('rejected', $document->status);
    }

    /** @test */
    public function reject_document_requires_reason()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);
        $document = RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $response = $this->actingAs($user)->post("/registration/documents/{$document->id}/reject", []);

        $response->assertSessionHasErrors(['rejection_reason']);
    }

    // =========================================================================
    // REGISTRATION STATUS
    // =========================================================================

    /** @test */
    public function admin_can_view_registration_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)->get("/registration/{$candidate->id}/status");

        $response->assertStatus(200);
        $response->assertViewIs('registration.status');
    }

    /** @test */
    public function status_returns_json_when_requested()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']);

        $response = $this->actingAs($user)
            ->getJson("/registration/{$candidate->id}/status");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'candidate',
            'registration_status' => [
                'documents',
                'documents_complete',
                'next_of_kin',
                'undertaking',
                'can_complete',
            ],
        ]);
    }

    // =========================================================================
    // START TRAINING
    // =========================================================================

    /** @test */
    public function admin_can_start_training_for_registered_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'registered']);
        $batch = Batch::factory()->create();

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/start-training", [
            'batch_id' => $batch->id,
        ]);

        $response->assertRedirect(route('training.index'));
        $response->assertSessionHas('success');

        $candidate->refresh();
        $this->assertEquals('training', $candidate->status);
        $this->assertEquals($batch->id, $candidate->batch_id);
    }

    /** @test */
    public function start_training_fails_for_non_registered_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'screening_passed']); // Not registered
        $batch = Batch::factory()->create();

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/start-training", [
            'batch_id' => $batch->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function start_training_validates_batch_exists()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'registered']);

        $response = $this->actingAs($user)->post("/registration/{$candidate->id}/start-training", [
            'batch_id' => 99999,
        ]);

        $response->assertSessionHasErrors(['batch_id']);
    }

    // =========================================================================
    // QR CODE VERIFICATION
    // =========================================================================

    /** @test */
    public function valid_qr_code_shows_verification_result()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'registered',
            'cnic' => '3520112345678',
        ]);

        $token = hash('sha256', $candidate->id . $candidate->cnic . config('app.key'));

        $response = $this->get("/registration/verify/{$candidate->id}/{$token}");

        $response->assertStatus(200);
        $response->assertViewIs('registration.verify-result');
        $response->assertViewHas('success', true);
    }

    /** @test */
    public function invalid_qr_token_returns_error()
    {
        $candidate = Candidate::factory()->create(['cnic' => '3520112345678']);

        $response = $this->get("/registration/verify/{$candidate->id}/invalid-token");

        $response->assertStatus(403);
        $response->assertViewHas('success', false);
    }

    /** @test */
    public function qr_verification_fails_for_nonexistent_candidate()
    {
        $response = $this->get('/registration/verify/99999/any-token');

        $response->assertStatus(404);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_registration()
    {
        $response = $this->get('/registration');

        $response->assertRedirect('/login');
    }
}
