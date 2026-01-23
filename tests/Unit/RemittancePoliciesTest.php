<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Oep;
use App\Models\Candidate;
use App\Models\Remittance;
use App\Models\RemittanceReceipt;
use App\Models\RemittanceUsageBreakdown;
use App\Policies\RemittanceReceiptPolicy;
use App\Policies\RemittanceUsageBreakdownPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemittancePoliciesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // REMITTANCE RECEIPT POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_receipt()
    {
        $policy = new RemittanceReceiptPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function project_director_can_view_any_receipt()
    {
        $policy = new RemittanceReceiptPolicy();
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function oep_can_view_receipts()
    {
        $policy = new RemittanceReceiptPolicy();
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function oep_can_view_receipt_for_assigned_candidates()
    {
        $policy = new RemittanceReceiptPolicy();
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);
        $remittance = Remittance::factory()->create(['candidate_id' => $candidate->id]);
        $receipt = RemittanceReceipt::factory()->create(['remittance_id' => $remittance->id]);

        $this->assertTrue($policy->view($user, $receipt));
    }

    #[Test]
    public function oep_cannot_view_receipt_for_other_candidates()
    {
        $policy = new RemittanceReceiptPolicy();
        $oep1 = Oep::factory()->create();
        $oep2 = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep1->id,
        ]);
        $candidate = Candidate::factory()->create(['oep_id' => $oep2->id]);
        $remittance = Remittance::factory()->create(['candidate_id' => $candidate->id]);
        $receipt = RemittanceReceipt::factory()->create(['remittance_id' => $remittance->id]);

        $this->assertFalse($policy->view($user, $receipt));
    }

    #[Test]
    public function super_admin_and_project_director_can_update_receipts()
    {
        $policy = new RemittanceReceiptPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $receipt = RemittanceReceipt::factory()->create();

        $this->assertTrue($policy->update($superAdmin, $receipt));
        $this->assertTrue($policy->update($admin, $receipt));
    }

    #[Test]
    public function only_super_admin_can_delete_receipts()
    {
        $policy = new RemittanceReceiptPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $receipt = RemittanceReceipt::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $receipt));
        $this->assertFalse($policy->delete($admin, $receipt));
    }

    #[Test]
    public function user_who_can_view_receipt_can_download()
    {
        $policy = new RemittanceReceiptPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);
        $receipt = RemittanceReceipt::factory()->create();

        $this->assertTrue($policy->download($user, $receipt));
    }

    // =========================================================================
    // REMITTANCE USAGE BREAKDOWN POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_breakdown()
    {
        $policy = new RemittanceUsageBreakdownPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function oep_can_create_breakdown()
    {
        $policy = new RemittanceUsageBreakdownPolicy();
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function oep_can_view_breakdown_for_assigned_candidates()
    {
        $policy = new RemittanceUsageBreakdownPolicy();
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);
        $remittance = Remittance::factory()->create(['candidate_id' => $candidate->id]);
        $breakdown = RemittanceUsageBreakdown::factory()->create(['remittance_id' => $remittance->id]);

        $this->assertTrue($policy->view($user, $breakdown));
    }

    #[Test]
    public function super_admin_and_project_director_can_update_breakdown()
    {
        $policy = new RemittanceUsageBreakdownPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $breakdown = RemittanceUsageBreakdown::factory()->create();

        $this->assertTrue($policy->update($superAdmin, $breakdown));
        $this->assertTrue($policy->update($admin, $breakdown));
    }

    #[Test]
    public function only_super_admin_can_delete_breakdown()
    {
        $policy = new RemittanceUsageBreakdownPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $breakdown = RemittanceUsageBreakdown::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $breakdown));
        $this->assertFalse($policy->delete($admin, $breakdown));
    }
}
