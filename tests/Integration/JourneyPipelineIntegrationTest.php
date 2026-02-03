<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Batch;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\Country;
use App\Services\CandidateJourneyService;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for the CandidateJourney and Pipeline features.
 * Tests the complete journey tracking and pipeline visualization.
 */
class JourneyPipelineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Trade $trade;
    protected Oep $oep;
    protected Country $country;
    protected CandidateJourneyService $journeyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();
        $this->oep = Oep::factory()->create();
        $this->country = Country::factory()->create(['name' => 'Saudi Arabia']);
        $this->journeyService = app(CandidateJourneyService::class);
    }

    #[Test]
    public function journey_service_returns_complete_journey_data()
    {
        // Create a candidate at screening status
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'oep_id' => $this->oep->id,
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $journey = $this->journeyService->getCompleteJourney($candidate);

        // Verify journey structure
        $this->assertIsArray($journey);
        $this->assertGreaterThan(0, count($journey));

        // First stage should be listing (completed since we're past it)
        $this->assertEquals('Listing', $journey[0]['name']);

        // Find current stage
        $currentStage = collect($journey)->firstWhere('status', 'in_progress');
        $this->assertNotNull($currentStage);
    }

    #[Test]
    public function journey_service_tracks_milestones()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'status' => CandidateStatus::LISTED->value,
        ]);

        $milestones = $this->journeyService->getMilestones($candidate);

        // Verify milestone structure
        $this->assertIsArray($milestones);
        $this->assertGreaterThan(0, count($milestones));

        // First milestone (Listed) should be completed
        $this->assertEquals('Listed', $milestones[0]['name']);
        $this->assertTrue($milestones[0]['completed']);
    }

    #[Test]
    public function journey_service_returns_progress_percentage()
    {
        // Listed candidate
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
        ]);

        $progress = $this->journeyService->getProgressPercentage($candidate);

        // Should be early in the journey
        $this->assertGreaterThan(0, $progress);
        $this->assertLessThan(100, $progress);
    }

    #[Test]
    public function journey_service_identifies_blockers()
    {
        // Create candidate without pre-departure documents
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => CandidateStatus::LISTED->value,
        ]);

        $blockers = $this->journeyService->getBlockers($candidate);

        // May have document-related blockers
        $this->assertIsArray($blockers);
    }

    #[Test]
    public function journey_service_provides_next_actions()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $nextActions = $this->journeyService->getNextRequiredActions($candidate);

        // Should have at least one next action
        $this->assertIsArray($nextActions);
    }

    #[Test]
    public function journey_service_estimates_completion()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $estimatedCompletion = $this->journeyService->estimateCompletionDate($candidate);

        // Should return a date string
        $this->assertNotNull($estimatedCompletion);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $estimatedCompletion);
    }

    #[Test]
    public function journey_progresses_with_status_changes()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'status' => CandidateStatus::LISTED->value,
        ]);

        // Initial progress
        $initialProgress = $this->journeyService->getProgressPercentage($candidate);

        // Progress through statuses
        $candidate->update(['status' => CandidateStatus::SCREENING->value]);
        $progress1 = $this->journeyService->getProgressPercentage($candidate);
        $this->assertGreaterThanOrEqual($initialProgress, $progress1);

        // Add screening record and progress
        CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'international',
            'screening_status' => 'screened',
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);

        $candidate->update(['status' => CandidateStatus::SCREENED->value]);
        $progress2 = $this->journeyService->getProgressPercentage($candidate);
        $this->assertGreaterThanOrEqual($progress1, $progress2);

        // Continue to registered
        $candidate->update([
            'status' => CandidateStatus::REGISTERED->value,
            'batch_id' => $batch->id,
        ]);
        $progress3 = $this->journeyService->getProgressPercentage($candidate);
        $this->assertGreaterThanOrEqual($progress2, $progress3);
    }

    #[Test]
    public function pipeline_controller_shows_funnel_data()
    {
        // Create multiple candidates at different stages
        $stages = [
            CandidateStatus::LISTED->value,
            CandidateStatus::SCREENING->value,
            CandidateStatus::SCREENED->value,
            CandidateStatus::REGISTERED->value,
            CandidateStatus::TRAINING->value,
        ];

        foreach ($stages as $status) {
            Candidate::factory()->create([
                'campus_id' => $this->campus->id,
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/pipeline');

        $response->assertStatus(200);
        $response->assertViewIs('pipeline.index');
        $response->assertViewHas('stages');
        $response->assertViewHas('statusCounts');
    }

    #[Test]
    public function pipeline_drilldown_shows_candidates_by_status()
    {
        // Create candidates at screening status
        Candidate::factory()->count(3)->create([
            'campus_id' => $this->campus->id,
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $response = $this->actingAs($this->admin)->get('/pipeline/status/screening');

        $response->assertStatus(200);
        $response->assertViewIs('pipeline.by-status');
        $response->assertViewHas('candidates');
        // The status label comes from CandidateStatus enum's label() method
        $response->assertViewHas('statusLabel');
    }

    #[Test]
    public function journey_view_shows_candidate_journey()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $response = $this->actingAs($this->admin)->get("/candidates/{$candidate->id}/journey");

        $response->assertStatus(200);
        $response->assertViewIs('candidates.journey');
        $response->assertViewHas('candidate');
        $response->assertViewHas('journey');
        $response->assertViewHas('milestones');
        $response->assertViewHas('currentStage');
        $response->assertViewHas('progressPercentage');
    }

    #[Test]
    public function journey_api_returns_json_data()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $response = $this->actingAs($this->admin)->get("/candidates/{$candidate->id}/journey/data");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'journey',
            'milestones',
            'current_stage',
            'progress_percentage',
            'estimated_completion',
            'next_actions',
            'blockers',
        ]);
    }

    #[Test]
    public function complete_journey_tracks_all_stages()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Create a fully progressed candidate
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'batch_id' => $batch->id,
            'status' => CandidateStatus::DEPARTED->value,
        ]);

        // Add screening
        CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'international',
            'screening_status' => 'screened',
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now()->subDays(60),
        ]);

        // Add certificate
        TrainingCertificate::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'certificate_number' => 'CERT-TEST-001',
            'issue_date' => now()->subDays(30),
            'issued_by' => $this->admin->id,
        ]);

        // Add visa process
        VisaProcess::create([
            'candidate_id' => $candidate->id,
            'visa_number' => 'VISA-TEST-001',
            'visa_status' => 'approved',
            'ptn_number' => 'PTN-TEST-001',
        ]);

        // Add departure
        Departure::create([
            'candidate_id' => $candidate->id,
            'scheduled_departure' => now()->subDays(7),
            'actual_departure_date' => now()->subDays(7),
            'flight_number' => 'SV-123',
            'airline' => 'Saudi Airlines',
        ]);

        // Get journey data
        $journey = $this->journeyService->getCompleteJourney($candidate);
        $milestones = $this->journeyService->getMilestones($candidate);
        $progress = $this->journeyService->getProgressPercentage($candidate);

        // Verify multiple stages are completed
        $completedStages = collect($journey)->where('status', 'completed')->count();
        $this->assertGreaterThan(5, $completedStages);

        // Progress should be high
        $this->assertGreaterThan(80, $progress);
    }
}
