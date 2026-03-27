<?php

namespace Tests\Unit;

use App\Enums\ContractStatus;
use App\Enums\EmploymentStatus;
use App\Enums\IqamaStatus;
use App\Enums\SwitchStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostDepartureTest extends TestCase
{
    // -----------------------------------------------------------------------
    // IqamaStatus Enum
    // -----------------------------------------------------------------------

    #[Test]
    public function iqama_status_has_correct_cases(): void
    {
        $this->assertEquals('pending', IqamaStatus::PENDING->value);
        $this->assertEquals('issued', IqamaStatus::ISSUED->value);
        $this->assertEquals('expired', IqamaStatus::EXPIRED->value);
        $this->assertEquals('renewed', IqamaStatus::RENEWED->value);
    }

    #[Test]
    public function iqama_status_has_labels(): void
    {
        $this->assertEquals('Pending', IqamaStatus::PENDING->label());
        $this->assertEquals('Issued', IqamaStatus::ISSUED->label());
        $this->assertEquals('Expired', IqamaStatus::EXPIRED->label());
        $this->assertEquals('Renewed', IqamaStatus::RENEWED->label());
    }

    #[Test]
    public function iqama_status_has_colors(): void
    {
        $this->assertEquals('warning', IqamaStatus::PENDING->color());
        $this->assertEquals('success', IqamaStatus::ISSUED->color());
        $this->assertEquals('danger', IqamaStatus::EXPIRED->color());
        $this->assertEquals('info', IqamaStatus::RENEWED->color());
    }

    // -----------------------------------------------------------------------
    // ContractStatus Enum
    // -----------------------------------------------------------------------

    #[Test]
    public function contract_status_has_correct_cases(): void
    {
        $this->assertEquals('pending', ContractStatus::PENDING->value);
        $this->assertEquals('active', ContractStatus::ACTIVE->value);
        $this->assertEquals('completed', ContractStatus::COMPLETED->value);
        $this->assertEquals('terminated', ContractStatus::TERMINATED->value);
    }

    #[Test]
    public function contract_status_has_labels(): void
    {
        $this->assertEquals('Pending', ContractStatus::PENDING->label());
        $this->assertEquals('Active', ContractStatus::ACTIVE->label());
        $this->assertEquals('Completed', ContractStatus::COMPLETED->label());
        $this->assertEquals('Terminated', ContractStatus::TERMINATED->label());
    }

    #[Test]
    public function contract_status_has_colors(): void
    {
        $this->assertEquals('secondary', ContractStatus::PENDING->color());
        $this->assertEquals('success', ContractStatus::ACTIVE->color());
        $this->assertEquals('info', ContractStatus::COMPLETED->color());
        $this->assertEquals('danger', ContractStatus::TERMINATED->color());
    }

    // -----------------------------------------------------------------------
    // EmploymentStatus Enum
    // -----------------------------------------------------------------------

    #[Test]
    public function employment_status_has_correct_cases(): void
    {
        $this->assertEquals('current', EmploymentStatus::CURRENT->value);
        $this->assertEquals('previous', EmploymentStatus::PREVIOUS->value);
        $this->assertEquals('terminated', EmploymentStatus::TERMINATED->value);
    }

    #[Test]
    public function employment_status_has_labels(): void
    {
        $this->assertEquals('Current', EmploymentStatus::CURRENT->label());
        $this->assertEquals('Previous', EmploymentStatus::PREVIOUS->label());
        $this->assertEquals('Terminated', EmploymentStatus::TERMINATED->label());
    }

    #[Test]
    public function employment_status_has_colors(): void
    {
        $this->assertEquals('success', EmploymentStatus::CURRENT->color());
        $this->assertEquals('secondary', EmploymentStatus::PREVIOUS->color());
        $this->assertEquals('danger', EmploymentStatus::TERMINATED->color());
    }

    // -----------------------------------------------------------------------
    // SwitchStatus Enum
    // -----------------------------------------------------------------------

    #[Test]
    public function switch_status_has_correct_cases(): void
    {
        $this->assertEquals('pending', SwitchStatus::PENDING->value);
        $this->assertEquals('approved', SwitchStatus::APPROVED->value);
        $this->assertEquals('completed', SwitchStatus::COMPLETED->value);
        $this->assertEquals('rejected', SwitchStatus::REJECTED->value);
    }

    #[Test]
    public function switch_status_has_labels(): void
    {
        $this->assertEquals('Pending Approval', SwitchStatus::PENDING->label());
        $this->assertEquals('Approved', SwitchStatus::APPROVED->label());
        $this->assertEquals('Completed', SwitchStatus::COMPLETED->label());
        $this->assertEquals('Rejected', SwitchStatus::REJECTED->label());
    }

    #[Test]
    public function switch_status_has_colors(): void
    {
        $this->assertEquals('warning', SwitchStatus::PENDING->color());
        $this->assertEquals('info', SwitchStatus::APPROVED->color());
        $this->assertEquals('success', SwitchStatus::COMPLETED->color());
        $this->assertEquals('danger', SwitchStatus::REJECTED->color());
    }

    // -----------------------------------------------------------------------
    // Enum from value
    // -----------------------------------------------------------------------

    #[Test]
    public function enums_can_be_created_from_string_values(): void
    {
        $this->assertEquals(IqamaStatus::ISSUED, IqamaStatus::from('issued'));
        $this->assertEquals(ContractStatus::ACTIVE, ContractStatus::from('active'));
        $this->assertEquals(EmploymentStatus::CURRENT, EmploymentStatus::from('current'));
        $this->assertEquals(SwitchStatus::APPROVED, SwitchStatus::from('approved'));
    }

    #[Test]
    public function enums_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(IqamaStatus::tryFrom('invalid'));
        $this->assertNull(ContractStatus::tryFrom('invalid'));
        $this->assertNull(EmploymentStatus::tryFrom('invalid'));
        $this->assertNull(SwitchStatus::tryFrom('invalid'));
    }
}
