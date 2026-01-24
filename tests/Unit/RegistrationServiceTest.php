<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\RegistrationService;
use App\Models\Candidate;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RegistrationService::class);
        Storage::fake('public');
    }

    // =========================================================================
    // REQUIRED DOCUMENTS
    // =========================================================================

    #[Test]
    public function it_returns_required_documents_list()
    {
        $documents = $this->service->getRequiredDocuments();

        $this->assertIsArray($documents);
        $this->assertArrayHasKey('cnic', $documents);
        $this->assertArrayHasKey('education', $documents);
        $this->assertArrayHasKey('domicile', $documents);
        $this->assertArrayHasKey('photo', $documents);
        $this->assertArrayHasKey('passport', $documents);
    }

    // =========================================================================
    // DOCUMENT COMPLETENESS
    // =========================================================================

    #[Test]
    public function it_checks_document_completeness_for_candidate_with_no_documents()
    {
        $candidate = Candidate::factory()->create();

        $result = $this->service->checkDocumentCompleteness($candidate);

        $this->assertFalse($result['is_complete']);
        $this->assertEquals(0, $result['completion_percentage']);
        $this->assertArrayHasKey('required', $result);
        $this->assertArrayHasKey('optional', $result);
    }

    #[Test]
    public function it_checks_document_completeness_for_candidate_with_partial_documents()
    {
        $candidate = Candidate::factory()->create();

        // Upload only CNIC and education (2 of 4 required)
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'status' => 'verified',
        ]);

        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'education',
            'status' => 'verified',
        ]);

        $result = $this->service->checkDocumentCompleteness($candidate);

        $this->assertFalse($result['is_complete']);
        $this->assertEquals(50, $result['completion_percentage']);
    }

    #[Test]
    public function it_checks_document_completeness_for_candidate_with_all_required_documents()
    {
        $candidate = Candidate::factory()->create();

        // Upload all 4 required documents
        foreach (['cnic', 'education', 'domicile', 'photo'] as $docType) {
            RegistrationDocument::factory()->create([
                'candidate_id' => $candidate->id,
                'document_type' => $docType,
                'status' => 'verified',
            ]);
        }

        $result = $this->service->checkDocumentCompleteness($candidate);

        $this->assertTrue($result['is_complete']);
        $this->assertEquals(100, $result['completion_percentage']);
    }

    #[Test]
    public function it_marks_incomplete_if_documents_not_verified()
    {
        $candidate = Candidate::factory()->create();

        // Upload all required documents but with pending status
        foreach (['cnic', 'education', 'domicile', 'photo'] as $docType) {
            RegistrationDocument::factory()->create([
                'candidate_id' => $candidate->id,
                'document_type' => $docType,
                'status' => 'pending',
            ]);
        }

        $result = $this->service->checkDocumentCompleteness($candidate);

        $this->assertFalse($result['is_complete']);
    }

    // =========================================================================
    // UNDERTAKING CONTENT
    // =========================================================================

    #[Test]
    public function it_generates_undertaking_content()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Test Candidate',
            'father_name' => 'Test Father',
            'cnic' => '3520112345671',
            'address' => 'Test Address',
            'district' => 'Lahore',
        ]);

        $content = $this->service->generateUndertakingContent($candidate);

        $this->assertStringContainsString('Test Candidate', $content);
        $this->assertStringContainsString('Test Father', $content);
        $this->assertStringContainsString('UNDERTAKING', $content);
        $this->assertStringContainsString('Lahore', $content);
    }

    #[Test]
    public function it_includes_next_of_kin_in_undertaking()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Test Candidate',
            'father_name' => 'Test Father',
        ]);

        NextOfKin::factory()->create([
            'candidate_id' => $candidate->id,
            'name' => 'Guardian Name',
            'relationship' => 'father',
            'phone' => '03001234567',
        ]);

        $candidate->refresh();

        $content = $this->service->generateUndertakingContent($candidate);

        $this->assertStringContainsString('Guardian Name', $content);
    }

    // =========================================================================
    // DOCUMENT VALIDATION
    // =========================================================================

    #[Test]
    public function it_validates_document_file_not_found()
    {
        $result = $this->service->validateDocument('nonexistent/path.pdf', 'cnic');

        $this->assertFalse($result['valid']);
        $this->assertEquals('File not found', $result['reason']);
    }

    #[Test]
    public function it_validates_document_too_small()
    {
        Storage::disk('public')->put('documents/small.pdf', 'x');

        $result = $this->service->validateDocument('documents/small.pdf', 'cnic');

        $this->assertFalse($result['valid']);
        $this->assertEquals('File too small', $result['reason']);
    }

    #[Test]
    public function it_validates_document_success()
    {
        // Create a file larger than 1KB
        Storage::disk('public')->put('documents/valid.pdf', str_repeat('x', 2048));

        $result = $this->service->validateDocument('documents/valid.pdf', 'cnic');

        $this->assertTrue($result['valid']);
    }

    // =========================================================================
    // OEP ALLOCATION
    // =========================================================================

    #[Test]
    public function it_allocates_oep_for_candidate()
    {
        $candidate = Candidate::factory()->create();

        $oep = $this->service->allocateOEP($candidate);

        $this->assertNotEmpty($oep);
        $this->assertIsString($oep);
    }

    // =========================================================================
    // REGISTRATION SUMMARY
    // =========================================================================

    #[Test]
    public function it_creates_registration_summary()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'Test Candidate',
            'registration_date' => now(),
        ]);

        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'status' => 'verified',
        ]);

        NextOfKin::factory()->create([
            'candidate_id' => $candidate->id,
            'name' => 'Next of Kin',
            'relationship' => 'father',
        ]);

        $summary = $this->service->createRegistrationSummary($candidate);

        $this->assertArrayHasKey('candidate_info', $summary);
        $this->assertArrayHasKey('documents', $summary);
        $this->assertArrayHasKey('next_of_kin', $summary);
        $this->assertArrayHasKey('completion', $summary);
        $this->assertEquals('Test Candidate', $summary['candidate_info']['name']);
    }

    #[Test]
    public function it_handles_missing_next_of_kin_in_summary()
    {
        $candidate = Candidate::factory()->create();

        $summary = $this->service->createRegistrationSummary($candidate);

        $this->assertNull($summary['next_of_kin']);
    }

    #[Test]
    public function it_handles_missing_undertaking_in_summary()
    {
        $candidate = Candidate::factory()->create();

        $summary = $this->service->createRegistrationSummary($candidate);

        $this->assertNull($summary['undertaking']);
    }
}
