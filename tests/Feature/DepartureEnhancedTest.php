<?php

namespace Tests\Feature;

use App\Enums\BriefingStatus;
use App\Enums\CandidateStatus;
use App\Enums\DepartureStatus;
use App\Enums\ProtectorStatus;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\Trade;
use App\Models\User;
use App\ValueObjects\BriefingDetails;
use App\ValueObjects\PTNDetails;
use App\ValueObjects\TicketDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartureEnhancedTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Candidate $candidate;
    protected Departure $departure;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $this->candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'status' => CandidateStatus::DEPARTED->value,
        ]);

        $this->departure = Departure::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_status' => DepartureStatus::PROCESSING->value,
        ]);
    }

    // -----------------------------------------------------------------------
    // Dashboard
    // -----------------------------------------------------------------------

    #[Test]
    public function enhanced_dashboard_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departure.enhanced-dashboard'));

        $response->assertOk();
        $response->assertViewIs('departure.enhanced-dashboard');
        $response->assertViewHas('dashboard');
        $response->assertViewHas('campuses');
    }

    // -----------------------------------------------------------------------
    // Checklist
    // -----------------------------------------------------------------------

    #[Test]
    public function checklist_view_loads_for_admin(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('departure.checklist', $this->departure));

        $response->assertOk();
        $response->assertViewIs('departure.checklist');
        $response->assertViewHas('checklist');
    }

    // -----------------------------------------------------------------------
    // PTN Update
    // -----------------------------------------------------------------------

    #[Test]
    public function admin_can_update_ptn(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.update-ptn', $this->departure), [
                'ptn_number' => 'PTN-2026-001',
                'issued_date' => '2026-02-10',
                'expiry_date' => '2026-08-10',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->departure->refresh();
        $ptn = $this->departure->ptn_details_object;
        $this->assertTrue($ptn->isIssued());
        $this->assertEquals('2026-02-10', $ptn->issuedDate);
    }

    #[Test]
    public function ptn_update_fails_without_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.update-ptn', $this->departure), []);

        $response->assertSessionHasErrors(['ptn_number', 'issued_date']);
    }

    // -----------------------------------------------------------------------
    // Protector Update
    // -----------------------------------------------------------------------

    #[Test]
    public function admin_can_update_protector_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.update-protector', $this->departure), [
                'status' => 'applied',
                'notes' => 'Applied on portal',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->departure->refresh();
        $this->assertEquals(ProtectorStatus::APPLIED, $this->departure->protector_status);
        $this->assertEquals('Applied on portal', $this->departure->protector_details['notes'] ?? null);
    }

    #[Test]
    public function protector_update_fails_with_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.update-protector', $this->departure), [
                'status' => 'invalid_status',
            ]);

        $response->assertSessionHasErrors(['status']);
    }

    // -----------------------------------------------------------------------
    // Ticket Update
    // -----------------------------------------------------------------------

    #[Test]
    public function admin_can_update_ticket_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.update-ticket', $this->departure), [
                'airline' => 'PIA',
                'flight_number' => 'PK-723',
                'departure_date' => now()->addDays(10)->format('Y-m-d'),
                'departure_time' => '10:00',
                'arrival_date' => now()->addDays(10)->format('Y-m-d'),
                'arrival_time' => '14:00',
                'departure_airport' => 'LHE',
                'arrival_airport' => 'RUH',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->departure->refresh();
        $ticket = $this->departure->ticket_details_object;
        $this->assertTrue($ticket->isComplete());
        $this->assertEquals('PIA', $ticket->airline);
    }

    #[Test]
    public function ticket_update_fails_without_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.update-ticket', $this->departure), []);

        $response->assertSessionHasErrors([
            'airline', 'flight_number', 'departure_date',
            'departure_time', 'arrival_date', 'arrival_time',
            'departure_airport', 'arrival_airport',
        ]);
    }

    // -----------------------------------------------------------------------
    // Briefing
    // -----------------------------------------------------------------------

    #[Test]
    public function admin_can_schedule_briefing(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.schedule-briefing', $this->departure), [
                'briefing_date' => now()->addDays(5)->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->departure->refresh();
        $this->assertEquals(BriefingStatus::SCHEDULED, $this->departure->briefing_status);
    }

    #[Test]
    public function briefing_schedule_fails_with_past_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('departure.schedule-briefing', $this->departure), [
                'briefing_date' => now()->subDay()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors(['briefing_date']);
    }

    // -----------------------------------------------------------------------
    // Ready to Depart
    // -----------------------------------------------------------------------

    #[Test]
    public function cannot_mark_ready_without_all_checklist_complete(): void
    {
        // departure has no PTN, protector, ticket, or briefing data
        $response = $this->actingAs($this->admin)
            ->post(route('departure.mark-ready', $this->departure));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function can_mark_ready_when_all_checklist_complete(): void
    {
        // Set up all required data on the departure
        $this->departure->update([
            'ptn_details' => (new PTNDetails(
                status: 'issued',
                issuedDate: '2026-02-01',
            ))->toArray(),
            'protector_status' => ProtectorStatus::DONE->value,
            'ticket_details' => (new TicketDetails(
                airline: 'PIA',
                flightNumber: 'PK-723',
                departureDate: now()->addDays(5)->format('Y-m-d'),
            ))->toArray(),
            'briefing_details' => (new BriefingDetails(
                completedDate: '2026-02-15',
                acknowledgmentSigned: true,
            ))->toArray(),
            'briefing_status' => BriefingStatus::COMPLETED->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departure.mark-ready', $this->departure));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->departure->refresh();
        $this->assertEquals(DepartureStatus::READY_TO_DEPART, $this->departure->departure_status);
    }

    // -----------------------------------------------------------------------
    // Record Departure
    // -----------------------------------------------------------------------

    #[Test]
    public function admin_can_record_actual_departure(): void
    {
        $this->departure->update([
            'departure_status' => DepartureStatus::READY_TO_DEPART->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('departure.record-departure-actual', $this->departure), [
                'actual_departure_time' => now()->toDateTimeLocalString(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->departure->refresh();
        $this->assertEquals(DepartureStatus::DEPARTED, $this->departure->departure_status);
        $this->assertNotNull($this->departure->departed_at);
    }
}
