<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\Remittance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemittanceReportApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $candidate;
    protected $departure;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->candidate = Candidate::factory()->create();
        $this->departure = Departure::factory()->create(['candidate_id' => $this->candidate->id]);
    }

    public function test_api_dashboard_returns_combined_statistics()
    {
        Remittance::factory()->count(5)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'year' => date('Y'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'statistics' => [
                    'total_remittances',
                    'total_amount',
                    'average_amount',
                ],
                'monthly_trends',
                'purpose_analysis',
            ]);
    }

    public function test_api_monthly_trends_returns_all_months()
    {
        $currentYear = date('Y');

        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'transfer_date' => "$currentYear-01-15",
            'year' => $currentYear,
            'month' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittance/reports/monthly-trends?year=$currentYear");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'year',
                'trends' => [
                    '*' => [
                        'month',
                        'month_name',
                        'total_amount',
                        'count',
                        'average_amount',
                    ]
                ],
            ])
            ->assertJsonPath('year', $currentYear)
            ->assertJsonCount(12, 'trends');
    }

    public function test_api_monthly_trends_defaults_to_current_year()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/monthly-trends');

        $response->assertStatus(200)
            ->assertJsonPath('year', date('Y'));
    }

    public function test_api_purpose_analysis_returns_distribution()
    {
        Remittance::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'primary_purpose' => 'family_support',
            'amount' => 50000,
        ]);

        Remittance::factory()->count(2)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'primary_purpose' => 'education',
            'amount' => 30000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/purpose-analysis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'purpose',
                    'count',
                    'total_amount',
                    'avg_amount',
                    'percentage',
                ]
            ]);
    }

    public function test_api_transfer_methods_returns_analysis()
    {
        Remittance::factory()->count(4)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'transfer_method' => 'bank_transfer',
        ]);

        Remittance::factory()->count(1)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'transfer_method' => 'online_transfer',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/transfer-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'method',
                    'count',
                    'total_amount',
                    'percentage',
                ]
            ]);
    }

    public function test_api_country_analysis_returns_data()
    {
        $departure = Departure::factory()->create([
            'candidate_id' => $this->candidate->id,
            'destination' => 'Saudi Arabia',
        ]);

        Remittance::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/country-analysis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'country',
                    'count',
                    'total_amount',
                    'avg_amount',
                ]
            ]);
    }

    public function test_api_proof_compliance_returns_detailed_report()
    {
        Remittance::factory()->count(8)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'has_proof' => true,
            'year' => date('Y'),
            'month' => date('n'),
        ]);

        Remittance::factory()->count(2)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'has_proof' => false,
            'year' => date('Y'),
            'month' => date('n'),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/proof-compliance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overall' => [
                    'total_remittances',
                    'with_proof',
                    'without_proof',
                    'compliance_rate',
                ],
                'by_purpose',
                'by_month',
            ])
            ->assertJsonPath('overall.total_remittances', 10)
            ->assertJsonPath('overall.with_proof', 8)
            ->assertJsonPath('overall.without_proof', 2)
            ->assertJsonPath('overall.compliance_rate', 80.0);
    }

    public function test_api_beneficiary_report_returns_analysis()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/beneficiary-report');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overview' => [
                    'total',
                    'active',
                    'primary',
                ],
                'by_relationship',
                'banking_info',
            ]);
    }

    public function test_api_impact_analytics_returns_comprehensive_data()
    {
        Remittance::factory()->count(5)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/impact-analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'avg_time_to_first_remittance',
                'remittance_frequency',
                'economic_impact' => [
                    'total_inflow',
                    'total_families_benefited',
                    'avg_per_family',
                ],
                'purpose_breakdown',
            ]);
    }

    public function test_api_top_candidates_returns_ordered_list()
    {
        $candidate2 = Candidate::factory()->create();
        $departure2 = Departure::factory()->create(['candidate_id' => $candidate2->id]);

        // High remitter
        Remittance::factory()->count(10)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'amount' => 100000,
        ]);

        // Low remitter
        Remittance::factory()->count(2)->create([
            'candidate_id' => $candidate2->id,
            'departure_id' => $departure2->id,
            'recorded_by' => $this->user->id,
            'amount' => 50000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/top-candidates?limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'candidate_id',
                    'candidate_name',
                    'total_amount',
                    'remittance_count',
                    'average_amount',
                ]
            ]);

        // Verify ordering (highest first)
        $data = $response->json();
        $this->assertGreaterThan($data[1]['total_amount'], $data[0]['total_amount']);
    }

    public function test_api_top_candidates_respects_limit_parameter()
    {
        Remittance::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/top-candidates?limit=1');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_api_reports_require_authentication()
    {
        $response = $this->getJson('/api/v1/remittance/reports/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/remittance/reports/monthly-trends');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/remittance/reports/purpose-analysis');
        $response->assertStatus(401);
    }

    public function test_api_dashboard_handles_empty_database()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/reports/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('statistics.total_remittances', 0);
    }
}
