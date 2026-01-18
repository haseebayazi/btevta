<?php

namespace App\Enums;

/**
 * Screening Status Enum
 *
 * AUDIT FIX: Created to replace hardcoded screening status strings.
 * Represents the outcome of candidate screening processes.
 */
enum ScreeningStatus: string
{
    case PENDING = 'pending';
    case SCREENED = 'screened';
    case DEFERRED = 'deferred';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SCREENED => 'Screened',
            self::DEFERRED => 'Deferred',
        };
    }

    /**
     * Get Bootstrap color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::SCREENED => 'success',
            self::DEFERRED => 'danger',
        };
    }

    /**
     * Check if screening allows progression
     */
    public function allowsProgression(): bool
    {
        return $this === self::SCREENED;
    }

    /**
     * Check if screening is terminal (no retry)
     */
    public function isTerminal(): bool
    {
        return $this === self::DEFERRED;
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
