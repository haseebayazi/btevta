<?php

namespace App\Services;

use App\Enums\CandidateStatus;
use App\Events\CandidateStatusUpdated;
use App\Models\Candidate;
use App\Models\CandidateStatusLog;
use Illuminate\Support\Facades\Request;

class StatusTransitionService
{
    /**
     * Transition candidate status with reason validation and full audit trail.
     *
     * @throws \InvalidArgumentException on invalid transition
     * @throws \RuntimeException on prerequisite failure
     */
    public function transition(
        Candidate $candidate,
        CandidateStatus $toStatus,
        string $reason,
        ?string $notes = null,
        ?array $context = null
    ): bool {
        $fromStatus = CandidateStatus::tryFrom($candidate->status);

        if ($fromStatus && !$fromStatus->canTransitionTo($toStatus)) {
            throw new \InvalidArgumentException(
                "Invalid transition from {$fromStatus->value} to {$toStatus->value}. " .
                'Valid next statuses: ' . implode(', ', array_map(fn($s) => $s->value, $fromStatus->validNextStatuses() ?: []))
            );
        }

        $this->validatePrerequisites($candidate, $toStatus);

        $previousStatus = $candidate->status;

        $candidate->status = $toStatus->value;
        $candidate->save();

        // Write dedicated audit record (in addition to Spatie activity log from observer)
        CandidateStatusLog::create([
            'candidate_id' => $candidate->id,
            'from_status'  => $previousStatus,
            'to_status'    => $toStatus->value,
            'reason'       => $reason,
            'notes'        => $notes,
            'context'      => $context,
            'changed_by'   => auth()->id() ?? 1,
            'changed_at'   => now(),
            'ip_address'   => Request::ip(),
            'user_agent'   => substr(Request::userAgent() ?? '', 0, 500),
        ]);

        event(new CandidateStatusUpdated($candidate, $previousStatus, $toStatus->value));

        return true;
    }

    /**
     * Validate business prerequisites before allowing a status transition.
     *
     * @throws \RuntimeException
     */
    protected function validatePrerequisites(Candidate $candidate, CandidateStatus $toStatus): void
    {
        switch ($toStatus) {
            case CandidateStatus::SCREENING:
                $total    = $candidate->preDepartureDocuments()->count();
                $verified = $candidate->preDepartureDocuments()->whereNotNull('verified_at')->count();
                if ($total > 0 && $verified < $total) {
                    throw new \RuntimeException(
                        "All pre-departure documents must be verified before screening. ({$verified}/{$total} verified)"
                    );
                }
                break;

            case CandidateStatus::REGISTERED:
                if ($candidate->status !== CandidateStatus::SCREENED->value) {
                    throw new \RuntimeException('Candidate must be screened before registration.');
                }
                break;

            case CandidateStatus::TRAINING:
                if (!$candidate->batch_id) {
                    throw new \RuntimeException('Candidate must be assigned to a batch before training.');
                }
                break;

            case CandidateStatus::TRAINING_COMPLETED:
                if (!$candidate->training?->isBothComplete()) {
                    throw new \RuntimeException('Both technical and soft skills training must be completed.');
                }
                break;

            case CandidateStatus::READY_TO_DEPART:
                if (method_exists($candidate->departure ?? new \stdClass, 'canMarkReadyToDepart')
                    && !$candidate->departure->canMarkReadyToDepart()) {
                    throw new \RuntimeException('All departure checklist items must be complete.');
                }
                break;

            case CandidateStatus::COMPLETED:
                if (!$candidate->postDepartureDetail?->compliance_verified) {
                    throw new \RuntimeException('90-day compliance must be verified before marking completed.');
                }
                break;
        }
    }

    /**
     * Get full status change history for a candidate.
     */
    public function getHistory(Candidate $candidate): \Illuminate\Database\Eloquent\Collection
    {
        return $candidate->statusLogs()->with('changedBy')->get();
    }
}
