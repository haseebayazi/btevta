<?php

namespace App\Listeners;

use App\Enums\CandidateStatus;
use App\Events\CandidateDeparted;
use Illuminate\Support\Facades\Log;

class HandleCandidateDeparted
{
    /**
     * Handle the CandidateDeparted event.
     *
     * When a candidate departs (Module 6), this listener transitions them
     * to POST_DEPARTURE status to begin post-departure tracking (Module 7).
     */
    public function handle(CandidateDeparted $event): void
    {
        $candidate = $event->candidate;
        $departure = $event->departure;

        if (!$candidate) {
            return;
        }

        // Transition candidate to POST_DEPARTURE if currently DEPARTED
        if ($candidate->status === CandidateStatus::DEPARTED->value) {
            $candidate->update([
                'status' => CandidateStatus::POST_DEPARTURE->value,
            ]);
        }

        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'departure_id' => $departure->id,
                'departed_at' => $departure->departed_at,
                'transition' => 'departed → post_departure',
            ])
            ->log('Candidate departed - transitioned to post-departure tracking');

        Log::info("Candidate {$candidate->btevta_id} departed - post-departure tracking initiated");
    }
}
