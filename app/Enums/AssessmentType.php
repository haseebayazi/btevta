<?php

namespace App\Enums;

enum AssessmentType: string
{
    case INITIAL = 'initial';
    case INTERIM = 'interim';
    case MIDTERM = 'midterm';
    case PRACTICAL = 'practical';
    case FINAL = 'final';

    public function label(): string
    {
        return match($this) {
            self::INITIAL => 'Initial Assessment',
            self::INTERIM => 'Interim Assessment',
            self::MIDTERM => 'Midterm Assessment',
            self::PRACTICAL => 'Practical Assessment',
            self::FINAL => 'Final Assessment',
        };
    }

    public function isRequired(): bool
    {
        return in_array($this, [self::MIDTERM, self::FINAL]);
    }

    public static function technicalTypes(): array
    {
        return [self::INITIAL, self::MIDTERM, self::PRACTICAL, self::FINAL];
    }

    public static function softSkillsTypes(): array
    {
        return [self::INTERIM, self::FINAL];
    }

    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
