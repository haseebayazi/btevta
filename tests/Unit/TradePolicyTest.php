<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Trade;
use App\Policies\TradePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TradePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TradePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TradePolicy();
    }

    #[Test]
    public function authorized_roles_can_view_any_trade()
    {
        $roles = ['super_admin', 'project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->viewAny($user), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function unauthorized_roles_cannot_view_any_trade()
    {
        $user = User::factory()->create(['role' => 'oep']);
        $this->assertFalse($this->policy->viewAny($user));
    }

    #[Test]
    public function authorized_roles_can_view_trade()
    {
        $trade = Trade::factory()->create();
        $roles = ['super_admin', 'project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->view($user, $trade), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function super_admin_can_create_trade()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function non_super_admin_cannot_create_trade()
    {
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->create($user), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function super_admin_can_update_trade()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $trade = Trade::factory()->create();
        $this->assertTrue($this->policy->update($user, $trade));
    }

    #[Test]
    public function non_super_admin_cannot_update_trade()
    {
        $trade = Trade::factory()->create();
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->update($user, $trade), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function super_admin_can_delete_trade()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $trade = Trade::factory()->create();
        $this->assertTrue($this->policy->delete($user, $trade));
    }

    #[Test]
    public function non_super_admin_cannot_delete_trade()
    {
        $trade = Trade::factory()->create();
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->delete($user, $trade), "Failed for role: {$role}");
        }
    }

    #[Test]
    public function super_admin_can_toggle_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $trade = Trade::factory()->create();
        $this->assertTrue($this->policy->toggleStatus($user, $trade));
    }

    #[Test]
    public function non_super_admin_cannot_toggle_status()
    {
        $trade = Trade::factory()->create();
        $roles = ['project_director', 'campus_admin', 'trainer', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse($this->policy->toggleStatus($user, $trade), "Failed for role: {$role}");
        }
    }

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
}
