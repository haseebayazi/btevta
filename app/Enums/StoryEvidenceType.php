<?php

namespace App\Enums;

enum StoryEvidenceType: string
{
    case PHOTO       = 'photo';
    case VIDEO       = 'video';
    case DOCUMENT    = 'document';
    case INTERVIEW   = 'interview';
    case TESTIMONIAL = 'testimonial';
    case CERTIFICATE = 'certificate';

    public function label(): string
    {
        return match ($this) {
            self::PHOTO       => 'Photograph',
            self::VIDEO       => 'Video',
            self::DOCUMENT    => 'Document',
            self::INTERVIEW   => 'Interview Recording',
            self::TESTIMONIAL => 'Written Testimonial',
            self::CERTIFICATE => 'Certificate/Award',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PHOTO       => 'fas fa-image',
            self::VIDEO       => 'fas fa-video',
            self::DOCUMENT    => 'fas fa-file-alt',
            self::INTERVIEW   => 'fas fa-microphone',
            self::TESTIMONIAL => 'fas fa-quote-right',
            self::CERTIFICATE => 'fas fa-certificate',
        };
    }

    public function allowedMimes(): array
    {
        return match ($this) {
            self::PHOTO       => ['jpg', 'jpeg', 'png', 'webp'],
            self::VIDEO       => ['mp4', 'mov', 'avi', 'webm'],
            self::DOCUMENT    => ['pdf', 'doc', 'docx'],
            self::INTERVIEW   => ['mp3', 'wav', 'mp4', 'mov'],
            self::TESTIMONIAL => ['pdf', 'doc', 'docx', 'txt'],
            self::CERTIFICATE => ['pdf', 'jpg', 'jpeg', 'png'],
        };
    }

    public function maxSizeMB(): int
    {
        return match ($this) {
            self::VIDEO, self::INTERVIEW => 100,
            default                      => 10,
        };
    }

    public function maxSizeKB(): int
    {
        return $this->maxSizeMB() * 1024;
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
