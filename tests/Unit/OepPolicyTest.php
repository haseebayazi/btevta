<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Oep;
use App\Models\Campus;
use App\Models\Candidate;
use App\Policies\OepPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OepPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected OepPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OepPolicy();
    }

    /** @test */
    public function authorized_roles_can_view_any_oep()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->viewAny($user), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function unauthorized_roles_cannot_view_any_oep()
    {
        $user = User::factory()->create(['role' => 'trainer']);
        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function super_admin_can_view_any_oep()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $oep = Oep::factory()->create();

        $this->assertTrue($this->policy->view($user, $oep));
    }

    /** @test */
    public function project_director_can_view_any_oep()
    {
        $user = User::factory()->create(['role' => 'project_director']);
        $oep = Oep::factory()->create();

        $this->assertTrue($this->policy->view($user, $oep));
    }

    /** @test */
    public function viewer_can_view_any_oep()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $oep = Oep::factory()->create();

        $this->assertTrue($this->policy->view($user, $oep));
    }

    /** @test */
    public function oep_user_can_view_their_own_oep()
    {
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);

        $this->assertTrue($this->policy->view($user, $oep));
    }

    /** @test */
    public function oep_user_cannot_view_other_oep()
    {
        $oep1 = Oep::factory()->create();
        $oep2 = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep1->id,
        ]);

        $this->assertFalse($this->policy->view($user, $oep2));
    }

    /** @test */
    public function campus_admin_can_view_oep_with_candidates_in_their_campus()
    {
        $campus = Campus::factory()->create();
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        // Create a candidate in this campus assigned to this OEP
        Candidate::factory()->create([
            'campus_id' => $campus->id,
            'oep_id' => $oep->id,
        ]);

        $this->assertTrue($this->policy->view($user, $oep));
    }

    /** @test */
    public function campus_admin_cannot_view_oep_without_candidates_in_their_campus()
    {
        $campus = Campus::factory()->create();
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        // No candidates in this campus for this OEP
        $this->assertFalse($this->policy->view($user, $oep));
    }

    /** @test */
    public function campus_admin_without_campus_id_cannot_view_oep()
    {
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => null,
        ]);
        $oep = Oep::factory()->create();

        $this->assertFalse($this->policy->view($user, $oep));
    }

    /** @test */
    public function super_admin_can_create_oep()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function non_super_admin_cannot_create_oep()
    {
        $roles = ['project_director', 'campus_admin', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->create($user), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function super_admin_can_update_oep()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $oep = Oep::factory()->create();
        $this->assertTrue($this->policy->update($user, $oep));
    }

    /** @test */
    public function non_super_admin_cannot_update_oep()
    {
        $oep = Oep::factory()->create();
        $roles = ['project_director', 'campus_admin', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->update($user, $oep), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function super_admin_can_delete_oep()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $oep = Oep::factory()->create();
        $this->assertTrue($this->policy->delete($user, $oep));
    }

    /** @test */
    public function non_super_admin_cannot_delete_oep()
    {
        $oep = Oep::factory()->create();
        $roles = ['project_director', 'campus_admin', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->delete($user, $oep), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function super_admin_can_toggle_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $oep = Oep::factory()->create();
        $this->assertTrue($this->policy->toggleStatus($user, $oep));
    }

    /** @test */
    public function non_super_admin_cannot_toggle_status()
    {
        $oep = Oep::factory()->create();
        $roles = ['project_director', 'campus_admin', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->toggleStatus($user, $oep), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function authorized_roles_can_access_api_list()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->apiList($user), "Failed for role: {$role}");
        }
    }

    /** @test */
    public function unauthorized_roles_cannot_access_api_list()
    {
        $user = User::factory()->create(['role' => 'trainer']);
        $this->assertFalse($this->policy->apiList($user));
    }
}
