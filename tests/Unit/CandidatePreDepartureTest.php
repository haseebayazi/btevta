<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidatePreDepartureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for uploaded_by foreign key
        $this->user = \App\Models\User::factory()->create(['role' => 'admin']);

        // Seed document checklists
        $this->seedDocumentChecklists();
    }

    protected function seedDocumentChecklists()
    {
        $checklists = [
            ['name' => 'CNIC', 'code' => 'CNIC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 1, 'is_active' => true],
            ['name' => 'Passport', 'code' => 'PASSPORT', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 2, 'is_active' => true],
            ['name' => 'Domicile', 'code' => 'DOMICILE', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 3, 'is_active' => true],
            ['name' => 'FRC', 'code' => 'FRC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 4, 'is_active' => true],
            ['name' => 'PCC', 'code' => 'PCC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 5, 'is_active' => true],
            ['name' => 'Resume', 'code' => 'RESUME', 'category' => 'optional', 'is_mandatory' => false, 'display_order' => 6, 'is_active' => true],
        ];

        foreach ($checklists as $checklist) {
            DocumentChecklist::create($checklist);
        }
    }

    /** @test */
    public function it_returns_true_when_all_mandatory_documents_are_uploaded()
    {
        $candidate = Candidate::factory()->create();
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
            ]);
        }

        $this->assertTrue($candidate->hasCompletedPreDepartureDocuments());
    }

    /** @test */
    public function it_returns_false_when_mandatory_documents_are_missing()
    {
        $candidate = Candidate::factory()->create();
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        // Upload only first 3 documents (missing 2)
        foreach ($mandatoryChecklists->take(3) as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
            ]);
        }

        $this->assertFalse($candidate->hasCompletedPreDepartureDocuments());
    }

    /** @test */
    public function it_returns_correct_document_completion_status()
    {
        $candidate = Candidate::factory()->create();
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        // Upload 3 out of 5 mandatory documents
        foreach ($mandatoryChecklists->take(3) as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
            ]);
        }

        $status = $candidate->getPreDepartureDocumentStatus();

        $this->assertEquals(5, $status['mandatory_total']);
        $this->assertEquals(3, $status['mandatory_uploaded']);
        $this->assertFalse($status['mandatory_complete']);
        $this->assertFalse($status['is_complete']);
        $this->assertEquals(60, $status['completion_percentage']); // 3/5 = 60%
    }

    /** @test */
    public function it_returns_100_percent_when_all_mandatory_documents_uploaded()
    {
        $candidate = Candidate::factory()->create();
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
            ]);
        }

        $status = $candidate->getPreDepartureDocumentStatus();

        $this->assertEquals(100, $status['completion_percentage']);
        $this->assertTrue($status['is_complete']);
    }

    /** @test */
    public function it_returns_missing_mandatory_documents()
    {
        $candidate = Candidate::factory()->create();
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();

        // Upload only first 2 documents
        foreach ($mandatoryChecklists->take(2) as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->user->id,
            ]);
        }

        $missing = $candidate->getMissingMandatoryDocuments();

        $this->assertCount(3, $missing); // 5 - 2 = 3 missing
        $this->assertTrue($missing->contains('code', 'DOMICILE'));
        $this->assertTrue($missing->contains('code', 'FRC'));
        $this->assertTrue($missing->contains('code', 'PCC'));
    }

    /** @test */
    public function it_has_pre_departure_documents_relationship()
    {
        $candidate = Candidate::factory()->create();
        $checklist = DocumentChecklist::first();

        $document = PreDepartureDocument::create([
            'candidate_id' => $candidate->id,
            'document_checklist_id' => $checklist->id,
            'file_path' => 'test/path.pdf',
            'original_filename' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'uploaded_at' => now(),
            'uploaded_by' => $this->user->id,
        ]);

        $this->assertCount(1, $candidate->preDepartureDocuments);
        $this->assertEquals($document->id, $candidate->preDepartureDocuments->first()->id);
    }
}
