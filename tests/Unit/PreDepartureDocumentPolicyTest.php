<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\PreDepartureDocument;
use App\Models\DocumentChecklist;
use App\Policies\PreDepartureDocumentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class PreDepartureDocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'campus_admin']);
        Role::create(['name' => 'project_director']);
        Role::create(['name' => 'oep']);
    }

    /** @test */
    public function super_admin_can_view_any_documents()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        
        $candidate = Candidate::factory()->create();
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function campus_admin_can_view_their_campus_candidates_documents()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create(['campus_id' => $campus->id]);
        $user->assignRole('campus_admin');
        
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_view_other_campus_documents()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create(['campus_id' => $campus1->id]);
        $user->assignRole('campus_admin');
        
        $candidate = Candidate::factory()->create(['campus_id' => $campus2->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function super_admin_can_create_documents_anytime()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        
        $candidate = Candidate::factory()->create(['status' => 'screening']); // Not 'new'
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->create($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_create_documents_after_new_status()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create(['campus_id' => $campus->id]);
        $user->assignRole('campus_admin');
        
        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'screening', // Not 'new'
        ]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->create($user, $candidate));
    }

    /** @test */
    public function campus_admin_can_create_documents_during_new_status()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create(['campus_id' => $campus->id]);
        $user->assignRole('campus_admin');
        
        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'new',
        ]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->create($user, $candidate));
    }

    /** @test */
    public function verified_documents_cannot_be_updated_by_campus_admin()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create(['campus_id' => $campus->id]);
        $user->assignRole('campus_admin');
        
        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'new',
        ]);

        $checklist = DocumentChecklist::factory()->create();
        $document = PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
            'verified_at' => now(),
            'verified_by' => 1,
        ]);

        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->update($user, $document));
    }

    /** @test */
    public function super_admin_can_update_verified_documents()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        
        $candidate = Candidate::factory()->create(['status' => 'new']);
        $checklist = DocumentChecklist::factory()->create();
        $document = PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
            'verified_at' => now(),
            'verified_by' => 1,
        ]);

        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->update($user, $document));
    }

    /** @test */
    public function only_authorized_roles_can_verify_documents()
    {
        $user = User::factory()->create();
        $user->assignRole('campus_admin');
        
        $candidate = Candidate::factory()->create();
        $checklist = DocumentChecklist::factory()->create();
        $document = PreDepartureDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
        ]);

        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->verify($user, $document));
    }

    /** @test */
    public function oep_can_view_their_assigned_candidates_documents()
    {
        $user = User::factory()->create();
        $user->assignRole('oep');
        
        $candidate = Candidate::factory()->create(['oep_id' => $user->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function oep_cannot_view_unassigned_candidates_documents()
    {
        $user = User::factory()->create();
        $user->assignRole('oep');
        
        $otherOep = User::factory()->create();
        $candidate = Candidate::factory()->create(['oep_id' => $otherOep->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->viewAny($user, $candidate));
    }
}
