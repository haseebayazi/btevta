<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\CandidateScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Feature tests for Screening REST API endpoints (Phase 3).
 */
class ScreeningRestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Oep $oep;
    protected Candidate $candidate;
    protected CandidateScreening $screening;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->oep = Oep::factory()->create();

        $this->admin = User::factory()->admin()->create();
        $this->campusAdmin = User::factory()->campusAdmin()->create([
            'campus_id' => $this->campus->id,
        ]);

        $this->candidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => $this->campus->id,
            'oep_id' => $this->oep->id,
        ]);

        $this->screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
            'outcome' => 'eligible',
        ]);
    }

    /** @test */
    public function it_lists_all_screenings_with_authentication()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/screenings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'candidate',
                        'screening_date',
                        'outcome',
                        'status',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_screening_list()
    {
        $response = $this->getJson('/api/v1/screenings');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_filters_screenings_by_campus_for_campus_admin()
    {
        // Create screening for different campus
        $otherCampus = Campus::factory()->create();
        $otherCandidate = Candidate::factory()->create([
            'campus_id' => $otherCampus->id,
        ]);
        CandidateScreening::factory()->create([
            'candidate_id' => $otherCandidate->id,
        ]);

        Sanctum::actingAs($this->campusAdmin);

        $response = $this->getJson('/api/v1/screenings');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->campus->id, $data[0]['candidate']['campus']['id']);
    }

    /** @test */
    public function it_shows_specific_screening()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/screenings/{$this->screening->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->screening->id,
                    'outcome' => 'eligible',
                ],
            ]);
    }

    /** @test */
    public function it_returns_screenings_by_candidate()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/screenings/candidate/{$this->candidate->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'candidate' => [
                    'id' => $this->candidate->id,
                    'name' => $this->candidate->name,
                ],
            ]);
    }

    /** @test */
    public function it_returns_screening_statistics()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/screenings/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_screenings',
                    'pending',
                    'completed',
                    'by_outcome',
                    'eligibility_rate',
                    'completed_today',
                    'completed_this_week',
                    'completed_this_month',
                ],
            ]);
    }

    /** @test */
    public function it_returns_pending_screenings()
    {
        // Create candidate in screening status
        $pendingCandidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/screenings/pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    /** @test */
    public function it_creates_screening_record()
    {
        $newCandidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/screenings', [
            'candidate_id' => $newCandidate->id,
            'screening_date' => now()->format('Y-m-d'),
            'screener_name' => 'Test Screener',
            'contact_method' => 'phone',
            'outcome' => 'eligible',
            'remarks' => 'Candidate is qualified',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Screening record created successfully',
            ]);

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $newCandidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        // Check candidate status updated
        $this->assertDatabaseHas('candidates', [
            'id' => $newCandidate->id,
            'status' => 'registration', // eligible outcome moves to registration
        ]);
    }

    /** @test */
    public function it_updates_screening_record()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/screenings/{$this->screening->id}", [
            'outcome' => 'not_eligible',
            'remarks' => 'Updated remarks',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Screening record updated successfully',
            ]);

        $this->assertDatabaseHas('candidate_screenings', [
            'id' => $this->screening->id,
            'outcome' => 'not_eligible',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/screenings', [
            'candidate_id' => $this->candidate->id,
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'screening_date',
                'screener_name',
                'contact_method',
                'outcome',
            ]);
    }

    /** @test */
    public function it_filters_screenings_by_status()
    {
        CandidateScreening::factory()->create([
            'candidate_id' => Candidate::factory()->create(['campus_id' => $this->campus->id]),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/screenings?status=completed');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $screening) {
            $this->assertEquals('completed', $screening['status']);
        }
    }

    /** @test */
    public function it_filters_screenings_by_outcome()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/screenings?outcome=eligible');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $screening) {
            $this->assertEquals('eligible', $screening['outcome']);
        }
    }

    /** @test */
    public function it_filters_screenings_by_date_range()
    {
        Sanctum::actingAs($this->admin);

        $fromDate = now()->subDays(7)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/screenings?from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_supports_pagination()
    {
        // Create multiple screenings
        CandidateScreening::factory()->count(25)->create([
            'candidate_id' => function() {
                return Candidate::factory()->create(['campus_id' => $this->campus->id])->id;
            },
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/screenings?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(1, $meta['last_page']);
    }
}
