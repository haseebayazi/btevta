<?php

namespace App\Enums;

enum StoryStatus: string
{
    case DRAFT          = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED       = 'approved';
    case PUBLISHED      = 'published';
    case REJECTED       = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT          => 'Draft',
            self::PENDING_REVIEW => 'Pending Review',
            self::APPROVED       => 'Approved',
            self::PUBLISHED      => 'Published',
            self::REJECTED       => 'Rejected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT          => 'secondary',
            self::PENDING_REVIEW => 'warning',
            self::APPROVED       => 'info',
            self::PUBLISHED      => 'success',
            self::REJECTED       => 'danger',
        };
    }

    public function canSubmitForReview(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    public function canApprove(): bool
    {
        return $this === self::PENDING_REVIEW;
    }

    public function canPublish(): bool
    {
        return $this === self::APPROVED;
    }

    public function canReject(): bool
    {
        return $this === self::PENDING_REVIEW || $this === self::APPROVED;
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn ($c) => $c->label(), self::cases())
        );
    }
}
