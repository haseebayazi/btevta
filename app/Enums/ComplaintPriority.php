<?php

namespace App\Enums;

/**
 * Complaint Priority Enum
 *
 * Represents the priority levels for complaints in the system.
 * Higher priority complaints have shorter SLA response times.
 */
enum ComplaintPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

    /**
     * Get human-readable label for the priority
     */
    public function label(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * Get Bootstrap color class for the priority
     */
    public function color(): string
    {
        return match($this) {
            self::LOW => 'secondary',
            self::NORMAL => 'info',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
        };
    }

    /**
     * Get icon for the priority (Font Awesome)
     */
    public function icon(): string
    {
        return match($this) {
            self::LOW => 'fa-arrow-down',
            self::NORMAL => 'fa-minus',
            self::HIGH => 'fa-arrow-up',
            self::URGENT => 'fa-exclamation-triangle',
        };
    }

    /**
     * Get the order for sorting (higher = more urgent)
     */
    public function order(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    /**
     * Get SLA response time in hours
     */
    public function slaHours(): int
    {
        return match($this) {
            self::LOW => 72,      // 3 days
            self::NORMAL => 48,   // 2 days
            self::HIGH => 24,     // 1 day
            self::URGENT => 4,    // 4 hours
        };
    }

    /**
     * Get SLA resolution time in hours
     */
    public function slaResolutionHours(): int
    {
        return match($this) {
            self::LOW => 168,     // 7 days
            self::NORMAL => 120,  // 5 days
            self::HIGH => 72,     // 3 days
            self::URGENT => 24,   // 1 day
        };
    }

    /**
     * Check if this priority requires immediate attention
     */
    public function requiresImmediateAttention(): bool
    {
        return in_array($this, [self::HIGH, self::URGENT]);
    }

    /**
     * Check if SLA is at risk based on hours elapsed
     */
    public function isSlAAtRisk(int $hoursElapsed): bool
    {
        // At risk if 75% of SLA time has passed
        return $hoursElapsed >= ($this->slaHours() * 0.75);
    }

    /**
     * Check if SLA is breached based on hours elapsed
     */
    public function isSlaBreached(int $hoursElapsed): bool
    {
        return $hoursElapsed > $this->slaHours();
    }

    /**
     * Get notification recipients for this priority
     */
    public function notificationRecipients(): array
    {
        return match($this) {
            self::LOW => ['assigned_user'],
            self::NORMAL => ['assigned_user', 'supervisor'],
            self::HIGH => ['assigned_user', 'supervisor', 'department_head'],
            self::URGENT => ['assigned_user', 'supervisor', 'department_head', 'admin'],
        };
    }

    /**
     * Get all priorities as array for dropdowns
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }

    /**
     * Get all priorities ordered by urgency (most urgent first)
     */
    public static function orderedByUrgency(): array
    {
        $cases = self::cases();
        usort($cases, fn($a, $b) => $b->order() - $a->order());
        return $cases;
    }
}
