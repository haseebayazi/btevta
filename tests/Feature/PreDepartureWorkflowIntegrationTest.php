<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PreDepartureWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for foreign key references
        $this->testUser = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $this->seedDocumentChecklists();
    }

    protected function seedDocumentChecklists()
    {
        $checklists = [
            ['name' => 'CNIC', 'code' => 'CNIC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 1, 'is_active' => true],
            ['name' => 'Passport', 'code' => 'PASSPORT', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 2, 'is_active' => true],
            ['name' => 'Domicile', 'code' => 'DOMICILE', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 3, 'is_active' => true],
            ['name' => 'FRC', 'code' => 'FRC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 4, 'is_active' => true],
            ['name' => 'PCC', 'code' => 'PCC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 5, 'is_active' => true],
        ];

        foreach ($checklists as $checklist) {
            DocumentChecklist::create($checklist);
        }
    }

    /** @test */
    public function candidate_cannot_transition_to_screening_without_pre_departure_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',  // Module 1 status
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        $result = $candidate->canTransitionToScreening();

        $this->assertFalse($result['can_transition']);
        $this->assertNotEmpty($result['issues']);
        $this->assertStringContainsString('mandatory pre-departure documents', $result['issues'][0]);
    }

    /** @test */
    public function candidate_can_transition_to_screening_with_all_mandatory_documents_uploaded_and_verified()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',  // Module 1 status
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // Upload AND verify all mandatory documents (Module 2 requirement)
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->testUser->id,
                'verified_at' => now(),  // Document must be verified
                'verified_by' => $this->testUser->id,
            ]);
        }

        $result = $candidate->canTransitionToScreening();

        $this->assertTrue($result['can_transition']);
        $this->assertEmpty($result['issues']);
    }

    /** @test */
    public function candidate_cannot_transition_to_screening_with_unverified_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'pre_departure_docs',  // Module 1 status
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // Upload all mandatory documents but DON'T verify them
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->testUser->id,
                // NOT verified - verified_at is null
            ]);
        }

        $result = $candidate->canTransitionToScreening();

        $this->assertFalse($result['can_transition']);
        $this->assertNotEmpty($result['issues']);
        $this->assertStringContainsString('pending verification', $result['issues'][0]);
    }

    /** @test */
    public function workflow_blocks_screening_with_partial_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',  // Module 1 status
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // Upload only 3 out of 5 mandatory documents (with verification)
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->take(3)->get();
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->testUser->id,
                'verified_at' => now(),
                'verified_by' => $this->testUser->id,
            ]);
        }

        $result = $candidate->canTransitionToScreening();

        $this->assertFalse($result['can_transition']);
        $this->assertStringContainsString('FRC', $result['issues'][0]);
        $this->assertStringContainsString('PCC', $result['issues'][0]);
    }

    /** @test */
    public function completion_status_is_accurate()
    {
        $candidate = Candidate::factory()->create();

        // Initially 0%
        $status = $candidate->getPreDepartureDocumentStatus();
        $this->assertEquals(0, $status['completion_percentage']);
        $this->assertFalse($status['is_complete']);

        // Upload 3 out of 5 = 60%
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->take(3)->get();
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->testUser->id,
            ]);
        }

        $status = $candidate->fresh()->getPreDepartureDocumentStatus();
        $this->assertEquals(60, $status['completion_percentage']);
        $this->assertEquals(3, $status['mandatory_uploaded']);
        $this->assertEquals(5, $status['mandatory_total']);
        $this->assertFalse($status['is_complete']);

        // Upload remaining 2 = 100%
        // Note: Using offset() instead of skip() for SQLite compatibility
        $remaining = DocumentChecklist::mandatory()->active()->orderBy('display_order')->offset(3)->limit(10)->get();
        foreach ($remaining as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->testUser->id,
            ]);
        }

        $status = $candidate->fresh()->getPreDepartureDocumentStatus();
        $this->assertEquals(100, $status['completion_percentage']);
        $this->assertTrue($status['is_complete']);
    }

    /** @test */
    public function missing_documents_list_is_accurate()
    {
        $candidate = Candidate::factory()->create();

        // Upload only CNIC and Passport
        $uploadedCodes = ['CNIC', 'PASSPORT'];
        foreach ($uploadedCodes as $code) {
            $checklist = DocumentChecklist::where('code', $code)->first();
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->testUser->id,
            ]);
        }

        $missing = $candidate->getMissingMandatoryDocuments();

        $this->assertCount(3, $missing); // Missing: Domicile, FRC, PCC
        $missingCodes = $missing->pluck('code')->toArray();
        $this->assertContains('DOMICILE', $missingCodes);
        $this->assertContains('FRC', $missingCodes);
        $this->assertContains('PCC', $missingCodes);
        $this->assertNotContains('CNIC', $missingCodes);
        $this->assertNotContains('PASSPORT', $missingCodes);
    }
}
