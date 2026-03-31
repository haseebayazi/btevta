<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class EmploymentPackage implements Arrayable
{
    public function __construct(
        public float $baseSalary = 0,
        public string $currency = 'SAR',
        public float $housingAllowance = 0,
        public float $foodAllowance = 0,
        public float $transportAllowance = 0,
        public float $otherAllowance = 0,
        public ?array $benefits = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) {
            return new self();
        }

        return new self(
            baseSalary: (float) ($data['base_salary'] ?? 0),
            currency: $data['currency'] ?? 'SAR',
            housingAllowance: (float) ($data['housing_allowance'] ?? 0),
            foodAllowance: (float) ($data['food_allowance'] ?? 0),
            transportAllowance: (float) ($data['transport_allowance'] ?? 0),
            otherAllowance: (float) ($data['other_allowance'] ?? 0),
            benefits: $data['benefits'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'base_salary' => $this->baseSalary,
            'currency' => $this->currency,
            'housing_allowance' => $this->housingAllowance,
            'food_allowance' => $this->foodAllowance,
            'transport_allowance' => $this->transportAllowance,
            'other_allowance' => $this->otherAllowance,
            'benefits' => $this->benefits,
            'notes' => $this->notes,
        ];
    }

    public function getTotal(): float
    {
        return $this->baseSalary
            + $this->housingAllowance
            + $this->foodAllowance
            + $this->transportAllowance
            + $this->otherAllowance;
    }

    public function getFormattedTotal(): string
    {
        return number_format($this->getTotal(), 2) . ' ' . $this->currency;
    }

    public function getBreakdown(): array
    {
        return [
            ['label' => 'Base Salary', 'amount' => $this->baseSalary, 'percentage' => $this->getPercentage($this->baseSalary)],
            ['label' => 'Housing', 'amount' => $this->housingAllowance, 'percentage' => $this->getPercentage($this->housingAllowance)],
            ['label' => 'Food', 'amount' => $this->foodAllowance, 'percentage' => $this->getPercentage($this->foodAllowance)],
            ['label' => 'Transport', 'amount' => $this->transportAllowance, 'percentage' => $this->getPercentage($this->transportAllowance)],
            ['label' => 'Other', 'amount' => $this->otherAllowance, 'percentage' => $this->getPercentage($this->otherAllowance)],
        ];
    }

    protected function getPercentage(float $amount): float
    {
        $total = $this->getTotal();
        return $total > 0 ? round(($amount / $total) * 100, 1) : 0;
    }
}
