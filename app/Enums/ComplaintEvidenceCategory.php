<?php

namespace App\Enums;

enum ComplaintEvidenceCategory: string
{
    case INITIAL_REPORT        = 'initial_report';
    case SUPPORTING_DOCUMENT   = 'supporting_document';
    case PHOTO_VIDEO           = 'photo_video';
    case WITNESS_STATEMENT     = 'witness_statement';
    case COMMUNICATION_RECORD  = 'communication_record';
    case RESOLUTION_PROOF      = 'resolution_proof';
    case OTHER                 = 'other';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL_REPORT       => 'Initial Report',
            self::SUPPORTING_DOCUMENT  => 'Supporting Document',
            self::PHOTO_VIDEO          => 'Photo/Video Evidence',
            self::WITNESS_STATEMENT    => 'Witness Statement',
            self::COMMUNICATION_RECORD => 'Communication Record',
            self::RESOLUTION_PROOF     => 'Resolution Proof',
            self::OTHER                => 'Other',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INITIAL_REPORT       => 'The original complaint report or incident description',
            self::SUPPORTING_DOCUMENT  => 'Documents that support the complaint (contracts, letters, etc.)',
            self::PHOTO_VIDEO          => 'Visual evidence of the issue',
            self::WITNESS_STATEMENT    => 'Statements from witnesses to the incident',
            self::COMMUNICATION_RECORD => 'Emails, messages, or call recordings',
            self::RESOLUTION_PROOF     => 'Evidence that the issue was resolved',
            self::OTHER                => 'Other relevant evidence',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::INITIAL_REPORT       => 'fas fa-file-medical',
            self::SUPPORTING_DOCUMENT  => 'fas fa-file-alt',
            self::PHOTO_VIDEO          => 'fas fa-photo-video',
            self::WITNESS_STATEMENT    => 'fas fa-user-check',
            self::COMMUNICATION_RECORD => 'fas fa-comments',
            self::RESOLUTION_PROOF     => 'fas fa-check-circle',
            self::OTHER                => 'fas fa-paperclip',
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
