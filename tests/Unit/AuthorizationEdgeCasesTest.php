<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\Complaint;
use App\Models\Remittance;
use App\Models\Oep;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Edge case tests for authorization.
 * Tests cross-campus access, role escalation, and inactive user access.
 */
class AuthorizationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // CROSS-CAMPUS ACCESS ATTEMPTS
    // =========================================================================

    #[Test]
    public function campus_admin_cannot_view_other_campus_candidates()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $otherCampusCandidate = Candidate::factory()->create([
            'campus_id' => $campus2->id,
        ]);

        $response = $this->actingAs($campusAdmin)->get("/candidates/{$otherCampusCandidate->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function campus_admin_cannot_edit_other_campus_candidates()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $otherCampusCandidate = Candidate::factory()->create([
            'campus_id' => $campus2->id,
        ]);

        $response = $this->actingAs($campusAdmin)->patch("/candidates/{$otherCampusCandidate->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function campus_admin_cannot_delete_other_campus_candidates()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $otherCampusCandidate = Candidate::factory()->create([
            'campus_id' => $campus2->id,
        ]);

        $response = $this->actingAs($campusAdmin)->delete("/candidates/{$otherCampusCandidate->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function campus_admin_cannot_access_other_campus_batches()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $otherCampusBatch = Batch::factory()->create([
            'campus_id' => $campus2->id,
            'trade_id' => $trade->id,
        ]);

        $response = $this->actingAs($campusAdmin)->get("/batches/{$otherCampusBatch->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function campus_admin_cannot_view_other_campus_complaints()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $candidate = Candidate::factory()->create(['campus_id' => $campus2->id]);
        $complaint = Complaint::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->actingAs($campusAdmin)->get("/complaints/{$complaint->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // ROLE ESCALATION ATTEMPTS
    // =========================================================================

    #[Test]
    public function regular_user_cannot_access_admin_routes()
    {
        $user = User::factory()->create(['role' => 'user']);

        $adminRoutes = [
            ['GET', '/admin/settings'],
            ['GET', '/admin/users'],
            ['POST', '/admin/users'],
            ['GET', '/admin/audit-logs'],
        ];

        foreach ($adminRoutes as [$method, $route]) {
            $response = $this->actingAs($user)->{strtolower($method)}($route);
            $this->assertTrue(
                in_array($response->status(), [403, 404]),
                "User should not access {$method} {$route}"
            );
        }
    }

    #[Test]
    public function viewer_cannot_modify_data()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        // Try to update
        $response = $this->actingAs($viewer)->patch("/candidates/{$candidate->id}", [
            'name' => 'Updated Name',
        ]);
        $response->assertStatus(403);

        // Try to delete
        $response = $this->actingAs($viewer)->delete("/candidates/{$candidate->id}");
        $response->assertStatus(403);
    }

    #[Test]
    public function campus_admin_cannot_create_other_admins()
    {
        $campus = Campus::factory()->create();
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $response = $this->actingAs($campusAdmin)->post('/admin/users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'role' => 'super_admin', // Trying to create super admin
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function user_cannot_change_own_role()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->patch("/profile", [
            'role' => 'super_admin',
        ]);

        // Role should not change
        $user->refresh();
        $this->assertEquals('user', $user->role);
    }

    #[Test]
    public function instructor_cannot_access_administrative_functions()
    {
        $campus = Campus::factory()->create();
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'campus_id' => $campus->id,
        ]);

        $adminFunctions = [
            ['DELETE', '/candidates/1'],
            ['POST', '/admin/settings'],
            ['GET', '/admin/users'],
            ['POST', '/bulk/delete'],
        ];

        foreach ($adminFunctions as [$method, $route]) {
            $response = $this->actingAs($instructor)->{strtolower($method)}($route);
            $this->assertTrue(
                in_array($response->status(), [403, 404]),
                "Instructor should not access {$method} {$route}"
            );
        }
    }

    // =========================================================================
    // OEP ISOLATION
    // =========================================================================

    #[Test]
    public function oep_can_only_see_assigned_candidates()
    {
        $oep1 = Oep::factory()->create();
        $oep2 = Oep::factory()->create();

        $oepUser = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep1->id,
        ]);

        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        // Candidate assigned to this OEP
        $assignedCandidate = Candidate::factory()->create([
            'oep_id' => $oep1->id,
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        // Candidate assigned to different OEP
        $unassignedCandidate = Candidate::factory()->create([
            'oep_id' => $oep2->id,
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        // Should see assigned candidate
        $response = $this->actingAs($oepUser)->get("/candidates/{$assignedCandidate->id}");
        $this->assertTrue(in_array($response->status(), [200, 302]));

        // Should not see unassigned candidate
        $response = $this->actingAs($oepUser)->get("/candidates/{$unassignedCandidate->id}");
        $response->assertStatus(403);
    }

    #[Test]
    public function oep_cannot_access_other_oep_remittances()
    {
        $oep1 = Oep::factory()->create();
        $oep2 = Oep::factory()->create();

        $oepUser = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep1->id,
        ]);

        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $otherOepCandidate = Candidate::factory()->create([
            'oep_id' => $oep2->id,
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        $remittance = Remittance::factory()->create([
            'candidate_id' => $otherOepCandidate->id,
        ]);

        $response = $this->actingAs($oepUser)->get("/remittances/{$remittance->id}");

        $response->assertStatus(403);
    }

    // =========================================================================
    // INACTIVE USER ACCESS
    // =========================================================================

    #[Test]
    public function inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'is_active' => false,
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        // Should be rejected or redirected with error
        $this->assertTrue(
            $response->status() === 422 ||
            $response->session()->has('error') ||
            $response->session()->has('errors')
        );
    }

    #[Test]
    public function deactivated_user_session_is_terminated()
    {
        $user = User::factory()->create(['is_active' => true]);

        // Login
        $this->actingAs($user);

        // Deactivate user
        $user->update(['is_active' => false]);

        // Try to access protected route
        $response = $this->get('/dashboard');

        // Should be redirected to login
        $this->assertTrue(in_array($response->status(), [302, 401, 403]));
    }

    #[Test]
    public function deleted_user_cannot_access_system()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        // Soft delete user
        $user->delete();

        // Try to access as deleted user
        $response = $this->actingAs(User::withTrashed()->find($userId))->get('/dashboard');

        $this->assertTrue(in_array($response->status(), [302, 401, 403]));
    }

    // =========================================================================
    // TOKEN/SESSION EDGE CASES
    // =========================================================================

    #[Test]
    public function api_requests_require_valid_token()
    {
        $response = $this->getJson('/api/candidates');

        $response->assertStatus(401);
    }

    #[Test]
    public function expired_token_is_rejected()
    {
        $user = User::factory()->create();

        // Create expired token (if using Sanctum)
        $token = $user->createToken('test-token', ['*'], now()->subDay())->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/candidates');

        // Should be unauthorized
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    #[Test]
    public function revoked_token_is_rejected()
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        // Revoke all tokens
        $user->tokens()->delete();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/candidates');

        $response->assertStatus(401);
    }

    // =========================================================================
    // RESOURCE OWNERSHIP
    // =========================================================================

    #[Test]
    public function user_cannot_access_other_users_api_tokens()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user2Token = $user2->createToken('test-token');

        $response = $this->actingAs($user1)->get("/api/tokens/{$user2Token->accessToken->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    #[Test]
    public function user_cannot_delete_other_users_tokens()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user2Token = $user2->createToken('test-token');

        $response = $this->actingAs($user1)->delete("/api/tokens/{$user2Token->accessToken->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    // =========================================================================
    // SUPER ADMIN EDGE CASES
    // =========================================================================

    #[Test]
    public function super_admin_can_access_all_campuses()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $campuses = Campus::factory()->count(3)->create();

        foreach ($campuses as $campus) {
            $response = $this->actingAs($superAdmin)->get("/campuses/{$campus->id}");
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function super_admin_can_access_all_candidates()
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $trade = Trade::factory()->create();

        $campuses = Campus::factory()->count(3)->create();

        foreach ($campuses as $campus) {
            $candidate = Candidate::factory()->create([
                'campus_id' => $campus->id,
                'trade_id' => $trade->id,
            ]);

            $response = $this->actingAs($superAdmin)->get("/candidates/{$candidate->id}");
            $response->assertStatus(200);
        }
    }

    #[Test]
    public function only_super_admin_can_access_system_settings()
    {
        $roles = ['admin', 'campus_admin', 'instructor', 'oep', 'viewer', 'user'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user)->get('/admin/settings');

            $this->assertTrue(
                in_array($response->status(), [403, 404]),
                "{$role} should not access system settings"
            );
        }

        // Super admin should have access
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $response = $this->actingAs($superAdmin)->get('/admin/settings');
        $this->assertTrue(in_array($response->status(), [200, 302]));
    }
}
