<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\DepartureService;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\User;
use App\Enums\CandidateStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartureServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DepartureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DepartureService();
    }

    // =========================================================================
    // STAGES AND CONSTANTS
    // =========================================================================

    #[Test]
    public function it_returns_all_departure_stages()
    {
        $stages = $this->service->getStages();

        $this->assertIsArray($stages);
        $this->assertArrayHasKey('pre_briefing', $stages);
        $this->assertArrayHasKey('departed', $stages);
        $this->assertArrayHasKey('iqama_issued', $stages);
        $this->assertArrayHasKey('compliance_verified', $stages);
    }

    #[Test]
    public function it_returns_compliance_status_types()
    {
        $statuses = $this->service->getComplianceStatus();

        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('pending', $statuses);
        $this->assertArrayHasKey('compliant', $statuses);
        $this->assertArrayHasKey('non_compliant', $statuses);
    }

    // =========================================================================
    // PRE-DEPARTURE BRIEFING
    // =========================================================================

    #[Test]
    public function it_can_record_pre_departure_briefing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        $data = [
            'briefing_date' => now()->toDateString(),
            'conducted_by' => $user->id,
            'topics' => 'Safety, Culture, Documents',
            'remarks' => 'Candidate understood all instructions',
        ];

        $departure = $this->service->recordPreDepartureBriefing($candidate->id, $data);

        $this->assertNotNull($departure);
        $this->assertEquals($candidate->id, $departure->candidate_id);
        $this->assertEquals('pre_briefing', $departure->current_stage);
        $this->assertEquals($data['briefing_date'], $departure->pre_briefing_date);
    }

    #[Test]
    public function it_throws_exception_for_nonexistent_candidate_on_briefing()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Candidate not found');

        $this->service->recordPreDepartureBriefing(99999, [
            'briefing_date' => now()->toDateString(),
        ]);
    }

    // =========================================================================
    // RECORD DEPARTURE
    // =========================================================================

    #[Test]
    public function it_can_record_departure()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        $data = [
            'departure_date' => now()->toDateString(),
            'flight_number' => 'SV123',
            'airport' => 'Lahore International',
            'destination' => 'Saudi Arabia',
        ];

        $departure = $this->service->recordDeparture($candidate->id, $data);

        $this->assertNotNull($departure);
        $this->assertEquals('departed', $departure->current_stage);
        $this->assertEquals($data['flight_number'], $departure->flight_number);

        // Verify candidate status updated
        $candidate->refresh();
        $this->assertEquals(CandidateStatus::DEPARTED->value, $candidate->status);
    }

    // =========================================================================
    // IQAMA RECORDING
    // =========================================================================

    #[Test]
    public function it_can_record_iqama_number()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $iqamaNumber = '2345678901';
        $issueDate = now()->toDateString();
        $expiryDate = now()->addYear()->toDateString();

        $result = $this->service->recordIqama($departure->id, $iqamaNumber, $issueDate, $expiryDate);

        $this->assertEquals($iqamaNumber, $result->iqama_number);
        $this->assertEquals('iqama_issued', $result->current_stage);
    }

    // =========================================================================
    // 90-DAY COMPLIANCE
    // =========================================================================

    #[Test]
    public function it_calculates_90_day_compliance_for_pending_departure()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => null,
        ]);

        $compliance = $this->service->check90DayCompliance($departure->id);

        $this->assertEquals('pending', $compliance['status']);
        $this->assertEquals('Departure date not recorded', $compliance['message']);
    }

    #[Test]
    public function it_calculates_90_day_compliance_items()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(30)->toDateString(),
            'iqama_number' => '1234567890',
            'absher_registered' => true,
            'qiwa_id' => 'QW123456',
            'salary_confirmed' => true,
            'accommodation_status' => 'verified',
        ]);

        $compliance = $this->service->check90DayCompliance($departure->id);

        $this->assertEquals('compliant', $compliance['status']);
        $this->assertEquals(100, $compliance['compliance_percentage']);
        $this->assertEquals(5, $compliance['completed_items']);
    }

    #[Test]
    public function it_marks_overdue_compliance_as_non_compliant()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(100)->toDateString(),
            'iqama_number' => null,
            'absher_registered' => false,
        ]);

        $compliance = $this->service->check90DayCompliance($departure->id);

        $this->assertEquals('non_compliant', $compliance['status']);
        $this->assertTrue($compliance['is_overdue']);
    }

    #[Test]
    public function it_calculates_days_remaining_correctly()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(60)->toDateString(),
        ]);

        $compliance = $this->service->check90DayCompliance($departure->id);

        $this->assertEquals(60, $compliance['days_since_departure']);
        $this->assertEquals(30, $compliance['days_remaining']);
    }

    // =========================================================================
    // SALARY CONFIRMATION
    // =========================================================================

    #[Test]
    public function it_can_record_salary_confirmation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $data = [
            'salary_amount' => 3000,
            'salary_currency' => 'SAR',
            'first_salary_date' => now()->toDateString(),
        ];

        $result = $this->service->recordSalaryConfirmation($departure->id, $data);

        $this->assertTrue($result->salary_confirmed);
        $this->assertEquals('salary_confirmed', $result->current_stage);
        $this->assertEquals(3000, $result->salary_amount);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_can_get_departure_statistics()
    {
        $candidate1 = Candidate::factory()->create();
        $candidate2 = Candidate::factory()->create();

        Departure::factory()->create([
            'candidate_id' => $candidate1->id,
            'departure_date' => now()->subDays(30),
            'iqama_number' => '123',
            'salary_confirmed' => true,
        ]);

        Departure::factory()->create([
            'candidate_id' => $candidate2->id,
            'departure_date' => now()->subDays(15),
            'iqama_number' => '456',
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['total_departures']);
        $this->assertEquals(2, $stats['iqama_issued']);
        $this->assertEquals(1, $stats['salary_confirmed']);
    }

    // =========================================================================
    // COMMUNICATION LOG
    // =========================================================================

    #[Test]
    public function it_can_add_communication_log()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $data = [
            'date' => now()->toDateString(),
            'type' => 'phone',
            'summary' => 'Checked on candidate welfare',
            'issues_reported' => null,
        ];

        $result = $this->service->addCommunicationLog($departure->id, $data);

        $logs = json_decode($result->communication_logs, true);
        $this->assertCount(1, $logs);
        $this->assertEquals('phone', $logs[0]['type']);
    }

    // =========================================================================
    // COMPLIANCE CHECKLIST
    // =========================================================================

    #[Test]
    public function it_returns_empty_checklist_for_missing_departure()
    {
        $candidate = Candidate::factory()->create();

        $checklist = $this->service->getComplianceChecklist($candidate->id);

        $this->assertEmpty($checklist['items']);
        $this->assertEquals(0, $checklist['percentage']);
    }

    #[Test]
    public function it_returns_correct_checklist_percentage()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'iqama_number' => '123',
            'absher_registered' => true,
            'qiwa_id' => null,
            'salary_confirmed' => false,
            'accommodation_status' => null,
        ]);

        $checklist = $this->service->getComplianceChecklist($candidate->id);

        $this->assertEquals(2, $checklist['completed']);
        $this->assertEquals(5, $checklist['total']);
        $this->assertEquals(40, $checklist['percentage']);
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    #[Test]
    public function it_generates_departure_timeline()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'pre_briefing_date' => now()->subDays(10)->toDateString(),
            'departure_date' => now()->subDays(5)->toDateString(),
            'iqama_issue_date' => now()->subDays(3)->toDateString(),
        ]);

        $timeline = $this->service->getDepartureTimeline($candidate->id);

        $this->assertCount(3, $timeline);
        $this->assertEquals('Pre-Departure Briefing', $timeline[0]['stage']);
        $this->assertEquals('Departure', $timeline[1]['stage']);
        $this->assertEquals('Iqama Issued', $timeline[2]['stage']);
    }
}
