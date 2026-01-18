<?php

namespace App\Enums;

enum ProtectorStatus: string
{
    case NOT_APPLIED = 'not_applied';
    case APPLIED = 'applied';
    case DONE = 'done';
    case PENDING = 'pending';
    case NOT_ISSUED = 'not_issued';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::NOT_APPLIED => 'Not Applied',
            self::APPLIED => 'Protector Applied',
            self::DONE => 'Protector Done',
            self::PENDING => 'Protector Pending',
            self::NOT_ISSUED => 'Not Issued',
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
