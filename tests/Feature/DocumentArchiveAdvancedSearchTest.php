<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DocumentArchive;
use App\Models\DocumentTag;
use App\Models\User;
use App\Models\Campus;
use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DocumentArchiveAdvancedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus1;
    protected Campus $campus2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create test users
        $this->campus1 = Campus::factory()->create(['name' => 'Campus 1']);
        $this->campus2 = Campus::factory()->create(['name' => 'Campus 2']);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus1->id,
        ]);
    }

    /** @test */
    public function admin_can_access_advanced_search_page()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search'));

        $response->assertStatus(200);
        $response->assertViewIs('document-archive.advanced-search');
    }

    /** @test */
    public function it_searches_by_keyword_across_multiple_fields()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'John Doe',
            'cnic' => '12345-6789012-3',
        ]);

        DocumentArchive::factory()->create([
            'document_name' => 'Passport Copy',
            'candidate_id' => $candidate->id,
            'is_current_version' => true,
        ]);

        DocumentArchive::factory()->create([
            'document_name' => 'CNIC Copy',
            'document_number' => 'DOC-12345',
            'is_current_version' => true,
        ]);

        // Search by document name
        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['keyword' => 'Passport']));

        $response->assertStatus(200);
        $response->assertSee('Passport Copy');
        $response->assertDontSee('CNIC Copy');

        // Search by document number
        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['keyword' => 'DOC-12345']));

        $response->assertStatus(200);
        $response->assertSee('CNIC Copy');
        $response->assertDontSee('Passport Copy');

        // Search by candidate name
        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['keyword' => 'John Doe']));

        $response->assertStatus(200);
        $response->assertSee('Passport Copy');
    }

    /** @test */
    public function it_filters_by_document_type()
    {
        DocumentArchive::factory()->create([
            'document_type' => 'CNIC',
            'is_current_version' => true,
        ]);

        DocumentArchive::factory()->create([
            'document_type' => 'Passport',
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['document_type' => 'CNIC']));

        $response->assertStatus(200);
        $response->assertSee('CNIC');
        $response->assertDontSee('Passport');
    }

    /** @test */
    public function it_filters_by_tags()
    {
        $urgentTag = DocumentTag::factory()->create(['name' => 'Urgent']);
        $verifiedTag = DocumentTag::factory()->create(['name' => 'Verified']);

        $doc1 = DocumentArchive::factory()->create(['is_current_version' => true]);
        $doc2 = DocumentArchive::factory()->create(['is_current_version' => true]);

        $doc1->tags()->attach($urgentTag);
        $doc2->tags()->attach($verifiedTag);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['tag_ids' => [$urgentTag->id]]));

        $response->assertStatus(200);
        $response->assertSee($doc1->document_name);
        $response->assertDontSee($doc2->document_name);
    }

    /** @test */
    public function it_filters_by_campus()
    {
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus1->id,
            'is_current_version' => true,
        ]);

        DocumentArchive::factory()->create([
            'campus_id' => $this->campus2->id,
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['campus_id' => $this->campus1->id]));

        $response->assertStatus(200);
        // Should only see documents from campus 1
        $this->assertEquals(1, $response->viewData('documents')->count());
    }

    /** @test */
    public function campus_admin_only_sees_their_campus_documents()
    {
        DocumentArchive::factory()->create([
            'campus_id' => $this->campus1->id,
            'document_name' => 'Campus 1 Doc',
            'is_current_version' => true,
        ]);

        DocumentArchive::factory()->create([
            'campus_id' => $this->campus2->id,
            'document_name' => 'Campus 2 Doc',
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->campusAdmin)
            ->get(route('document-archive.advanced-search', ['keyword' => 'Doc']));

        $response->assertStatus(200);
        $response->assertSee('Campus 1 Doc');
        $response->assertDontSee('Campus 2 Doc');
    }

    /** @test */
    public function it_filters_by_upload_date_range()
    {
        $oldDoc = DocumentArchive::factory()->create([
            'uploaded_at' => now()->subDays(60),
            'is_current_version' => true,
        ]);

        $recentDoc = DocumentArchive::factory()->create([
            'uploaded_at' => now()->subDays(5),
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', [
                'upload_date_from' => now()->subDays(10)->format('Y-m-d'),
                'upload_date_to' => now()->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertSee($recentDoc->document_name);
        $response->assertDontSee($oldDoc->document_name);
    }

    /** @test */
    public function it_filters_by_expiry_date_range()
    {
        $expiringSoon = DocumentArchive::factory()->create([
            'expiry_date' => now()->addDays(10),
            'is_current_version' => true,
        ]);

        $expiringLater = DocumentArchive::factory()->create([
            'expiry_date' => now()->addDays(60),
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', [
                'expiry_date_from' => now()->format('Y-m-d'),
                'expiry_date_to' => now()->addDays(30)->format('Y-m-d'),
            ]));

        $response->assertStatus(200);
        $response->assertSee($expiringSoon->document_name);
        $response->assertDontSee($expiringLater->document_name);
    }

    /** @test */
    public function it_filters_by_expired_status()
    {
        $expired = DocumentArchive::factory()->create([
            'expiry_date' => now()->subDays(5),
            'is_current_version' => true,
        ]);

        $valid = DocumentArchive::factory()->create([
            'expiry_date' => now()->addDays(30),
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['is_expired' => true]));

        $response->assertStatus(200);
        $response->assertSee($expired->document_name);
        $response->assertDontSee($valid->document_name);
    }

    /** @test */
    public function it_filters_by_has_expiry()
    {
        $withExpiry = DocumentArchive::factory()->create([
            'expiry_date' => now()->addDays(30),
            'is_current_version' => true,
        ]);

        $withoutExpiry = DocumentArchive::factory()->create([
            'expiry_date' => null,
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['has_expiry' => true]));

        $response->assertStatus(200);
        $response->assertSee($withExpiry->document_name);
        $response->assertDontSee($withoutExpiry->document_name);
    }

    /** @test */
    public function it_filters_by_file_type()
    {
        $pdfDoc = DocumentArchive::factory()->create([
            'file_type' => 'pdf',
            'is_current_version' => true,
        ]);

        $imageDoc = DocumentArchive::factory()->create([
            'file_type' => 'jpg',
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['file_type' => 'pdf']));

        $response->assertStatus(200);
        $response->assertSee($pdfDoc->document_name);
        $response->assertDontSee($imageDoc->document_name);
    }

    /** @test */
    public function it_only_returns_current_version_documents()
    {
        $currentVersion = DocumentArchive::factory()->create([
            'document_name' => 'Current Doc',
            'is_current_version' => true,
        ]);

        $oldVersion = DocumentArchive::factory()->create([
            'document_name' => 'Old Version Doc',
            'is_current_version' => false,
            'replaces_document_id' => $currentVersion->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['keyword' => 'Doc']));

        $response->assertStatus(200);
        $response->assertSee('Current Doc');
        $response->assertDontSee('Old Version Doc');
    }

    /** @test */
    public function it_combines_multiple_filters()
    {
        $tag = DocumentTag::factory()->create(['name' => 'Urgent']);

        $matchingDoc = DocumentArchive::factory()->create([
            'document_type' => 'CNIC',
            'campus_id' => $this->campus1->id,
            'expiry_date' => now()->addDays(15),
            'is_current_version' => true,
        ]);
        $matchingDoc->tags()->attach($tag);

        $nonMatchingDoc = DocumentArchive::factory()->create([
            'document_type' => 'Passport',
            'campus_id' => $this->campus2->id,
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', [
                'document_type' => 'CNIC',
                'campus_id' => $this->campus1->id,
                'tag_ids' => [$tag->id],
                'has_expiry' => true,
            ]));

        $response->assertStatus(200);
        $response->assertSee($matchingDoc->document_name);
        $response->assertDontSee($nonMatchingDoc->document_name);
    }

    /** @test */
    public function it_paginates_results()
    {
        // Create 25 documents (more than page size of 20)
        DocumentArchive::factory()->count(25)->create(['is_current_version' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('document-archive.advanced-search', ['keyword' => 'Document']));

        $response->assertStatus(200);
        $this->assertEquals(20, $response->viewData('documents')->count());
        $this->assertEquals(25, $response->viewData('documents')->total());
    }
}
