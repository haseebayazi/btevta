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

class PreDepartureDocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function super_admin_can_view_any_documents()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        
        $candidate = Candidate::factory()->create();
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function campus_admin_can_view_their_campus_candidates_documents()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id
        ]);
        
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_view_other_campus_documents()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id
        ]);
        
        $candidate = Candidate::factory()->create(['campus_id' => $campus2->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function super_admin_can_create_documents_anytime()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        
        $candidate = Candidate::factory()->create(['status' => 'screening']); // Not 'new'
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->create($user, $candidate));
    }

    /** @test */
    public function campus_admin_cannot_create_documents_after_new_status()
    {
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id
        ]);
        
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
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id
        ]);
        
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
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id
        ]);
        
        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'status' => 'new',
        ]);

        $checklist = DocumentChecklist::create([
            'name' => 'CNIC',
            'code' => 'cnic',
            'category' => 'mandatory',
            'is_mandatory' => true,
            'description' => 'CNIC copy',
        ]);
        $document = PreDepartureDocument::create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
            'file_path' => 'test/path.pdf',
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'verified_at' => now(),
            'verified_by' => 1,
        ]);

        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->update($user, $document));
    }

    /** @test */
    public function super_admin_can_update_verified_documents()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        
        $candidate = Candidate::factory()->create(['status' => 'new']);
        $checklist = DocumentChecklist::create([
            'name' => 'Passport',
            'code' => 'passport',
            'category' => 'mandatory',
            'is_mandatory' => true,
            'description' => 'Passport copy',
        ]);
        $document = PreDepartureDocument::create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
            'file_path' => 'test/passport.pdf',
            'original_filename' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'verified_at' => now(),
            'verified_by' => 1,
        ]);

        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->update($user, $document));
    }

    /** @test */
    public function only_authorized_roles_can_verify_documents()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);
        
        $candidate = Candidate::factory()->create();
        $checklist = DocumentChecklist::create([
            'name' => 'Medical Certificate',
            'code' => 'medical',
            'category' => 'mandatory',
            'is_mandatory' => true,
            'description' => 'Medical certificate',
        ]);
        $document = PreDepartureDocument::create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
            'file_path' => 'test/medical.pdf',
            'original_filename' => 'medical.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->verify($user, $document));
    }

    /** @test */
    public function oep_can_view_their_assigned_candidates_documents()
    {
        $oep = \App\Models\Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep->id
        ]);
        
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertTrue($policy->viewAny($user, $candidate));
    }

    /** @test */
    public function oep_cannot_view_unassigned_candidates_documents()
    {
        $oep1 = \App\Models\Oep::factory()->create();
        $oep2 = \App\Models\Oep::factory()->create();
        $user = User::factory()->create([
            'role' => 'oep',
            'oep_id' => $oep1->id
        ]);
        
        $candidate = Candidate::factory()->create(['oep_id' => $oep2->id]);
        $policy = new PreDepartureDocumentPolicy();

        $this->assertFalse($policy->viewAny($user, $candidate));
    }
}
