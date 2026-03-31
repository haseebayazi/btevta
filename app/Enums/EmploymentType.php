<?php

namespace App\Enums;

enum EmploymentType: string
{
    case INITIAL = 'initial';
    case TRANSFER = 'transfer';
    case SWITCH = 'switch';

    public function label(): string
    {
        return match($this) {
            self::INITIAL => 'Initial Assignment',
            self::TRANSFER => 'Transfer',
            self::SWITCH => 'Company Switch',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::INITIAL => 'blue',
            self::TRANSFER => 'cyan',
            self::SWITCH => 'yellow',
        };
    }
}
