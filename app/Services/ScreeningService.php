<?php

namespace App\Services;

use App\Models\CandidateScreening;
use App\Models\Candidate;
use App\Models\Undertaking;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScreeningService
{
    /**
     * Generate undertaking content
     */
    public function generateUndertakingContent($candidate): string
    {
        return "
            UNDERTAKING

            I, {$candidate->name}, S/O / D/O {$candidate->father_name}, 
            CNIC: {$candidate->formatted_cnic}, resident of {$candidate->address}, 
            District {$candidate->district}, hereby undertake that:

            1. All information provided by me during the screening and registration process is true and correct.
            2. I have not concealed any information that may affect my candidature.
            3. I will abide by all rules and regulations of the training program.
            4. I understand that any false information may lead to cancellation of my candidature.
            5. I will maintain discipline during the training period.
            6. I will not engage in any political or illegal activities.
            7. I will respect the cultural norms of the host country upon deployment.
            8. I understand that the training and deployment are subject to successful completion of all requirements.
            9. I will return to Pakistan upon completion of my contract period.
            10. I will maintain regular contact with my family and the relevant authorities.

            Date: " . now()->format('d-m-Y') . "
            
            Candidate Signature: _________________
            
            Witness 1: _________________
            Name:
            CNIC:
            
            Witness 2: _________________
            Name:
            CNIC:
        ";
    }

    /**
     * Get call logs for a screening
     */
    public function getCallLogs($screening): array
    {
        // This would typically fetch from a call_logs table
        // For now, we'll parse from remarks
        $logs = [];
        
        if ($screening->remarks) {
            $lines = explode("\n", $screening->remarks);
            foreach ($lines as $line) {
                if (strpos($line, 'Call') !== false) {
                    $logs[] = [
                        'timestamp' => Carbon::parse(substr($line, 0, 19)),
                        'details' => $line
                    ];
                }
            }
        }
        
        return $logs;
    }

    /**
     * Generate screening report
     */
    public function generateReport($filters = []): array
    {
        $query = CandidateScreening::query();

        // Apply filters
        if (!empty($filters['from_date'])) {
            $query->whereDate('screened_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('screened_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['screening_type'])) {
            $query->where('screening_type', $filters['screening_type']);
        }

        $total = $query->count();
        $passed = clone $query;
        $failed = clone $query;

        return [
            'total_screenings' => $total,
            'passed' => $passed->where('status', 'passed')->count(),
            'failed' => $failed->where('status', 'failed')->count(),
            // BUG FIX: Correct clone syntax
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'by_type' => $this->getScreeningsByType($filters),
            'by_screener' => $this->getScreeningsByScreener($filters),
            'average_call_attempts' => $this->calculateAverageCallAttempts($filters),
            'daily_stats' => $this->getDailyStats($filters),
        ];
    }

    /**
     * Get screenings grouped by type
     */
    protected function getScreeningsByType($filters = []): \Illuminate\Support\Collection
    {
        return CandidateScreening::select('screening_type', DB::raw('count(*) as count'))
            ->when(!empty($filters['from_date']), function($q) use ($filters) {
                $q->whereDate('screened_at', '>=', $filters['from_date']);
            })
            ->groupBy('screening_type')
            ->pluck('count', 'screening_type');
    }

    /**
     * Get screenings grouped by screener
     */
    protected function getScreeningsByScreener($filters = []): \Illuminate\Support\Collection
    {
        return CandidateScreening::select('screened_by', DB::raw('count(*) as count'))
            ->with('screener:id,name')
            ->when(!empty($filters['from_date']), function($q) use ($filters) {
                $q->whereDate('screened_at', '>=', $filters['from_date']);
            })
            ->whereNotNull('screened_by')
            ->groupBy('screened_by')
            ->get()
            ->map(function($item) {
                return [
                    'screener' => $item->screener->name ?? 'Unknown',
                    'count' => $item->count
                ];
            });
    }

    /**
     * Calculate average call attempts
     */
    protected function calculateAverageCallAttempts($filters = []): float
    {
        $avg = CandidateScreening::where('screening_type', 'call')
            ->when(!empty($filters['from_date']), function($q) use ($filters) {
                $q->whereDate('screened_at', '>=', $filters['from_date']);
            })
            ->avg('call_count');
        
        return round($avg ?: 0, 2);
    }

    /**
     * Get daily statistics
     */
    protected function getDailyStats($filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $fromDate = $filters['from_date'] ?? Carbon::now()->subDays(30);
        $toDate = $filters['to_date'] ?? Carbon::now();

        return CandidateScreening::select(
                DB::raw('DATE(screened_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('SUM(CASE WHEN status = "passed" THEN 1 ELSE 0 END) as passed'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->whereBetween('screened_at', [$fromDate, $toDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Auto-schedule next screening
     */
    public function scheduleNextScreening($candidate, $completedType): void
    {
        $sequence = [
            'desk' => 'call',
            'call' => 'physical',
            'physical' => null
        ];

        $nextType = $sequence[$completedType] ?? null;

        if ($nextType) {
            CandidateScreening::create([
                'candidate_id' => $candidate->id,
                'screening_type' => $nextType,
                'status' => 'pending',
                'remarks' => 'Auto-scheduled after ' . $completedType . ' screening'
            ]);
        }
    }

    /**
     * Check screening eligibility
     */
    public function checkEligibility($candidateId, $screeningType): array
    {
        $candidate = Candidate::find($candidateId);

        if (!$candidate) {
            return ['eligible' => false, 'reason' => 'Candidate not found'];
        }

        // Check prerequisites
        $prerequisites = [
            'desk' => [],
            'call' => ['desk'],
            'physical' => ['desk', 'call'],
            'medical' => ['desk', 'call', 'physical'],
        ];

        $required = $prerequisites[$screeningType] ?? [];

        foreach ($required as $type) {
            $passed = CandidateScreening::where('candidate_id', $candidateId)
                ->where('screening_type', $type)
                ->where('status', 'passed')
                ->exists();

            if (!$passed) {
                return [
                    'eligible' => false,
                    'reason' => "Prerequisite {$type} screening not completed"
                ];
            }
        }

        return ['eligible' => true];
    }
}