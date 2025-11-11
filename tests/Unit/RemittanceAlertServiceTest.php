<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RemittanceAlertService;
use App\Models\Remittance;
use App\Models\RemittanceAlert;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RemittanceAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RemittanceAlertService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RemittanceAlertService();
    }

    public function test_generate_missing_remittance_alerts_creates_alert_for_old_departure()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(100), // Deployed 100 days ago
        ]);

        $alertsCreated = $this->service->generateMissingRemittanceAlerts();

        $this->assertEquals(1, $alertsCreated);
        $this->assertDatabaseHas('remittance_alerts', [
            'candidate_id' => $candidate->id,
            'alert_type' => 'missing_remittance',
            'is_resolved' => false,
        ]);
    }

    public function test_generate_missing_remittance_alerts_does_not_create_duplicate()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(100),
        ]);

        // Generate alerts twice
        $this->service->generateMissingRemittanceAlerts();
        $alertsCreated = $this->service->generateMissingRemittanceAlerts();

        $this->assertEquals(0, $alertsCreated);
        $this->assertEquals(1, RemittanceAlert::where('candidate_id', $candidate->id)->count());
    }

    public function test_generate_missing_remittance_alerts_skips_recent_departures()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(30), // Recently deployed
        ]);

        $alertsCreated = $this->service->generateMissingRemittanceAlerts();

        $this->assertEquals(0, $alertsCreated);
    }

    public function test_generate_missing_proof_alerts_creates_alert_for_old_remittance()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'has_proof' => false,
            'transfer_date' => now()->subDays(40), // 40 days old without proof
        ]);

        $alertsCreated = $this->service->generateMissingProofAlerts();

        $this->assertEquals(1, $alertsCreated);
        $this->assertDatabaseHas('remittance_alerts', [
            'candidate_id' => $candidate->id,
            'alert_type' => 'missing_proof',
            'is_resolved' => false,
        ]);
    }

    public function test_generate_missing_proof_alerts_skips_remittances_with_proof()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'has_proof' => true,
            'transfer_date' => now()->subDays(40),
        ]);

        $alertsCreated = $this->service->generateMissingProofAlerts();

        $this->assertEquals(0, $alertsCreated);
    }

    public function test_generate_first_remittance_delay_alerts_creates_alert()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(70), // 70 days ago, no remittance
        ]);

        $alertsCreated = $this->service->generateFirstRemittanceDelayAlerts();

        $this->assertEquals(1, $alertsCreated);
        $this->assertDatabaseHas('remittance_alerts', [
            'candidate_id' => $candidate->id,
            'alert_type' => 'first_remittance_delay',
            'is_resolved' => false,
        ]);
    }

    public function test_generate_first_remittance_delay_alerts_skips_candidates_with_remittances()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(70),
        ]);
        $user = User::factory()->create();

        // Has a remittance
        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
        ]);

        $alertsCreated = $this->service->generateFirstRemittanceDelayAlerts();

        $this->assertEquals(0, $alertsCreated);
    }

    public function test_generate_low_frequency_alerts_creates_alert_for_infrequent_remitters()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subMonths(7), // 7 months ago
        ]);
        $user = User::factory()->create();

        // Only 2 remittances in 7 months (below threshold of 3)
        Remittance::factory()->count(2)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
        ]);

        $alertsCreated = $this->service->generateLowFrequencyAlerts();

        $this->assertEquals(1, $alertsCreated);
        $this->assertDatabaseHas('remittance_alerts', [
            'candidate_id' => $candidate->id,
            'alert_type' => 'low_frequency',
            'severity' => 'info',
        ]);
    }

    public function test_generate_low_frequency_alerts_skips_candidates_with_enough_remittances()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subMonths(7),
        ]);
        $user = User::factory()->create();

        // 5 remittances (above threshold)
        Remittance::factory()->count(5)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
        ]);

        $alertsCreated = $this->service->generateLowFrequencyAlerts();

        $this->assertEquals(0, $alertsCreated);
    }

    public function test_generate_unusual_amount_alerts_detects_outliers()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        // Create normal remittances (around 50,000)
        Remittance::factory()->count(5)->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'amount' => 50000,
            'transfer_date' => now()->subMonths(2),
        ]);

        // Create an unusual amount (very high)
        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'amount' => 500000, // 10x higher
            'transfer_date' => now()->subDays(5),
        ]);

        $alertsCreated = $this->service->generateUnusualAmountAlerts();

        $this->assertGreaterThanOrEqual(1, $alertsCreated);
    }

    public function test_get_unresolved_alerts_count_returns_correct_count()
    {
        $candidate = Candidate::factory()->create();

        RemittanceAlert::factory()->count(3)->unresolved()->create([
            'candidate_id' => $candidate->id,
        ]);

        RemittanceAlert::factory()->count(2)->create([
            'candidate_id' => $candidate->id,
            'is_resolved' => true,
        ]);

        $count = $this->service->getUnresolvedAlertsCount();
        $this->assertEquals(3, $count);

        $candidateCount = $this->service->getUnresolvedAlertsCount($candidate->id);
        $this->assertEquals(3, $candidateCount);
    }

    public function test_get_critical_alerts_count_returns_correct_count()
    {
        RemittanceAlert::factory()->count(2)->critical()->unresolved()->create();
        RemittanceAlert::factory()->count(3)->unresolved()->create(['severity' => 'warning']);

        $count = $this->service->getCriticalAlertsCount();
        $this->assertEquals(2, $count);
    }

    public function test_mark_old_alerts_as_read_updates_old_alerts()
    {
        // Create old unread alert
        RemittanceAlert::factory()->create([
            'is_read' => false,
            'created_at' => now()->subDays(35),
        ]);

        // Create recent unread alert
        RemittanceAlert::factory()->create([
            'is_read' => false,
            'created_at' => now()->subDays(5),
        ]);

        $updated = $this->service->markOldAlertsAsRead(30);

        $this->assertEquals(1, $updated);
        $this->assertEquals(1, RemittanceAlert::where('is_read', false)->count());
    }

    public function test_auto_resolve_alerts_resolves_missing_remittance_alert()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        // Create missing remittance alert
        RemittanceAlert::factory()->missingRemittance()->unresolved()->create([
            'candidate_id' => $candidate->id,
        ]);

        // Add a recent remittance
        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'transfer_date' => now()->subDays(10),
        ]);

        $resolved = $this->service->autoResolveAlerts();

        $this->assertEquals(1, $resolved);
        $this->assertEquals(0, RemittanceAlert::where('is_resolved', false)->count());
    }

    public function test_auto_resolve_alerts_resolves_missing_proof_alert()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        $remittance = Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
            'has_proof' => false,
        ]);

        // Create missing proof alert
        RemittanceAlert::factory()->missingProof()->unresolved()->create([
            'candidate_id' => $candidate->id,
            'remittance_id' => $remittance->id,
        ]);

        // Upload proof
        $remittance->update(['has_proof' => true]);

        $resolved = $this->service->autoResolveAlerts();

        $this->assertEquals(1, $resolved);
    }

    public function test_auto_resolve_alerts_resolves_first_remittance_delay_alert()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);
        $user = User::factory()->create();

        // Create first remittance delay alert
        RemittanceAlert::factory()->unresolved()->create([
            'candidate_id' => $candidate->id,
            'alert_type' => 'first_remittance_delay',
        ]);

        // Add first remittance
        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'recorded_by' => $user->id,
        ]);

        $resolved = $this->service->autoResolveAlerts();

        $this->assertEquals(1, $resolved);
    }

    public function test_get_alert_statistics_returns_comprehensive_stats()
    {
        RemittanceAlert::factory()->count(2)->critical()->unresolved()->create();
        RemittanceAlert::factory()->count(3)->unresolved()->create(['severity' => 'warning']);
        RemittanceAlert::factory()->count(2)->create(['is_resolved' => true]);
        RemittanceAlert::factory()->count(1)->unread()->unresolved()->create();

        $stats = $this->service->getAlertStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_alerts', $stats);
        $this->assertArrayHasKey('unresolved_alerts', $stats);
        $this->assertArrayHasKey('critical_alerts', $stats);
        $this->assertArrayHasKey('unread_alerts', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_severity', $stats);

        $this->assertEquals(8, $stats['total_alerts']);
        $this->assertEquals(6, $stats['unresolved_alerts']);
        $this->assertEquals(2, $stats['critical_alerts']);
    }

    public function test_generate_all_alerts_returns_breakdown()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->subDays(100),
        ]);

        $result = $this->service->generateAllAlerts();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_generated', $result);
        $this->assertArrayHasKey('breakdown', $result);
        $this->assertArrayHasKey('missing_remittances', $result['breakdown']);
        $this->assertArrayHasKey('missing_proofs', $result['breakdown']);
        $this->assertArrayHasKey('first_remittance_delay', $result['breakdown']);
        $this->assertArrayHasKey('low_frequency', $result['breakdown']);
        $this->assertArrayHasKey('unusual_amount', $result['breakdown']);
    }
}
