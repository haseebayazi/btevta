<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $limit = $request->input('limit', 10);
        $candidates = $this->analyticsService->getTopRemittingCandidates($limit);

        return response()->json($candidates);
    }
}
