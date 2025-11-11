<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RemittanceAnalyticsService;
use App\Models\Remittance;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemittanceAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RemittanceAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RemittanceAnalyticsService();
    }

    public function test_get_dashboard_stats_returns_comprehensive_statistics()
    {
        // Create test data
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        Remittance::factory()->count(5)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'status' => 'verified',
            'has_proof' => true,
            'year' => date('Y'),
            'month' => date('n'),
        ]);

        $stats = $this->service->getDashboardStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_remittances', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
        $this->assertArrayHasKey('average_amount', $stats);
        $this->assertArrayHasKey('total_candidates', $stats);
        $this->assertArrayHasKey('current_year_count', $stats);
        $this->assertArrayHasKey('status_breakdown', $stats);
        $this->assertArrayHasKey('proof_compliance_rate', $stats);

        $this->assertEquals(5, $stats['total_remittances']);
        $this->assertEquals(5, $stats['current_year_count']);
        $this->assertEquals(5, $stats['current_month_count']);
        $this->assertEquals(1, $stats['total_candidates']);
    }

    public function test_get_monthly_trends_returns_data_for_all_months()
    {
        // Create remittances in different months
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        $currentYear = date('Y');

        // Create remittances in January and June
        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_date' => "$currentYear-01-15",
            'year' => $currentYear,
            'month' => 1,
        ]);

        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_date' => "$currentYear-06-15",
            'year' => $currentYear,
            'month' => 6,
        ]);

        $trends = $this->service->getMonthlyTrends($currentYear);

        $this->assertIsArray($trends);
        $this->assertCount(12, $trends); // Should have all 12 months

        // Check January has data
        $this->assertEquals(1, $trends[1]['count']);
        $this->assertEquals('January', $trends[1]['month']);

        // Check June has data
        $this->assertEquals(1, $trends[6]['count']);
        $this->assertEquals('June', $trends[6]['month']);

        // Check February is zero (no remittances)
        $this->assertEquals(0, $trends[2]['count']);
        $this->assertEquals('February', $trends[2]['month']);
    }

    public function test_get_purpose_analysis_returns_correct_distribution()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        // Create remittances with different purposes
        Remittance::factory()->count(3)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'primary_purpose' => 'family_support',
            'amount' => 50000,
        ]);

        Remittance::factory()->count(2)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'primary_purpose' => 'education',
            'amount' => 30000,
        ]);

        $analysis = $this->service->getPurposeAnalysis();

        $this->assertIsArray($analysis);
        $this->assertNotEmpty($analysis);

        // Find family_support in results
        $familySupport = collect($analysis)->firstWhere('purpose', 'family_support');
        $this->assertNotNull($familySupport);
        $this->assertEquals(3, $familySupport['count']);
        $this->assertEquals(150000, $familySupport['total_amount']);

        // Find education in results
        $education = collect($analysis)->firstWhere('purpose', 'education');
        $this->assertNotNull($education);
        $this->assertEquals(2, $education['count']);
        $this->assertEquals(60000, $education['total_amount']);
    }

    public function test_get_transfer_method_analysis_calculates_percentages()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        Remittance::factory()->count(4)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_method' => 'bank_transfer',
        ]);

        Remittance::factory()->count(1)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_method' => 'online_transfer',
        ]);

        $analysis = $this->service->getTransferMethodAnalysis();

        $this->assertIsArray($analysis);
        $this->assertNotEmpty($analysis);

        $bankTransfer = collect($analysis)->firstWhere('method', 'bank_transfer');
        $this->assertNotNull($bankTransfer);
        $this->assertEquals(4, $bankTransfer['count']);
        $this->assertEquals(80.0, $bankTransfer['percentage']); // 4 out of 5 = 80%

        $onlineTransfer = collect($analysis)->firstWhere('method', 'online_transfer');
        $this->assertEquals(1, $onlineTransfer['count']);
        $this->assertEquals(20.0, $onlineTransfer['percentage']); // 1 out of 5 = 20%
    }

    public function test_get_proof_compliance_report_returns_detailed_breakdown()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        // Create remittances with proof
        Remittance::factory()->count(8)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'has_proof' => true,
            'year' => date('Y'),
            'month' => date('n'),
        ]);

        // Create remittances without proof
        Remittance::factory()->count(2)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'has_proof' => false,
            'year' => date('Y'),
            'month' => date('n'),
        ]);

        $report = $this->service->getProofComplianceReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('overall', $report);
        $this->assertArrayHasKey('by_purpose', $report);
        $this->assertArrayHasKey('by_month', $report);

        $this->assertEquals(10, $report['overall']['total_remittances']);
        $this->assertEquals(8, $report['overall']['with_proof']);
        $this->assertEquals(2, $report['overall']['without_proof']);
        $this->assertEquals(80.0, $report['overall']['compliance_rate']);
    }

    public function test_get_top_remitting_candidates_returns_ordered_list()
    {
        $user = User::factory()->create();

        // Create candidate with many remittances
        $highRemitter = Candidate::factory()->create(['name' => 'High Remitter']);
        $departure1 = Departure::factory()->create(['candidate_id' => $highRemitter->id]);
        Remittance::factory()->count(10)->create([
            'candidate_id' => $highRemitter->id,
            'departure_id' => $departure1->id,
            'recorded_by' => $user->id,
            'amount' => 100000,
        ]);

        // Create candidate with few remittances
        $lowRemitter = Candidate::factory()->create(['name' => 'Low Remitter']);
        $departure2 = Departure::factory()->create(['candidate_id' => $lowRemitter->id]);
        Remittance::factory()->count(2)->create([
            'candidate_id' => $lowRemitter->id,
            'departure_id' => $departure2->id,
            'recorded_by' => $user->id,
            'amount' => 50000,
        ]);

        $topCandidates = $this->service->getTopRemittingCandidates(5);

        $this->assertNotEmpty($topCandidates);
        $this->assertEquals($highRemitter->id, $topCandidates->first()->id);
        $this->assertEquals(10, $topCandidates->first()->remittance_count);
        $this->assertEquals(1000000, $topCandidates->first()->total_amount);
    }

    public function test_get_remittances_by_date_range_filters_correctly()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        // Create remittances in range
        Remittance::factory()->count(3)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_date' => '2025-06-15',
        ]);

        // Create remittance outside range
        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_date' => '2025-01-15',
        ]);

        $remittances = $this->service->getRemittancesByDateRange('2025-06-01', '2025-06-30');

        $this->assertCount(3, $remittances);
    }

    public function test_dashboard_stats_handles_empty_database()
    {
        $stats = $this->service->getDashboardStats();

        $this->assertEquals(0, $stats['total_remittances']);
        $this->assertEquals(0, $stats['total_amount']);
        $this->assertEquals(0, $stats['total_candidates']);
        $this->assertEquals(0, $stats['proof_compliance_rate']);
    }

    public function test_monthly_trends_fills_missing_months_with_zeros()
    {
        $trends = $this->service->getMonthlyTrends(2025);

        $this->assertCount(12, $trends);

        foreach ($trends as $month => $data) {
            $this->assertEquals(0, $data['count']);
            $this->assertEquals(0, $data['total_amount']);
        }
    }
}
