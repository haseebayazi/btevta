<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Departure;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepartureApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($this->user, ['*']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    #[Test]
    public function it_returns_paginated_departures()
    {
        $candidates = Candidate::factory()->count(15)->create(['status' => 'departed']);
        foreach ($candidates as $candidate) {
            Departure::factory()->create(['candidate_id' => $candidate->id]);
        }

        $response = $this->getJson('/api/v1/departures');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'candidate_id', 'departure_date', 'candidate']
                ],
                'meta' => ['current_page', 'per_page', 'total']
            ]);
    }

    #[Test]
    public function it_filters_departures_by_date_range()
    {
        $candidate1 = Candidate::factory()->create();
        $candidate2 = Candidate::factory()->create();
        $candidate3 = Candidate::factory()->create();

        Departure::factory()->create([
            'candidate_id' => $candidate1->id,
            'departure_date' => '2024-01-15',
        ]);
        Departure::factory()->create([
            'candidate_id' => $candidate2->id,
            'departure_date' => '2024-02-15',
        ]);
        Departure::factory()->create([
            'candidate_id' => $candidate3->id,
            'departure_date' => '2024-03-15',
        ]);

        $response = $this->getJson('/api/v1/departures?from_date=2024-01-01&to_date=2024-02-28');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    #[Test]
    public function it_returns_single_departure()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->getJson("/api/v1/departures/{$departure->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'candidate_id', 'departure_date', 'candidate']
            ]);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_departure()
    {
        $response = $this->getJson('/api/v1/departures/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // BY CANDIDATE
    // =========================================================================

    #[Test]
    public function it_returns_departure_by_candidate()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->getJson("/api/v1/departures/candidate/{$candidate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $departure->id);
    }

    #[Test]
    public function it_returns_404_for_candidate_without_departure()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->getJson("/api/v1/departures/candidate/{$candidate->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    #[Test]
    public function it_creates_a_departure()
    {
        $candidate = Candidate::factory()->create(['status' => 'ready']);

        $data = [
            'candidate_id' => $candidate->id,
            'departure_date' => '2024-06-15',
            'flight_number' => 'PK-301',
            'destination_country' => 'Saudi Arabia',
        ];

        $response = $this->postJson('/api/v1/departures', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('departures', [
            'candidate_id' => $candidate->id,
            'flight_number' => 'PK-301',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_for_departure()
    {
        $response = $this->postJson('/api/v1/departures', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_id', 'departure_date']);
    }

    #[Test]
    public function it_prevents_duplicate_departure_for_candidate()
    {
        $candidate = Candidate::factory()->create();
        Departure::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->postJson('/api/v1/departures', [
            'candidate_id' => $candidate->id,
            'departure_date' => '2024-06-20',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    #[Test]
    public function it_updates_departure_details()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->putJson("/api/v1/departures/{$departure->id}", [
            'iqama_number' => 'IQ123456789',
            'absher_registered' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.iqama_number', 'IQ123456789');
    }

    #[Test]
    public function it_updates_salary_information()
    {
        $candidate = Candidate::factory()->create();
        $departure = Departure::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->putJson("/api/v1/departures/{$departure->id}", [
            'first_salary_date' => '2024-07-01',
            'salary_amount' => 2500.00,
            'salary_confirmed' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.salary_confirmed', true);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_departure_statistics()
    {
        $candidates = Candidate::factory()->count(20)->create();
        foreach ($candidates as $index => $candidate) {
            Departure::factory()->create([
                'candidate_id' => $candidate->id,
                'briefing_completed' => $index % 2 == 0,
                'absher_registered' => $index % 3 == 0,
                'salary_confirmed' => $index % 4 == 0,
            ]);
        }

        $response = $this->getJson('/api/v1/departures/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_departed',
                    'briefing_completed',
                    'absher_registered',
                    'salary_confirmed',
                    'by_month',
                ]
            ]);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function unauthenticated_user_cannot_access_departures()
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/departures');

        $response->assertStatus(401);
    }
}
