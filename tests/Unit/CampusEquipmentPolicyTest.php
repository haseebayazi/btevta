<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\CampusEquipment;
use App\Policies\CampusEquipmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampusEquipmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CampusEquipmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CampusEquipmentPolicy();
    }

    // =========================================================================
    // SUPER ADMIN
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_equipment()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function super_admin_can_view_specific_equipment()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertTrue($this->policy->view($user, $equipment));
    }

    /** @test */
    public function super_admin_can_create_equipment()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function super_admin_can_update_equipment()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertTrue($this->policy->update($user, $equipment));
    }

    /** @test */
    public function super_admin_can_delete_equipment()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertTrue($this->policy->delete($user, $equipment));
    }

    /** @test */
    public function super_admin_can_log_usage()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertTrue($this->policy->logUsage($user, $equipment));
    }

    /** @test */
    public function super_admin_can_view_reports()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewReports($user));
    }

    // =========================================================================
    // PROJECT DIRECTOR
    // =========================================================================

    /** @test */
    public function project_director_can_view_any_equipment()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function project_director_can_create_equipment()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->create($user));
    }

    // =========================================================================
    // CAMPUS ADMIN
    // =========================================================================

    /** @test */
    public function campus_admin_can_view_equipment_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->view($user, $equipment));
    }

    /** @test */
    public function campus_admin_cannot_view_equipment_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->view($user, $equipment));
    }

    /** @test */
    public function campus_admin_can_create_equipment()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function campus_admin_can_update_equipment_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->update($user, $equipment));
    }

    /** @test */
    public function campus_admin_cannot_update_equipment_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->update($user, $equipment));
    }

    /** @test */
    public function campus_admin_cannot_delete_equipment()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus->id]);

        $this->assertFalse($this->policy->delete($user, $equipment));
    }

    /** @test */
    public function campus_admin_can_log_usage_for_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->logUsage($user, $equipment));
    }

    /** @test */
    public function campus_admin_can_view_reports()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($this->policy->viewReports($user));
    }

    // =========================================================================
    // INSTRUCTOR
    // =========================================================================

    /** @test */
    public function instructor_can_log_usage_for_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'instructor',
            'campus_id' => $campus->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->logUsage($user, $equipment));
    }

    /** @test */
    public function instructor_cannot_log_usage_for_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'instructor',
            'campus_id' => $campus1->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->logUsage($user, $equipment));
    }

    // =========================================================================
    // VIEWER
    // =========================================================================

    /** @test */
    public function viewer_can_view_any_equipment()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function viewer_can_view_specific_equipment()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertTrue($this->policy->view($user, $equipment));
    }

    /** @test */
    public function viewer_cannot_create_equipment()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function viewer_cannot_update_equipment()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertFalse($this->policy->update($user, $equipment));
    }

    /** @test */
    public function viewer_cannot_delete_equipment()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $equipment = CampusEquipment::factory()->create();

        $this->assertFalse($this->policy->delete($user, $equipment));
    }
}
