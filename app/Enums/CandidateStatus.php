<?php

namespace App\Enums;

/**
 * Candidate Status Enum - WASL v3
 *
 * Represents the lifecycle status of a candidate in the WASL system.
 * New sequential workflow with enhanced stages from LISTED through COMPLETED
 */
enum CandidateStatus: string
{
    // New sequential workflow
    case LISTED = 'listed';
    case PRE_DEPARTURE_DOCS = 'pre_departure_docs';
    case SCREENING = 'screening';
    case SCREENED = 'screened';
    case REGISTERED = 'registered';
    case TRAINING = 'training';
    case TRAINING_COMPLETED = 'training_completed';
    case VISA_PROCESS = 'visa_process';
    case VISA_APPROVED = 'visa_approved';
    case DEPARTURE_PROCESSING = 'departure_processing';
    case READY_TO_DEPART = 'ready_to_depart';
    case DEPARTED = 'departed';
    case POST_DEPARTURE = 'post_departure';
    case COMPLETED = 'completed';

    // Terminal states
    case DEFERRED = 'deferred';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match($this) {
            self::LISTED => 'Listed',
            self::PRE_DEPARTURE_DOCS => 'Pre-Departure Documents',
            self::SCREENING => 'In Screening',
            self::SCREENED => 'Screened',
            self::REGISTERED => 'Registered',
            self::TRAINING => 'In Training',
            self::TRAINING_COMPLETED => 'Training Completed',
            self::VISA_PROCESS => 'Visa Processing',
            self::VISA_APPROVED => 'Visa Approved',
            self::DEPARTURE_PROCESSING => 'Departure Processing',
            self::READY_TO_DEPART => 'Ready to Depart',
            self::DEPARTED => 'Departed',
            self::POST_DEPARTURE => 'Post Departure',
            self::COMPLETED => 'Completed',
            self::DEFERRED => 'Deferred',
            self::REJECTED => 'Rejected',
            self::WITHDRAWN => 'Withdrawn',
        };
    }

    /**
     * Get Bootstrap color class for the status
     */
    public function color(): string
    {
        return match($this) {
            self::LISTED => 'secondary',
            self::PRE_DEPARTURE_DOCS => 'info',
            self::SCREENING => 'warning',
            self::SCREENED => 'primary',
            self::REGISTERED => 'info',
            self::TRAINING => 'warning',
            self::TRAINING_COMPLETED => 'success',
            self::VISA_PROCESS => 'warning',
            self::VISA_APPROVED => 'success',
            self::DEPARTURE_PROCESSING => 'warning',
            self::READY_TO_DEPART => 'primary',
            self::DEPARTED => 'success',
            self::POST_DEPARTURE => 'info',
            self::COMPLETED => 'success',
            self::DEFERRED => 'secondary',
            self::REJECTED => 'danger',
            self::WITHDRAWN => 'dark',
        };
    }

    /**
     * Get the order in the workflow (for sorting)
     */
    public function order(): int
    {
        return match($this) {
            self::LISTED => 1,
            self::PRE_DEPARTURE_DOCS => 2,
            self::SCREENING => 3,
            self::SCREENED => 4,
            self::REGISTERED => 5,
            self::TRAINING => 6,
            self::TRAINING_COMPLETED => 7,
            self::VISA_PROCESS => 8,
            self::VISA_APPROVED => 9,
            self::DEPARTURE_PROCESSING => 10,
            self::READY_TO_DEPART => 11,
            self::DEPARTED => 12,
            self::POST_DEPARTURE => 13,
            self::COMPLETED => 14,
            self::DEFERRED => 98,
            self::REJECTED => 99,
            self::WITHDRAWN => 97,
        };
    }

    /**
     * Check if this is a terminal status (no further progression)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::REJECTED,
            self::WITHDRAWN,
        ]);
    }

    /**
     * Check if editing is allowed
     */
    public function allowsEdit(): bool
    {
        return !$this->isTerminal();
    }

    /**
     * Get valid next statuses based on current status
     */
    public function validNextStatuses(): array
    {
        $transitions = [
            self::LISTED->value => [self::PRE_DEPARTURE_DOCS, self::DEFERRED, self::WITHDRAWN],
            self::PRE_DEPARTURE_DOCS->value => [self::SCREENING, self::DEFERRED, self::WITHDRAWN],
            self::SCREENING->value => [self::SCREENED, self::DEFERRED, self::WITHDRAWN],
            self::SCREENED->value => [self::REGISTERED, self::DEFERRED, self::WITHDRAWN],
            self::REGISTERED->value => [self::TRAINING, self::DEFERRED, self::WITHDRAWN],
            self::TRAINING->value => [self::TRAINING_COMPLETED, self::DEFERRED, self::WITHDRAWN],
            self::TRAINING_COMPLETED->value => [self::VISA_PROCESS, self::DEFERRED, self::WITHDRAWN],
            self::VISA_PROCESS->value => [self::VISA_APPROVED, self::REJECTED, self::WITHDRAWN],
            self::VISA_APPROVED->value => [self::DEPARTURE_PROCESSING, self::WITHDRAWN],
            self::DEPARTURE_PROCESSING->value => [self::READY_TO_DEPART, self::WITHDRAWN],
            self::READY_TO_DEPART->value => [self::DEPARTED, self::WITHDRAWN],
            self::DEPARTED->value => [self::POST_DEPARTURE],
            self::POST_DEPARTURE->value => [self::COMPLETED],
        ];

        return $transitions[$this->value] ?? [];
    }

    /**
     * Check if transition to given status is valid
     */
    public function canTransitionTo(CandidateStatus $status): bool
    {
        return in_array($status, $this->validNextStatuses());
    }

    /**
     * Get all active (non-terminal) statuses
     */
    public static function activeStatuses(): array
    {
        return [
            self::LISTED,
            self::PRE_DEPARTURE_DOCS,
            self::SCREENING,
            self::SCREENED,
            self::REGISTERED,
            self::TRAINING,
            self::TRAINING_COMPLETED,
            self::VISA_PROCESS,
            self::VISA_APPROVED,
            self::DEPARTURE_PROCESSING,
            self::READY_TO_DEPART,
            self::DEPARTED,
            self::POST_DEPARTURE,
        ];
    }

    /**
     * Get all statuses as array for dropdowns
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
