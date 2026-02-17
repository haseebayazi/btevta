<?php

namespace App\ValueObjects;

use App\Enums\VisaStageResult;
use Illuminate\Contracts\Support\Arrayable;

class VisaStageDetails implements Arrayable
{
    public function __construct(
        public ?string $appointmentDate = null,
        public ?string $appointmentTime = null,
        public ?string $center = null,
        public ?string $resultStatus = null,
        public ?string $evidencePath = null,
        public ?string $notes = null,
        public ?string $updatedAt = null,
        public ?int $updatedBy = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (!$data) {
            return new self();
        }

        return new self(
            appointmentDate: $data['appointment_date'] ?? null,
            appointmentTime: $data['appointment_time'] ?? null,
            center: $data['center'] ?? null,
            resultStatus: $data['result_status'] ?? null,
            evidencePath: $data['evidence_path'] ?? null,
            notes: $data['notes'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            updatedBy: isset($data['updated_by']) ? (int) $data['updated_by'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'appointment_date' => $this->appointmentDate,
            'appointment_time' => $this->appointmentTime,
            'center' => $this->center,
            'result_status' => $this->resultStatus,
            'evidence_path' => $this->evidencePath,
            'notes' => $this->notes,
            'updated_at' => $this->updatedAt,
            'updated_by' => $this->updatedBy,
        ], fn($value) => $value !== null);
    }

    public function isScheduled(): bool
    {
        return $this->appointmentDate !== null;
    }

    public function hasResult(): bool
    {
        return $this->resultStatus !== null;
    }

    public function isPassed(): bool
    {
        return $this->resultStatus === VisaStageResult::PASS->value;
    }

    public function hasEvidence(): bool
    {
        return !empty($this->evidencePath);
    }

    public function getResultEnum(): ?VisaStageResult
    {
        return $this->resultStatus ? VisaStageResult::tryFrom($this->resultStatus) : null;
    }

    public function withAppointment(string $date, string $time, string $center): self
    {
        return new self(
            appointmentDate: $date,
            appointmentTime: $time,
            center: $center,
            resultStatus: VisaStageResult::SCHEDULED->value,
            evidencePath: $this->evidencePath,
            notes: $this->notes,
            updatedAt: now()->toDateTimeString(),
            updatedBy: auth()->id(),
        );
    }

    public function withResult(string $resultStatus, ?string $notes = null, ?string $evidencePath = null): self
    {
        return new self(
            appointmentDate: $this->appointmentDate,
            appointmentTime: $this->appointmentTime,
            center: $this->center,
            resultStatus: $resultStatus,
            evidencePath: $evidencePath ?? $this->evidencePath,
            notes: $notes ?? $this->notes,
            updatedAt: now()->toDateTimeString(),
            updatedBy: auth()->id(),
        );
    }
}
