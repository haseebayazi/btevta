<?php

namespace App\Enums;

enum DepartureStatus: string
{
    case PROCESSING = 'processing';
    case READY_TO_DEPART = 'ready_to_depart';
    case DEPARTED = 'departed';

    public function label(): string
    {
        return match($this) {
            self::PROCESSING => 'Processing',
            self::READY_TO_DEPART => 'Ready to Depart',
            self::DEPARTED => 'Departed',
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
