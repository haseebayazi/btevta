<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Candidate;
use App\Policies\BatchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BatchPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BatchPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BatchPolicy();
    }

    // =========================================================================
    // SUPER ADMIN
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function super_admin_can_view_specific_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->view($user, $batch));
    }

    #[Test]
    public function super_admin_can_create_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function super_admin_can_update_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->update($user, $batch));
    }

    #[Test]
    public function super_admin_can_delete_empty_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->delete($user, $batch));
    }

    #[Test]
    public function super_admin_cannot_delete_batch_with_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create();
        Candidate::factory()->count(5)->create(['batch_id' => $batch->id]);

        $this->assertFalse($this->policy->delete($user, $batch));
    }

    #[Test]
    public function super_admin_can_assign_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->assignCandidates($user, $batch));
    }

    #[Test]
    public function super_admin_can_change_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->changeStatus($user, $batch));
    }

    // =========================================================================
    // PROJECT DIRECTOR
    // =========================================================================

    #[Test]
    public function project_director_can_view_any_batch()
    {
        $user = User::factory()->create(['role' => 'project_director']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function project_director_can_view_specific_batch()
    {
        $user = User::factory()->create(['role' => 'project_director']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->view($user, $batch));
    }

    #[Test]
    public function project_director_can_create_batch()
    {
        $user = User::factory()->create(['role' => 'project_director']);

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function project_director_can_update_batch()
    {
        $user = User::factory()->create(['role' => 'project_director']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->update($user, $batch));
    }

    #[Test]
    public function project_director_cannot_delete_batch_with_candidates()
    {
        $user = User::factory()->create(['role' => 'project_director']);
        $batch = Batch::factory()->create();
        Candidate::factory()->count(3)->create(['batch_id' => $batch->id]);

        $this->assertFalse($this->policy->delete($user, $batch));
    }

    #[Test]
    public function project_director_can_change_status()
    {
        $user = User::factory()->create(['role' => 'project_director']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->changeStatus($user, $batch));
    }

    // =========================================================================
    // CAMPUS ADMIN
    // =========================================================================

    #[Test]
    public function campus_admin_can_view_batches_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->view($user, $batch));
    }

    #[Test]
    public function campus_admin_cannot_view_batches_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->view($user, $batch));
    }

    #[Test]
    public function campus_admin_can_create_batch()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function campus_admin_can_update_batches_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->update($user, $batch));
    }

    #[Test]
    public function campus_admin_cannot_update_batches_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->update($user, $batch));
    }

    #[Test]
    public function campus_admin_can_delete_planned_batch_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'planned'
        ]);

        $this->assertTrue($this->policy->delete($user, $batch));
    }

    #[Test]
    public function campus_admin_cannot_delete_active_batch()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'active'
        ]);

        $this->assertFalse($this->policy->delete($user, $batch));
    }

    #[Test]
    public function campus_admin_cannot_delete_batch_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $batch = Batch::factory()->create([
            'campus_id' => $campus2->id,
            'status' => 'planned'
        ]);

        $this->assertFalse($this->policy->delete($user, $batch));
    }

    #[Test]
    public function campus_admin_can_assign_candidates_to_their_campus_batch()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->assignCandidates($user, $batch));
    }

    #[Test]
    public function campus_admin_can_change_status()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->changeStatus($user, $batch));
    }

    // =========================================================================
    // TRAINER
    // =========================================================================

    #[Test]
    public function trainer_can_view_batches_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'trainer',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->view($user, $batch));
    }

    #[Test]
    public function trainer_cannot_view_batches_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'trainer',
            'campus_id' => $campus1->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->view($user, $batch));
    }

    #[Test]
    public function trainer_cannot_create_batch()
    {
        $user = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->create($user));
    }

    #[Test]
    public function trainer_cannot_update_batch()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'trainer',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);

        $this->assertFalse($this->policy->update($user, $batch));
    }

    #[Test]
    public function trainer_cannot_delete_batch()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'trainer',
            'campus_id' => $campus->id,
        ]);
        $batch = Batch::factory()->create(['campus_id' => $campus->id]);

        $this->assertFalse($this->policy->delete($user, $batch));
    }

    #[Test]
    public function trainer_cannot_change_status()
    {
        $user = User::factory()->create(['role' => 'trainer']);
        $batch = Batch::factory()->create();

        $this->assertFalse($this->policy->changeStatus($user, $batch));
    }

    // =========================================================================
    // VIEWER
    // =========================================================================

    #[Test]
    public function viewer_can_view_any_batch()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function viewer_can_view_specific_batch()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $batch = Batch::factory()->create();

        $this->assertTrue($this->policy->view($user, $batch));
    }

    #[Test]
    public function viewer_cannot_create_batch()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($this->policy->create($user));
    }

    #[Test]
    public function viewer_cannot_update_batch()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $batch = Batch::factory()->create();

        $this->assertFalse($this->policy->update($user, $batch));
    }

    #[Test]
    public function viewer_cannot_delete_batch()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $batch = Batch::factory()->create();

        $this->assertFalse($this->policy->delete($user, $batch));
    }

    #[Test]
    public function viewer_cannot_change_status()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $batch = Batch::factory()->create();

        $this->assertFalse($this->policy->changeStatus($user, $batch));
    }

    // =========================================================================
    // API METHODS
    // =========================================================================

    #[Test]
    public function authorized_roles_can_access_api_list()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->apiList($user), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function unauthorized_roles_cannot_access_api_list()
    {
        $user = User::factory()->create(['role' => 'oep']);

        $this->assertFalse($this->policy->apiList($user));
    }

    #[Test]
    public function authorized_roles_can_access_by_campus_endpoint()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->byCampus($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    #[Test]
    public function campus_admin_without_campus_id_cannot_view_batches()
    {
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => null,
        ]);
        $batch = Batch::factory()->create();

        $this->assertFalse($this->policy->view($user, $batch));
    }

    #[Test]
    public function trainer_without_campus_id_cannot_view_batches()
    {
        $user = User::factory()->create([
            'role' => 'trainer',
            'campus_id' => null,
        ]);
        $batch = Batch::factory()->create();

        $this->assertFalse($this->policy->view($user, $batch));
    }

    #[Test]
    public function no_one_can_delete_batch_with_candidates_regardless_of_role()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(10)->create(['batch_id' => $batch->id]);

        $roles = ['super_admin', 'project_director', 'campus_admin'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            if ($role === 'campus_admin') {
                $user->campus_id = $batch->campus_id;
                $user->save();
                $batch->status = 'planned';
                $batch->save();
            }
            $this->assertFalse($this->policy->delete($user, $batch), "Failed for role: {$role}");
        }
    }
}
