<?php

namespace App\Enums;

/**
 * Candidate Status Enum
 *
 * Represents the lifecycle status of a candidate in the BTEVTA system.
 * Statuses flow: new -> screening -> registered -> training -> visa_process -> ready -> departed
 * Special statuses: rejected, dropped, returned (can occur at various stages)
 */
enum CandidateStatus: string
{
    case NEW = 'new';
    case SCREENING = 'screening';
    case REGISTERED = 'registered';
    case TRAINING = 'training';
    case VISA_PROCESS = 'visa_process';
    case READY = 'ready';
    case DEPARTED = 'departed';
    case REJECTED = 'rejected';
    case DROPPED = 'dropped';
    case RETURNED = 'returned';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::SCREENING => 'Screening',
            self::REGISTERED => 'Registered',
            self::TRAINING => 'Training',
            self::VISA_PROCESS => 'Visa Processing',
            self::READY => 'Ready to Depart',
            self::DEPARTED => 'Departed',
            self::REJECTED => 'Rejected',
            self::DROPPED => 'Dropped',
            self::RETURNED => 'Returned',
        };
    }

    /**
     * Get Bootstrap color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::NEW => 'secondary',
            self::SCREENING => 'info',
            self::REGISTERED => 'primary',
            self::TRAINING => 'warning',
            self::VISA_PROCESS => 'info',
            self::READY => 'success',
            self::DEPARTED => 'success',
            self::REJECTED => 'danger',
            self::DROPPED => 'dark',
            self::RETURNED => 'warning',
        };
    }

    /**
     * Get the order in the workflow (for sorting)
     */
    public function order(): int
    {
        return match($this) {
            self::NEW => 1,
            self::SCREENING => 2,
            self::REGISTERED => 3,
            self::TRAINING => 4,
            self::VISA_PROCESS => 5,
            self::READY => 6,
            self::DEPARTED => 7,
            self::REJECTED => 99,
            self::DROPPED => 98,
            self::RETURNED => 97,
        };
    }

    /**
     * Check if this is a terminal status (no further progression)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::DEPARTED,
            self::REJECTED,
            self::DROPPED,
            self::RETURNED,
        ]);
    }

    /**
     * Check if this is a negative/exit status
     */
    public function isNegative(): bool
    {
        return in_array($this, [
            self::REJECTED,
            self::DROPPED,
            self::RETURNED,
        ]);
    }

    /**
     * Get valid next statuses based on current status
     */
    public function validNextStatuses(): array
    {
        return match($this) {
            self::NEW => [self::SCREENING, self::REJECTED, self::DROPPED],
            self::SCREENING => [self::REGISTERED, self::REJECTED, self::DROPPED],
            self::REGISTERED => [self::TRAINING, self::REJECTED, self::DROPPED],
            self::TRAINING => [self::VISA_PROCESS, self::REJECTED, self::DROPPED],
            self::VISA_PROCESS => [self::READY, self::REJECTED, self::DROPPED],
            self::READY => [self::DEPARTED, self::DROPPED],
            self::DEPARTED => [self::RETURNED],
            self::REJECTED, self::DROPPED, self::RETURNED => [],
        };
    }

    /**
     * Check if transition to given status is valid
     */
    public function canTransitionTo(CandidateStatus $status): bool
    {
        return in_array($status, $this->validNextStatuses());
    }

    /**
     * Get all active (non-terminal) statuses
     */
    public static function activeStatuses(): array
    {
        return [
            self::NEW,
            self::SCREENING,
            self::REGISTERED,
            self::TRAINING,
            self::VISA_PROCESS,
            self::READY,
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
