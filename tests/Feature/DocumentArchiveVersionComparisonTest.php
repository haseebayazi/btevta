<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DocumentArchive;
use App\Models\User;
use App\Models\Campus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class DocumentArchiveVersionComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->campus = Campus::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function it_compares_two_document_versions()
    {
        $originalDoc = DocumentArchive::factory()->create([
            'document_name' => 'Passport v1',
            'document_number' => 'PASS-001',
            'file_size' => 1024000, // 1MB
            'file_type' => 'pdf',
            'description' => 'Original passport',
            'expiry_date' => now()->addYears(1),
            'is_current_version' => false,
            'version' => 1,
        ]);

        $newVersion = DocumentArchive::factory()->create([
            'document_name' => 'Passport v2',
            'document_number' => 'PASS-002',
            'file_size' => 2048000, // 2MB
            'file_type' => 'pdf',
            'description' => 'Renewed passport',
            'expiry_date' => now()->addYears(2),
            'is_current_version' => true,
            'version' => 2,
            'replaces_document_id' => $originalDoc->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $newVersion), [
                'version1_id' => $originalDoc->id,
                'version2_id' => $newVersion->id,
            ]);

        $response->assertStatus(200);
        $response->assertViewIs('document-archive.compare-versions');
        $response->assertViewHas('version1');
        $response->assertViewHas('version2');
        $response->assertViewHas('comparison');
    }

    /** @test */
    public function it_detects_changes_between_versions()
    {
        $originalDoc = DocumentArchive::factory()->create([
            'document_name' => 'CNIC v1',
            'document_number' => 'CNIC-12345',
            'description' => 'Original CNIC',
            'is_current_version' => false,
            'version' => 1,
        ]);

        $newVersion = DocumentArchive::factory()->create([
            'document_name' => 'CNIC v2',
            'document_number' => 'CNIC-67890', // Changed
            'description' => 'Original CNIC', // Same
            'is_current_version' => true,
            'version' => 2,
            'replaces_document_id' => $originalDoc->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $newVersion), [
                'version1_id' => $originalDoc->id,
                'version2_id' => $newVersion->id,
            ]);

        $response->assertStatus(200);

        $comparison = $response->viewData('comparison')['metadata'];

        // Document number changed
        $this->assertTrue($comparison['document_number']['changed']);
        $this->assertEquals('CNIC-12345', $comparison['document_number']['v1']);
        $this->assertEquals('CNIC-67890', $comparison['document_number']['v2']);

        // Description did not change
        $this->assertFalse($comparison['description']['changed']);
        $this->assertEquals('Original CNIC', $comparison['description']['v1']);
        $this->assertEquals('Original CNIC', $comparison['description']['v2']);
    }

    /** @test */
    public function it_rejects_comparison_of_unrelated_documents()
    {
        $doc1 = DocumentArchive::factory()->create([
            'is_current_version' => true,
        ]);

        $doc2 = DocumentArchive::factory()->create([
            'is_current_version' => true,
        ]);

        $doc3 = DocumentArchive::factory()->create([
            'is_current_version' => true,
        ]);

        // Try to compare doc2 and doc3 under doc1's route
        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $doc1), [
                'version1_id' => $doc2->id,
                'version2_id' => $doc3->id,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_allows_comparison_when_version1_is_current_document()
    {
        $currentDoc = DocumentArchive::factory()->create([
            'document_name' => 'Current',
            'is_current_version' => true,
            'version' => 2,
        ]);

        $oldVersion = DocumentArchive::factory()->create([
            'document_name' => 'Old',
            'is_current_version' => false,
            'version' => 1,
            'replaces_document_id' => $currentDoc->id,
        ]);

        // Compare using current doc as base
        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $currentDoc), [
                'version1_id' => $currentDoc->id,
                'version2_id' => $oldVersion->id,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        $smallDoc = DocumentArchive::factory()->create([
            'file_size' => 512, // 512 bytes
            'is_current_version' => false,
        ]);

        $largeDoc = DocumentArchive::factory()->create([
            'file_size' => 5242880, // 5 MB
            'is_current_version' => true,
            'replaces_document_id' => $smallDoc->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $largeDoc), [
                'version1_id' => $smallDoc->id,
                'version2_id' => $largeDoc->id,
            ]);

        $response->assertStatus(200);

        $comparison = $response->viewData('comparison')['metadata'];

        // File sizes should be formatted
        $this->assertStringContainsString('B', $comparison['file_size']['v1']);
        $this->assertStringContainsString('MB', $comparison['file_size']['v2']);
    }

    /** @test */
    public function it_shows_uploader_information()
    {
        $uploader1 = User::factory()->create(['name' => 'John Doe']);
        $uploader2 = User::factory()->create(['name' => 'Jane Smith']);

        $version1 = DocumentArchive::factory()->create([
            'uploaded_by' => $uploader1->id,
            'is_current_version' => false,
        ]);

        $version2 = DocumentArchive::factory()->create([
            'uploaded_by' => $uploader2->id,
            'is_current_version' => true,
            'replaces_document_id' => $version1->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $version2), [
                'version1_id' => $version1->id,
                'version2_id' => $version2->id,
            ]);

        $response->assertStatus(200);

        $comparison = $response->viewData('comparison')['metadata'];

        $this->assertEquals('John Doe', $comparison['uploaded_by']['v1']);
        $this->assertEquals('Jane Smith', $comparison['uploaded_by']['v2']);
        $this->assertTrue($comparison['uploaded_by']['changed']);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $doc = DocumentArchive::factory()->create();

        // Missing version1_id
        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $doc), [
                'version2_id' => $doc->id,
            ]);

        $response->assertSessionHasErrors('version1_id');

        // Missing version2_id
        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $doc), [
                'version1_id' => $doc->id,
            ]);

        $response->assertSessionHasErrors('version2_id');
    }

    /** @test */
    public function it_validates_version_ids_exist()
    {
        $doc = DocumentArchive::factory()->create();

        // Non-existent version IDs
        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $doc), [
                'version1_id' => 99999,
                'version2_id' => 99998,
            ]);

        $response->assertSessionHasErrors(['version1_id', 'version2_id']);
    }

    /** @test */
    public function it_compares_expiry_dates()
    {
        $version1 = DocumentArchive::factory()->create([
            'expiry_date' => now()->addYear(),
            'is_current_version' => false,
        ]);

        $version2 = DocumentArchive::factory()->create([
            'expiry_date' => now()->addYears(2),
            'is_current_version' => true,
            'replaces_document_id' => $version1->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $version2), [
                'version1_id' => $version1->id,
                'version2_id' => $version2->id,
            ]);

        $response->assertStatus(200);

        $comparison = $response->viewData('comparison')['metadata'];

        $this->assertTrue($comparison['expiry_date']['changed']);
        $this->assertNotEquals($comparison['expiry_date']['v1'], $comparison['expiry_date']['v2']);
    }

    /** @test */
    public function unauthorized_user_cannot_compare_versions()
    {
        $viewer = User::factory()->create(['role' => 'viewer']);

        $doc1 = DocumentArchive::factory()->create();
        $doc2 = DocumentArchive::factory()->create([
            'replaces_document_id' => $doc1->id,
        ]);

        $response = $this->actingAs($viewer)
            ->post(route('document-archive.compare-versions', $doc2), [
                'version1_id' => $doc1->id,
                'version2_id' => $doc2->id,
            ]);

        // Should be forbidden by policy
        $response->assertStatus(403);
    }

    /** @test */
    public function it_compares_documents_with_null_values()
    {
        $version1 = DocumentArchive::factory()->create([
            'document_number' => null,
            'description' => null,
            'expiry_date' => null,
            'is_current_version' => false,
        ]);

        $version2 = DocumentArchive::factory()->create([
            'document_number' => 'NEW-123',
            'description' => 'New description',
            'expiry_date' => now()->addYear(),
            'is_current_version' => true,
            'replaces_document_id' => $version1->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('document-archive.compare-versions', $version2), [
                'version1_id' => $version1->id,
                'version2_id' => $version2->id,
            ]);

        $response->assertStatus(200);

        $comparison = $response->viewData('comparison')['metadata'];

        // All should be marked as changed
        $this->assertTrue($comparison['document_number']['changed']);
        $this->assertTrue($comparison['description']['changed']);
        $this->assertTrue($comparison['expiry_date']['changed']);
    }
}
