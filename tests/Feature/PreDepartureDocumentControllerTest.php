<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PreDepartureDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Campus $otherCampus;
    protected Candidate $candidate;
    protected DocumentChecklist $checklist;

    protected function setUp(): void
    {
        parent::setUp();

        // Create campuses
        $this->campus = Campus::factory()->create([
            'name' => 'Test Campus',
            'code' => 'CAMP-TEST',
        ]);

        $this->otherCampus = Campus::factory()->create([
            'name' => 'Other Campus',
            'code' => 'CAMP-OTHER',
        ]);

        // Create users
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus->id,
            'is_active' => true,
        ]);

        // Create candidate with 'listed' status (editable)
        $this->candidate = Candidate::factory()->create([
            'status' => 'listed',
            'campus_id' => $this->campus->id,
        ]);

        // Create document checklist
        $this->checklist = DocumentChecklist::create([
            'name' => 'CNIC Front & Back',
            'code' => 'cnic',
            'description' => 'Computerized National Identity Card',
            'category' => 'mandatory',
            'is_mandatory' => true,
            'display_order' => 1,
            'is_active' => true,
        ]);

        // Setup fake storage
        Storage::fake('private');
    }

    #[Test]
    public function authenticated_user_can_view_candidate_documents()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('candidates.pre-departure-documents.index', $this->candidate));

        $response->assertStatus(200);
        $response->assertViewIs('candidates.pre-departure-documents.index');
        $response->assertViewHas('candidate');
        $response->assertViewHas('checklists');
        $response->assertViewHas('documents');
        $response->assertViewHas('status');
    }

    #[Test]
    public function can_upload_document_for_listed_candidate()
    {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->create('cnic.pdf', 1024, 'application/pdf');

        $response = $this->post(route('candidates.pre-departure-documents.store', $this->candidate), [
            'document_checklist_id' => $this->checklist->id,
            'file' => $file,
            'notes' => 'Test upload',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pre_departure_documents', [
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
        ]);

        // Verify file was stored
        $document = PreDepartureDocument::where('candidate_id', $this->candidate->id)->first();
        $this->assertNotNull($document);
        $this->assertNotNull($document->file_path);
    }

    #[Test]
    public function cannot_upload_document_for_screening_candidate()
    {
        $this->actingAs($this->admin);

        // Change candidate status to 'screening' (not editable)
        $this->candidate->update(['status' => 'screening']);

        $file = UploadedFile::fake()->create('cnic.pdf', 1024, 'application/pdf');

        $response = $this->post(route('candidates.pre-departure-documents.store', $this->candidate), [
            'document_checklist_id' => $this->checklist->id,
            'file' => $file,
            'notes' => 'Test upload',
        ]);

        $response->assertStatus(403); // Forbidden
    }

    #[Test]
    public function campus_admin_cannot_view_other_campus_candidates()
    {
        $this->actingAs($this->campusAdmin);

        // Create candidate in other campus
        $otherCandidate = Candidate::factory()->create([
            'status' => 'listed',
            'campus_id' => $this->otherCampus->id,
        ]);

        $response = $this->get(route('candidates.pre-departure-documents.index', $otherCandidate));

        $response->assertStatus(403); // Forbidden
    }

    #[Test]
    public function admin_can_verify_document()
    {
        $this->actingAs($this->admin);

        // Create a document record
        $document = PreDepartureDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
            'file_path' => 'test/path/cnic.pdf',
            'original_filename' => 'cnic.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->admin->id,
            'uploaded_at' => now(),
        ]);

        $response = $this->post(route('candidates.pre-departure-documents.verify', [$this->candidate, $document]), [
            'notes' => 'Document verified successfully',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertNotNull($document->verified_at);
        $this->assertEquals($this->admin->id, $document->verified_by);
        $this->assertEquals('Document verified successfully', $document->verification_notes);
    }

    #[Test]
    public function admin_can_reject_document_with_reason()
    {
        $this->actingAs($this->admin);

        // Create a document record
        $document = PreDepartureDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
            'file_path' => 'test/path/cnic.pdf',
            'original_filename' => 'cnic.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->admin->id,
            'uploaded_at' => now(),
        ]);

        $response = $this->post(route('candidates.pre-departure-documents.reject', [$this->candidate, $document]), [
            'reason' => 'Document is blurry and unreadable',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $document->refresh();
        $this->assertNull($document->verified_at);
        $this->assertEquals('Document is blurry and unreadable', $document->verification_notes);
    }

    #[Test]
    public function can_download_uploaded_document()
    {
        $this->actingAs($this->admin);

        // Create a fake file in storage
        $filePath = 'candidates/' . $this->candidate->id . '/documents/cnic.pdf';
        Storage::disk('private')->put($filePath, 'fake pdf content');

        // Create document record
        $document = PreDepartureDocument::create([
            'candidate_id' => $this->candidate->id,
            'document_checklist_id' => $this->checklist->id,
            'file_path' => $filePath,
            'original_filename' => 'cnic.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->admin->id,
            'uploaded_at' => now(),
        ]);

        $response = $this->get(route('candidates.pre-departure-documents.download', [$this->candidate, $document]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    #[Test]
    public function upload_fails_for_invalid_file_type()
    {
        $this->actingAs($this->admin);

        // Try to upload an executable file (not allowed)
        $file = UploadedFile::fake()->create('malicious.exe', 1024, 'application/x-msdownload');

        $response = $this->post(route('candidates.pre-departure-documents.store', $this->candidate), [
            'document_checklist_id' => $this->checklist->id,
            'file' => $file,
            'notes' => 'Test upload',
        ]);

        $response->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('pre_departure_documents', [
            'candidate_id' => $this->candidate->id,
            'original_filename' => 'malicious.exe',
        ]);
    }
}
