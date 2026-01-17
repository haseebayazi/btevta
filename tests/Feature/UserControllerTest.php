<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user for authentication
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function admin_can_view_users_list()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertViewHas('users');
    }

    /** @test */
    public function admin_can_view_create_user_form()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('users.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
    }

    /** @test */
    public function admin_can_create_new_user()
    {
        $this->actingAs($this->admin);

        $campus = Campus::factory()->create();

        $userData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password123',
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
            'phone' => '03001234567',
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'role' => 'campus_admin',
        ]);
    }

    /** @test */
    public function admin_can_view_user_details()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->get(route('users.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.show');
        $response->assertViewHas('user');
    }

    /** @test */
    public function admin_can_update_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['name' => 'Old Name']);

        $response = $this->put(route('users.update', $user), [
            'name' => 'New Name',
            'email' => $user->email,
            'role' => $user->role,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function admin_can_delete_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /** @test */
    public function admin_can_view_settings_page()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings');
        $response->assertViewHas('settings');
    }

    /** @test */
    public function admin_can_update_settings()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('settings.save'), [
            'app_name' => 'BTEVTA System',
            'support_email' => 'support@theleap.org',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_can_view_audit_logs()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.audit-logs'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.audit-logs');
        $response->assertViewHas('logs');
        $response->assertViewHas('users');
    }

    /** @test */
    public function email_must_be_unique()
    {
        $this->actingAs($this->admin);

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function password_must_be_at_least_8_characters()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function oep_id_can_be_assigned_to_user()
    {
        $this->actingAs($this->admin);

        $userData = [
            'name' => 'OEP User',
            'email' => 'oep@example.com',
            'password' => 'password123',
            'role' => 'oep_coordinator',
            'oep_id' => 1,
        ];

        $response = $this->post(route('users.store'), $userData);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'oep@example.com',
            'oep_id' => 1,
        ]);
    }
}
