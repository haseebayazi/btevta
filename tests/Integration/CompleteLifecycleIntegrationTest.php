<?php

namespace Tests\Integration;

use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\CandidateStatusLog;
use App\Models\DocumentRenewalRequest;
use App\Models\PreDepartureDocument;
use App\Models\Trade;
use App\Models\User;
use App\Services\CandidateJourneyService;
use App\Services\DocumentRenewalService;
use App\Services\StatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Module 10 – End-to-end lifecycle integration tests.
 *
 * Covers:
 *  - StatusTransitionService validates prerequisites and writes audit log
 *  - Stage-skip prevention
 *  - Blocker detection (expired documents)
 *  - CandidateJourneyService progress calculation
 *  - DocumentRenewalService auto-request creation
 */
class CompleteLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = User::factory()->admin()->create();
        $this->campus = Campus::factory()->create();
        $this->trade  = Trade::factory()->create();

        $this->actingAs($this->admin);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Status Transition & Audit Trail
    // ──────────────────────────────────────────────────────────────────────

    #[Test]
    public function status_transition_service_advances_status_and_writes_audit_log(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::LISTED);

        $service = app(StatusTransitionService::class);
        $service->transition(
            $candidate,
            CandidateStatus::PRE_DEPARTURE_DOCS,
            'Moving to document collection phase',
        );

        $candidate->refresh();

        $this->assertEquals(CandidateStatus::PRE_DEPARTURE_DOCS->value, $candidate->status);

        $this->assertDatabaseHas('candidate_status_logs', [
            'candidate_id' => $candidate->id,
            'from_status'  => CandidateStatus::LISTED->value,
            'to_status'    => CandidateStatus::PRE_DEPARTURE_DOCS->value,
            'reason'       => 'Moving to document collection phase',
        ]);
    }

    #[Test]
    public function status_transition_service_rejects_invalid_transition(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::LISTED);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid transition/');

        app(StatusTransitionService::class)->transition(
            $candidate,
            CandidateStatus::REGISTERED,
            'Trying to skip stages'
        );
    }

    #[Test]
    public function get_history_returns_all_audit_log_entries(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::LISTED);
        $service   = app(StatusTransitionService::class);

        $service->transition($candidate, CandidateStatus::PRE_DEPARTURE_DOCS, 'Step 1');
        $candidate->refresh();
        $service->transition($candidate, CandidateStatus::SCREENING, 'Step 2');

        $history = $service->getHistory($candidate->fresh());

        $this->assertCount(2, $history);
        $this->assertEquals(CandidateStatus::SCREENING->value, $history->first()->to_status);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Journey Service
    // ──────────────────────────────────────────────────────────────────────

    #[Test]
    public function journey_service_returns_correct_progress_percentage(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::TRAINING);
        $service   = app(CandidateJourneyService::class);

        $percentage = $service->getProgressPercentage($candidate);

        // TRAINING has order 6; 14 total stages → stages before TRAINING = 6
        // 6 completed out of 14 ≈ 43 %
        $this->assertGreaterThan(30, $percentage);
        $this->assertLessThan(60, $percentage);
    }

    #[Test]
    public function journey_service_marks_all_stages_complete_for_completed_candidate(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::COMPLETED);
        $journey   = app(CandidateJourneyService::class)->getCompleteJourney($candidate);

        $notPending = collect($journey)->reject(fn($s) => $s['state'] === 'pending');
        $this->assertCount(count($journey), $notPending);
    }

    #[Test]
    public function journey_service_detects_expired_document_as_blocker(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::PRE_DEPARTURE_DOCS);

        // Create an expired document
        PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'expiry_date'  => now()->subDay()->toDateString(),
        ]);

        $blockers = app(CandidateJourneyService::class)->getBlockers($candidate);

        $types = array_column($blockers, 'type');
        $this->assertContains('document_expired', $types);
    }

    #[Test]
    public function journey_service_estimates_completion_date_in_the_future(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::SCREENING);
        $estimate  = app(CandidateJourneyService::class)->estimateCompletionDate($candidate);

        $this->assertNotNull($estimate);
        $this->assertGreaterThan(now()->toDateString(), $estimate);
    }

    #[Test]
    public function journey_service_returns_null_completion_date_when_already_completed(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::COMPLETED);
        $estimate  = app(CandidateJourneyService::class)->estimateCompletionDate($candidate);

        $this->assertNull($estimate);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Document Renewal Service
    // ──────────────────────────────────────────────────────────────────────

    #[Test]
    public function document_renewal_service_creates_renewal_request(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::PRE_DEPARTURE_DOCS);
        $doc       = PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'expiry_date'  => now()->addDays(10)->toDateString(),
        ]);

        $request = app(DocumentRenewalService::class)->requestRenewal(
            $candidate,
            'passport',
            $doc,
            'Passport expiring soon'
        );

        $this->assertInstanceOf(DocumentRenewalRequest::class, $request);
        $this->assertEquals('pending', $request->status);
        $this->assertEquals($candidate->id, $request->candidate_id);
        $this->assertDatabaseHas('document_renewal_requests', [
            'candidate_id'  => $candidate->id,
            'document_type' => 'passport',
            'status'        => 'pending',
        ]);
    }

    #[Test]
    public function document_renewal_service_auto_creates_requests_for_expiring_docs(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::PRE_DEPARTURE_DOCS);

        // Document expiring in 15 days – within the 30-day window
        PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'expiry_date'  => now()->addDays(15)->toDateString(),
        ]);

        // Document NOT expiring yet (50 days)
        PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'expiry_date'  => now()->addDays(50)->toDateString(),
        ]);

        $count = app(DocumentRenewalService::class)->createRenewalRequestsForExpiringDocuments();

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function document_renewal_service_does_not_duplicate_pending_requests(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::PRE_DEPARTURE_DOCS);
        $doc       = PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'expiry_date'  => now()->addDays(15)->toDateString(),
        ]);

        $service = app(DocumentRenewalService::class);

        $service->requestRenewal($candidate, 'passport', $doc, 'First request');
        $count = $service->createRenewalRequestsForExpiringDocuments();

        // Already has a pending request – should not create another
        $this->assertEquals(0, $count);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Journey Controller (HTTP-level)
    // ──────────────────────────────────────────────────────────────────────

    #[Test]
    public function journey_data_endpoint_returns_json(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::TRAINING);

        $response = $this->getJson(route('candidates.journey.data', $candidate));

        $response->assertOk()
            ->assertJsonStructure([
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
    public function journey_show_returns_200_for_authorised_user(): void
    {
        $candidate = $this->makeCandidate(CandidateStatus::SCREENING);

        $response = $this->get(route('candidates.journey', $candidate));

        $response->assertOk();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function makeCandidate(CandidateStatus $status): Candidate
    {
        return Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id'  => $this->trade->id,
            'status'    => $status->value,
        ]);
    }
}
