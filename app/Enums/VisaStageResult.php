<?php

namespace App\Enums;

enum VisaStageResult: string
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case PASS = 'pass';
    case FAIL = 'fail';
    case REFUSED = 'refused';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SCHEDULED => 'Scheduled',
            self::PASS => 'Pass',
            self::FAIL => 'Fail',
            self::REFUSED => 'Refused',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'secondary',
            self::SCHEDULED => 'info',
            self::PASS => 'success',
            self::FAIL => 'danger',
            self::REFUSED => 'dark',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'fas fa-clock',
            self::SCHEDULED => 'fas fa-calendar-check',
            self::PASS => 'fas fa-check-circle',
            self::FAIL => 'fas fa-times-circle',
            self::REFUSED => 'fas fa-ban',
        };
    }

    public function allowsProgress(): bool
    {
        return $this === self::PASS;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::FAIL, self::REFUSED]);
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
