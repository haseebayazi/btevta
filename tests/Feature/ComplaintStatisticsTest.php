<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Complaint;
use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComplaintStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function admin_can_view_complaint_statistics()
    {
        $this->actingAs($this->admin);

        // Create some test complaints
        Complaint::factory()->count(5)->create(['status' => 'open']);
        Complaint::factory()->count(3)->create(['status' => 'in_progress']);
        Complaint::factory()->count(2)->create(['status' => 'resolved']);

        $response = $this->get(route('complaints.statistics'));

        $response->assertStatus(200);
        $response->assertViewIs('complaints.statistics');
        $response->assertViewHas('statistics');
    }

    #[Test]
    public function statistics_contain_total_complaints()
    {
        $this->actingAs($this->admin);

        Complaint::factory()->count(10)->create();

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertEquals(10, $statistics['totalComplaints']);
    }

    #[Test]
    public function statistics_contain_status_breakdown()
    {
        $this->actingAs($this->admin);

        Complaint::factory()->count(5)->create(['status' => 'open']);
        Complaint::factory()->count(3)->create(['status' => 'in_progress']);
        Complaint::factory()->count(2)->create(['status' => 'resolved']);

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertEquals(8, $statistics['openComplaints']); // open + in_progress
        $this->assertEquals(2, $statistics['resolvedComplaints']);
    }

    #[Test]
    public function statistics_contain_category_breakdown()
    {
        $this->actingAs($this->admin);

        Complaint::factory()->count(3)->create(['category' => 'training']);
        Complaint::factory()->count(2)->create(['category' => 'visa']);
        Complaint::factory()->count(1)->create(['category' => 'salary']);

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('byCategory', $statistics);
        $this->assertEquals(3, $statistics['byCategory']['training'] ?? 0);
        $this->assertEquals(2, $statistics['byCategory']['visa'] ?? 0);
        $this->assertEquals(1, $statistics['byCategory']['salary'] ?? 0);
    }

    #[Test]
    public function statistics_contain_priority_breakdown()
    {
        $this->actingAs($this->admin);

        Complaint::factory()->count(2)->create(['priority' => 'critical']);
        Complaint::factory()->count(3)->create(['priority' => 'high']);
        Complaint::factory()->count(5)->create(['priority' => 'medium']);

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('byPriority', $statistics);
    }

    #[Test]
    public function statistics_contain_recent_complaints()
    {
        $this->actingAs($this->admin);

        Complaint::factory()->count(15)->create();

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('recentComplaints', $statistics);
        $this->assertCount(10, $statistics['recentComplaints']); // Should be limited to 10
    }

    #[Test]
    public function statistics_calculate_sla_compliance_rate()
    {
        $this->actingAs($this->admin);

        // Create resolved complaints within SLA
        Complaint::factory()->count(7)->create([
            'status' => 'resolved',
            'resolved_at' => now(),
            'created_at' => now()->subDays(3),
            'sla_days' => 7,
        ]);

        // Create resolved complaints outside SLA
        Complaint::factory()->count(3)->create([
            'status' => 'resolved',
            'resolved_at' => now(),
            'created_at' => now()->subDays(10),
            'sla_days' => 7,
        ]);

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('slaComplianceRate', $statistics);
        // 7 out of 10 = 70%
        $this->assertEquals(70, $statistics['slaComplianceRate']);
    }

    #[Test]
    public function statistics_show_monthly_trends()
    {
        $this->actingAs($this->admin);

        // Create complaints over multiple months
        Complaint::factory()->count(5)->create([
            'created_at' => now()->subMonths(1),
        ]);
        Complaint::factory()->count(3)->create([
            'created_at' => now()->subMonths(2),
        ]);

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('monthlyTrends', $statistics);
    }

    #[Test]
    public function statistics_show_top_assignees()
    {
        $this->actingAs($this->admin);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Complaint::factory()->count(5)->create(['assigned_to' => $user1->id]);
        Complaint::factory()->count(3)->create(['assigned_to' => $user2->id]);

        $response = $this->get(route('complaints.statistics'));

        $statistics = $response->viewData('statistics');

        $this->assertArrayHasKey('topAssignees', $statistics);
    }

    #[Test]
    public function unauthenticated_users_cannot_view_statistics()
    {
        $response = $this->get(route('complaints.statistics'));

        $response->assertRedirect(route('login'));
    }
}
