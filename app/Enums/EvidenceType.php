<?php

namespace App\Enums;

enum EvidenceType: string
{
    case AUDIO = 'audio';
    case VIDEO = 'video';
    case WRITTEN = 'written';
    case SCREENSHOT = 'screenshot';
    case DOCUMENT = 'document';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::AUDIO => 'Audio Recording',
            self::VIDEO => 'Video Recording',
            self::WRITTEN => 'Written Note',
            self::SCREENSHOT => 'Screenshot',
            self::DOCUMENT => 'Document',
            self::OTHER => 'Other',
        };
    }

    public function allowedMimes(): array
    {
        return match($this) {
            self::AUDIO => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
            self::VIDEO => ['video/mp4', 'video/webm', 'video/quicktime'],
            self::WRITTEN => ['text/plain'],
            self::SCREENSHOT => ['image/png', 'image/jpeg'],
            self::DOCUMENT => ['application/pdf', 'image/png', 'image/jpeg'],
            self::OTHER => ['application/pdf', 'image/png', 'image/jpeg', 'audio/mpeg', 'video/mp4'],
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
