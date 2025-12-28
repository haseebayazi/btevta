<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Oep;
use App\Policies\CandidatePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidatePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CandidatePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CandidatePolicy();
    }

    // =========================================================================
    // SUPER ADMIN
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function super_admin_can_view_specific_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create();

        $this->assertTrue($this->policy->view($user, $candidate));
    }

    /** @test */
    public function super_admin_can_create_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function super_admin_can_update_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create();

        $this->assertTrue($this->policy->update($user, $candidate));
    }

    /** @test */
    public function super_admin_can_delete_candidate()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create();

        $this->assertTrue($this->policy->delete($user, $candidate));
    }

    // =========================================================================
    // ADMIN
    // =========================================================================

    /** @test */
    public function admin_can_view_any_candidate()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function admin_can_create_candidate()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function admin_can_update_any_candidate()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $candidate = Candidate::factory()->create();

        $this->assertTrue($this->policy->update($user, $candidate));
    }

    // =========================================================================
    // CAMPUS ADMIN
    // =========================================================================

    /** @test */
    public function campus_admin_can_view_candidates_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->view($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_view_candidates_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->view($user, $candidate));
    }

    /** @test */
    public function campus_admin_can_create_candidate()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function campus_admin_can_update_candidates_from_their_campus()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);

        $this->assertTrue($this->policy->update($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_update_candidates_from_other_campus()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus2->id]);

        $this->assertFalse($this->policy->update($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_delete_candidates()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);

        $this->assertFalse($this->policy->delete($user, $candidate));
    }

    // =========================================================================
    // OEP
    // =========================================================================

    /** @test */
    public function oep_can_view_candidates_assigned_to_them()
    {
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);

        $this->assertTrue($this->policy->view($user, $candidate));
    }

    /** @test */
    public function oep_cannot_view_candidates_not_assigned_to_them()
    {
        $oep1 = Oep::factory()->create();
        $oep2 = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep1->id,
        ]);
        $candidate = Candidate::factory()->create(['oep_id' => $oep2->id]);

        $this->assertFalse($this->policy->view($user, $candidate));
    }

    /** @test */
    public function oep_cannot_create_candidates()
    {
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function oep_can_update_limited_fields_for_assigned_candidates()
    {
        $oep = Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id,
        ]);
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);

        // OEP should have limited update access (e.g., post-departure info)
        $this->assertTrue($this->policy->updateDepartureInfo($user, $candidate));
    }

    // =========================================================================
    // INSTRUCTOR
    // =========================================================================

    /** @test */
    public function instructor_can_view_candidates_in_their_batch()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $batch = \App\Models\Batch::factory()->create(['trainer_id' => $user->id]);
        $candidate = Candidate::factory()->create(['batch_id' => $batch->id]);

        $this->assertTrue($this->policy->view($user, $candidate));
    }

    /** @test */
    public function instructor_cannot_view_candidates_in_other_batches()
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $otherBatch = \App\Models\Batch::factory()->create();
        $candidate = Candidate::factory()->create(['batch_id' => $otherBatch->id]);

        $this->assertFalse($this->policy->view($user, $candidate));
    }

    /** @test */
    public function instructor_cannot_create_candidates()
    {
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertFalse($this->policy->create($user));
    }

    // =========================================================================
    // VIEWER
    // =========================================================================

    /** @test */
    public function viewer_can_view_candidates()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $candidate = Candidate::factory()->create();

        $this->assertTrue($this->policy->view($user, $candidate));
    }

    /** @test */
    public function viewer_cannot_create_candidates()
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function viewer_cannot_update_candidates()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $candidate = Candidate::factory()->create();

        $this->assertFalse($this->policy->update($user, $candidate));
    }

    /** @test */
    public function viewer_cannot_delete_candidates()
    {
        $user = User::factory()->create(['role' => 'viewer']);
        $candidate = Candidate::factory()->create();

        $this->assertFalse($this->policy->delete($user, $candidate));
    }

    // =========================================================================
    // INACTIVE USER
    // =========================================================================

    /** @test */
    public function inactive_user_cannot_perform_any_action()
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);
        $candidate = Candidate::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
        $this->assertFalse($this->policy->view($user, $candidate));
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $candidate));
        $this->assertFalse($this->policy->delete($user, $candidate));
    }
}
