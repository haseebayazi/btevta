<?php

namespace App\Enums;

enum TrainingType: string
{
    case TECHNICAL = 'technical';
    case SOFT_SKILLS = 'soft_skills';
    case BOTH = 'both';

    public function label(): string
    {
        return match($this) {
            self::TECHNICAL => 'Technical Training',
            self::SOFT_SKILLS => 'Soft Skills Training',
            self::BOTH => 'Technical & Soft Skills',
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
