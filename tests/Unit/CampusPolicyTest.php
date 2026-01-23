<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Policies\CampusPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampusPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CampusPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CampusPolicy();
    }

    // =========================================================================
    // VIEW ANY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_campus()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function project_director_can_view_any_campus()
    {
        $user = User::factory()->create(['role' => 'project_director']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function campus_admin_can_view_campus_list()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function viewer_can_view_campus_list()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function unauthorized_roles_cannot_view_campus_list()
    {
        $roles = ['trainer', 'oep'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->viewAny($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function super_admin_can_view_specific_campus()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $campus = Campus::factory()->create();

        $this->assertTrue($this->policy->view($user, $campus));
    }

    /** @test */
    public function project_director_can_view_specific_campus()
    {
        $user = User::factory()->create(['role' => 'project_director']);
        $campus = Campus::factory()->create();

        $this->assertTrue($this->policy->view($user, $campus));
    }

    /** @test */
    public function viewer_can_view_specific_campus()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $campus = Campus::factory()->create();

        $this->assertTrue($this->policy->view($user, $campus));
    }

    /** @test */
    public function campus_admin_can_view_their_own_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($this->policy->view($user, $campus));
    }

    /** @test */
    public function campus_admin_cannot_view_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $this->assertFalse($this->policy->view($user, $campus2));
    }

    /** @test */
    public function campus_admin_without_campus_id_cannot_view_campus()
    {
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => null,
        ]);
        $campus = Campus::factory()->create();

        $this->assertFalse($this->policy->view($user, $campus));
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /** @test */
    public function super_admin_can_create_campus()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function non_super_admin_cannot_create_campus()
    {
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->create($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function super_admin_can_update_campus()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $campus = Campus::factory()->create();

        $this->assertTrue($this->policy->update($user, $campus));
    }

    /** @test */
    public function non_super_admin_cannot_update_campus()
    {
        $campus = Campus::factory()->create();
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->update($user, $campus), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function super_admin_can_delete_campus()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $campus = Campus::factory()->create();

        $this->assertTrue($this->policy->delete($user, $campus));
    }

    /** @test */
    public function non_super_admin_cannot_delete_campus()
    {
        $campus = Campus::factory()->create();
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->delete($user, $campus), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // TOGGLE STATUS
    // =========================================================================

    /** @test */
    public function super_admin_can_toggle_campus_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $campus = Campus::factory()->create();

        $this->assertTrue($this->policy->toggleStatus($user, $campus));
    }

    /** @test */
    public function non_super_admin_cannot_toggle_campus_status()
    {
        $campus = Campus::factory()->create();
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->toggleStatus($user, $campus), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // API LIST
    // =========================================================================

    /** @test */
    public function authorized_roles_can_access_api_list()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->apiList($user), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function unauthorized_roles_cannot_access_api_list()
    {
        $roles = ['trainer', 'oep'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->apiList($user), "Failed for role: {$role}");
        }
    }
}
