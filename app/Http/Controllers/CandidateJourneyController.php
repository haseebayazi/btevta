<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Services\CandidateJourneyService;
use Illuminate\Http\Request;

class CandidateJourneyController extends Controller
{
    protected CandidateJourneyService $service;

    public function __construct(CandidateJourneyService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the candidate's journey/timeline
     *
     * @param Candidate $candidate
     * @return \Illuminate\View\View
     */
    public function show(Candidate $candidate)
    {
        $this->authorize('view', $candidate);

        $journeyData = $this->service->getJourneyData($candidate);
        $activities = $this->service->getActivities($candidate);

        return view('candidates.journey', [
            'candidate' => $journeyData['candidate'],
            'milestones' => $journeyData['milestones'],
            'currentStage' => $journeyData['currentStage'],
            'completionPercentage' => $journeyData['completionPercentage'],
            'activities' => $activities,
        ]);
    }
}
