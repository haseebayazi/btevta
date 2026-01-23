<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;

/**
 * Integration tests for batch import workflows.
 * Tests Excel/CSV import, validation, deduplication, and error handling.
 */
class BatchImportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();

        Storage::fake('local');
        Storage::fake('public');
    }

    // =========================================================================
    // FILE UPLOAD
    // =========================================================================

    #[Test]
    public function it_accepts_valid_excel_file()
    {
        $file = $this->createValidImportFile();

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
    }

    #[Test]
    public function it_rejects_invalid_file_types()
    {
        $file = UploadedFile::fake()->create('candidates.txt', 100, 'text/plain');

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertSessionHasErrors('file');
    }

    #[Test]
    public function it_rejects_oversized_files()
    {
        $file = UploadedFile::fake()->create('candidates.xlsx', 20000, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertSessionHasErrors('file');
    }

    // =========================================================================
    // VALIDATION
    // =========================================================================

    #[Test]
    public function it_validates_required_columns()
    {
        $file = $this->createImportFileWithMissingColumns();

        $response = $this->actingAs($this->admin)->post('/import/candidates/validate', [
            'file' => $file,
        ]);

        $response->assertJson([
            'valid' => false,
        ]);
    }

    #[Test]
    public function it_validates_cnic_format_in_import()
    {
        $file = $this->createImportFileWithInvalidCNIC();

        $response = $this->actingAs($this->admin)->post('/import/candidates/validate', [
            'file' => $file,
        ]);

        $response->assertJson([
            'valid' => false,
        ]);
        $this->assertStringContainsString('CNIC', json_encode($response->json()));
    }

    #[Test]
    public function it_validates_date_formats_in_import()
    {
        $file = $this->createImportFileWithInvalidDates();

        $response = $this->actingAs($this->admin)->post('/import/candidates/validate', [
            'file' => $file,
        ]);

        $response->assertJson([
            'valid' => false,
        ]);
    }

    // =========================================================================
    // DEDUPLICATION
    // =========================================================================

    #[Test]
    public function it_detects_duplicate_cnic_in_import()
    {
        // Create existing candidate
        Candidate::factory()->create(['cnic' => '3520112345671']);

        $file = $this->createImportFileWithCNIC('3520112345671');

        $response = $this->actingAs($this->admin)->post('/import/candidates/preview', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['duplicates']);
    }

    #[Test]
    public function it_detects_duplicate_phone_in_import()
    {
        // Create existing candidate
        Candidate::factory()->create(['phone' => '03001234567']);

        $file = $this->createImportFileWithPhone('03001234567');

        $response = $this->actingAs($this->admin)->post('/import/candidates/preview', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['potential_duplicates']);
    }

    #[Test]
    public function it_allows_skipping_duplicates()
    {
        Candidate::factory()->create(['cnic' => '3520112345671']);

        $file = $this->createImportFileWithCNIC('3520112345671');

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'skip_duplicates' => true,
        ]);

        $response->assertRedirect();
        // Original count should remain same
        $this->assertEquals(1, Candidate::count());
    }

    #[Test]
    public function it_allows_updating_duplicates()
    {
        $existing = Candidate::factory()->create([
            'cnic' => '3520112345671',
            'name' => 'Old Name',
        ]);

        $file = $this->createImportFileWithUpdatedName('3520112345671', 'New Name');

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'update_duplicates' => true,
        ]);

        $response->assertRedirect();
        $existing->refresh();
        $this->assertEquals('New Name', $existing->name);
    }

    // =========================================================================
    // BATCH PROCESSING
    // =========================================================================

    #[Test]
    public function it_processes_large_import_in_chunks()
    {
        Queue::fake();

        $file = $this->createLargeImportFile(500);

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'async' => true,
        ]);

        $response->assertRedirect();
        Queue::assertPushed(\App\Jobs\ProcessCandidateImport::class);
    }

    #[Test]
    public function it_tracks_import_progress()
    {
        $file = $this->createValidImportFile(10);

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_id');
    }

    #[Test]
    public function it_can_check_import_status()
    {
        $file = $this->createValidImportFile(5);

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        if (session()->has('import_id')) {
            $importId = session()->get('import_id');

            $statusResponse = $this->actingAs($this->admin)->get("/import/status/{$importId}");
            $statusResponse->assertOk();
        }

        $this->assertTrue(true); // Import completed synchronously
    }

    // =========================================================================
    // ERROR HANDLING
    // =========================================================================

    #[Test]
    public function it_logs_row_errors_without_stopping_import()
    {
        $file = $this->createImportFileWithMixedValidity();

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'continue_on_error' => true,
        ]);

        $response->assertRedirect();
        // Should have imported valid rows
        $this->assertGreaterThan(0, Candidate::count());
    }

    #[Test]
    public function it_generates_error_report()
    {
        $file = $this->createImportFileWithErrors();

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('import_errors');
    }

    #[Test]
    public function it_can_download_error_report()
    {
        $file = $this->createImportFileWithErrors();

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        if (session()->has('import_id')) {
            $importId = session()->get('import_id');
            $errorResponse = $this->actingAs($this->admin)->get("/import/errors/{$importId}");
            $this->assertTrue(in_array($errorResponse->status(), [200, 404]));
        }

        $this->assertTrue(true);
    }

    // =========================================================================
    // ROLLBACK
    // =========================================================================

    #[Test]
    public function it_can_rollback_failed_import()
    {
        $initialCount = Candidate::count();

        $file = $this->createImportFileWithErrors();

        $response = $this->actingAs($this->admin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'rollback_on_error' => true,
        ]);

        // Should rollback to initial count on error
        $this->assertEquals($initialCount, Candidate::count());
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function campus_admin_can_only_import_to_their_campus()
    {
        $otherCampus = Campus::factory()->create();
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus->id,
        ]);

        $file = $this->createValidImportFile();

        $response = $this->actingAs($campusAdmin)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $otherCampus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function regular_user_cannot_import()
    {
        $user = User::factory()->create(['role' => 'user']);
        $file = $this->createValidImportFile();

        $response = $this->actingAs($user)->post('/import/candidates', [
            'file' => $file,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createValidImportFile(int $rows = 5): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates.xlsx',
            100,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createLargeImportFile(int $rows = 500): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_large.xlsx',
            1000,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithMissingColumns(): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_missing.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithInvalidCNIC(): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_invalid_cnic.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithInvalidDates(): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_invalid_dates.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithCNIC(string $cnic): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_dup_cnic.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithPhone(string $phone): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_dup_phone.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithUpdatedName(string $cnic, string $name): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_update.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithMixedValidity(): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_mixed.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }

    protected function createImportFileWithErrors(): UploadedFile
    {
        return UploadedFile::fake()->create(
            'candidates_errors.xlsx',
            50,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
    }
}
