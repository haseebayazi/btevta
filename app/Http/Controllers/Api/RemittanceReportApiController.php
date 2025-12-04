<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remittance;
use App\Services\RemittanceAnalyticsService;
use Illuminate\Http\Request;

class RemittanceReportApiController extends Controller
{
    protected $analyticsService;

    public function __construct(RemittanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard()
    {
        $this->authorize('viewReports', Remittance::class);

        $stats = $this->analyticsService->getDashboardStats();
        $monthlyTrends = $this->analyticsService->getMonthlyTrends();
        $purposeAnalysis = $this->analyticsService->getPurposeAnalysis();

        return response()->json([
            'statistics' => $stats,
            'monthly_trends' => $monthlyTrends,
            'purpose_analysis' => $purposeAnalysis,
        ]);
    }

    /**
     * Get monthly trends
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function monthlyTrends(Request $request)
    {
        $this->authorize('viewReports', Remittance::class);

        $year = $request->input('year', date('Y'));
        $trends = $this->analyticsService->getMonthlyTrends($year);

        return response()->json([
            'year' => $year,
            'trends' => $trends,
        ]);
    }

    /**
     * Get purpose analysis
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function purposeAnalysis()
    {
        $this->authorize('viewReports', Remittance::class);

        $analysis = $this->analyticsService->getPurposeAnalysis();

        return response()->json($analysis);
    }

    /**
     * Get transfer method analysis
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferMethods()
    {
        $this->authorize('viewReports', Remittance::class);

        $methods = $this->analyticsService->getTransferMethodAnalysis();

        return response()->json($methods);
    }

    /**
     * Get country analysis
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function countryAnalysis()
    {
        $this->authorize('viewReports', Remittance::class);

        $countries = $this->analyticsService->getCountryAnalysis();

        return response()->json($countries);
    }

    /**
     * Get proof compliance report
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function proofCompliance()
    {
        $this->authorize('viewReports', Remittance::class);

        $report = $this->analyticsService->getProofComplianceReport();

        return response()->json($report);
    }

    /**
     * Get beneficiary report
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function beneficiaryReport()
    {
        $this->authorize('viewReports', Remittance::class);

        $report = $this->analyticsService->getBeneficiaryReport();

        return response()->json($report);
    }

    /**
     * Get impact analytics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function impactAnalytics()
    {
        $this->authorize('viewReports', Remittance::class);

        $impact = $this->analyticsService->getImpactAnalytics();

        return response()->json($impact);
    }

    /**
     * Get top remitting candidates
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function topCandidates(Request $request)
    {
        $this->authorize('viewReports', Remittance::class);

        $limit = $request->input('limit', 10);
        $candidates = $this->analyticsService->getTopRemittingCandidates($limit);

        return response()->json($candidates);
    }
}
