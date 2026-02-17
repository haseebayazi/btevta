<?php

namespace Tests\Unit;

use App\Enums\VisaStageResult;
use App\Enums\VisaApplicationStatus;
use App\Enums\VisaIssuedStatus;
use PHPUnit\Framework\TestCase;

class VisaEnumsTest extends TestCase
{
    // =========================================================================
    // VisaStageResult Tests
    // =========================================================================

    public function test_visa_stage_result_has_all_cases(): void
    {
        $cases = VisaStageResult::cases();
        $values = array_column($cases, 'value');

        $this->assertContains('pending', $values);
        $this->assertContains('scheduled', $values);
        $this->assertContains('pass', $values);
        $this->assertContains('fail', $values);
        $this->assertContains('refused', $values);
    }

    public function test_visa_stage_result_labels(): void
    {
        $this->assertEquals('Pending', VisaStageResult::PENDING->label());
        $this->assertEquals('Scheduled', VisaStageResult::SCHEDULED->label());
        $this->assertEquals('Pass', VisaStageResult::PASS->label());
        $this->assertEquals('Fail', VisaStageResult::FAIL->label());
        $this->assertEquals('Refused', VisaStageResult::REFUSED->label());
    }

    public function test_visa_stage_result_colors(): void
    {
        $this->assertEquals('secondary', VisaStageResult::PENDING->color());
        $this->assertEquals('info', VisaStageResult::SCHEDULED->color());
        $this->assertEquals('success', VisaStageResult::PASS->color());
        $this->assertEquals('danger', VisaStageResult::FAIL->color());
        $this->assertEquals('dark', VisaStageResult::REFUSED->color());
    }

    public function test_visa_stage_result_allows_progress(): void
    {
        $this->assertTrue(VisaStageResult::PASS->allowsProgress());
        $this->assertFalse(VisaStageResult::PENDING->allowsProgress());
        $this->assertFalse(VisaStageResult::FAIL->allowsProgress());
        $this->assertFalse(VisaStageResult::REFUSED->allowsProgress());
    }

    public function test_visa_stage_result_is_terminal(): void
    {
        $this->assertTrue(VisaStageResult::FAIL->isTerminal());
        $this->assertTrue(VisaStageResult::REFUSED->isTerminal());
        $this->assertFalse(VisaStageResult::PASS->isTerminal());
        $this->assertFalse(VisaStageResult::PENDING->isTerminal());
        $this->assertFalse(VisaStageResult::SCHEDULED->isTerminal());
    }

    public function test_visa_stage_result_to_array(): void
    {
        $array = VisaStageResult::toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Pending', $array['pending']);
        $this->assertEquals('Pass', $array['pass']);
    }

    // =========================================================================
    // VisaApplicationStatus Tests
    // =========================================================================

    public function test_visa_application_status_has_all_cases(): void
    {
        $cases = VisaApplicationStatus::cases();
        $values = array_column($cases, 'value');

        $this->assertContains('not_applied', $values);
        $this->assertContains('applied', $values);
        $this->assertContains('refused', $values);
    }

    public function test_visa_application_status_labels(): void
    {
        $this->assertEquals('Not Applied', VisaApplicationStatus::NOT_APPLIED->label());
        $this->assertEquals('Applied', VisaApplicationStatus::APPLIED->label());
        $this->assertEquals('Refused', VisaApplicationStatus::REFUSED->label());
    }

    public function test_visa_application_status_colors(): void
    {
        $this->assertEquals('secondary', VisaApplicationStatus::NOT_APPLIED->color());
        $this->assertEquals('info', VisaApplicationStatus::APPLIED->color());
        $this->assertEquals('danger', VisaApplicationStatus::REFUSED->color());
    }

    // =========================================================================
    // VisaIssuedStatus Tests
    // =========================================================================

    public function test_visa_issued_status_has_all_cases(): void
    {
        $cases = VisaIssuedStatus::cases();
        $values = array_column($cases, 'value');

        $this->assertContains('pending', $values);
        $this->assertContains('confirmed', $values);
        $this->assertContains('refused', $values);
    }

    public function test_visa_issued_status_labels(): void
    {
        $this->assertEquals('Pending', VisaIssuedStatus::PENDING->label());
        $this->assertEquals('Confirmed', VisaIssuedStatus::CONFIRMED->label());
        $this->assertEquals('Refused', VisaIssuedStatus::REFUSED->label());
    }

    public function test_visa_issued_status_colors(): void
    {
        $this->assertEquals('warning', VisaIssuedStatus::PENDING->color());
        $this->assertEquals('success', VisaIssuedStatus::CONFIRMED->color());
        $this->assertEquals('danger', VisaIssuedStatus::REFUSED->color());
    }
}
