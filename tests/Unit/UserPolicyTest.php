<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    // =========================================================================
    // VIEW ANY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_user()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function project_director_can_view_any_user()
    {
        $user = User::factory()->create(['role' => 'project_director']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function campus_admin_cannot_view_any_user()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);

        $this->assertFalse($this->policy->viewAny($user));
    }

    #[Test]
    public function other_roles_cannot_view_any_user()
    {
        $roles = ['trainer', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->viewAny($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    #[Test]
    public function user_can_view_their_own_profile()
    {
        $user = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->view($user, $user));
    }

    #[Test]
    public function super_admin_can_view_any_user_profile()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->view($admin, $otherUser));
    }

    #[Test]
    public function project_director_can_view_any_user_profile()
    {
        $director = User::factory()->create(['role' => 'project_director']);
        $otherUser = User::factory()->create(['role' => 'campus_admin']);

        $this->assertTrue($this->policy->view($director, $otherUser));
    }

    #[Test]
    public function user_cannot_view_other_users_profile()
    {
        $user1 = User::factory()->create(['role' => 'campus_admin']);
        $user2 = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->view($user1, $user2));
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    #[Test]
    public function super_admin_can_create_user()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function non_super_admin_cannot_create_user()
    {
        $roles = ['project_director', 'campus_admin', 'trainer', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->create($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    #[Test]
    public function user_can_update_their_own_profile()
    {
        $user = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->update($user, $user));
    }

    #[Test]
    public function super_admin_can_update_any_user()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'campus_admin']);

        $this->assertTrue($this->policy->update($admin, $otherUser));
    }

    #[Test]
    public function user_cannot_update_other_users_profile()
    {
        $user1 = User::factory()->create(['role' => 'campus_admin']);
        $user2 = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->update($user1, $user2));
    }

    #[Test]
    public function project_director_cannot_update_other_users()
    {
        $director = User::factory()->create(['role' => 'project_director']);
        $otherUser = User::factory()->create(['role' => 'campus_admin']);

        $this->assertFalse($this->policy->update($director, $otherUser));
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    #[Test]
    public function super_admin_can_delete_other_users()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->delete($admin, $otherUser));
    }

    #[Test]
    public function super_admin_cannot_delete_themselves()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    #[Test]
    public function non_admin_cannot_delete_users()
    {
        $roles = ['project_director', 'campus_admin', 'trainer', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $otherUser = User::factory()->create(['role' => 'trainer']);
            $this->assertFalse($this->policy->delete($user, $otherUser), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // TOGGLE STATUS
    // =========================================================================

    #[Test]
    public function super_admin_can_toggle_other_users_status()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->toggleStatus($admin, $otherUser));
    }

    #[Test]
    public function super_admin_cannot_toggle_their_own_status()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->assertFalse($this->policy->toggleStatus($admin, $admin));
    }

    #[Test]
    public function non_admin_cannot_toggle_status()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->toggleStatus($user, $otherUser));
    }

    // =========================================================================
    // RESET PASSWORD
    // =========================================================================

    #[Test]
    public function super_admin_can_reset_other_users_password()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->resetPassword($admin, $otherUser));
    }

    #[Test]
    public function super_admin_cannot_reset_their_own_password()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->assertFalse($this->policy->resetPassword($admin, $admin));
    }

    #[Test]
    public function non_admin_cannot_reset_passwords()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertFalse($this->policy->resetPassword($user, $otherUser));
    }

    // =========================================================================
    // MANAGE SETTINGS
    // =========================================================================

    #[Test]
    public function super_admin_can_manage_settings()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->manageSettings($admin));
    }

    #[Test]
    public function non_super_admin_cannot_manage_settings()
    {
        $roles = ['project_director', 'campus_admin', 'trainer', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->manageSettings($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // VIEW AUDIT LOGS
    // =========================================================================

    #[Test]
    public function super_admin_can_view_audit_logs()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewAuditLogs($admin));
    }

    #[Test]
    public function project_director_can_view_audit_logs()
    {
        $director = User::factory()->create(['role' => 'project_director']);

        $this->assertTrue($this->policy->viewAuditLogs($director));
    }

    #[Test]
    public function other_roles_cannot_view_audit_logs()
    {
        $roles = ['campus_admin', 'trainer', 'oep', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->viewAuditLogs($user), "Failed for role: {$role}");
        }
    }

    // =========================================================================
    // GLOBAL SEARCH
    // =========================================================================

    #[Test]
    public function authorized_roles_can_use_global_search()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'oep', 'viewer', 'trainer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->globalSearch($user), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function unauthorized_roles_cannot_use_global_search()
    {
        // Test with a role that doesn't exist in the authorized list
        $user = User::factory()->create(['role' => 'guest']);

        $this->assertFalse($this->policy->globalSearch($user));
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    #[Test]
    public function inactive_super_admin_still_has_permissions()
    {
        // Policy doesn't check is_active, that's handled at middleware level
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => false
        ]);
        $otherUser = User::factory()->create(['role' => 'trainer']);

        $this->assertTrue($this->policy->delete($admin, $otherUser));
    }
}
