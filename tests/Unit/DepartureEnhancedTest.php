<?php

namespace Tests\Unit;

use App\Enums\BriefingStatus;
use App\Enums\DepartureStatus;
use App\Enums\ProtectorStatus;
use App\ValueObjects\BriefingDetails;
use App\ValueObjects\PTNDetails;
use App\ValueObjects\TicketDetails;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartureEnhancedTest extends TestCase
{
    // -----------------------------------------------------------------------
    // PTNDetails Value Object
    // -----------------------------------------------------------------------

    #[Test]
    public function ptn_details_value_object_from_null_returns_empty(): void
    {
        $ptn = PTNDetails::fromArray(null);

        $this->assertNull($ptn->status);
        $this->assertNull($ptn->issuedDate);
        $this->assertFalse($ptn->isIssued());
    }

    #[Test]
    public function ptn_details_value_object_is_issued_when_status_and_date_set(): void
    {
        $ptn = PTNDetails::fromArray([
            'status' => 'issued',
            'issued_date' => '2026-01-15',
            'expiry_date' => '2026-07-15',
        ]);

        $this->assertTrue($ptn->isIssued());
        $this->assertEquals('issued', $ptn->status);
        $this->assertEquals('2026-01-15', $ptn->issuedDate);
    }

    #[Test]
    public function ptn_details_is_not_issued_without_date(): void
    {
        $ptn = PTNDetails::fromArray(['status' => 'issued']);

        $this->assertFalse($ptn->isIssued());
    }

    #[Test]
    public function ptn_details_to_array_excludes_nulls(): void
    {
        $ptn = new PTNDetails(status: 'issued', issuedDate: '2026-01-15');

        $array = $ptn->toArray();

        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('issued_date', $array);
        $this->assertArrayNotHasKey('expiry_date', $array);
        $this->assertArrayNotHasKey('evidence_path', $array);
    }

    #[Test]
    public function ptn_details_is_expired_with_past_date(): void
    {
        $ptn = new PTNDetails(status: 'issued', issuedDate: '2025-01-01', expiryDate: '2025-06-01');

        $this->assertTrue($ptn->isExpired());
    }

    // -----------------------------------------------------------------------
    // TicketDetails Value Object
    // -----------------------------------------------------------------------

    #[Test]
    public function ticket_details_value_object_from_null_returns_empty(): void
    {
        $ticket = TicketDetails::fromArray(null);

        $this->assertNull($ticket->airline);
        $this->assertFalse($ticket->isComplete());
    }

    #[Test]
    public function ticket_details_is_complete_with_required_fields(): void
    {
        $ticket = TicketDetails::fromArray([
            'airline' => 'PIA',
            'flight_number' => 'PK-723',
            'departure_date' => '2026-03-01',
            'departure_time' => '10:00',
            'arrival_date' => '2026-03-01',
            'arrival_time' => '14:00',
            'departure_airport' => 'LHE',
            'arrival_airport' => 'RUH',
        ]);

        $this->assertTrue($ticket->isComplete());
        $this->assertEquals('PIA', $ticket->airline);
        $this->assertEquals('PK-723', $ticket->flightNumber);
    }

    #[Test]
    public function ticket_details_is_not_complete_without_all_required(): void
    {
        $ticket = TicketDetails::fromArray(['airline' => 'PIA']);

        $this->assertFalse($ticket->isComplete());
    }

    #[Test]
    public function ticket_details_get_departure_date_time_returns_carbon(): void
    {
        $ticket = new TicketDetails(departureDate: '2026-03-01', departureTime: '10:30');

        $dt = $ticket->getDepartureDateTime();

        $this->assertNotNull($dt);
        $this->assertEquals('2026-03-01 10:30:00', $dt->toDateTimeString());
    }

    #[Test]
    public function ticket_details_get_departure_date_time_returns_null_without_date(): void
    {
        $ticket = new TicketDetails();

        $this->assertNull($ticket->getDepartureDateTime());
    }

    // -----------------------------------------------------------------------
    // BriefingDetails Value Object
    // -----------------------------------------------------------------------

    #[Test]
    public function briefing_details_value_object_from_null_returns_empty(): void
    {
        $briefing = BriefingDetails::fromArray(null);

        $this->assertNull($briefing->scheduledDate);
        $this->assertFalse($briefing->acknowledgmentSigned);
        $this->assertFalse($briefing->isComplete());
    }

    #[Test]
    public function briefing_details_is_complete_when_date_and_ack_present(): void
    {
        $briefing = BriefingDetails::fromArray([
            'completed_date' => '2026-02-15',
            'acknowledgment_signed' => true,
        ]);

        $this->assertTrue($briefing->isComplete());
    }

    #[Test]
    public function briefing_details_is_not_complete_without_acknowledgment(): void
    {
        $briefing = BriefingDetails::fromArray(['completed_date' => '2026-02-15']);

        $this->assertFalse($briefing->isComplete());
    }

    #[Test]
    public function briefing_details_has_documents_with_document_path(): void
    {
        $briefing = new BriefingDetails(documentPath: 'departures/1/briefing.pdf');

        $this->assertTrue($briefing->hasDocuments());
    }

    #[Test]
    public function briefing_details_has_documents_with_video_path(): void
    {
        $briefing = new BriefingDetails(videoPath: 'departures/1/briefing.mp4');

        $this->assertTrue($briefing->hasDocuments());
    }

    // -----------------------------------------------------------------------
    // Enum Tests
    // -----------------------------------------------------------------------

    #[Test]
    public function briefing_status_enum_has_correct_labels(): void
    {
        $this->assertEquals('Not Scheduled', BriefingStatus::NOT_SCHEDULED->label());
        $this->assertEquals('Scheduled', BriefingStatus::SCHEDULED->label());
        $this->assertEquals('Completed', BriefingStatus::COMPLETED->label());
    }

    #[Test]
    public function briefing_status_enum_has_colors(): void
    {
        $this->assertEquals('secondary', BriefingStatus::NOT_SCHEDULED->color());
        $this->assertEquals('info', BriefingStatus::SCHEDULED->color());
        $this->assertEquals('success', BriefingStatus::COMPLETED->color());
    }

    #[Test]
    public function departure_status_enum_has_cancelled_case(): void
    {
        $status = DepartureStatus::CANCELLED;

        $this->assertEquals('cancelled', $status->value);
        $this->assertEquals('Cancelled', $status->label());
        $this->assertEquals('danger', $status->color());
    }

    #[Test]
    public function departure_status_to_array_includes_all_cases(): void
    {
        $array = DepartureStatus::toArray();

        $this->assertArrayHasKey('processing', $array);
        $this->assertArrayHasKey('ready_to_depart', $array);
        $this->assertArrayHasKey('departed', $array);
        $this->assertArrayHasKey('cancelled', $array);
    }

    #[Test]
    public function protector_status_enum_has_color_and_icon(): void
    {
        $this->assertEquals('success', ProtectorStatus::DONE->color());
        $this->assertEquals('info', ProtectorStatus::APPLIED->color());
        $this->assertStringContainsString('fas fa-', ProtectorStatus::DONE->icon());
    }

    #[Test]
    public function briefing_status_to_array_returns_all_statuses(): void
    {
        $array = BriefingStatus::toArray();

        $this->assertArrayHasKey('not_scheduled', $array);
        $this->assertArrayHasKey('scheduled', $array);
        $this->assertArrayHasKey('completed', $array);
    }
}
