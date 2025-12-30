<?php

namespace App\Enums;

/**
 * Visa Stage Enum
 *
 * Represents the stages of the visa processing workflow.
 * Stages flow: initiated -> interview -> trade_test -> takamol -> medical ->
 *              enumber -> biometrics -> visa_submission -> visa_issued -> ticket -> completed
 */
enum VisaStage: string
{
    case INITIATED = 'initiated';
    case INTERVIEW = 'interview';
    case TRADE_TEST = 'trade_test';
    case TAKAMOL = 'takamol';
    case MEDICAL = 'medical';
    case ENUMBER = 'enumber';
    case BIOMETRICS = 'biometrics';
    case VISA_SUBMISSION = 'visa_submission';
    case VISA_ISSUED = 'visa_issued';
    case TICKET = 'ticket';
    case COMPLETED = 'completed';

    /**
     * Get human-readable label for the stage
     */
    public function label(): string
    {
        return match($this) {
            self::INITIATED => 'Initiated',
            self::INTERVIEW => 'Interview',
            self::TRADE_TEST => 'Trade Test',
            self::TAKAMOL => 'Takamol Test',
            self::MEDICAL => 'Medical (GAMCA)',
            self::ENUMBER => 'E-Number',
            self::BIOMETRICS => 'Biometrics (Etimad)',
            self::VISA_SUBMISSION => 'Visa Submission',
            self::VISA_ISSUED => 'Visa & PTN',
            self::TICKET => 'Ticket & Travel',
            self::COMPLETED => 'Completed',
        };
    }

    /**
     * Get Bootstrap color class for the stage
     */
    public function color(): string
    {
        return match($this) {
            self::INITIATED => 'secondary',
            self::INTERVIEW, self::TRADE_TEST, self::TAKAMOL,
            self::MEDICAL, self::ENUMBER, self::BIOMETRICS => 'info',
            self::VISA_SUBMISSION => 'warning',
            self::VISA_ISSUED => 'primary',
            self::TICKET, self::COMPLETED => 'success',
        };
    }

    /**
     * Get the order in the workflow (for sorting and progress)
     */
    public function order(): int
    {
        return match($this) {
            self::INITIATED => 1,
            self::INTERVIEW => 2,
            self::TRADE_TEST => 3,
            self::TAKAMOL => 4,
            self::MEDICAL => 5,
            self::ENUMBER => 6,
            self::BIOMETRICS => 7,
            self::VISA_SUBMISSION => 8,
            self::VISA_ISSUED => 9,
            self::TICKET => 10,
            self::COMPLETED => 11,
        };
    }

    /**
     * Get progress percentage based on current stage
     */
    public function progressPercentage(): int
    {
        $totalStages = count(self::cases()) - 1; // Exclude completed for percentage
        return min(100, round(($this->order() / $totalStages) * 100));
    }

    /**
     * Check if this stage is completed (relative to given stage)
     */
    public function isCompletedRelativeTo(VisaStage $currentStage): bool
    {
        return $this->order() < $currentStage->order();
    }

    /**
     * Get the next stage in the workflow
     */
    public function nextStage(): ?VisaStage
    {
        $nextOrder = $this->order() + 1;
        foreach (self::cases() as $stage) {
            if ($stage->order() === $nextOrder) {
                return $stage;
            }
        }
        return null;
    }

    /**
     * Get the previous stage in the workflow
     */
    public function previousStage(): ?VisaStage
    {
        $prevOrder = $this->order() - 1;
        foreach (self::cases() as $stage) {
            if ($stage->order() === $prevOrder) {
                return $stage;
            }
        }
        return null;
    }

    /**
     * Check if this is the final stage
     */
    public function isFinal(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Check if visa has been issued (for filtering)
     */
    public function hasVisaIssued(): bool
    {
        return $this->order() >= self::VISA_ISSUED->order();
    }

    /**
     * Get stages that require document upload
     */
    public static function stagesRequiringDocuments(): array
    {
        return [
            self::TAKAMOL,
            self::MEDICAL,
            self::TICKET,
        ];
    }

    /**
     * Get all stages as array for dropdowns (with metadata)
     */
    public static function toArrayWithMeta(): array
    {
        $result = [];
        foreach (self::cases() as $stage) {
            $result[$stage->value] = [
                'label' => $stage->label(),
                'order' => $stage->order(),
                'color' => $stage->color(),
            ];
        }
        return $result;
    }

    /**
     * Get all stages as simple array for dropdowns
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
