<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Correspondence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

/**
 * Feature tests for Correspondence REST API endpoints (Phase 3).
 */
class CorrespondenceRestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Oep $oep;
    protected Correspondence $correspondence;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->oep = Oep::factory()->create();

        $this->admin = User::factory()->admin()->create();
        $this->campusAdmin = User::factory()->campusAdmin()->create([
            'campus_id' => $this->campus->id,
        ]);

        $this->correspondence = Correspondence::factory()->create([
            'campus_id' => $this->campus->id,
            'oep_id' => $this->oep->id,
            'created_by' => $this->admin->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_lists_all_correspondence_with_authentication()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/correspondence');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'reference_number',
                        'type',
                        'subject',
                        'status',
                    ],
                ],
                'meta',
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/correspondence');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_filters_correspondence_for_campus_admin()
    {
        // Create correspondence for different campus
        $otherCampus = Campus::factory()->create();
        Correspondence::factory()->create([
            'campus_id' => $otherCampus->id,
        ]);

        Sanctum::actingAs($this->campusAdmin);

        $response = $this->getJson('/api/v1/correspondence');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->campus->id, $data[0]['campus']['id']);
    }

    /** @test */
    public function it_shows_specific_correspondence()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/correspondence/{$this->correspondence->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->correspondence->id,
                    'subject' => $this->correspondence->subject,
                ],
            ]);
    }

    /** @test */
    public function it_creates_correspondence()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/correspondence', [
            'organization_type' => 'government',
            'type' => 'incoming',
            'subject' => 'Test Subject',
            'sender' => 'Test Sender',
            'recipient' => 'Test Recipient',
            'date_received' => now()->format('Y-m-d'),
            'campus_id' => $this->campus->id,
            'content' => 'Test content',
            'priority' => 'normal',
            'status' => 'pending',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Correspondence created successfully',
            ]);

        $this->assertDatabaseHas('correspondences', [
            'subject' => 'Test Subject',
            'type' => 'incoming',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_updates_correspondence()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/correspondence/{$this->correspondence->id}", [
            'status' => 'replied',
            'response_date' => now()->format('Y-m-d'),
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Correspondence updated successfully',
            ]);

        $this->assertDatabaseHas('correspondences', [
            'id' => $this->correspondence->id,
            'status' => 'replied',
        ]);
    }

    /** @test */
    public function it_deletes_correspondence()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/v1/correspondence/{$this->correspondence->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Correspondence deleted successfully',
            ]);

        $this->assertSoftDeleted('correspondences', [
            'id' => $this->correspondence->id,
        ]);
    }

    /** @test */
    public function it_returns_correspondence_statistics()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/correspondence/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'pending',
                    'replied',
                    'closed',
                    'overdue',
                    'by_type',
                    'by_organization_type',
                    'avg_response_time_days',
                ],
            ]);
    }

    /** @test */
    public function it_returns_pending_correspondence()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/correspondence/pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('pending', $item['status']);
        }
    }

    /** @test */
    public function it_filters_by_status()
    {
        Correspondence::factory()->create([
            'status' => 'replied',
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/correspondence?status=pending');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('pending', $item['status']);
        }
    }

    /** @test */
    public function it_filters_by_type()
    {
        Sanctum::actingAs($this->admin);

        $type = $this->correspondence->type;
        $response = $this->getJson("/api/v1/correspondence?type={$type}");

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals($type, $item['type']);
        }
    }

    /** @test */
    public function it_searches_correspondence()
    {
        $uniqueWord = 'UniqueSearchTerm123';
        $searchableCorrespondence = Correspondence::factory()->create([
            'subject' => "Test {$uniqueWord} Subject",
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson("/api/v1/correspondence?search={$uniqueWord}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString($uniqueWord, $data[0]['subject']);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        Sanctum::actingAs($this->admin);

        $fromDate = now()->subDays(7)->format('Y-m-d');
        $toDate = now()->format('Y-m-d');

        $response = $this->getJson("/api/v1/correspondence?from_date={$fromDate}&to_date={$toDate}");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/correspondence', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'organization_type',
                'type',
                'subject',
                'sender',
                'recipient',
                'content',
            ]);
    }

    /** @test */
    public function it_validates_type_field_values()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/correspondence', [
            'organization_type' => 'government',
            'type' => 'invalid_type',
            'subject' => 'Test',
            'sender' => 'Test',
            'recipient' => 'Test',
            'content' => 'Test',
            'date_received' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_supports_pagination()
    {
        Correspondence::factory()->count(25)->create([
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/correspondence?per_page=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertGreaterThan(1, $meta['last_page']);
    }
}
