<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Candidate;
use App\Models\Complaint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Feature tests for Complaint REST API endpoints (Phase 3).
 */
class ComplaintRestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Oep $oep;
    protected Candidate $candidate;
    protected Complaint $complaint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->oep = Oep::factory()->create();

        $this->admin = User::factory()->admin()->create();
        $this->campusAdmin = User::factory()->campusAdmin()->create([
            'campus_id' => $this->campus->id,
        ]);

        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'oep_id' => $this->oep->id,
        ]);

        $this->complaint = Complaint::factory()->create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'status' => 'registered',
            'priority' => 'normal',
        ]);
    }

    /** @test */
    public function it_lists_all_complaints_with_authentication()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'complaint_reference',
                        'subject',
                        'status',
                        'priority',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/complaints');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_filters_complaints_for_campus_admin()
    {
        $otherCampus = Campus::factory()->create();
        $otherCandidate = Candidate::factory()->create(['campus_id' => $otherCampus->id]);
        Complaint::factory()->create([
            'candidate_id' => $otherCandidate->id,
            'campus_id' => $otherCampus->id,
        ]);

        Sanctum::actingAs($this->campusAdmin);

        $response = $this->getJson('/api/v1/complaints');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->campus->id, $data[0]['campus']['id']);
    }

    /** @test */
    public function it_shows_specific_complaint_with_sla_status()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/complaints/{$this->complaint->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'complaint_reference',
                    'status',
                    'sla_days',
                    'sla_due_date',
                    'sla_breached',
                ],
                'sla_status',
            ]);
    }

    /** @test */
    public function it_registers_complaint()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/complaints', [
            'candidate_id' => $this->candidate->id,
            'complainant_name' => 'John Doe',
            'complainant_contact' => '+92300123456',
            'complainant_email' => 'john@example.com',
            'complaint_category' => 'training',
            'subject' => 'Test Complaint',
            'description' => 'Detailed description of the complaint',
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Complaint registered successfully',
            ]);

        $this->assertDatabaseHas('complaints', [
            'complainant_name' => 'John Doe',
            'complaint_category' => 'training',
            'priority' => 'high',
        ]);
    }

    /** @test */
    public function it_updates_complaint()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/complaints/{$this->complaint->id}", [
            'priority' => 'urgent',
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Complaint updated successfully',
            ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $this->complaint->id,
            'priority' => 'urgent',
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function it_assigns_complaint()
    {
        $assignee = User::factory()->create();

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/complaints/{$this->complaint->id}/assign", [
            'assigned_to' => $assignee->id,
            'remarks' => 'Assigned for investigation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Complaint assigned successfully',
            ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $this->complaint->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    /** @test */
    public function it_escalates_complaint()
    {
        Sanctum::actingAs($this->admin);

        $currentLevel = $this->complaint->escalation_level;

        $response = $this->postJson("/api/v1/complaints/{$this->complaint->id}/escalate", [
            'escalation_reason' => 'Complaint not resolved within SLA',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Complaint escalated successfully',
            ]);

        $this->complaint->refresh();
        $this->assertEquals($currentLevel + 1, $this->complaint->escalation_level);
    }

    /** @test */
    public function it_resolves_complaint()
    {
        $this->complaint->update(['status' => 'in_progress']);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson("/api/v1/complaints/{$this->complaint->id}/resolve", [
            'resolution_details' => 'Issue has been resolved',
            'action_taken' => 'Contacted candidate and resolved issue',
            'resolution_category' => 'accepted',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Complaint resolved successfully',
            ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $this->complaint->id,
            'status' => 'resolved',
        ]);
    }

    /** @test */
    public function it_returns_complaint_statistics()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_complaints',
                    'open',
                    'by_category',
                    'by_priority',
                    'average_resolution_time',
                    'sla_compliance_rate',
                ],
            ]);
    }

    /** @test */
    public function it_returns_overdue_complaints()
    {
        // Create overdue complaint
        Complaint::factory()->create([
            'campus_id' => $this->campus->id,
            'sla_due_date' => now()->subDays(2),
            'status' => 'in_progress',
            'sla_breached' => true,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints/overdue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /** @test */
    public function it_filters_by_status()
    {
        Complaint::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'resolved',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints?status=registered');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $complaint) {
            $this->assertEquals('registered', $complaint['status']);
        }
    }

    /** @test */
    public function it_filters_by_priority()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints?priority=normal');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $complaint) {
            $this->assertEquals('normal', $complaint['priority']);
        }
    }

    /** @test */
    public function it_filters_by_sla_breached()
    {
        Complaint::factory()->create([
            'campus_id' => $this->campus->id,
            'sla_breached' => true,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints?sla_breached=true');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $complaint) {
            $this->assertTrue($complaint['sla_breached']);
        }
    }

    /** @test */
    public function it_searches_complaints()
    {
        $uniqueTerm = 'UniqueComplaintTerm123';
        Complaint::factory()->create([
            'campus_id' => $this->campus->id,
            'subject' => "Test {$uniqueTerm} Subject",
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/complaints?search={$uniqueTerm}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString($uniqueTerm, $data[0]['subject']);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/complaints', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'complainant_name',
                'complainant_contact',
                'complaint_category',
                'subject',
                'description',
                'priority',
            ]);
    }

    /** @test */
    public function it_supports_pagination()
    {
        Complaint::factory()->count(25)->create([
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/complaints?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(1, $meta['last_page']);
    }
}
