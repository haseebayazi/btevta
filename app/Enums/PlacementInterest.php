<?php

namespace App\Enums;

enum PlacementInterest: string
{
    case LOCAL = 'local';
    case INTERNATIONAL = 'international';

    public function label(): string
    {
        return match($this) {
            self::LOCAL => 'Local Placement',
            self::INTERNATIONAL => 'International Placement',
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
