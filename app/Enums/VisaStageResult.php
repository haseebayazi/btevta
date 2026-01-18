<?php

namespace App\Enums;

enum VisaStageResult: string
{
    case PENDING = 'pending';
    case PASS = 'pass';
    case FAIL = 'fail';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PASS => 'Pass',
            self::FAIL => 'Fail',
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
