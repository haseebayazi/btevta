<?php

namespace App\Enums;

enum EmployerSize: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match($this) {
            self::SMALL => 'Small (1-50)',
            self::MEDIUM => 'Medium (51-200)',
            self::LARGE => 'Large (201-1000)',
            self::ENTERPRISE => 'Enterprise (1000+)',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SMALL => 'gray',
            self::MEDIUM => 'blue',
            self::LARGE => 'indigo',
            self::ENTERPRISE => 'purple',
        };
    }
}
