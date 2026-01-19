<?php

namespace App\Enums;

enum FlightType: string
{
    case DIRECT = 'direct';
    case CONNECTED = 'connected';

    public function label(): string
    {
        return match($this) {
            self::DIRECT => 'Direct Flight',
            self::CONNECTED => 'Connected Flight',
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
