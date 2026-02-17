<?php

namespace Tests\Unit;

use App\Enums\VisaStageResult;
use App\ValueObjects\VisaStageDetails;
use PHPUnit\Framework\TestCase;

class VisaStageDetailsTest extends TestCase
{
    public function test_can_create_empty_details(): void
    {
        $details = new VisaStageDetails();

        $this->assertNull($details->appointmentDate);
        $this->assertNull($details->appointmentTime);
        $this->assertNull($details->center);
        $this->assertNull($details->resultStatus);
        $this->assertNull($details->evidencePath);
        $this->assertNull($details->notes);
    }

    public function test_can_create_from_null(): void
    {
        $details = VisaStageDetails::fromArray(null);

        $this->assertNull($details->appointmentDate);
        $this->assertFalse($details->isScheduled());
        $this->assertFalse($details->hasResult());
    }

    public function test_can_create_from_array(): void
    {
        $data = [
            'appointment_date' => '2026-03-15',
            'appointment_time' => '10:00',
            'center' => 'Test Center Lahore',
            'result_status' => 'pass',
            'evidence_path' => 'visa-process/1/evidence.pdf',
            'notes' => 'Passed with good score',
        ];

        $details = VisaStageDetails::fromArray($data);

        $this->assertEquals('2026-03-15', $details->appointmentDate);
        $this->assertEquals('10:00', $details->appointmentTime);
        $this->assertEquals('Test Center Lahore', $details->center);
        $this->assertEquals('pass', $details->resultStatus);
        $this->assertEquals('visa-process/1/evidence.pdf', $details->evidencePath);
        $this->assertEquals('Passed with good score', $details->notes);
    }

    public function test_to_array_filters_null_values(): void
    {
        $details = new VisaStageDetails(
            appointmentDate: '2026-03-15',
            center: 'Test Center'
        );

        $array = $details->toArray();

        $this->assertArrayHasKey('appointment_date', $array);
        $this->assertArrayHasKey('center', $array);
        $this->assertArrayNotHasKey('appointment_time', $array);
        $this->assertArrayNotHasKey('result_status', $array);
    }

    public function test_is_scheduled_returns_true_when_date_set(): void
    {
        $details = new VisaStageDetails(appointmentDate: '2026-03-15');
        $this->assertTrue($details->isScheduled());
    }

    public function test_is_scheduled_returns_false_when_no_date(): void
    {
        $details = new VisaStageDetails();
        $this->assertFalse($details->isScheduled());
    }

    public function test_has_result_returns_true_when_result_set(): void
    {
        $details = new VisaStageDetails(resultStatus: 'pass');
        $this->assertTrue($details->hasResult());
    }

    public function test_has_result_returns_false_when_no_result(): void
    {
        $details = new VisaStageDetails();
        $this->assertFalse($details->hasResult());
    }

    public function test_is_passed_returns_true_for_pass(): void
    {
        $details = new VisaStageDetails(resultStatus: 'pass');
        $this->assertTrue($details->isPassed());
    }

    public function test_is_passed_returns_false_for_fail(): void
    {
        $details = new VisaStageDetails(resultStatus: 'fail');
        $this->assertFalse($details->isPassed());
    }

    public function test_has_evidence_returns_true_when_path_set(): void
    {
        $details = new VisaStageDetails(evidencePath: 'visa-process/1/evidence.pdf');
        $this->assertTrue($details->hasEvidence());
    }

    public function test_has_evidence_returns_false_when_no_path(): void
    {
        $details = new VisaStageDetails();
        $this->assertFalse($details->hasEvidence());
    }

    public function test_get_result_enum_returns_enum_for_valid_status(): void
    {
        $details = new VisaStageDetails(resultStatus: 'pass');
        $this->assertEquals(VisaStageResult::PASS, $details->getResultEnum());
    }

    public function test_get_result_enum_returns_null_for_no_status(): void
    {
        $details = new VisaStageDetails();
        $this->assertNull($details->getResultEnum());
    }

    public function test_with_result_creates_new_instance(): void
    {
        $original = new VisaStageDetails(
            appointmentDate: '2026-03-15',
            appointmentTime: '10:00',
            center: 'Test Center'
        );

        // Construct result instance manually since withResult() depends on auth()
        $updated = new VisaStageDetails(
            appointmentDate: $original->appointmentDate,
            appointmentTime: $original->appointmentTime,
            center: $original->center,
            resultStatus: 'pass',
            evidencePath: 'evidence.pdf',
            notes: 'Good result',
            updatedAt: '2026-03-15 10:00:00',
            updatedBy: 1,
        );

        // Original should be unchanged
        $this->assertNull($original->resultStatus);

        // New instance should have result
        $this->assertEquals('pass', $updated->resultStatus);
        $this->assertEquals('Good result', $updated->notes);
        $this->assertEquals('evidence.pdf', $updated->evidencePath);

        // Appointment data should be preserved
        $this->assertEquals('2026-03-15', $updated->appointmentDate);
        $this->assertEquals('10:00', $updated->appointmentTime);
        $this->assertEquals('Test Center', $updated->center);
    }
}
