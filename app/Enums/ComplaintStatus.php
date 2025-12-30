<?php

namespace App\Enums;

/**
 * Complaint Status Enum
 *
 * Represents the lifecycle status of a complaint in the system.
 */
enum ComplaintStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
        };
    }

    /**
     * Get Bootstrap color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::OPEN => 'danger',
            self::ASSIGNED => 'warning',
            self::IN_PROGRESS => 'info',
            self::RESOLVED => 'success',
            self::CLOSED => 'secondary',
        };
    }

    /**
     * Get the order in the workflow
     */
    public function order(): int
    {
        return match($this) {
            self::OPEN => 1,
            self::ASSIGNED => 2,
            self::IN_PROGRESS => 3,
            self::RESOLVED => 4,
            self::CLOSED => 5,
        };
    }

    /**
     * Check if this is an active (open) status
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::OPEN,
            self::ASSIGNED,
            self::IN_PROGRESS,
        ]);
    }

    /**
     * Check if this is a closed/terminal status
     */
    public function isClosed(): bool
    {
        return in_array($this, [
            self::RESOLVED,
            self::CLOSED,
        ]);
    }

    /**
     * Check if complaint can be escalated
     */
    public function canEscalate(): bool
    {
        return in_array($this, [
            self::OPEN,
            self::ASSIGNED,
            self::IN_PROGRESS,
        ]);
    }

    /**
     * Check if complaint can be reopened
     */
    public function canReopen(): bool
    {
        return in_array($this, [
            self::RESOLVED,
            self::CLOSED,
        ]);
    }

    /**
     * Get valid next statuses based on current status
     */
    public function validNextStatuses(): array
    {
        return match($this) {
            self::OPEN => [self::ASSIGNED, self::IN_PROGRESS, self::CLOSED],
            self::ASSIGNED => [self::IN_PROGRESS, self::RESOLVED, self::CLOSED],
            self::IN_PROGRESS => [self::RESOLVED, self::CLOSED],
            self::RESOLVED => [self::CLOSED, self::IN_PROGRESS], // Can reopen
            self::CLOSED => [self::OPEN], // Can reopen
        };
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

    /**
     * Get active statuses
     */
    public static function activeStatuses(): array
    {
        return [
            self::OPEN,
            self::ASSIGNED,
            self::IN_PROGRESS,
        ];
    }
}
