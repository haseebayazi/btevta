<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\Country;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InitialScreeningControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $candidate;
    protected $country;
    protected $documentChecklist;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create a campus
        $campus = Campus::factory()->create();

        // Create a trade
        $trade = Trade::factory()->create();

        // Create candidate in pre_departure_docs status (ready for screening)
        $this->candidate = Candidate::factory()->create([
            'status' => 'pre_departure_docs',
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        // Create destination country
        $this->country = Country::factory()->create([
            'name' => 'Saudi Arabia',
            'code' => 'SAU',
            'code_2' => 'SA',
            'is_destination' => true,
            'is_active' => true,
        ]);

        // Create a mandatory document checklist
        $this->documentChecklist = DocumentChecklist::create([
            'name' => 'CNIC Copy',
            'code' => 'cnic',
            'category' => 'mandatory',
            'is_mandatory' => true,
            'is_active' => true,
            'display_order' => 1,
        ]);
    }

    /**
     * Helper method to setup completed and verified documents for a candidate
     */
    protected function setupCompletedDocuments(Candidate $candidate): void
    {
        // Get all mandatory document checklists
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        // Create pre-departure documents for each mandatory checklist (uploaded AND verified)
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'documents/test_' . $checklist->code . '.pdf',
                'original_filename' => $checklist->code . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
                'verified_at' => now(),
                'verified_by' => $this->user->id,
            ]);
        }
    }

    #[Test]
    public function user_can_view_initial_screening_dashboard()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('screening.initial-screening-dashboard');
        $response->assertViewHas('stats');
        $response->assertViewHas('pendingCandidates');
        $response->assertViewHas('recentlyScreened');
    }

    #[Test]
    public function user_can_view_initial_screening_form()
    {
        $this->actingAs($this->user);

        // Setup completed documents for the candidate
        $this->setupCompletedDocuments($this->candidate);

        $response = $this->get(route('candidates.initial-screening', $this->candidate));

        $response->assertStatus(200);
        $response->assertViewIs('screening.initial-screening');
        $response->assertViewHas('candidate');
        $response->assertViewHas('countries');
    }

    #[Test]
    public function user_cannot_screen_candidate_without_pre_departure_docs()
    {
        $this->actingAs($this->user);

        // Create candidate in wrong status
        $candidate = Candidate::factory()->create([
            'status' => 'listed',
        ]);

        $response = $this->get(route('candidates.initial-screening', $candidate));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function can_screen_candidate_with_local_placement()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'local',
            'screening_status' => 'screened',
            'notes' => 'Candidate approved for local placement',
        ]);

        $response->assertRedirect(route('candidates.show', $this->candidate));
        $response->assertSessionHas('success');

        // Verify screening record created
        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'screening_type' => 'initial',
            'consent_for_work' => true,
            'placement_interest' => 'local',
            'screening_status' => 'screened',
        ]);

        // Verify candidate status updated
        $this->candidate->refresh();
        $this->assertEquals('screened', $this->candidate->status);
    }

    #[Test]
    public function can_screen_candidate_with_international_placement()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'international',
            'target_country_id' => $this->country->id,
            'screening_status' => 'screened',
            'notes' => 'Candidate approved for Saudi Arabia',
        ]);

        $response->assertRedirect(route('candidates.show', $this->candidate));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'international',
            'target_country_id' => $this->country->id,
            'screening_status' => 'screened',
        ]);
    }

    #[Test]
    public function cannot_screen_without_consent()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => false,
            'placement_interest' => 'local',
            'screening_status' => 'screened',
        ]);

        $response->assertSessionHasErrors('consent_for_work');
    }

    #[Test]
    public function international_placement_requires_country()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'international',
            // Missing target_country_id
            'screening_status' => 'screened',
        ]);

        $response->assertSessionHasErrors('target_country_id');
    }

    #[Test]
    public function can_defer_candidate_screening()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'local',
            'screening_status' => 'deferred',
            'notes' => 'Candidate not ready at this time',
        ]);

        $response->assertRedirect(route('candidates.show', $this->candidate));

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'screening_status' => 'deferred',
        ]);

        // Verify candidate status updated to deferred
        $this->candidate->refresh();
        $this->assertEquals('deferred', $this->candidate->status);
    }

    #[Test]
    public function can_save_screening_as_pending()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'local',
            'screening_status' => 'pending',
            'notes' => 'Need more information',
        ]);

        $response->assertRedirect(route('candidates.show', $this->candidate));

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'screening_status' => 'pending',
        ]);
    }

    #[Test]
    public function can_upload_evidence_with_screening()
    {
        Storage::fake('public');
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('screening-evidence.pdf', 1000);

        $response = $this->post(route('candidates.initial-screening.store', $this->candidate), [
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'local',
            'screening_status' => 'screened',
            'evidence' => $file,
        ]);

        $response->assertRedirect(route('candidates.show', $this->candidate));

        // Verify file was stored
        $screening = $this->candidate->screenings()->where('screening_type', 'initial')->first();
        $this->assertNotNull($screening->evidence_path);
        Storage::disk('public')->assertExists($screening->evidence_path);
    }

    #[Test]
    public function dashboard_shows_correct_statistics()
    {
        $this->actingAs($this->user);

        // Create candidates in different statuses
        Candidate::factory()->create(['status' => 'screening']);
        Candidate::factory()->create(['status' => 'screened']);
        Candidate::factory()->create(['status' => 'deferred']);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);
        $stats = $response->viewData('stats');

        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['screened']);
        $this->assertEquals(1, $stats['deferred']);
    }

    #[Test]
    public function campus_admin_sees_only_own_campus_candidates()
    {
        // Create campus admin
        $campus = Campus::factory()->create();
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
            'is_active' => true,
        ]);

        // Create candidates in different campuses
        $ownCandidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'screening',
        ]);

        $otherCandidate = Candidate::factory()->create([
            'campus_id' => Campus::factory()->create()->id,
            'status' => 'screening',
        ]);

        // Setup completed documents for both candidates
        $this->setupCompletedDocuments($ownCandidate);
        $this->setupCompletedDocuments($otherCandidate);

        $this->actingAs($campusAdmin);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Should only see own campus candidate
        $this->assertTrue($pendingCandidates->contains('id', $ownCandidate->id));
        $this->assertFalse($pendingCandidates->contains('id', $otherCandidate->id));
    }

    #[Test]
    public function dashboard_shows_ready_for_screening_flag_for_verified_documents()
    {
        $this->actingAs($this->user);

        // Setup completed AND verified documents
        $this->setupCompletedDocuments($this->candidate);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Find our candidate in the collection
        $candidate = $pendingCandidates->firstWhere('id', $this->candidate->id);

        $this->assertNotNull($candidate, 'Candidate should be in pending list');
        
        // Verify ready_for_screening is set and true
        $this->assertTrue(
            isset($candidate->ready_for_screening),
            'ready_for_screening property should be set'
        );
        $this->assertTrue(
            $candidate->ready_for_screening,
            'ready_for_screening should be true for candidate with verified documents'
        );
    }

    #[Test]
    public function dashboard_shows_not_ready_for_screening_when_documents_uploaded_but_not_verified()
    {
        $this->actingAs($this->user);

        // Get all mandatory document checklists
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        // Create pre-departure documents for each mandatory checklist (uploaded but NOT verified)
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $this->candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'documents/test_' . $checklist->code . '.pdf',
                'original_filename' => $checklist->code . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
                'verified_at' => null, // NOT verified
                'verified_by' => null,
            ]);
        }

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Find our candidate in the collection
        $candidate = $pendingCandidates->firstWhere('id', $this->candidate->id);

        $this->assertNotNull($candidate, 'Candidate should be in pending list');
        
        // Verify ready_for_screening is set but false
        $this->assertTrue(
            isset($candidate->ready_for_screening),
            'ready_for_screening property should be set'
        );
        $this->assertFalse(
            $candidate->ready_for_screening,
            'ready_for_screening should be false when documents are uploaded but not verified'
        );
    }

    #[Test]
    public function dashboard_shows_not_ready_for_screening_when_documents_missing()
    {
        $this->actingAs($this->user);

        // Don't upload any documents

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Find our candidate in the collection
        $candidate = $pendingCandidates->firstWhere('id', $this->candidate->id);

        $this->assertNotNull($candidate, 'Candidate should be in pending list even without documents');
        
        // Verify ready_for_screening is set but false
        $this->assertTrue(
            isset($candidate->ready_for_screening),
            'ready_for_screening property should be set'
        );
        $this->assertFalse(
            $candidate->ready_for_screening,
            'ready_for_screening should be false when documents are missing'
        );
    }

    #[Test]
    public function dashboard_includes_candidates_in_listed_status()
    {
        $this->actingAs($this->user);

        // Create a candidate in 'listed' status
        $listedCandidate = Candidate::factory()->create([
            'status' => 'listed',
            'campus_id' => $this->candidate->campus_id,
            'trade_id' => $this->candidate->trade_id,
        ]);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Should include the listed candidate
        $this->assertTrue(
            $pendingCandidates->contains('id', $listedCandidate->id),
            'Dashboard should include candidates in listed status'
        );
    }

    #[Test]
    public function dashboard_includes_candidates_in_screening_status()
    {
        $this->actingAs($this->user);

        // Create a candidate in 'screening' status
        $screeningCandidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => $this->candidate->campus_id,
            'trade_id' => $this->candidate->trade_id,
        ]);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Should include the screening candidate
        $this->assertTrue(
            $pendingCandidates->contains('id', $screeningCandidate->id),
            'Dashboard should include candidates in screening status'
        );
    }

    #[Test]
    public function dashboard_document_status_is_set_for_all_candidates()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);

        $pendingCandidates = $response->viewData('pendingCandidates');

        // Check that all candidates have document_status set
        foreach ($pendingCandidates as $candidate) {
            $this->assertTrue(
                isset($candidate->document_status),
                "document_status should be set for candidate {$candidate->id}"
            );
            $this->assertIsArray(
                $candidate->document_status,
                "document_status should be an array for candidate {$candidate->id}"
            );
        }
    }
}
