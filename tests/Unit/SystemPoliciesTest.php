<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\CampusKpi;
use App\Models\SystemSetting;
use App\Models\VisaPartner;
use App\Models\PasswordHistory;
use App\Models\EquipmentUsageLog;
use App\Models\CampusEquipment;
use App\Policies\CampusKpiPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\VisaPartnerPolicy;
use App\Policies\PasswordHistoryPolicy;
use App\Policies\EquipmentUsageLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemPoliciesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // SYSTEM SETTING POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_system_settings()
    {
        $policy = new SystemSettingPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function only_super_admin_can_update_system_settings()
    {
        $policy = new SystemSettingPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = SystemSetting::factory()->create();

        $this->assertTrue($policy->update($superAdmin, $setting));
        $this->assertFalse($policy->update($admin, $setting));
    }

    /** @test */
    public function only_super_admin_can_create_system_settings()
    {
        $policy = new SystemSettingPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($policy->create($superAdmin));
        $this->assertFalse($policy->create($admin));
    }

    /** @test */
    public function only_super_admin_can_delete_system_settings()
    {
        $policy = new SystemSettingPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $setting = SystemSetting::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $setting));
        $this->assertFalse($policy->delete($admin, $setting));
    }

    /** @test */
    public function regular_users_cannot_view_system_settings()
    {
        $policy = new SystemSettingPolicy();
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $this->assertFalse($policy->viewAny($campusAdmin));
        $this->assertFalse($policy->viewAny($instructor));
    }

    // =========================================================================
    // VISA PARTNER POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_visa_partner()
    {
        $policy = new VisaPartnerPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function project_director_can_view_any_visa_partner()
    {
        $policy = new VisaPartnerPolicy();
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function only_super_admin_can_create_visa_partner()
    {
        $policy = new VisaPartnerPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($policy->create($superAdmin));
        $this->assertFalse($policy->create($admin));
    }

    /** @test */
    public function super_admin_and_project_director_can_update_visa_partner()
    {
        $policy = new VisaPartnerPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $partner = VisaPartner::factory()->create();

        $this->assertTrue($policy->update($superAdmin, $partner));
        $this->assertTrue($policy->update($admin, $partner));
    }

    /** @test */
    public function only_super_admin_can_delete_visa_partner()
    {
        $policy = new VisaPartnerPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $partner = VisaPartner::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $partner));
        $this->assertFalse($policy->delete($admin, $partner));
    }

    // =========================================================================
    // CAMPUS KPI POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_kpi()
    {
        $policy = new CampusKpiPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function campus_admin_can_view_kpis_from_their_campus()
    {
        $policy = new CampusKpiPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $kpi = CampusKpi::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($policy->view($user, $kpi));
    }

    /** @test */
    public function campus_admin_cannot_view_kpis_from_other_campus()
    {
        $policy = new CampusKpiPolicy();
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $kpi = CampusKpi::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($policy->view($user, $kpi));
    }

    /** @test */
    public function only_super_admin_and_project_director_can_create_kpis()
    {
        $policy = new CampusKpiPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);

        $this->assertTrue($policy->create($superAdmin));
        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($campusAdmin));
    }

    // =========================================================================
    // PASSWORD HISTORY POLICY
    // =========================================================================

    /** @test */
    public function only_super_admin_can_view_password_history()
    {
        $policy = new PasswordHistoryPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertFalse($policy->viewAny($admin));
    }

    /** @test */
    public function nobody_can_create_password_history_directly()
    {
        $policy = new PasswordHistoryPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->assertFalse($policy->create($superAdmin));
    }

    /** @test */
    public function nobody_can_delete_password_history()
    {
        $policy = new PasswordHistoryPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $targetUser = User::factory()->create();
        $history = PasswordHistory::factory()->create(['user_id' => $targetUser->id]);

        $this->assertFalse($policy->delete($superAdmin, $history));
    }

    // =========================================================================
    // EQUIPMENT USAGE LOG POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_usage_log()
    {
        $policy = new EquipmentUsageLogPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function campus_admin_can_view_usage_logs_from_their_campus()
    {
        $policy = new EquipmentUsageLogPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $equipment = CampusEquipment::factory()->create(['campus_id' => $campus->id]);
        $log = EquipmentUsageLog::factory()->create(['equipment_id' => $equipment->id]);

        $this->assertTrue($policy->view($user, $log));
    }

    /** @test */
    public function instructor_can_create_usage_log()
    {
        $policy = new EquipmentUsageLogPolicy();
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function only_super_admin_can_delete_usage_log()
    {
        $policy = new EquipmentUsageLogPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $log = EquipmentUsageLog::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $log));
        $this->assertFalse($policy->delete($campusAdmin, $log));
    }
}
