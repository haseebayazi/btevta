<?php

namespace App\Enums;

enum PTNStatus: string
{
    case NOT_APPLIED = 'not_applied';
    case ISSUED = 'issued';
    case DONE = 'done';
    case PENDING = 'pending';
    case NOT_ISSUED = 'not_issued';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::NOT_APPLIED => 'Not Applied',
            self::ISSUED => 'PTN Issued',
            self::DONE => 'PTN Done',
            self::PENDING => 'PTN Pending',
            self::NOT_ISSUED => 'Not Issued',
            self::REFUSED => 'Refused',
        };
    }

    public function isDeferred(): bool
    {
        return in_array($this, [self::NOT_ISSUED, self::REFUSED]);
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
