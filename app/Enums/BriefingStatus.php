<?php

namespace App\Enums;

enum BriefingStatus: string
{
    case NOT_SCHEDULED = 'not_scheduled';
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::NOT_SCHEDULED => 'Not Scheduled',
            self::SCHEDULED => 'Scheduled',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NOT_SCHEDULED => 'secondary',
            self::SCHEDULED => 'info',
            self::COMPLETED => 'success',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::NOT_SCHEDULED => 'fas fa-calendar',
            self::SCHEDULED => 'fas fa-calendar-check',
            self::COMPLETED => 'fas fa-check-circle',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
