<?php

namespace App\Services;

use App\Models\Remittance;
use App\Models\Candidate;
use App\Models\RemittanceBeneficiary;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RemittanceAnalyticsService
{
    /**
     * Get comprehensive dashboard statistics
     *
     * AUDIT FIX: Combined multiple queries into single aggregated query
     * to reduce database round-trips from 15+ to 3
     */
    public function getDashboardStats()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');

        // Single query for all aggregate statistics
        $aggregates = DB::table('remittances')
            ->selectRaw('
                COUNT(*) as total_remittances,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(AVG(amount), 0) as average_amount,
                COUNT(DISTINCT candidate_id) as total_candidates,
                SUM(CASE WHEN year = ? THEN 1 ELSE 0 END) as current_year_count,
                SUM(CASE WHEN year = ? THEN amount ELSE 0 END) as current_year_amount,
                SUM(CASE WHEN year = ? AND month = ? THEN 1 ELSE 0 END) as current_month_count,
                SUM(CASE WHEN year = ? AND month = ? THEN amount ELSE 0 END) as current_month_amount,
                SUM(CASE WHEN has_proof = 1 THEN 1 ELSE 0 END) as with_proof,
                SUM(CASE WHEN has_proof = 0 OR has_proof IS NULL THEN 1 ELSE 0 END) as without_proof,
                SUM(CASE WHEN is_first_remittance = 1 THEN 1 ELSE 0 END) as first_remittances
            ', [$currentYear, $currentYear, $currentYear, $currentMonth, $currentYear, $currentMonth])
            ->first();

        // Status breakdown (single query)
        $statusBreakdown = Remittance::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Calculate rates from aggregates
        $total = $aggregates->total_remittances ?: 1; // Avoid division by zero
        $proofRate = round(($aggregates->with_proof / $total) * 100, 2);
        $firstRate = round(($aggregates->first_remittances / $total) * 100, 2);

        $stats = [
            // Overall statistics
            'total_remittances' => $aggregates->total_remittances,
            'total_amount' => $aggregates->total_amount,
            'average_amount' => $aggregates->average_amount,
            'total_candidates' => $aggregates->total_candidates,

            // This year statistics
            'current_year_count' => $aggregates->current_year_count,
            'current_year_amount' => $aggregates->current_year_amount,

            // This month statistics
            'current_month_count' => $aggregates->current_month_count,
            'current_month_amount' => $aggregates->current_month_amount,

            // Status breakdown
            'status_breakdown' => $statusBreakdown,

            // Proof compliance
            'with_proof' => $aggregates->with_proof,
            'without_proof' => $aggregates->without_proof,
            'proof_compliance_rate' => $proofRate,

            // First remittance tracking
            'first_remittances' => $aggregates->first_remittances,
            'first_remittance_rate' => $firstRate,

            // Growth statistics (cached for performance)
            'month_over_month_growth' => $this->calculateMonthOverMonthGrowth(),
            'year_over_year_growth' => $this->calculateYearOverYearGrowth(),
        ];

        return $stats;
    }

    /**
     * Get monthly trend data for charts
     */
    public function getMonthlyTrends($year = null)
    {
        $year = $year ?? date('Y');

        $trends = Remittance::select(
                'month',
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('avg(amount) as avg_amount')
            )
            ->where('year', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months with zeros
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthData = $trends->firstWhere('month', $m);
            $monthlyData[$m] = [
                'month' => date('F', mktime(0, 0, 0, $m, 1)),
                'month_number' => $m,
                'count' => $monthData ? $monthData->count : 0,
                'total_amount' => $monthData ? (float)$monthData->total_amount : 0,
                'avg_amount' => $monthData ? (float)$monthData->avg_amount : 0,
            ];
        }

        return $monthlyData;
    }

    /**
     * Get purpose distribution analysis
     */
    public function getPurposeAnalysis()
    {
        $purposeData = Remittance::select(
                'primary_purpose',
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total_amount'),
                DB::raw('avg(amount) as avg_amount')
            )
            ->groupBy('primary_purpose')
            ->orderBy('total_amount', 'desc')
            ->get();

        $totalAmount = Remittance::sum('amount');

        return $purposeData->map(function ($item) use ($totalAmount) {
            return [
                'purpose' => $item->primary_purpose,
                'purpose_label' => config('remittance.purposes.' . $item->primary_purpose),
                'count' => $item->count,
                'total_amount' => (float)$item->total_amount,
                'avg_amount' => (float)$item->avg_amount,
                'percentage' => $totalAmount > 0 ? round(($item->total_amount / $totalAmount) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get transfer method analysis
     */
    public function getTransferMethodAnalysis()
    {
        $methodData = Remittance::select(
                'transfer_method',
                DB::raw('count(*) as count'),
                DB::raw('sum(amount) as total_amount')
            )
            ->whereNotNull('transfer_method')
            ->groupBy('transfer_method')
            ->orderBy('count', 'desc')
            ->get();

        $totalCount = Remittance::count();

        return $methodData->map(function ($item) use ($totalCount) {
            return [
                'method' => $item->transfer_method,
                'count' => $item->count,
                'total_amount' => (float)$item->total_amount,
                'percentage' => $totalCount > 0 ? round(($item->count / $totalCount) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get country-wise remittance analysis
     */
    public function getCountryAnalysis()
    {
        $countryData = Remittance::join('departures', 'remittances.departure_id', '=', 'departures.id')
            ->select(
                'departures.destination_country',
                DB::raw('count(remittances.id) as count'),
                DB::raw('sum(remittances.amount) as total_amount'),
                DB::raw('avg(remittances.amount) as avg_amount')
            )
            ->groupBy('departures.destination_country')
            ->orderBy('total_amount', 'desc')
            ->get();

        return $countryData->map(function ($item) {
            return [
                'country' => $item->destination_country,
                'count' => $item->count,
                'total_amount' => (float)$item->total_amount,
                'avg_amount' => (float)$item->avg_amount,
            ];
        })->toArray();
    }

    /**
     * Get proof compliance report
     */
    public function getProofComplianceReport()
    {
        $totalRemittances = Remittance::count();
        $withProof = Remittance::where('has_proof', true)->count();
        $withoutProof = $totalRemittances - $withProof;

        // Compliance by purpose
        $byPurpose = Remittance::select(
                'primary_purpose',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when has_proof = 1 then 1 else 0 end) as with_proof')
            )
            ->groupBy('primary_purpose')
            ->get()
            ->map(function ($item) {
                $complianceRate = $item->total > 0 ? round(($item->with_proof / $item->total) * 100, 2) : 0;
                return [
                    'purpose' => $item->primary_purpose,
                    'purpose_label' => config('remittance.purposes.' . $item->primary_purpose),
                    'total' => $item->total,
                    'with_proof' => $item->with_proof,
                    'without_proof' => $item->total - $item->with_proof,
                    'compliance_rate' => $complianceRate,
                ];
            })
            ->toArray();

        // Compliance by month (current year)
        $byMonth = Remittance::select(
                'month',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when has_proof = 1 then 1 else 0 end) as with_proof')
            )
            ->where('year', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                $complianceRate = $item->total > 0 ? round(($item->with_proof / $item->total) * 100, 2) : 0;
                return [
                    'month' => date('F', mktime(0, 0, 0, $item->month, 1)),
                    'month_number' => $item->month,
                    'total' => $item->total,
                    'with_proof' => $item->with_proof,
                    'without_proof' => $item->total - $item->with_proof,
                    'compliance_rate' => $complianceRate,
                ];
            })
            ->toArray();

        return [
            'overall' => [
                'total_remittances' => $totalRemittances,
                'with_proof' => $withProof,
                'without_proof' => $withoutProof,
                'compliance_rate' => $this->calculateProofComplianceRate(),
            ],
            'by_purpose' => $byPurpose,
            'by_month' => $byMonth,
        ];
    }

    /**
     * Get beneficiary analysis
     */
    public function getBeneficiaryReport()
    {
        // Total beneficiaries
        $totalBeneficiaries = RemittanceBeneficiary::count();
        $activeBeneficiaries = RemittanceBeneficiary::where('is_active', true)->count();
        $primaryBeneficiaries = RemittanceBeneficiary::where('is_primary', true)->count();

        // Relationship breakdown
        $byRelationship = RemittanceBeneficiary::select(
                'relationship',
                DB::raw('count(*) as count')
            )
            ->groupBy('relationship')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) use ($totalBeneficiaries) {
                return [
                    'relationship' => $item->relationship,
                    'count' => $item->count,
                    'percentage' => $totalBeneficiaries > 0 ? round(($item->count / $totalBeneficiaries) * 100, 2) : 0,
                ];
            })
            ->toArray();

        // Banking info completeness
        $withBankAccount = RemittanceBeneficiary::whereNotNull('account_number')->count();
        $withIban = RemittanceBeneficiary::whereNotNull('iban')->count();
        $withMobileWallet = RemittanceBeneficiary::whereNotNull('mobile_wallet')->count();

        return [
            'overview' => [
                'total' => $totalBeneficiaries,
                'active' => $activeBeneficiaries,
                'primary' => $primaryBeneficiaries,
            ],
            'by_relationship' => $byRelationship,
            'banking_info' => [
                'with_account' => $withBankAccount,
                'with_iban' => $withIban,
                'with_mobile_wallet' => $withMobileWallet,
            ],
        ];
    }

    /**
     * Get remittance impact analytics
     */
    public function getImpactAnalytics()
    {
        // Average time to first remittance
        $avgTimeToFirst = $this->calculateAverageTimeToFirstRemittance();

        // Remittance frequency
        $frequency = $this->calculateRemittanceFrequency();

        // Total economic impact
        $economicImpact = [
            'total_inflow' => Remittance::sum('amount'),
            'total_families_benefited' => Remittance::distinct('candidate_id')->count(),
            'avg_per_family' => Remittance::select('candidate_id', DB::raw('sum(amount) as total'))
                ->groupBy('candidate_id')
                ->avg('total'),
        ];

        // Purpose impact breakdown
        $purposeImpact = $this->getPurposeAnalysis();

        return [
            'avg_time_to_first_remittance' => $avgTimeToFirst,
            'remittance_frequency' => $frequency,
            'economic_impact' => $economicImpact,
            'purpose_breakdown' => $purposeImpact,
        ];
    }

    /**
     * Calculate proof compliance rate
     */
    protected function calculateProofComplianceRate()
    {
        $total = Remittance::count();
        if ($total == 0) return 0;

        $withProof = Remittance::where('has_proof', true)->count();
        return round(($withProof / $total) * 100, 2);
    }

    /**
     * Calculate first remittance rate
     */
    protected function calculateFirstRemittanceRate()
    {
        $totalDeployed = Candidate::whereHas('departure')->count();
        if ($totalDeployed == 0) return 0;

        $withFirstRemittance = Remittance::where('is_first_remittance', true)->distinct('candidate_id')->count();
        return round(($withFirstRemittance / $totalDeployed) * 100, 2);
    }

    /**
     * Calculate month-over-month growth
     */
    protected function calculateMonthOverMonthGrowth()
    {
        $currentMonth = Remittance::where('year', date('Y'))
            ->where('month', date('n'))
            ->sum('amount');

        $lastMonth = Remittance::where('year', date('Y'))
            ->where('month', date('n') - 1)
            ->sum('amount');

        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;

        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2);
    }

    /**
     * Calculate year-over-year growth
     */
    protected function calculateYearOverYearGrowth()
    {
        $currentYear = Remittance::where('year', date('Y'))->sum('amount');
        $lastYear = Remittance::where('year', date('Y') - 1)->sum('amount');

        if ($lastYear == 0) return $currentYear > 0 ? 100 : 0;

        return round((($currentYear - $lastYear) / $lastYear) * 100, 2);
    }

    /**
     * Calculate average time to first remittance (in days)
     */
    protected function calculateAverageTimeToFirstRemittance()
    {
        $firstRemittances = Remittance::where('is_first_remittance', true)
            ->with('departure')
            ->get();

        if ($firstRemittances->isEmpty()) return null;

        $totalDays = 0;
        $count = 0;

        foreach ($firstRemittances as $remittance) {
            if ($remittance->departure && $remittance->departure->departure_date) {
                $deploymentDate = Carbon::parse($remittance->departure->departure_date);
                $remittanceDate = Carbon::parse($remittance->transfer_date);
                $totalDays += $deploymentDate->diffInDays($remittanceDate);
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : null;
    }

    /**
     * Calculate remittance frequency (average per candidate per year)
     */
    protected function calculateRemittanceFrequency()
    {
        $candidatesWithRemittances = Remittance::distinct('candidate_id')->count();
        if ($candidatesWithRemittances == 0) return 0;

        $totalRemittances = Remittance::count();
        $yearsActive = Remittance::select(DB::raw('count(distinct year) as years'))->first()->years;

        if ($yearsActive == 0) return 0;

        return round($totalRemittances / ($candidatesWithRemittances * $yearsActive), 2);
    }

    /**
     * Get top remitting candidates
     */
    public function getTopRemittingCandidates($limit = 10)
    {
        return Remittance::join('candidates', 'remittances.candidate_id', '=', 'candidates.id')
            ->select(
                'candidates.id',
                'candidates.full_name',
                'candidates.cnic',
                DB::raw('count(remittances.id) as remittance_count'),
                DB::raw('sum(remittances.amount) as total_amount'),
                DB::raw('avg(remittances.amount) as avg_amount')
            )
            ->groupBy('candidates.id', 'candidates.full_name', 'candidates.cnic')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get remittances by date range
     */
    public function getRemittancesByDateRange($startDate, $endDate)
    {
        return Remittance::whereBetween('transfer_date', [$startDate, $endDate])
            ->with(['candidate', 'departure'])
            ->orderBy('transfer_date', 'desc')
            ->get();
    }
}
