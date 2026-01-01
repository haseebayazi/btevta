<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\NextOfKin;
use App\Models\RegistrationDocument;
use App\Models\Undertaking;
use App\Policies\NextOfKinPolicy;
use App\Policies\RegistrationDocumentPolicy;
use App\Policies\UndertakingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationPoliciesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // NEXT OF KIN POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_next_of_kin()
    {
        $policy = new NextOfKinPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function campus_admin_can_create_next_of_kin()
    {
        $policy = new NextOfKinPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function campus_admin_can_view_next_of_kin_from_their_campus()
    {
        $policy = new NextOfKinPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $nextOfKin = NextOfKin::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($policy->view($user, $nextOfKin));
    }

    /** @test */
    public function campus_admin_cannot_view_next_of_kin_from_other_campus()
    {
        $policy = new NextOfKinPolicy();
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus2->id]);
        $nextOfKin = NextOfKin::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertFalse($policy->view($user, $nextOfKin));
    }

    /** @test */
    public function viewer_cannot_create_next_of_kin()
    {
        $policy = new NextOfKinPolicy();
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($policy->create($user));
    }

    /** @test */
    public function only_super_admin_can_delete_next_of_kin()
    {
        $policy = new NextOfKinPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $nextOfKin = NextOfKin::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $nextOfKin));
        $this->assertFalse($policy->delete($campusAdmin, $nextOfKin));
    }

    // =========================================================================
    // REGISTRATION DOCUMENT POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_document()
    {
        $policy = new RegistrationDocumentPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function campus_admin_can_upload_documents()
    {
        $policy = new RegistrationDocumentPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function campus_admin_can_view_documents_from_their_campus()
    {
        $policy = new RegistrationDocumentPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $document = RegistrationDocument::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($policy->view($user, $document));
    }

    /** @test */
    public function viewer_can_download_documents()
    {
        $policy = new RegistrationDocumentPolicy();
        $user = User::factory()->create(['role' => 'viewer']);
        $document = RegistrationDocument::factory()->create();

        $this->assertTrue($policy->download($user, $document));
    }

    /** @test */
    public function only_super_admin_and_project_director_can_verify_documents()
    {
        $policy = new RegistrationDocumentPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $document = RegistrationDocument::factory()->create();

        $this->assertTrue($policy->verify($superAdmin, $document));
        $this->assertTrue($policy->verify($admin, $document));
        $this->assertFalse($policy->verify($campusAdmin, $document));
    }

    // =========================================================================
    // UNDERTAKING POLICY
    // =========================================================================

    /** @test */
    public function super_admin_can_view_any_undertaking()
    {
        $policy = new UndertakingPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    /** @test */
    public function campus_admin_can_create_undertaking()
    {
        $policy = new UndertakingPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($policy->create($user));
    }

    /** @test */
    public function campus_admin_can_view_undertakings_from_their_campus()
    {
        $policy = new UndertakingPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $undertaking = Undertaking::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($policy->view($user, $undertaking));
    }

    /** @test */
    public function only_super_admin_can_delete_undertaking()
    {
        $policy = new UndertakingPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $undertaking = Undertaking::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $undertaking));
        $this->assertFalse($policy->delete($admin, $undertaking));
    }

    /** @test */
    public function viewer_can_download_undertaking()
    {
        $policy = new UndertakingPolicy();
        $user = User::factory()->create(['role' => 'viewer']);
        $undertaking = Undertaking::factory()->create();

        $this->assertTrue($policy->download($user, $undertaking));
    }
}
