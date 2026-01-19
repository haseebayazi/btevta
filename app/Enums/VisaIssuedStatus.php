<?php

namespace App\Enums;

enum VisaIssuedStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::REFUSED => 'Refused',
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
