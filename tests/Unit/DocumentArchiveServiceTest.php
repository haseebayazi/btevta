<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\DocumentArchiveService;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\DocumentArchive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentArchiveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocumentArchiveService();
        Storage::fake('public');
    }

    // =========================================================================
    // DOCUMENT TYPES
    // =========================================================================

    #[Test]
    public function it_returns_all_document_types()
    {
        $types = $this->service->getDocumentTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('cnic', $types);
        $this->assertArrayHasKey('passport', $types);
        $this->assertArrayHasKey('education_certificate', $types);
        $this->assertArrayHasKey('visa_copy', $types);
    }

    // =========================================================================
    // UPLOAD DOCUMENT
    // =========================================================================

    #[Test]
    public function it_can_upload_document()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $file = UploadedFile::fake()->create('cnic.pdf', 500);

        $document = $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'document_name' => 'CNIC Copy',
        ], $file);

        $this->assertNotNull($document);
        $this->assertEquals($candidate->id, $document->candidate_id);
        $this->assertEquals('cnic', $document->document_type);
        $this->assertEquals(1, $document->version);
        $this->assertTrue($document->is_current);
    }

    #[Test]
    public function it_increments_version_on_re_upload()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        // Upload first version
        $file1 = UploadedFile::fake()->create('cnic_v1.pdf', 500);
        $doc1 = $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ], $file1);

        $this->assertEquals(1, $doc1->version);

        // Upload second version
        $file2 = UploadedFile::fake()->create('cnic_v2.pdf', 500);
        $doc2 = $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ], $file2);

        $this->assertEquals(2, $doc2->version);
        $this->assertTrue($doc2->is_current);

        // Verify old version is archived
        $doc1->refresh();
        $this->assertFalse($doc1->is_current);
        $this->assertNotNull($doc1->archived_at);
    }

    // =========================================================================
    // GET DOCUMENT
    // =========================================================================

    #[Test]
    public function it_can_get_document_and_increment_download_count()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 500);

        $document = $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'passport',
        ], $file);

        $initialCount = $document->download_count ?? 0;

        $retrieved = $this->service->getDocument($document->id);

        $this->assertEquals($document->id, $retrieved->id);
        $this->assertEquals($initialCount + 1, $retrieved->download_count);
    }

    // =========================================================================
    // VERSION HISTORY
    // =========================================================================

    #[Test]
    public function it_returns_version_history()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        // Upload multiple versions
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->create("doc_v{$i}.pdf", 500);
            $this->service->uploadDocument([
                'candidate_id' => $candidate->id,
                'document_type' => 'education_certificate',
            ], $file);
        }

        $versions = $this->service->getVersions($candidate->id, 'education_certificate');

        $this->assertCount(3, $versions);
        $this->assertEquals(3, $versions->first()->version);
        $this->assertEquals(1, $versions->last()->version);
    }

    // =========================================================================
    // SEARCH DOCUMENTS
    // =========================================================================

    #[Test]
    public function it_can_search_documents_with_filters()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();

        // Create test documents
        $file = UploadedFile::fake()->create('test.pdf', 500);
        $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'campus_id' => $campus->id,
            'document_type' => 'cnic',
        ], $file);

        $results = $this->service->searchDocuments([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ]);

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    #[Test]
    public function it_filters_by_date_range()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        $file = UploadedFile::fake()->create('test.pdf', 500);
        $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'passport',
        ], $file);

        $results = $this->service->searchDocuments([
            'from_date' => now()->subDay()->toDateString(),
            'to_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    // =========================================================================
    // EXPIRING DOCUMENTS
    // =========================================================================

    #[Test]
    public function it_returns_expiring_documents()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $file = UploadedFile::fake()->create('passport.pdf', 500);

        $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'passport',
            'expiry_date' => now()->addDays(15)->toDateString(),
        ], $file);

        $expiring = $this->service->getExpiringDocuments(30);

        $this->assertGreaterThanOrEqual(1, $expiring->count());
    }

    #[Test]
    public function it_calculates_expiry_urgency_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        // Critical urgency - expires in 5 days
        $file = UploadedFile::fake()->create('urgent.pdf', 500);
        $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'passport',
            'expiry_date' => now()->addDays(5)->toDateString(),
        ], $file);

        $expiring = $this->service->getExpiringDocuments(30);
        $urgentDoc = $expiring->first();

        $this->assertEquals('critical', $urgentDoc['urgency']);
    }

    // =========================================================================
    // EXPIRED DOCUMENTS
    // =========================================================================

    #[Test]
    public function it_returns_expired_documents()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $file = UploadedFile::fake()->create('expired.pdf', 500);

        $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'medical_certificate',
            'expiry_date' => now()->subDays(10)->toDateString(),
        ], $file);

        $expired = $this->service->getExpiredDocuments();

        $this->assertGreaterThanOrEqual(1, $expired->count());
    }

    // =========================================================================
    // MISSING DOCUMENTS
    // =========================================================================

    #[Test]
    public function it_returns_missing_documents_for_candidate()
    {
        $candidate = Candidate::factory()->create(['status' => 'new']);

        $missing = $this->service->getMissingDocuments($candidate->id);

        $this->assertArrayHasKey('missing_documents', $missing);
        $this->assertArrayHasKey('completion_percentage', $missing);
        $this->assertContains('cnic', $missing['missing_documents']);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_document_statistics()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();
        $file = UploadedFile::fake()->create('test.pdf', 500);

        $this->service->uploadDocument([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
        ], $file);

        $stats = $this->service->getStatistics();

        $this->assertArrayHasKey('total_documents', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('total_storage', $stats);
    }

    // =========================================================================
    // BULK DELETE
    // =========================================================================

    #[Test]
    public function it_can_bulk_delete_documents()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        $docIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $file = UploadedFile::fake()->create("doc{$i}.pdf", 500);
            $doc = $this->service->uploadDocument([
                'candidate_id' => $candidate->id,
                'document_type' => "doc_type_{$i}",
            ], $file);
            $docIds[] = $doc->id;
        }

        $result = $this->service->bulkDelete($docIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['deleted']);
    }

    // =========================================================================
    // CLEANUP OLD VERSIONS
    // =========================================================================

    #[Test]
    public function it_can_cleanup_old_versions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $candidate = Candidate::factory()->create();

        // Create multiple versions
        for ($i = 1; $i <= 5; $i++) {
            $file = UploadedFile::fake()->create("doc_v{$i}.pdf", 500);
            $doc = $this->service->uploadDocument([
                'candidate_id' => $candidate->id,
                'document_type' => 'cnic',
            ], $file);

            // Manually archive old versions for testing
            if ($i < 5) {
                $doc->update([
                    'is_current' => false,
                    'archived_at' => now()->subDays(100),
                ]);
            }
        }

        $result = $this->service->cleanupOldVersions(2, 90);

        $this->assertArrayHasKey('cleaned', $result);
    }
}
