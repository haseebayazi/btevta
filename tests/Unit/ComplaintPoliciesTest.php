<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Complaint;
use App\Models\ComplaintEvidence;
use App\Models\ComplaintUpdate;
use App\Policies\ComplaintEvidencePolicy;
use App\Policies\ComplaintUpdatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComplaintPoliciesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // COMPLAINT EVIDENCE POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_evidence()
    {
        $policy = new ComplaintEvidencePolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function project_director_can_view_any_evidence()
    {
        $policy = new ComplaintEvidencePolicy();
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function campus_admin_can_view_evidence_from_their_campus()
    {
        $policy = new ComplaintEvidencePolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $complaint = Complaint::factory()->create(['campus_id' => $campus->id]);
        $evidence = ComplaintEvidence::factory()->create(['complaint_id' => $complaint->id]);

        $this->assertTrue($policy->view($user, $evidence));
    }

    #[Test]
    public function campus_admin_cannot_view_evidence_from_other_campus()
    {
        $policy = new ComplaintEvidencePolicy();
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $complaint = Complaint::factory()->create(['campus_id' => $campus2->id]);
        $evidence = ComplaintEvidence::factory()->create(['complaint_id' => $complaint->id]);

        $this->assertFalse($policy->view($user, $evidence));
    }

    #[Test]
    public function authorized_roles_can_upload_evidence()
    {
        $policy = new ComplaintEvidencePolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);

        $this->assertTrue($policy->create($superAdmin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($campusAdmin));
    }

    #[Test]
    public function viewer_cannot_upload_evidence()
    {
        $policy = new ComplaintEvidencePolicy();
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($policy->create($user));
    }

    #[Test]
    public function only_super_admin_can_delete_evidence()
    {
        $policy = new ComplaintEvidencePolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $evidence = ComplaintEvidence::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $evidence));
        $this->assertFalse($policy->delete($admin, $evidence));
    }

    // =========================================================================
    // COMPLAINT UPDATE POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_update()
    {
        $policy = new ComplaintUpdatePolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function authorized_roles_can_create_updates()
    {
        $policy = new ComplaintUpdatePolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);

        $this->assertTrue($policy->create($superAdmin));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($campusAdmin));
    }

    #[Test]
    public function viewer_cannot_create_updates()
    {
        $policy = new ComplaintUpdatePolicy();
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($policy->create($user));
    }

    #[Test]
    public function campus_admin_can_view_updates_from_their_campus()
    {
        $policy = new ComplaintUpdatePolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $complaint = Complaint::factory()->create(['campus_id' => $campus->id]);
        $update = ComplaintUpdate::factory()->create(['complaint_id' => $complaint->id]);

        $this->assertTrue($policy->view($user, $update));
    }

    #[Test]
    public function super_admin_can_update_any_complaint_update()
    {
        $policy = new ComplaintUpdatePolicy();
        $user = User::factory()->create(['role' => 'super_admin']);
        $update = ComplaintUpdate::factory()->create();

        $this->assertTrue($policy->update($user, $update));
    }

    #[Test]
    public function only_super_admin_can_delete_updates()
    {
        $policy = new ComplaintUpdatePolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $update = ComplaintUpdate::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $update));
        $this->assertFalse($policy->delete($admin, $update));
    }
}
