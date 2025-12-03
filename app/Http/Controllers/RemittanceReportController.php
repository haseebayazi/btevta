<?php

namespace App\Http\Controllers;

use App\Services\RemittanceAnalyticsService;
use App\Models\Remittance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class RemittanceReportController extends Controller
{
    protected $analyticsService;

    public function __construct(RemittanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the analytics dashboard
     */
    public function dashboard()
    {
        $this->authorize('viewDashboard', \App\Policies\RemittanceReportPolicy::class);

        $stats = $this->analyticsService->getDashboardStats();
        $monthlyTrends = $this->analyticsService->getMonthlyTrends();
        $purposeAnalysis = $this->analyticsService->getPurposeAnalysis();
        $transferMethods = $this->analyticsService->getTransferMethodAnalysis();
        $countryAnalysis = $this->analyticsService->getCountryAnalysis();
        $topCandidates = $this->analyticsService->getTopRemittingCandidates(5);

        return view('remittances.reports.dashboard', compact(
            'stats',
            'monthlyTrends',
            'purposeAnalysis',
            'transferMethods',
            'countryAnalysis',
            'topCandidates'
        ));
    }

    /**
     * Display monthly remittance report
     */
    public function monthlyReport(Request $request)
    {
        $this->authorize('viewMonthly', \App\Policies\RemittanceReportPolicy::class);

        $year = $request->get('year', date('Y'));
        $monthlyTrends = $this->analyticsService->getMonthlyTrends($year);

        // Get available years
        $years = Remittance::selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('remittances.reports.monthly', compact('monthlyTrends', 'year', 'years'));
    }

    /**
     * Display purpose analysis report
     */
    public function purposeAnalysis()
    {
        $this->authorize('viewPurposeAnalysis', \App\Policies\RemittanceReportPolicy::class);

        $purposeAnalysis = $this->analyticsService->getPurposeAnalysis();

        return view('remittances.reports.purpose-analysis', compact('purposeAnalysis'));
    }

    /**
     * Display beneficiary report
     */
    public function beneficiaryReport()
    {
        $this->authorize('viewBeneficiary', \App\Policies\RemittanceReportPolicy::class);

        $beneficiaryReport = $this->analyticsService->getBeneficiaryReport();

        return view('remittances.reports.beneficiary', compact('beneficiaryReport'));
    }

    /**
     * Display proof compliance report
     */
    public function proofComplianceReport()
    {
        $this->authorize('viewCompliance', \App\Policies\RemittanceReportPolicy::class);

        $complianceReport = $this->analyticsService->getProofComplianceReport();

        return view('remittances.reports.proof-compliance', compact('complianceReport'));
    }

    /**
     * Display impact analytics
     */
    public function impactAnalytics()
    {
        $this->authorize('viewImpact', \App\Policies\RemittanceReportPolicy::class);

        $impactData = $this->analyticsService->getImpactAnalytics();

        return view('remittances.reports.impact', compact('impactData'));
    }

    /**
     * Export reports to Excel or PDF
     */
    public function export(Request $request, $type)
    {
        $this->authorize('export', \App\Policies\RemittanceReportPolicy::class);

        $format = $request->get('format', 'excel'); // excel or pdf
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to', date('Y-m-d'));

        switch ($type) {
            case 'dashboard':
                return $this->exportDashboard($format);
            case 'monthly':
                return $this->exportMonthly($format, $request->get('year', date('Y')));
            case 'purpose':
                return $this->exportPurposeAnalysis($format);
            case 'compliance':
                return $this->exportComplianceReport($format);
            case 'remittances':
                return $this->exportRemittances($format, $dateFrom, $dateTo);
            default:
                return back()->with('error', 'Invalid export type.');
        }
    }

    /**
     * Export dashboard data
     */
    protected function exportDashboard($format)
    {
        $stats = $this->analyticsService->getDashboardStats();
        $purposeAnalysis = $this->analyticsService->getPurposeAnalysis();
        $monthlyTrends = $this->analyticsService->getMonthlyTrends();

        if ($format === 'excel') {
            return $this->exportToExcel('Dashboard Report', [
                'Overview' => $this->prepareDashboardOverviewData($stats),
                'Purpose Analysis' => $purposeAnalysis,
                'Monthly Trends' => array_values($monthlyTrends),
            ]);
        } else {
            return $this->exportToPdf('dashboard-report', compact('stats', 'purposeAnalysis', 'monthlyTrends'));
        }
    }

    /**
     * Export monthly report
     */
    protected function exportMonthly($format, $year)
    {
        $monthlyTrends = $this->analyticsService->getMonthlyTrends($year);

        if ($format === 'excel') {
            return $this->exportToExcel("Monthly Report $year", [
                'Monthly Data' => array_values($monthlyTrends),
            ]);
        } else {
            return $this->exportToPdf('monthly-report', compact('monthlyTrends', 'year'));
        }
    }

    /**
     * Export purpose analysis
     */
    protected function exportPurposeAnalysis($format)
    {
        $purposeAnalysis = $this->analyticsService->getPurposeAnalysis();

        if ($format === 'excel') {
            return $this->exportToExcel('Purpose Analysis', [
                'Purpose Data' => $purposeAnalysis,
            ]);
        } else {
            return $this->exportToPdf('purpose-analysis', compact('purposeAnalysis'));
        }
    }

    /**
     * Export compliance report
     */
    protected function exportComplianceReport($format)
    {
        $complianceReport = $this->analyticsService->getProofComplianceReport();

        if ($format === 'excel') {
            return $this->exportToExcel('Proof Compliance Report', [
                'Overall' => [$complianceReport['overall']],
                'By Purpose' => $complianceReport['by_purpose'],
                'By Month' => $complianceReport['by_month'],
            ]);
        } else {
            return $this->exportToPdf('compliance-report', compact('complianceReport'));
        }
    }

    /**
     * Export remittances data
     */
    protected function exportRemittances($format, $dateFrom, $dateTo)
    {
        $remittances = $this->analyticsService->getRemittancesByDateRange($dateFrom, $dateTo);

        if ($format === 'excel') {
            $data = $remittances->map(function ($remittance) {
                return [
                    'Date' => $remittance->transfer_date->format('Y-m-d'),
                    'Transaction Ref' => $remittance->transaction_reference,
                    'Candidate' => $remittance->candidate->full_name,
                    'CNIC' => $remittance->candidate->cnic,
                    'Amount' => $remittance->amount,
                    'Currency' => $remittance->currency,
                    'Purpose' => config('remittance.purposes.' . $remittance->primary_purpose),
                    'Receiver' => $remittance->receiver_name,
                    'Status' => config('remittance.statuses.' . $remittance->status . '.label'),
                    'Has Proof' => $remittance->has_proof ? 'Yes' : 'No',
                ];
            })->toArray();

            return $this->exportToExcel('Remittances Report', [
                'Remittances' => $data,
            ]);
        } else {
            return $this->exportToPdf('remittances-report', compact('remittances', 'dateFrom', 'dateTo'));
        }
    }

    /**
     * Export data to Excel (CSV format for simplicity)
     */
    protected function exportToExcel($filename, $sheets)
    {
        $csvContent = '';

        foreach ($sheets as $sheetName => $data) {
            $csvContent .= "Sheet: $sheetName\n";

            if (!empty($data)) {
                // Add headers
                $headers = array_keys($data[0]);
                $csvContent .= implode(',', $headers) . "\n";

                // Add data rows
                foreach ($data as $row) {
                    $values = array_map(function($value) {
                        return is_numeric($value) ? $value : '"' . str_replace('"', '""', $value) . '"';
                    }, array_values($row));
                    $csvContent .= implode(',', $values) . "\n";
                }
            }

            $csvContent .= "\n";
        }

        $filename = str_replace(' ', '_', strtolower($filename)) . '_' . date('Y-m-d') . '.csv';

        return Response::make($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Export data to PDF (simple HTML to PDF conversion)
     */
    protected function exportToPdf($view, $data)
    {
        // For now, return a simple HTML response that can be printed to PDF
        // In production, you would use a PDF library like dompdf or wkhtmltopdf

        $html = view('remittances.reports.pdf.' . $view, $data)->render();

        return Response::make($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    /**
     * Prepare dashboard overview data for export
     */
    protected function prepareDashboardOverviewData($stats)
    {
        return [
            [
                'Metric' => 'Total Remittances',
                'Value' => $stats['total_remittances'],
            ],
            [
                'Metric' => 'Total Amount (PKR)',
                'Value' => number_format($stats['total_amount'], 2),
            ],
            [
                'Metric' => 'Average Amount (PKR)',
                'Value' => number_format($stats['average_amount'], 2),
            ],
            [
                'Metric' => 'Total Candidates',
                'Value' => $stats['total_candidates'],
            ],
            [
                'Metric' => 'Proof Compliance Rate (%)',
                'Value' => $stats['proof_compliance_rate'],
            ],
            [
                'Metric' => 'Month over Month Growth (%)',
                'Value' => $stats['month_over_month_growth'],
            ],
            [
                'Metric' => 'Year over Year Growth (%)',
                'Value' => $stats['year_over_year_growth'],
            ],
        ];
    }
}
