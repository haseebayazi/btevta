<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Services\CandidateJourneyService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class CandidateJourneyController extends Controller
{
    public function __construct(protected CandidateJourneyService $journeyService)
    {
    }

    /**
     * Show visual journey timeline for candidate
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $journey = $this->journeyService->getCompleteJourney($candidate);
        $milestones = $this->journeyService->getMilestones($candidate);
        $currentStage = $this->journeyService->getCurrentStage($candidate);
        $nextActions = $this->journeyService->getNextRequiredActions($candidate);
        $blockers = $this->journeyService->getBlockers($candidate);
        $estimatedCompletion = $this->journeyService->estimateCompletionDate($candidate);
        $progressPercentage = $this->journeyService->getProgressPercentage($candidate);

        return view('candidates.journey', compact(
            'candidate',
            'journey',
            'milestones',
            'currentStage',
            'nextActions',
            'blockers',
            'estimatedCompletion',
            'progressPercentage'
        ));
    }

    /**
     * Get journey data as JSON for AJAX/API
     */
    public function journeyData(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        return response()->json([
            'journey' => $this->journeyService->getCompleteJourney($candidate),
            'milestones' => $this->journeyService->getMilestones($candidate),
            'current_stage' => $this->journeyService->getCurrentStage($candidate),
            'progress_percentage' => $this->journeyService->getProgressPercentage($candidate),
            'estimated_completion' => $this->journeyService->estimateCompletionDate($candidate),
            'next_actions' => $this->journeyService->getNextRequiredActions($candidate),
            'blockers' => $this->journeyService->getBlockers($candidate),
        ]);
    }

    /**
     * Export journey to PDF
     */
    public function exportPdf(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $journey = $this->journeyService->getCompleteJourney($candidate);
        $milestones = $this->journeyService->getMilestones($candidate);
        $currentStage = $this->journeyService->getCurrentStage($candidate);
        $progressPercentage = $this->journeyService->getProgressPercentage($candidate);

        $pdf = Pdf::loadView('candidates.journey-pdf', compact(
            'candidate',
            'journey',
            'milestones',
            'currentStage',
            'progressPercentage'
        ));

        $filename = "candidate-journey-{$candidate->btevta_id}.pdf";

        return $pdf->download($filename);
    }
}
