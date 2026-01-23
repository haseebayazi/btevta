<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\DocumentArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Feature tests for Document Archive REST API endpoints (Phase 3).
 */
class DocumentArchiveRestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Candidate $candidate;
    protected DocumentArchive $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();

        $this->admin = User::factory()->admin()->create();
        $this->campusAdmin = User::factory()->campusAdmin()->create([
            'campus_id' => $this->campus->id,
        ]);

        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
        ]);

        $this->document = DocumentArchive::factory()->create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'passport',
            'category' => 'identity',
            'status' => 'active',
            'expiry_date' => now()->addDays(45),
        ]);
    }

    #[Test]
    public function it_lists_all_documents_with_authentication()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'document_name',
                        'document_type',
                        'category',
                        'status',
                        'expiry_date',
                    ],
                ],
                'meta',
            ]);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/documents');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_filters_documents_for_campus_admin()
    {
        $otherCampus = Campus::factory()->create();
        $otherCandidate = Candidate::factory()->create(['campus_id' => $otherCampus->id]);
        DocumentArchive::factory()->create([
            'candidate_id' => $otherCandidate->id,
            'campus_id' => $otherCampus->id,
        ]);

        Sanctum::actingAs($this->campusAdmin);

        $response = $this->getJson('/api/v1/documents');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->campus->id, $data[0]['campus']['id']);
    }

    #[Test]
    public function it_shows_specific_document()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/documents/{$this->document->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'document_name',
                    'document_type',
                    'category',
                    'file_path',
                    'expiry_date',
                    'is_expired',
                    'days_until_expiry',
                ],
            ]);
    }

    #[Test]
    public function it_returns_documents_for_specific_candidate()
    {
        DocumentArchive::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/documents/candidate/{$this->candidate->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(4, $data); // 3 + 1 from setUp
        foreach ($data as $doc) {
            $this->assertEquals($this->candidate->id, $doc['candidate']['id']);
        }
    }

    #[Test]
    public function it_returns_document_statistics()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_documents',
                    'by_type',
                    'by_category',
                    'by_status',
                    'expiring_soon_30_days',
                    'expiring_soon_60_days',
                    'expired',
                ],
            ]);
    }

    #[Test]
    public function it_returns_expiring_documents()
    {
        // Create documents expiring in 15 days
        DocumentArchive::factory()->count(2)->create([
            'campus_id' => $this->campus->id,
            'expiry_date' => now()->addDays(15),
        ]);

        // Create document expiring in 60 days (should not be included)
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'expiry_date' => now()->addDays(60),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents/expiring?days=30');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'expiry_window_days',
                ],
            ]);

        $meta = $response->json('meta');
        $this->assertEquals(30, $meta['expiry_window_days']);
    }

    #[Test]
    public function it_returns_expired_documents()
    {
        // Create expired documents
        DocumentArchive::factory()->count(2)->create([
            'campus_id' => $this->campus->id,
            'expiry_date' => now()->subDays(10),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents/expired');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $doc) {
            $this->assertTrue($doc['is_expired']);
        }
    }

    #[Test]
    public function it_searches_documents()
    {
        $uniqueTerm = 'UniquePassport123';
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'document_name' => "Test {$uniqueTerm} Document",
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/documents/search?q={$uniqueTerm}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString($uniqueTerm, $data[0]['document_name']);
    }

    #[Test]
    public function it_returns_download_information()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/documents/{$this->document->id}/download");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'document_id',
                    'document_name',
                    'file_type',
                    'file_size',
                    'download_url',
                ],
            ]);
    }

    #[Test]
    public function it_filters_by_document_type()
    {
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'document_type' => 'visa',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents?document_type=passport');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $doc) {
            $this->assertEquals('passport', $doc['document_type']);
        }
    }

    #[Test]
    public function it_filters_by_category()
    {
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'category' => 'training',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents?category=identity');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $doc) {
            $this->assertEquals('identity', $doc['category']);
        }
    }

    #[Test]
    public function it_filters_by_status()
    {
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'expired',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents?status=active');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $doc) {
            $this->assertEquals('active', $doc['status']);
        }
    }

    #[Test]
    public function it_filters_by_candidate()
    {
        $otherCandidate = Candidate::factory()->create(['campus_id' => $this->campus->id]);
        DocumentArchive::factory()->create([
            'candidate_id' => $otherCandidate->id,
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/documents?candidate_id={$this->candidate->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $doc) {
            $this->assertEquals($this->candidate->id, $doc['candidate']['id']);
        }
    }

    #[Test]
    public function it_filters_by_date_range()
    {
        // Create document from yesterday
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'created_at' => now()->subDays(1),
        ]);

        // Create document from 10 days ago
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'created_at' => now()->subDays(10),
        ]);

        Sanctum::actingAs($this->admin);

        $fromDate = now()->subDays(5)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/documents?from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    #[Test]
    public function it_filters_expiring_soon_documents()
    {
        // Create document expiring in 10 days
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'expiry_date' => now()->addDays(10),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents?expiring_soon=true&expiry_days=30');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    #[Test]
    public function it_validates_search_query_minimum_length()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents/search?q=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    #[Test]
    public function it_supports_pagination()
    {
        DocumentArchive::factory()->count(25)->create([
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(1, $meta['last_page']);
    }

    #[Test]
    public function it_orders_documents_by_latest()
    {
        // Create documents with different timestamps
        $older = DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'created_at' => now()->subDays(5),
        ]);

        $newer = DocumentArchive::factory()->create([
            'campus_id' => $this->campus->id,
            'created_at' => now()->subDays(1),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/documents');

        $response->assertStatus(200);

        $data = $response->json('data');
        // First document should be the newest
        $this->assertEquals($newer->id, $data[0]['id']);
    }
}
