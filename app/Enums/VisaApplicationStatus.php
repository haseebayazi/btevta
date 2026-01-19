<?php

namespace App\Enums;

enum VisaApplicationStatus: string
{
    case NOT_APPLIED = 'not_applied';
    case APPLIED = 'applied';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::NOT_APPLIED => 'Not Applied',
            self::APPLIED => 'Applied',
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
