<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Services\CandidateJourneyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CandidateJourneyController extends Controller
{
    public function __construct(protected CandidateJourneyService $journeyService)
    {
    }

    /**
     * Show the visual journey timeline for a candidate.
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $candidate->load(['trade', 'campus', 'batch', 'oep', 'screenings', 'trainingCertificates', 'visaProcess', 'departure']);

        return view('candidates.journey', [
            'candidate'           => $candidate,
            'milestones'          => $this->journeyService->getMilestones($candidate),
            'currentStage'        => $this->journeyService->getCurrentStage($candidate)['name'] ?? 'Unknown',
            'completionPercentage' => $this->journeyService->getProgressPercentage($candidate),
            'activities'          => $this->journeyService->getActivities($candidate),
            'nextActions'         => $this->journeyService->getNextRequiredActions($candidate),
            'blockers'            => $this->journeyService->getBlockers($candidate),
            'estimatedCompletion' => $this->journeyService->estimateCompletionDate($candidate),
        ]);
    }

    /**
     * Return journey data as JSON (for AJAX refresh or API consumers).
     */
    public function journeyData(Candidate $candidate): JsonResponse
    {
        $this->authorize('view', $candidate);

        return response()->json([
            'journey'              => $this->journeyService->getCompleteJourney($candidate),
            'milestones'           => $this->journeyService->getMilestones($candidate),
            'current_stage'        => $this->journeyService->getCurrentStage($candidate),
            'progress_percentage'  => $this->journeyService->getProgressPercentage($candidate),
            'estimated_completion' => $this->journeyService->estimateCompletionDate($candidate),
            'next_actions'         => $this->journeyService->getNextRequiredActions($candidate),
            'blockers'             => $this->journeyService->getBlockers($candidate),
        ]);
    }

    /**
     * Export the candidate journey as a PDF report.
     */
    public function exportPdf(Candidate $candidate): Response
    {
        $this->authorize('view', $candidate);

        $candidate->load(['trade', 'campus', 'batch', 'oep', 'visaProcess', 'departure', 'postDepartureDetail']);

        $journey    = $this->journeyService->getCompleteJourney($candidate);
        $milestones = $this->journeyService->getMilestones($candidate);

        $pdf = Pdf::loadView('candidates.journey-pdf', compact('candidate', 'journey', 'milestones'))
            ->setPaper('a4', 'portrait');

        $filename = 'candidate-journey-' . ($candidate->btevta_id ?? $candidate->id) . '.pdf';

        return $pdf->download($filename);
    }
}
