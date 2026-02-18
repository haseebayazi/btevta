<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class PTNDetails implements Arrayable
{
    public function __construct(
        public ?string $status = null,
        public ?string $issuedDate = null,
        public ?string $expiryDate = null,
        public ?string $evidencePath = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (! $data) {
            return new self();
        }

        return new self(
            status: $data['status'] ?? null,
            issuedDate: $data['issued_date'] ?? null,
            expiryDate: $data['expiry_date'] ?? null,
            evidencePath: $data['evidence_path'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'issued_date' => $this->issuedDate,
            'expiry_date' => $this->expiryDate,
            'evidence_path' => $this->evidencePath,
            'notes' => $this->notes,
        ], fn($v) => $v !== null);
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued' && $this->issuedDate !== null;
    }

    public function isExpired(): bool
    {
        if (! $this->expiryDate) {
            return false;
        }

        return now()->greaterThan($this->expiryDate);
    }
}
