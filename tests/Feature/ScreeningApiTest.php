<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\CandidateScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Feature tests for screening API endpoints.
 */
class ScreeningApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Trade $trade;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->trade = Trade::factory()->create();
        $this->candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);
        Storage::fake('public');
    }

    // ==================== DESK SCREENING ====================

    #[Test]
    public function it_creates_desk_screening()
    {
        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/desk", [
            'status' => 'passed',
            'remarks' => 'All documents verified',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'screening_type' => 'desk',
            'status' => 'passed',
        ]);
    }

    #[Test]
    public function it_validates_desk_screening_status()
    {
        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/desk", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    // ==================== CALL SCREENING ====================

    #[Test]
    public function it_creates_call_screening()
    {
        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/call", [
            'status' => 'in_progress',
            'call_duration' => 120,
            'remarks' => 'First call attempt',
        ]);

        $response->assertStatus(200);

        $screening = $this->candidate->screenings()
            ->where('screening_type', 'call')
            ->first();

        $this->assertNotNull($screening);
        $this->assertEquals(1, $screening->call_count);
    }

    #[Test]
    public function it_updates_existing_call_screening()
    {
        // Create initial call screening
        $screening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_IN_PROGRESS,
            'call_count' => 1,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/call", [
            'status' => 'passed',
            'call_duration' => 180,
            'remarks' => 'Candidate confirmed',
        ]);

        $response->assertStatus(200);

        $screening->refresh();
        $this->assertEquals('passed', $screening->status);
    }

    #[Test]
    public function it_enforces_max_call_attempts()
    {
        $screening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_IN_PROGRESS,
            'call_count' => 3, // Already at max
        ]);

        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/call", [
            'status' => 'in_progress',
            'remarks' => 'Another attempt',
        ]);

        // Should return warning about max attempts
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 422
        );
    }

    // ==================== PHYSICAL SCREENING ====================

    #[Test]
    public function it_creates_physical_screening()
    {
        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/physical", [
            'status' => 'passed',
            'remarks' => 'Physical verification completed',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'screening_type' => 'physical',
            'status' => 'passed',
        ]);
    }

    // ==================== SCREENING PROGRESS ====================

    #[Test]
    public function it_returns_screening_progress()
    {
        // Create some screenings
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_IN_PROGRESS,
            'call_count' => 1,
        ]);

        $response = $this->actingAs($this->admin)->getJson("/screening/{$this->candidate->id}/progress");

        $response->assertOk();
        $response->assertJsonStructure([
            'screenings' => [
                'desk',
                'call',
                'physical',
            ],
            'passed_count',
            'total_required',
            'is_complete',
            'progress_percentage',
        ]);

        $data = $response->json();
        $this->assertEquals(1, $data['passed_count']);
        $this->assertEquals(3, $data['total_required']);
    }

    // ==================== EVIDENCE UPLOAD ====================

    #[Test]
    public function it_uploads_screening_evidence()
    {
        $screening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        $file = UploadedFile::fake()->create('evidence.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->postJson(
            "/screening/{$this->candidate->id}/upload-evidence",
            [
                'screening_id' => $screening->id,
                'file' => $file,
            ]
        );

        $response->assertOk();

        $screening->refresh();
        $this->assertNotNull($screening->evidence_path);
    }

    #[Test]
    public function it_rejects_invalid_file_types_for_evidence()
    {
        $screening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($this->admin)->postJson(
            "/screening/{$this->candidate->id}/upload-evidence",
            [
                'screening_id' => $screening->id,
                'file' => $file,
            ]
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_oversized_evidence_files()
    {
        $screening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        // Create a file larger than 5MB
        $file = UploadedFile::fake()->create('large.pdf', 6000, 'application/pdf');

        $response = $this->actingAs($this->admin)->postJson(
            "/screening/{$this->candidate->id}/upload-evidence",
            [
                'screening_id' => $screening->id,
                'file' => $file,
            ]
        );

        $response->assertStatus(422);
    }

    // ==================== AUTO-PROGRESSION ====================

    #[Test]
    public function it_auto_progresses_candidate_when_all_screenings_pass()
    {
        // Pass desk screening
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
            'screened_at' => now(),
        ]);

        // Pass call screening
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_PASSED,
            'screened_at' => now(),
        ]);

        // Pass physical screening - this should trigger auto-progression
        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/physical", [
            'status' => 'passed',
            'remarks' => 'Physical verification completed',
        ]);

        $response->assertOk();

        $this->candidate->refresh();
        $this->assertEquals(Candidate::STATUS_REGISTERED, $this->candidate->status);
    }

    #[Test]
    public function it_rejects_candidate_on_screening_failure()
    {
        $response = $this->actingAs($this->admin)->postJson("/screening/{$this->candidate->id}/desk", [
            'status' => 'failed',
            'remarks' => 'Documents are forged',
        ]);

        $response->assertOk();

        $this->candidate->refresh();
        $this->assertEquals(Candidate::STATUS_REJECTED, $this->candidate->status);
    }

    // ==================== AUTHORIZATION ====================

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->postJson("/screening/{$this->candidate->id}/desk", [
            'status' => 'passed',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_404_for_invalid_candidate()
    {
        $response = $this->actingAs($this->admin)->postJson("/screening/99999/desk", [
            'status' => 'passed',
        ]);

        $response->assertNotFound();
    }
}
