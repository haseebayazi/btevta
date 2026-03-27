<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case CURRENT = 'current';
    case PREVIOUS = 'previous';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::CURRENT => 'Current',
            self::PREVIOUS => 'Previous',
            self::TERMINATED => 'Terminated',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::CURRENT => 'success',
            self::PREVIOUS => 'secondary',
            self::TERMINATED => 'danger',
        };
    }
}
