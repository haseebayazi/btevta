<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Feature tests for registration API endpoints.
 */
class RegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Trade $trade;
    protected Campus $campus;
    protected Batch $batch;
    protected Candidate $candidate;

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
        $this->candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
        ]);
        Storage::fake('public');
    }

    // ==================== DOCUMENT UPLOAD ====================

    #[Test]
    public function it_uploads_registration_document()
    {
        $file = UploadedFile::fake()->create('cnic.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/documents",
            [
                'document_type' => 'cnic',
                'file' => $file,
                'expiry_date' => now()->addYear()->format('Y-m-d'),
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('registration_documents', [
            'candidate_id' => $this->candidate->id,
            'document_type' => 'cnic',
        ]);
    }

    #[Test]
    public function it_validates_document_type()
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/documents",
            [
                'document_type' => 'invalid_type',
                'file' => $file,
            ]
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function it_accepts_image_documents()
    {
        $file = UploadedFile::fake()->image('photo.jpg', 400, 400);

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/documents",
            [
                'document_type' => 'photo',
                'file' => $file,
            ]
        );

        $response->assertStatus(200);
    }

    // ==================== DOCUMENT VERIFICATION ====================

    #[Test]
    public function it_verifies_document()
    {
        $document = RegistrationDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/test.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/documents/{$document->id}/verify"
        );

        $response->assertOk();

        $document->refresh();
        $this->assertEquals('verified', $document->status);
    }

    #[Test]
    public function it_rejects_document_with_reason()
    {
        $document = RegistrationDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/test.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/documents/{$document->id}/reject",
            [
                'reason' => 'Document is blurry and unreadable',
            ]
        );

        $response->assertOk();

        $document->refresh();
        $this->assertEquals('rejected', $document->status);
        $this->assertStringContainsString('blurry', $document->rejection_reason);
    }

    // ==================== REGISTRATION STATUS ====================

    #[Test]
    public function it_returns_registration_status()
    {
        // Add some documents
        RegistrationDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/cnic.pdf',
            'status' => 'verified',
        ]);

        RegistrationDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_type' => 'education',
            'file_path' => 'documents/education.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->getJson(
            "/registration/{$this->candidate->id}/status"
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'candidate',
            'documents',
            'next_of_kin',
            'undertaking',
            'can_start_training',
            'missing_requirements',
        ]);
    }

    #[Test]
    public function it_identifies_missing_requirements()
    {
        $response = $this->actingAs($this->admin)->getJson(
            "/registration/{$this->candidate->id}/status"
        );

        $response->assertOk();
        $data = $response->json();

        $this->assertFalse($data['can_start_training']);
        $this->assertNotEmpty($data['missing_requirements']);
    }

    // ==================== START TRAINING ====================

    #[Test]
    public function it_starts_training_for_eligible_candidate()
    {
        $this->setupCandidateForTraining();

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/start-training",
            [
                'batch_id' => $this->batch->id,
            ]
        );

        $response->assertOk();

        $this->candidate->refresh();
        $this->assertEquals(Candidate::STATUS_TRAINING, $this->candidate->status);
        $this->assertEquals($this->batch->id, $this->candidate->batch_id);
    }

    #[Test]
    public function it_rejects_training_start_without_documents()
    {
        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/start-training",
            [
                'batch_id' => $this->batch->id,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonFragment(['message']);
    }

    #[Test]
    public function it_rejects_training_start_without_next_of_kin()
    {
        // Add documents but not next of kin
        foreach (['cnic', 'education', 'photo'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $this->candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
            ]);
        }

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/start-training",
            [
                'batch_id' => $this->batch->id,
            ]
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_training_start_without_undertaking()
    {
        // Add documents and next of kin but not undertaking
        foreach (['cnic', 'education', 'photo'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $this->candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
            ]);
        }

        $nextOfKin = NextOfKin::create([
            'name' => 'Father Name',
            'relationship' => 'Father',
            'phone' => '03009876543',
            'address' => 'Home Address',
        ]);
        $this->candidate->update(['next_of_kin_id' => $nextOfKin->id]);

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/start-training",
            [
                'batch_id' => $this->batch->id,
            ]
        );

        $response->assertStatus(422);
    }

    // ==================== COMPLETE REGISTRATION ====================

    #[Test]
    public function it_completes_registration_with_valid_documents()
    {
        // Add all required documents with valid expiry
        foreach (['cnic', 'education', 'photo', 'domicile'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $this->candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
                'expiry_date' => now()->addYear(),
            ]);
        }

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/complete"
        );

        $response->assertOk();
    }

    #[Test]
    public function it_blocks_completion_with_expired_documents()
    {
        // Add documents with one expired
        RegistrationDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/cnic.pdf',
            'status' => 'verified',
            'expiry_date' => now()->subDay(), // Expired
        ]);

        foreach (['education', 'photo', 'domicile'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $this->candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
                'expiry_date' => now()->addYear(),
            ]);
        }

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/complete"
        );

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertStringContainsString('expired', strtolower($data['message']));
    }

    #[Test]
    public function it_blocks_completion_with_pending_documents()
    {
        // Add documents with one pending
        RegistrationDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/cnic.pdf',
            'status' => 'pending', // Not verified
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            "/registration/{$this->candidate->id}/complete"
        );

        $response->assertStatus(422);
    }

    // ==================== HELPER METHODS ====================

    protected function setupCandidateForTraining(): void
    {
        // Add required documents
        foreach (['cnic', 'education', 'photo'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $this->candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
                'expiry_date' => now()->addYear(),
            ]);
        }

        // Add next of kin
        $nextOfKin = NextOfKin::create([
            'name' => 'Father Name',
            'relationship' => 'Father',
            'phone' => '03009876543',
            'address' => 'Home Address',
        ]);
        $this->candidate->update(['next_of_kin_id' => $nextOfKin->id]);

        // Add undertaking
        Undertaking::create([
            'candidate_id' => $this->candidate->id,
            'type' => 'registration',
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }
}
