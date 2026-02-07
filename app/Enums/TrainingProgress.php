<?php

namespace App\Enums;

enum TrainingProgress: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::NOT_STARTED => 'Not Started',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NOT_STARTED => 'secondary',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::NOT_STARTED => 'fas fa-clock',
            self::IN_PROGRESS => 'fas fa-spinner fa-spin',
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
