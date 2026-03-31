<?php

namespace Tests\Unit;

use App\Enums\EmployerSize;
use App\Enums\EmploymentType;
use App\ValueObjects\EmploymentPackage;
use App\Models\EmployerDocument;
use PHPUnit\Framework\TestCase;

class EmployerEnhancedTest extends TestCase
{
    public function test_employer_size_enum_has_correct_cases(): void
    {
        $cases = EmployerSize::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('small', EmployerSize::SMALL->value);
        $this->assertEquals('medium', EmployerSize::MEDIUM->value);
        $this->assertEquals('large', EmployerSize::LARGE->value);
        $this->assertEquals('enterprise', EmployerSize::ENTERPRISE->value);
    }

    public function test_employer_size_labels(): void
    {
        $this->assertEquals('Small (1-50)', EmployerSize::SMALL->label());
        $this->assertEquals('Medium (51-200)', EmployerSize::MEDIUM->label());
        $this->assertEquals('Large (201-1000)', EmployerSize::LARGE->label());
        $this->assertEquals('Enterprise (1000+)', EmployerSize::ENTERPRISE->label());
    }

    public function test_employer_size_colors(): void
    {
        $this->assertEquals('gray', EmployerSize::SMALL->color());
        $this->assertEquals('blue', EmployerSize::MEDIUM->color());
        $this->assertEquals('indigo', EmployerSize::LARGE->color());
        $this->assertEquals('purple', EmployerSize::ENTERPRISE->color());
    }

    public function test_employment_type_enum_has_correct_cases(): void
    {
        $cases = EmploymentType::cases();

        $this->assertCount(3, $cases);
        $this->assertEquals('initial', EmploymentType::INITIAL->value);
        $this->assertEquals('transfer', EmploymentType::TRANSFER->value);
        $this->assertEquals('switch', EmploymentType::SWITCH->value);
    }

    public function test_employment_type_labels(): void
    {
        $this->assertEquals('Initial Assignment', EmploymentType::INITIAL->label());
        $this->assertEquals('Transfer', EmploymentType::TRANSFER->label());
        $this->assertEquals('Company Switch', EmploymentType::SWITCH->label());
    }

    public function test_employment_type_colors(): void
    {
        $this->assertEquals('blue', EmploymentType::INITIAL->color());
        $this->assertEquals('cyan', EmploymentType::TRANSFER->color());
        $this->assertEquals('yellow', EmploymentType::SWITCH->color());
    }

    public function test_employment_package_from_null_returns_default(): void
    {
        $package = EmploymentPackage::fromArray(null);

        $this->assertEquals(0, $package->baseSalary);
        $this->assertEquals('SAR', $package->currency);
        $this->assertEquals(0, $package->housingAllowance);
        $this->assertEquals(0, $package->foodAllowance);
        $this->assertEquals(0, $package->transportAllowance);
        $this->assertEquals(0, $package->otherAllowance);
        $this->assertNull($package->benefits);
        $this->assertNull($package->notes);
    }

    public function test_employment_package_from_array(): void
    {
        $data = [
            'base_salary' => 3000,
            'currency' => 'SAR',
            'housing_allowance' => 500,
            'food_allowance' => 300,
            'transport_allowance' => 200,
            'other_allowance' => 100,
        ];

        $package = EmploymentPackage::fromArray($data);

        $this->assertEquals(3000, $package->baseSalary);
        $this->assertEquals('SAR', $package->currency);
        $this->assertEquals(500, $package->housingAllowance);
        $this->assertEquals(300, $package->foodAllowance);
        $this->assertEquals(200, $package->transportAllowance);
        $this->assertEquals(100, $package->otherAllowance);
    }

    public function test_employment_package_get_total(): void
    {
        $package = new EmploymentPackage(
            baseSalary: 3000,
            housingAllowance: 500,
            foodAllowance: 300,
            transportAllowance: 200,
            otherAllowance: 100,
        );

        $this->assertEquals(4100, $package->getTotal());
    }

    public function test_employment_package_formatted_total(): void
    {
        $package = new EmploymentPackage(
            baseSalary: 3000,
            currency: 'SAR',
            housingAllowance: 500,
        );

        $this->assertEquals('3,500.00 SAR', $package->getFormattedTotal());
    }

    public function test_employment_package_to_array(): void
    {
        $package = new EmploymentPackage(
            baseSalary: 3000,
            currency: 'SAR',
            housingAllowance: 500,
        );

        $array = $package->toArray();

        $this->assertEquals(3000, $array['base_salary']);
        $this->assertEquals('SAR', $array['currency']);
        $this->assertEquals(500, $array['housing_allowance']);
        $this->assertArrayHasKey('food_allowance', $array);
        $this->assertArrayHasKey('transport_allowance', $array);
        $this->assertArrayHasKey('other_allowance', $array);
    }

    public function test_employment_package_breakdown(): void
    {
        $package = new EmploymentPackage(
            baseSalary: 3000,
            housingAllowance: 1000,
            foodAllowance: 500,
            transportAllowance: 250,
            otherAllowance: 250,
        );

        $breakdown = $package->getBreakdown();

        $this->assertCount(5, $breakdown);
        $this->assertEquals('Base Salary', $breakdown[0]['label']);
        $this->assertEquals(3000, $breakdown[0]['amount']);
        $this->assertEquals(60.0, $breakdown[0]['percentage']);
    }

    public function test_employment_package_zero_total_percentage(): void
    {
        $package = new EmploymentPackage();

        $breakdown = $package->getBreakdown();

        foreach ($breakdown as $item) {
            $this->assertEquals(0, $item['percentage']);
        }
    }

    public function test_employer_document_types(): void
    {
        $types = EmployerDocument::documentTypes();

        $this->assertArrayHasKey('license', $types);
        $this->assertArrayHasKey('registration', $types);
        $this->assertArrayHasKey('permission', $types);
        $this->assertArrayHasKey('contract_template', $types);
        $this->assertArrayHasKey('other', $types);
        $this->assertCount(5, $types);
    }

    public function test_employment_package_roundtrip(): void
    {
        $original = new EmploymentPackage(
            baseSalary: 5000,
            currency: 'AED',
            housingAllowance: 1000,
            foodAllowance: 500,
            transportAllowance: 300,
            otherAllowance: 200,
        );

        $array = $original->toArray();
        $restored = EmploymentPackage::fromArray($array);

        $this->assertEquals($original->baseSalary, $restored->baseSalary);
        $this->assertEquals($original->currency, $restored->currency);
        $this->assertEquals($original->housingAllowance, $restored->housingAllowance);
        $this->assertEquals($original->getTotal(), $restored->getTotal());
    }
}
