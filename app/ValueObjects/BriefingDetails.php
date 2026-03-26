<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;

class BriefingDetails implements Arrayable
{
    public function __construct(
        public ?string $scheduledDate = null,
        public ?string $completedDate = null,
        public ?string $documentPath = null,
        public ?string $videoPath = null,
        public bool $acknowledgmentSigned = false,
        public ?string $acknowledgmentPath = null,
        public ?string $notes = null,
        public ?int $conductedBy = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        if (! $data) {
            return new self();
        }

        return new self(
            scheduledDate: $data['scheduled_date'] ?? null,
            completedDate: $data['completed_date'] ?? null,
            documentPath: $data['document_path'] ?? null,
            videoPath: $data['video_path'] ?? null,
            acknowledgmentSigned: $data['acknowledgment_signed'] ?? false,
            acknowledgmentPath: $data['acknowledgment_path'] ?? null,
            notes: $data['notes'] ?? null,
            conductedBy: $data['conducted_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'scheduled_date' => $this->scheduledDate,
            'completed_date' => $this->completedDate,
            'document_path' => $this->documentPath,
            'video_path' => $this->videoPath,
            'acknowledgment_signed' => $this->acknowledgmentSigned,
            'acknowledgment_path' => $this->acknowledgmentPath,
            'notes' => $this->notes,
            'conducted_by' => $this->conductedBy,
        ], fn($v) => $v !== null && $v !== false);
    }

    public function isComplete(): bool
    {
        return $this->completedDate !== null && $this->acknowledgmentSigned;
    }

    public function hasDocuments(): bool
    {
        return $this->documentPath !== null || $this->videoPath !== null;
    }
}
