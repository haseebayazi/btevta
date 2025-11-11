<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\Remittance;
use App\Models\RemittanceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemittanceAlertApiControllerTest extends TestCase
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

    public function test_api_index_returns_paginated_alerts()
    {
        RemittanceAlert::factory()->count(5)->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'candidate_id',
                        'alert_type',
                        'severity',
                        'title',
                        'message',
                        'is_read',
                        'is_resolved',
                    ]
                ],
                'current_page',
                'total',
            ]);
    }

    public function test_api_index_filters_by_status()
    {
        RemittanceAlert::factory()->count(3)->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(2)->create([
            'candidate_id' => $this->candidate->id,
            'is_resolved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/?status=unresolved');

        $response->assertStatus(200)
            ->assertJsonPath('total', 3);
    }

    public function test_api_index_filters_by_severity()
    {
        RemittanceAlert::factory()->count(2)->critical()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'severity' => 'warning',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/?severity=critical');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    public function test_api_index_filters_by_alert_type()
    {
        RemittanceAlert::factory()->count(2)->missingRemittance()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(3)->missingProof()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/?type=missing_remittance');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    public function test_api_show_returns_single_alert()
    {
        $alert = RemittanceAlert::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittance/alerts/{$alert->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $alert->id,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity,
            ]);
    }

    public function test_api_show_marks_alert_as_read()
    {
        $alert = RemittanceAlert::factory()->unread()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $this->assertFalse($alert->is_read);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittance/alerts/{$alert->id}");

        $response->assertStatus(200);

        $alert->refresh();
        $this->assertTrue($alert->is_read);
    }

    public function test_api_show_returns_404_for_nonexistent_alert()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/99999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Alert not found']);
    }

    public function test_api_unread_count_returns_correct_number()
    {
        RemittanceAlert::factory()->count(3)->unread()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(2)->create([
            'candidate_id' => $this->candidate->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/stats/unread-count');

        $response->assertStatus(200)
            ->assertJson(['count' => 3]);
    }

    public function test_api_unread_count_filters_by_candidate()
    {
        RemittanceAlert::factory()->count(2)->unread()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $candidate2 = Candidate::factory()->create();
        RemittanceAlert::factory()->count(3)->unread()->create([
            'candidate_id' => $candidate2->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittance/alerts/stats/unread-count?candidate_id={$this->candidate->id}");

        $response->assertStatus(200)
            ->assertJson(['count' => 2]);
    }

    public function test_api_statistics_returns_comprehensive_stats()
    {
        RemittanceAlert::factory()->count(2)->critical()->unresolved()->create();
        RemittanceAlert::factory()->count(3)->unresolved()->create(['severity' => 'warning']);
        RemittanceAlert::factory()->count(2)->create(['is_resolved' => true]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/stats/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_alerts',
                'unresolved_alerts',
                'critical_alerts',
                'unread_alerts',
                'by_type',
                'by_severity',
            ])
            ->assertJsonPath('total_alerts', 7)
            ->assertJsonPath('unresolved_alerts', 5)
            ->assertJsonPath('critical_alerts', 2);
    }

    public function test_api_by_candidate_returns_candidate_alerts()
    {
        RemittanceAlert::factory()->count(3)->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(1)->critical()->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittance/alerts/candidate/{$this->candidate->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'candidate_id',
                'alerts',
                'summary' => [
                    'total',
                    'unresolved',
                    'critical',
                ],
            ])
            ->assertJsonPath('candidate_id', $this->candidate->id)
            ->assertJsonPath('summary.total', 4)
            ->assertJsonPath('summary.unresolved', 4)
            ->assertJsonPath('summary.critical', 1);
    }

    public function test_api_mark_as_read_updates_alert()
    {
        $alert = RemittanceAlert::factory()->unread()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/remittance/alerts/{$alert->id}/read");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Alert marked as read']);

        $alert->refresh();
        $this->assertTrue($alert->is_read);
    }

    public function test_api_resolve_updates_alert_with_notes()
    {
        $alert = RemittanceAlert::factory()->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $data = [
            'resolution_notes' => 'Issue resolved via API',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/remittance/alerts/{$alert->id}/resolve", $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Alert resolved successfully']);

        $alert->refresh();
        $this->assertTrue($alert->is_resolved);
        $this->assertEquals('Issue resolved via API', $alert->resolution_notes);
        $this->assertEquals($this->user->id, $alert->resolved_by);
        $this->assertNotNull($alert->resolved_at);
    }

    public function test_api_resolve_requires_resolution_notes()
    {
        $alert = RemittanceAlert::factory()->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/remittance/alerts/{$alert->id}/resolve", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['resolution_notes']);
    }

    public function test_api_dismiss_quickly_resolves_alert()
    {
        $alert = RemittanceAlert::factory()->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/remittance/alerts/{$alert->id}/dismiss");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Alert dismissed successfully']);

        $alert->refresh();
        $this->assertTrue($alert->is_resolved);
        $this->assertEquals('Dismissed via API', $alert->resolution_notes);
    }

    public function test_api_alerts_require_authentication()
    {
        $response = $this->getJson('/api/v1/remittance/alerts/');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/remittance/alerts/stats/unread-count');
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/remittance/alerts/stats/overview');
        $response->assertStatus(401);
    }

    public function test_api_index_defaults_to_unresolved_alerts()
    {
        RemittanceAlert::factory()->count(3)->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(5)->create([
            'candidate_id' => $this->candidate->id,
            'is_resolved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/');

        $response->assertStatus(200)
            ->assertJsonPath('total', 3);
    }

    public function test_api_index_can_show_all_alerts()
    {
        RemittanceAlert::factory()->count(3)->unresolved()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        RemittanceAlert::factory()->count(2)->create([
            'candidate_id' => $this->candidate->id,
            'is_resolved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittance/alerts/?status=all');

        $response->assertStatus(200)
            ->assertJsonPath('total', 5);
    }
}
