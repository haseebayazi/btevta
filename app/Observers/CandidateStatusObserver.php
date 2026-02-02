<?php

namespace App\Observers;

use App\Models\Candidate;
use App\Enums\CandidateStatus;
use Illuminate\Support\Facades\Auth;

/**
 * Observer for Candidate model to track status transitions.
 * Logs all status changes with detailed audit trail.
 */
class CandidateStatusObserver
{
    /**
     * Handle the Candidate "updating" event.
     * Validates status transitions and logs changes.
     */
    public function updating(Candidate $candidate): void
    {
        // Check if status is being changed
        if ($candidate->isDirty('status')) {
            $oldStatus = $candidate->getOriginal('status');
            $newStatus = $candidate->status;

            // Log the transition
            $this->logStatusTransition($candidate, $oldStatus, $newStatus);
        }
    }

    /**
     * Handle the Candidate "updated" event.
     */
    public function updated(Candidate $candidate): void
    {
        // After update logic if needed
    }

    /**
     * Log status transition to activity log.
     */
    protected function logStatusTransition(Candidate $candidate, ?string $oldStatus, string $newStatus): void
    {
        $oldStatusLabel = $oldStatus ? (CandidateStatus::tryFrom($oldStatus)?->label() ?? $oldStatus) : 'None';
        $newStatusLabel = CandidateStatus::tryFrom($newStatus)?->label() ?? $newStatus;

        $description = "Candidate status changed from '{$oldStatusLabel}' to '{$newStatusLabel}'";

        activity()
            ->causedBy(Auth::user())
            ->performedOn($candidate)
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'old_status_label' => $oldStatusLabel,
                'new_status_label' => $newStatusLabel,
                'transition_type' => $this->getTransitionType($oldStatus, $newStatus),
                'module' => $this->getModuleFromStatus($newStatus),
            ])
            ->log($description);
    }

    /**
     * Determine the type of transition.
     */
    protected function getTransitionType(?string $oldStatus, string $newStatus): string
    {
        $progressStatuses = [
            'listed', 'pre_departure_docs', 'screening', 'screened',
            'registered', 'training', 'training_completed',
            'visa_process', 'visa_approved',
            'departure_processing', 'ready_to_depart', 'departed',
            'post_departure', 'completed'
        ];

        $terminalStatuses = ['rejected', 'withdrawn', 'deferred'];

        if (in_array($newStatus, $terminalStatuses)) {
            return 'terminal';
        }

        if ($oldStatus === null) {
            return 'initial';
        }

        $oldIndex = array_search($oldStatus, $progressStatuses);
        $newIndex = array_search($newStatus, $progressStatuses);

        if ($oldIndex !== false && $newIndex !== false) {
            if ($newIndex > $oldIndex) {
                return 'forward';
            } elseif ($newIndex < $oldIndex) {
                return 'backward';
            }
        }

        return 'lateral';
    }

    /**
     * Get the module number associated with a status.
     */
    protected function getModuleFromStatus(string $status): ?int
    {
        return match ($status) {
            'listed', 'pre_departure_docs' => 1,
            'screening', 'screened' => 2,
            'registered' => 3,
            'training', 'training_completed' => 4,
            'visa_process', 'visa_approved' => 5,
            'departure_processing', 'ready_to_depart', 'departed' => 6,
            'post_departure', 'completed' => 7,
            default => null,
        };
    }
}
