<?php

namespace App\Enums;

enum IqamaStatus: string
{
    case PENDING = 'pending';
    case ISSUED = 'issued';
    case EXPIRED = 'expired';
    case RENEWED = 'renewed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::ISSUED => 'Issued',
            self::EXPIRED => 'Expired',
            self::RENEWED => 'Renewed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::ISSUED => 'success',
            self::EXPIRED => 'danger',
            self::RENEWED => 'info',
        };
    }
}
