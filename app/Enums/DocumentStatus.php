<?php

namespace App\Enums;

/**
 * Document Status Enum
 *
 * AUDIT FIX: Created to replace hardcoded document status strings.
 * Represents the verification status of uploaded documents.
 */
enum DocumentStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending Verification',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
        };
    }

    /**
     * Get Bootstrap color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::VERIFIED => 'success',
            self::REJECTED => 'danger',
            self::EXPIRED => 'secondary',
        };
    }

    /**
     * Check if document needs attention
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::PENDING, self::REJECTED, self::EXPIRED]);
    }

    /**
     * Check if document is valid for use
     */
    public function isValid(): bool
    {
        return $this === self::VERIFIED;
    }

    /**
     * Get all statuses as array for dropdowns
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
