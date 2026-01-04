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
    case PASSED = 'passed';
    case FAILED = 'failed';
    case DEFERRED = 'deferred';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PASSED => 'Passed',
            self::FAILED => 'Failed',
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
            self::PASSED => 'success',
            self::FAILED => 'danger',
            self::DEFERRED => 'info',
        };
    }

    /**
     * Check if screening allows progression
     */
    public function allowsProgression(): bool
    {
        return $this === self::PASSED;
    }

    /**
     * Check if screening is terminal (no retry)
     */
    public function isTerminal(): bool
    {
        return $this === self::FAILED;
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
