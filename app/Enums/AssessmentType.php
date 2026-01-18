<?php

namespace App\Enums;

enum AssessmentType: string
{
    case INTERIM = 'interim';
    case FINAL = 'final';

    public function label(): string
    {
        return match($this) {
            self::INTERIM => 'Interim Assessment',
            self::FINAL => 'Final Assessment',
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
