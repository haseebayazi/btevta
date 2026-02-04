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

        // Upload remaining documents to reach 100%
        // Get all mandatory documents and filter out the already uploaded ones
        $allMandatory = DocumentChecklist::mandatory()->active()->orderBy('display_order')->get();
        $alreadyUploadedIds = $candidate->preDepartureDocuments()->pluck('document_checklist_id')->toArray();
        $remaining = $allMandatory->filter(fn($doc) => !in_array($doc->id, $alreadyUploadedIds));
        
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

    // ============================================
    // MODULE 1 → MODULE 2 TRANSITION TESTS
    // Tests for WASL v3 routing data to Initial Screening
    // ============================================

    /** @test */
    public function legacy_screening_dashboard_redirects_to_initial_screening_dashboard()
    {
        $response = $this->actingAs($this->testUser)
            ->get(route('screening.dashboard'));

        $response->assertRedirect(route('screening.initial-dashboard'));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function legacy_screening_pending_redirects_to_initial_screening_dashboard()
    {
        $response = $this->actingAs($this->testUser)
            ->get(route('screening.pending'));

        $response->assertRedirect(route('screening.initial-dashboard'));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function legacy_screening_index_redirects_to_initial_screening_dashboard()
    {
        $response = $this->actingAs($this->testUser)
            ->get(route('screening.index'));

        $response->assertRedirect(route('screening.initial-dashboard'));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function legacy_screening_create_redirects_to_initial_screening_dashboard()
    {
        $response = $this->actingAs($this->testUser)
            ->get(route('screening.create'));

        $response->assertRedirect(route('screening.initial-dashboard'));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function dashboard_screening_tab_redirects_to_initial_screening_dashboard()
    {
        $response = $this->actingAs($this->testUser)
            ->get(route('dashboard.screening'));

        $response->assertRedirect(route('screening.initial-dashboard'));
        $response->assertSessionHas('info');
    }

    /** @test */
    public function legacy_log_call_redirects_to_initial_screening_for_candidate()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        $response = $this->actingAs($this->testUser)
            ->post(route('screening.log-call', $candidate), [
                'screened_at' => now()->toDateString(),
                'call_duration' => 60,
                'remarks' => 'Test call',
            ]);

        $response->assertRedirect(route('candidates.initial-screening', $candidate));
        $response->assertSessionHas('warning');
    }

    /** @test */
    public function legacy_record_outcome_redirects_to_initial_screening_for_candidate()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        $response = $this->actingAs($this->testUser)
            ->post(route('screening.outcome', $candidate), [
                'status' => 'passed',
                'remarks' => 'Test outcome',
            ]);

        $response->assertRedirect(route('candidates.initial-screening', $candidate));
        $response->assertSessionHas('warning');
    }

    /** @test */
    public function initial_screening_dashboard_is_accessible()
    {
        $response = $this->actingAs($this->testUser)
            ->get(route('screening.initial-dashboard'));

        $response->assertOk();
        $response->assertViewIs('screening.initial-screening-dashboard');
    }

    /** @test */
    public function initial_screening_form_accessible_when_documents_verified()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // Upload AND verify all mandatory documents
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
                'verified_at' => now(),
                'verified_by' => $this->testUser->id,
            ]);
        }

        $response = $this->actingAs($this->testUser)
            ->get(route('candidates.initial-screening', $candidate));

        $response->assertOk();
        $response->assertViewIs('screening.initial-screening');
    }

    /** @test */
    public function initial_screening_form_blocked_without_verified_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // No documents uploaded
        $response = $this->actingAs($this->testUser)
            ->get(route('candidates.initial-screening', $candidate));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function candidate_show_page_displays_initial_screening_button_when_ready()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // Upload AND verify all mandatory documents
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
                'verified_at' => now(),
                'verified_by' => $this->testUser->id,
            ]);
        }

        $response = $this->actingAs($this->testUser)
            ->get(route('candidates.show', $candidate));

        $response->assertOk();
        $response->assertSee('Start Initial Screening');
        $response->assertSee('All documents verified');
    }

    /** @test */
    public function pre_departure_documents_page_shows_proceed_to_screening_button_when_ready()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
            'name' => 'Test Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        // Upload AND verify all mandatory documents
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
                'verified_at' => now(),
                'verified_by' => $this->testUser->id,
            ]);
        }

        $response = $this->actingAs($this->testUser)
            ->get(route('candidates.pre-departure-documents.index', $candidate));

        $response->assertOk();
        $response->assertSee('Proceed to Initial Screening');
        $response->assertSee('ready for Initial Screening');
    }
}
