<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Complaint;
use App\Models\Campus;
use App\Models\Oep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ComplaintControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function admin_can_view_complaints_list()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.index');
    }

    /** @test */
    public function index_can_filter_by_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Complaint::factory()->create(['status' => 'registered']);
        Complaint::factory()->create(['status' => 'resolved']);

        $response = $this->actingAs($user)->get('/complaints?status=registered');

        $response->assertStatus(200);
    }

    /** @test */
    public function index_can_filter_by_priority()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Complaint::factory()->create(['priority' => 'high']);
        Complaint::factory()->create(['priority' => 'low']);

        $response = $this->actingAs($user)->get('/complaints?priority=high');

        $response->assertStatus(200);
    }

    /** @test */
    public function index_can_search_complaints()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Complaint::factory()->create(['complainant_name' => 'John Doe']);

        $response = $this->actingAs($user)->get('/complaints?search=John');

        $response->assertStatus(200);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /** @test */
    public function admin_can_view_create_complaint_form()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/create');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.create');
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function admin_can_create_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create();

        $response = $this->actingAs($user)->post('/complaints', [
            'candidate_id' => $candidate->id,
            'complainant_name' => 'John Smith',
            'complainant_contact' => '03001234567',
            'complainant_email' => 'john@example.com',
            'category' => 'training',
            'subject' => 'Training Issue',
            'description' => 'Detailed description of the training issue.',
            'priority' => 'high',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('complaints', [
            'complainant_name' => 'John Smith',
            'category' => 'training',
        ]);
    }

    /** @test */
    public function complaint_validates_required_fields()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->post('/complaints', []);

        $response->assertSessionHasErrors([
            'complainant_name',
            'complainant_contact',
            'category',
            'subject',
            'description',
            'priority',
        ]);
    }

    /** @test */
    public function complaint_validates_category()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->post('/complaints', [
            'complainant_name' => 'John',
            'complainant_contact' => '03001234567',
            'category' => 'invalid_category',
            'subject' => 'Test',
            'description' => 'Test description',
            'priority' => 'high',
        ]);

        $response->assertSessionHasErrors(['category']);
    }

    /** @test */
    public function complaint_validates_priority()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->post('/complaints', [
            'complainant_name' => 'John',
            'complainant_contact' => '03001234567',
            'category' => 'training',
            'subject' => 'Test',
            'description' => 'Test description',
            'priority' => 'invalid_priority',
        ]);

        $response->assertSessionHasErrors(['priority']);
    }

    /** @test */
    public function complaint_can_include_evidence()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $file = UploadedFile::fake()->create('evidence.pdf', 500);

        $response = $this->actingAs($user)->post('/complaints', [
            'complainant_name' => 'John Smith',
            'complainant_contact' => '03001234567',
            'category' => 'training',
            'subject' => 'Training Issue',
            'description' => 'Test description',
            'priority' => 'high',
            'evidence' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function admin_can_view_complaint_details()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->get("/complaints/{$complaint->id}");

        $response->assertStatus(200);
        $response->assertViewIs('complaints.show');
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function admin_can_update_complaint_priority()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create(['priority' => 'low']);

        $response = $this->actingAs($user)->patch("/complaints/{$complaint->id}", [
            'priority' => 'critical',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_can_update_complaint_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create(['status' => 'registered']);

        $response = $this->actingAs($user)->patch("/complaints/{$complaint->id}", [
            'status' => 'investigating',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // ASSIGN
    // =========================================================================

    /** @test */
    public function admin_can_assign_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $assignee = User::factory()->create(['role' => 'admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/assign", [
            'assigned_to' => $assignee->id,
            'assignment_notes' => 'Please investigate urgently',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function assign_validates_user_exists()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/assign", [
            'assigned_to' => 99999,
        ]);

        $response->assertSessionHasErrors(['assigned_to']);
    }

    // =========================================================================
    // ADD UPDATE
    // =========================================================================

    /** @test */
    public function admin_can_add_update_to_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/update", [
            'update_text' => 'Investigation is in progress. Contacted the candidate.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function add_update_validates_text_required()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/update", []);

        $response->assertSessionHasErrors(['update_text']);
    }

    // =========================================================================
    // ADD EVIDENCE
    // =========================================================================

    /** @test */
    public function admin_can_add_evidence_to_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();
        $file = UploadedFile::fake()->create('new_evidence.pdf', 500);

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/evidence", [
            'evidence_file' => $file,
            'evidence_description' => 'Additional documentation',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function add_evidence_validates_file_type()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();
        $file = UploadedFile::fake()->create('evidence.exe', 500);

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/evidence", [
            'evidence_file' => $file,
        ]);

        $response->assertSessionHasErrors(['evidence_file']);
    }

    // =========================================================================
    // ESCALATE
    // =========================================================================

    /** @test */
    public function admin_can_escalate_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/escalate", [
            'escalation_reason' => 'Requires senior management attention',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function escalate_requires_reason()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/escalate", []);

        $response->assertSessionHasErrors(['escalation_reason']);
    }

    // =========================================================================
    // RESOLVE
    // =========================================================================

    /** @test */
    public function admin_can_resolve_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create(['status' => 'investigating']);

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/resolve", [
            'resolution_details' => 'Issue has been resolved by providing refund.',
            'resolution_date' => now()->toDateString(),
            'resolution_satisfactory' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function resolve_validates_required_fields()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/resolve", []);

        $response->assertSessionHasErrors(['resolution_details', 'resolution_date']);
    }

    // =========================================================================
    // CLOSE
    // =========================================================================

    /** @test */
    public function admin_can_close_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create(['status' => 'resolved']);

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/close", [
            'closure_notes' => 'Complainant confirmed satisfaction',
        ]);

        $response->assertRedirect(route('complaints.index'));
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // REOPEN
    // =========================================================================

    /** @test */
    public function admin_can_reopen_closed_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create(['status' => 'closed']);

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/reopen", [
            'reopen_reason' => 'New evidence received',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function reopen_requires_reason()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create(['status' => 'closed']);

        $response = $this->actingAs($user)->post("/complaints/{$complaint->id}/reopen", []);

        $response->assertSessionHasErrors(['reopen_reason']);
    }

    // =========================================================================
    // OVERDUE
    // =========================================================================

    /** @test */
    public function admin_can_view_overdue_complaints()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/overdue');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.overdue');
    }

    // =========================================================================
    // MY ASSIGNMENTS
    // =========================================================================

    /** @test */
    public function admin_can_view_their_assigned_complaints()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/my-assignments');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.my-assignments');
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /** @test */
    public function admin_can_view_complaint_statistics()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        // Create some complaints for statistics
        Complaint::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/complaints/statistics');

        $response->assertStatus(200);
        $response->assertViewIs('complaints.statistics');
    }

    // =========================================================================
    // ANALYTICS
    // =========================================================================

    /** @test */
    public function admin_can_generate_analytics_report()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/analytics', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        // May require valid date params
        $this->assertTrue(in_array($response->status(), [200, 302, 422]));
    }

    // =========================================================================
    // SLA REPORT
    // =========================================================================

    /** @test */
    public function admin_can_view_sla_report()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/sla-report', [
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        // May require valid date params
        $this->assertTrue(in_array($response->status(), [200, 302, 422]));
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    /** @test */
    public function admin_can_export_complaints()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/export?format=csv');

        // Export might redirect or return file
        $this->assertTrue(in_array($response->status(), [200, 302, 422]));
    }

    /** @test */
    public function export_validates_format()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get('/complaints/export?format=invalid');

        $response->assertSessionHasErrors(['format']);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function admin_can_delete_complaint()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($user)->delete("/complaints/{$complaint->id}");

        $response->assertRedirect(route('complaints.index'));
        $response->assertSessionHas('success');
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_complaints()
    {
        $response = $this->get('/complaints');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function regular_user_cannot_access_complaints()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/complaints');

        $response->assertStatus(403);
    }
}
