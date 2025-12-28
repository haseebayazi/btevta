<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateApiControllerTest extends TestCase
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

    /** @test */
    public function it_returns_paginated_candidates()
    {
        Candidate::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/candidates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'cnic', 'status']
                ],
                'meta' => ['current_page', 'per_page', 'total']
            ]);
    }

    /** @test */
    public function it_filters_candidates_by_status()
    {
        Candidate::factory()->count(5)->create(['status' => 'screening']);
        Candidate::factory()->count(3)->create(['status' => 'training']);

        $response = $this->getJson('/api/v1/candidates?status=screening');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_filters_candidates_by_campus()
    {
        $campus = Campus::factory()->create();
        Candidate::factory()->count(4)->create(['campus_id' => $campus->id]);
        Candidate::factory()->count(3)->create();

        $response = $this->getJson("/api/v1/candidates?campus_id={$campus->id}");

        $response->assertStatus(200);
        $this->assertCount(4, $response->json('data'));
    }

    /** @test */
    public function it_searches_candidates_by_name()
    {
        Candidate::factory()->create(['name' => 'Ahmed Khan']);
        Candidate::factory()->create(['name' => 'Muhammad Ali']);
        Candidate::factory()->create(['name' => 'Ahmed Hassan']);

        $response = $this->getJson('/api/v1/candidates?search=Ahmed');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_searches_candidates_by_cnic()
    {
        Candidate::factory()->create(['cnic' => '1234567890123']);
        Candidate::factory()->create(['cnic' => '9876543210123']);

        $response = $this->getJson('/api/v1/candidates?search=12345');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function it_returns_single_candidate()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->getJson("/api/v1/candidates/{$candidate->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'cnic', 'status', 'campus', 'trade']
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_candidate()
    {
        $response = $this->getJson('/api/v1/candidates/99999');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_includes_relationships_in_show()
    {
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();
        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        $response = $this->getJson("/api/v1/candidates/{$candidate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.campus.id', $campus->id)
            ->assertJsonPath('data.trade.id', $trade->id);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function it_creates_a_new_candidate()
    {
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $data = [
            'name' => 'New Candidate',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
            'gender' => 'male',
            'date_of_birth' => '1995-01-15',
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ];

        $response = $this->postJson('/api/v1/candidates', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New Candidate');
        $this->assertDatabaseHas('candidates', ['cnic' => '1234567890123']);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/candidates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'cnic', 'phone']);
    }

    /** @test */
    public function it_validates_unique_cnic()
    {
        Candidate::factory()->create(['cnic' => '1234567890123']);

        $response = $this->postJson('/api/v1/candidates', [
            'name' => 'Test',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cnic']);
    }

    /** @test */
    public function it_validates_cnic_format()
    {
        $response = $this->postJson('/api/v1/candidates', [
            'name' => 'Test',
            'cnic' => 'invalid-cnic',
            'phone' => '03001234567',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cnic']);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function it_updates_a_candidate()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->putJson("/api/v1/candidates/{$candidate->id}", [
            'name' => 'Updated Name',
            'phone' => '03009876543',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    /** @test */
    public function it_validates_update_data()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->putJson("/api/v1/candidates/{$candidate->id}", [
            'phone' => 'not-a-phone',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function it_soft_deletes_a_candidate()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->deleteJson("/api/v1/candidates/{$candidate->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('candidates', ['id' => $candidate->id]);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /** @test */
    public function it_returns_candidate_statistics()
    {
        Candidate::factory()->count(10)->create(['status' => 'new']);
        Candidate::factory()->count(5)->create(['status' => 'screening']);
        Candidate::factory()->count(8)->create(['status' => 'training']);
        Candidate::factory()->count(3)->create(['status' => 'departed']);

        $response = $this->getJson('/api/v1/candidates/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'by_status',
                    'by_campus',
                    'by_trade',
                ]
            ]);
        $this->assertEquals(26, $response->json('data.total'));
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_api()
    {
        // Clear the authenticated user
        Sanctum::actingAs(User::factory()->create(), []);
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/candidates');

        $response->assertStatus(401);
    }

    /** @test */
    public function campus_admin_only_sees_their_campus_candidates()
    {
        $campus = Campus::factory()->create();
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        Sanctum::actingAs($campusAdmin, ['*']);

        Candidate::factory()->count(3)->create(['campus_id' => $campus->id]);
        Candidate::factory()->count(5)->create(); // Other campus

        $response = $this->getJson('/api/v1/candidates');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    // =========================================================================
    // PAGINATION LIMITS
    // =========================================================================

    /** @test */
    public function it_respects_per_page_parameter()
    {
        Candidate::factory()->count(50)->create();

        $response = $this->getJson('/api/v1/candidates?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(10, $response->json('meta.per_page'));
    }

    /** @test */
    public function it_limits_maximum_per_page()
    {
        Candidate::factory()->count(200)->create();

        $response = $this->getJson('/api/v1/candidates?per_page=500');

        $response->assertStatus(200);
        // Should be capped at max (e.g., 100)
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }
}
