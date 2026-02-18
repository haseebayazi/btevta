<?php

namespace App\Enums;

enum DepartureStatus: string
{
    case PROCESSING = 'processing';
    case READY_TO_DEPART = 'ready_to_depart';
    case DEPARTED = 'departed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PROCESSING => 'Processing',
            self::READY_TO_DEPART => 'Ready to Depart',
            self::DEPARTED => 'Departed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PROCESSING => 'warning',
            self::READY_TO_DEPART => 'info',
            self::DEPARTED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PROCESSING => 'fas fa-cog fa-spin',
            self::READY_TO_DEPART => 'fas fa-plane-departure',
            self::DEPARTED => 'fas fa-plane',
            self::CANCELLED => 'fas fa-times-circle',
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
