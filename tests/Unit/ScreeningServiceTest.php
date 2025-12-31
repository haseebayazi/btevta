<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ScreeningService;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScreeningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ScreeningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScreeningService();
    }

    // =========================================================================
    // UNDERTAKING CONTENT
    // =========================================================================

    /** @test */
    public function it_generates_undertaking_content()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Muhammad Ali',
            'father_name' => 'Muhammad Khan',
            'cnic' => '3520112345671',
            'address' => '123 Main Street',
            'district' => 'Lahore',
        ]);

        $content = $this->service->generateUndertakingContent($candidate);

        $this->assertStringContainsString('Muhammad Ali', $content);
        $this->assertStringContainsString('Muhammad Khan', $content);
        $this->assertStringContainsString('Lahore', $content);
        $this->assertStringContainsString('UNDERTAKING', $content);
    }

    // =========================================================================
    // CALL LOGS
    // =========================================================================

    /** @test */
    public function it_parses_call_logs_from_remarks()
    {
        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'remarks' => "2024-01-15 10:30:00 Call made to candidate\n2024-01-16 11:00:00 Call follow-up",
        ]);

        $logs = $this->service->getCallLogs($screening);

        $this->assertIsArray($logs);
    }

    /** @test */
    public function it_returns_empty_logs_when_no_remarks()
    {
        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'remarks' => null,
        ]);

        $logs = $this->service->getCallLogs($screening);

        $this->assertEmpty($logs);
    }

    // =========================================================================
    // SCREENING REPORT
    // =========================================================================

    /** @test */
    public function it_generates_screening_report()
    {
        $candidate = Candidate::factory()->create();
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_type' => 'desk',
            'status' => 'passed',
            'screened_at' => now(),
        ]);

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_type' => 'call',
            'status' => 'pending',
            'screened_at' => now(),
        ]);

        $report = $this->service->generateReport();

        $this->assertArrayHasKey('total_screenings', $report);
        $this->assertArrayHasKey('passed', $report);
        $this->assertArrayHasKey('failed', $report);
        $this->assertArrayHasKey('pending', $report);
        $this->assertArrayHasKey('by_type', $report);
    }

    /** @test */
    public function it_filters_report_by_date_range()
    {
        $candidate = Candidate::factory()->create();

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screened_at' => now()->subDays(5),
        ]);

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screened_at' => now()->subDays(30),
        ]);

        $report = $this->service->generateReport([
            'from_date' => now()->subDays(10)->toDateString(),
            'to_date' => now()->toDateString(),
        ]);

        $this->assertArrayHasKey('total_screenings', $report);
    }

    /** @test */
    public function it_filters_report_by_screening_type()
    {
        $candidate = Candidate::factory()->create();

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_type' => 'desk',
            'screened_at' => now(),
        ]);

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_type' => 'call',
            'screened_at' => now(),
        ]);

        $report = $this->service->generateReport([
            'screening_type' => 'desk',
        ]);

        $this->assertArrayHasKey('total_screenings', $report);
    }

    // =========================================================================
    // SCHEDULE NEXT SCREENING
    // =========================================================================

    /** @test */
    public function it_schedules_call_screening_after_desk()
    {
        $candidate = Candidate::factory()->create();

        $this->service->scheduleNextScreening($candidate, 'desk');

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $candidate->id,
            'screening_type' => 'call',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_schedules_physical_screening_after_call()
    {
        $candidate = Candidate::factory()->create();

        $this->service->scheduleNextScreening($candidate, 'call');

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $candidate->id,
            'screening_type' => 'physical',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_does_not_schedule_after_physical()
    {
        $candidate = Candidate::factory()->create();
        $initialCount = CandidateScreening::count();

        $this->service->scheduleNextScreening($candidate, 'physical');

        $this->assertEquals($initialCount, CandidateScreening::count());
    }

    // =========================================================================
    // CHECK ELIGIBILITY
    // =========================================================================

    /** @test */
    public function it_returns_ineligible_for_nonexistent_candidate()
    {
        $result = $this->service->checkEligibility(99999, 'desk');

        $this->assertFalse($result['eligible']);
        $this->assertEquals('Candidate not found', $result['reason']);
    }

    /** @test */
    public function it_returns_eligible_for_desk_screening()
    {
        $candidate = Candidate::factory()->create();

        $result = $this->service->checkEligibility($candidate->id, 'desk');

        $this->assertTrue($result['eligible']);
    }

    /** @test */
    public function it_requires_desk_screening_for_call()
    {
        $candidate = Candidate::factory()->create();

        $result = $this->service->checkEligibility($candidate->id, 'call');

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('desk', $result['reason']);
    }

    /** @test */
    public function it_allows_call_after_desk_passed()
    {
        $candidate = Candidate::factory()->create();
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_type' => 'desk',
            'status' => 'passed',
        ]);

        $result = $this->service->checkEligibility($candidate->id, 'call');

        $this->assertTrue($result['eligible']);
    }

    /** @test */
    public function it_requires_all_prerequisites_for_physical()
    {
        $candidate = Candidate::factory()->create();

        $result = $this->service->checkEligibility($candidate->id, 'physical');

        $this->assertFalse($result['eligible']);
    }

    // =========================================================================
    // 3-CALL WORKFLOW
    // =========================================================================

    /** @test */
    public function it_records_call_attempt()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_type' => 'call',
            'call_stage' => 'pending',
        ]);

        $result = $this->service->recordCallAttempt($screening, 1, [
            'outcome' => 'answered',
            'response' => 'documents_ready',
            'notes' => 'Candidate confirmed documents are ready',
        ]);

        $this->assertNotNull($result->call_1_at);
        $this->assertEquals('answered', $result->call_1_outcome);
        $this->assertEquals(1, $result->total_call_attempts);
    }

    /** @test */
    public function it_throws_exception_for_invalid_call_number()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->recordCallAttempt($screening, 4, [
            'outcome' => 'answered',
        ]);
    }

    /** @test */
    public function it_progresses_call_stage_on_successful_call()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'call_1_document',
        ]);

        $result = $this->service->recordCallAttempt($screening, 1, [
            'outcome' => 'answered',
            'response' => 'documents_ready',
        ]);

        $this->assertEquals('call_2_registration', $result->call_stage);
    }

    /** @test */
    public function it_marks_unreachable_after_max_attempts()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'call_1_document',
            'total_call_attempts' => 4,
        ]);

        $result = $this->service->recordCallAttempt($screening, 1, [
            'outcome' => 'switched_off',
        ]);

        $this->assertEquals('unreachable', $result->call_stage);
    }

    /** @test */
    public function it_handles_callback_request()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
        ]);

        $callbackTime = now()->addHours(2);

        $result = $this->service->recordCallAttempt($screening, 1, [
            'outcome' => 'answered',
            'response' => 'callback_requested',
            'callback_at' => $callbackTime,
            'callback_reason' => 'Candidate is busy',
        ]);

        $this->assertNotNull($result->callback_scheduled_at);
        $this->assertEquals('Candidate is busy', $result->callback_reason);
    }

    // =========================================================================
    // PENDING CALLBACKS
    // =========================================================================

    /** @test */
    public function it_returns_pending_callbacks()
    {
        $candidate = Candidate::factory()->create();
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'callback_scheduled_at' => now()->subHour(),
            'call_stage' => 'call_1_document',
        ]);

        $callbacks = $this->service->getPendingCallbacks();

        $this->assertGreaterThanOrEqual(1, $callbacks->count());
    }

    // =========================================================================
    // CALL STAGE STATISTICS
    // =========================================================================

    /** @test */
    public function it_returns_call_stage_statistics()
    {
        $candidate = Candidate::factory()->create();

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'call_1_document',
        ]);

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'completed',
        ]);

        $stats = $this->service->getCallStageStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('call_1_document', $stats);
        $this->assertArrayHasKey('completed', $stats);
    }

    // =========================================================================
    // CALL SUCCESS RATES
    // =========================================================================

    /** @test */
    public function it_returns_call_success_rates()
    {
        $candidate = Candidate::factory()->create();

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_1_at' => now(),
            'call_1_outcome' => 'answered',
        ]);

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_1_at' => now(),
            'call_1_outcome' => 'no_answer',
        ]);

        $rates = $this->service->getCallSuccessRates();

        $this->assertArrayHasKey('call_1', $rates);
        $this->assertArrayHasKey('total', $rates['call_1']);
        $this->assertArrayHasKey('answered', $rates['call_1']);
        $this->assertArrayHasKey('success_rate', $rates['call_1']);
    }

    // =========================================================================
    // PENDING APPOINTMENTS
    // =========================================================================

    /** @test */
    public function it_returns_pending_appointments()
    {
        $candidate = Candidate::factory()->create();
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'registration_appointment_at' => now()->addDay(),
            'final_outcome' => 'pending',
        ]);

        $appointments = $this->service->getPendingAppointments();

        $this->assertGreaterThanOrEqual(1, $appointments->count());
    }

    // =========================================================================
    // TODAY'S APPOINTMENTS
    // =========================================================================

    /** @test */
    public function it_returns_todays_appointments()
    {
        $candidate = Candidate::factory()->create();
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'registration_appointment_at' => now(),
        ]);

        $appointments = $this->service->getTodaysAppointments();

        $this->assertGreaterThanOrEqual(1, $appointments->count());
    }

    // =========================================================================
    // RESPONSE RATE ANALYTICS
    // =========================================================================

    /** @test */
    public function it_returns_response_rate_analytics()
    {
        $candidate = Candidate::factory()->create();

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'completed',
            'final_outcome' => 'registered',
        ]);

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'unreachable',
            'final_outcome' => 'unreachable',
        ]);

        $analytics = $this->service->getResponseRateAnalytics();

        $this->assertArrayHasKey('total_screenings', $analytics);
        $this->assertArrayHasKey('completed', $analytics);
        $this->assertArrayHasKey('registration_rate', $analytics);
        $this->assertArrayHasKey('call_success_rates', $analytics);
    }

    // =========================================================================
    // BULK UPDATE CALL STAGE
    // =========================================================================

    /** @test */
    public function it_can_bulk_update_call_stage()
    {
        $candidate = Candidate::factory()->create();

        $screenings = CandidateScreening::factory()->count(3)->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'pending',
        ]);

        $ids = $screenings->pluck('id')->toArray();

        $updated = $this->service->bulkUpdateCallStage($ids, 'call_1_document');

        $this->assertEquals(3, $updated);
    }

    // =========================================================================
    // CANDIDATES NEEDING FOLLOW-UP
    // =========================================================================

    /** @test */
    public function it_returns_candidates_needing_follow_up()
    {
        $candidate = Candidate::factory()->create();

        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'call_stage' => 'call_1_document',
            'call_1_at' => now()->subDays(2),
            'final_outcome' => 'pending',
        ]);

        $needFollowUp = $this->service->getCandidatesNeedingFollowUp();

        $this->assertGreaterThanOrEqual(1, $needFollowUp->count());
    }
}
