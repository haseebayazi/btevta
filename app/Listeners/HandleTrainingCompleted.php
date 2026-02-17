<?php

namespace App\Listeners;

use App\Enums\CandidateStatus;
use App\Enums\VisaStage;
use App\Events\TrainingCompleted;
use App\Models\VisaProcess;
use Illuminate\Support\Facades\Log;

class HandleTrainingCompleted
{
    /**
     * Handle the TrainingCompleted event.
     *
     * When both technical and soft-skills training are completed (Module 4 dual-status),
     * this listener ensures the candidate transitions to visa processing and a
     * VisaProcess record is created if one doesn't already exist.
     */
    public function handle(TrainingCompleted $event): void
    {
        $candidate = $event->candidate;

        if (!$candidate) {
            return;
        }

        // Only auto-transition if candidate is still in TRAINING status
        // (the legacy completeTraining() path handles its own transition)
        if ($candidate->status !== CandidateStatus::TRAINING->value) {
            return;
        }

        // Verify training_status is 'completed'
        if ($candidate->training_status !== 'completed') {
            return;
        }

        // Transition candidate to VISA_PROCESS
        $candidate->update([
            'status' => CandidateStatus::VISA_PROCESS->value,
            'training_end_date' => now(),
            'at_risk_reason' => null,
            'at_risk_since' => null,
        ]);

        // Auto-create VisaProcess record if not already existing
        VisaProcess::firstOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'overall_status' => VisaStage::INITIATED->value,
                'interview_status' => 'pending',
                'trade_test_status' => 'pending',
                'takamol_status' => 'pending',
                'medical_status' => 'pending',
                'biometric_status' => 'pending',
                'visa_status' => 'pending',
                'created_by' => auth()->id(),
            ]
        );

        activity()
            ->performedOn($candidate)
            ->causedBy(auth()->user())
            ->withProperties([
                'training_id' => $event->training->id,
                'transition' => 'training_completed → visa_process',
            ])
            ->log('Training completed - auto-transitioned to visa processing');

        Log::info("Candidate {$candidate->btevta_id} auto-transitioned to visa processing after training completion");
    }
}
