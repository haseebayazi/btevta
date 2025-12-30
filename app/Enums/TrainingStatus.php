<?php

namespace App\Enums;

/**
 * Training Status Enum
 *
 * Represents the training status of a candidate or training session.
 */
enum TrainingStatus: string
{
    // Candidate Training Status
    case PENDING = 'pending';
    case ENROLLED = 'enrolled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case WITHDRAWN = 'withdrawn';

    // Training Class/Session Status
    case SCHEDULED = 'scheduled';
    case ONGOING = 'ongoing';
    case CANCELLED = 'cancelled';
    case POSTPONED = 'postponed';
    case RESCHEDULED = 'rescheduled';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ENROLLED => 'Enrolled',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::WITHDRAWN => 'Withdrawn',
            self::SCHEDULED => 'Scheduled',
            self::ONGOING => 'Ongoing',
            self::CANCELLED => 'Cancelled',
            self::POSTPONED => 'Postponed',
            self::RESCHEDULED => 'Rescheduled',
        };
    }

    /**
     * Get Bootstrap color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'secondary',
            self::ENROLLED => 'info',
            self::IN_PROGRESS, self::ONGOING => 'primary',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::WITHDRAWN, self::CANCELLED => 'dark',
            self::SCHEDULED => 'info',
            self::POSTPONED, self::RESCHEDULED => 'warning',
        };
    }

    /**
     * Check if this is a terminal status
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::WITHDRAWN,
            self::CANCELLED,
        ]);
    }

    /**
     * Check if this is an active/ongoing status
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::ENROLLED,
            self::IN_PROGRESS,
            self::ONGOING,
        ]);
    }

    /**
     * Check if training can proceed (not blocked)
     */
    public function canProceed(): bool
    {
        return !in_array($this, [
            self::FAILED,
            self::WITHDRAWN,
            self::CANCELLED,
        ]);
    }

    /**
     * Get candidate-specific training statuses
     */
    public static function candidateStatuses(): array
    {
        return [
            self::PENDING,
            self::ENROLLED,
            self::IN_PROGRESS,
            self::COMPLETED,
            self::FAILED,
            self::WITHDRAWN,
        ];
    }

    /**
     * Get class/session-specific statuses
     */
    public static function classStatuses(): array
    {
        return [
            self::SCHEDULED,
            self::ONGOING,
            self::COMPLETED,
            self::CANCELLED,
            self::POSTPONED,
            self::RESCHEDULED,
        ];
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
