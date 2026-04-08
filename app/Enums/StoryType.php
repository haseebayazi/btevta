<?php

namespace App\Enums;

enum StoryType: string
{
    case EMPLOYMENT = 'employment';
    case CAREER_GROWTH = 'career_growth';
    case SKILL_ACHIEVEMENT = 'skill_achievement';
    case REMITTANCE = 'remittance';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYMENT       => 'Employment Success',
            self::CAREER_GROWTH    => 'Career Growth',
            self::SKILL_ACHIEVEMENT => 'Skill Achievement',
            self::REMITTANCE       => 'Remittance Impact',
            self::OTHER            => 'Other Success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EMPLOYMENT       => 'fas fa-briefcase',
            self::CAREER_GROWTH    => 'fas fa-chart-line',
            self::SKILL_ACHIEVEMENT => 'fas fa-award',
            self::REMITTANCE       => 'fas fa-money-bill-wave',
            self::OTHER            => 'fas fa-star',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EMPLOYMENT       => 'primary',
            self::CAREER_GROWTH    => 'success',
            self::SKILL_ACHIEVEMENT => 'warning',
            self::REMITTANCE       => 'info',
            self::OTHER            => 'secondary',
        };
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn ($c) => $c->label(), self::cases())
        );
    }
}
