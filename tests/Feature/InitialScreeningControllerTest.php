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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InitialScreeningControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $candidate;
    protected $country;

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

        $this->actingAs($campusAdmin);

        $response = $this->get(route('screening.initial-dashboard'));

        $response->assertStatus(200);
        
        $pendingCandidates = $response->viewData('pendingCandidates');
        
        // Should only see own campus candidate
        $this->assertTrue($pendingCandidates->contains('id', $ownCandidate->id));
        $this->assertFalse($pendingCandidates->contains('id', $otherCandidate->id));
    }
}
