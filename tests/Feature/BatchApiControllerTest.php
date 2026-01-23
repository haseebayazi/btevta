<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Candidate;
use App\Models\Oep;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BatchApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Campus $campus;
    protected Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();

        $this->user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($this->user, ['*']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    #[Test]
    public function it_returns_paginated_batches()
    {
        Batch::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/batches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'batch_code', 'name', 'capacity', 'status']
                    ],
                    'current_page',
                    'per_page',
                    'total'
                ]
            ]);
    }

    #[Test]
    public function it_filters_batches_by_status()
    {
        Batch::factory()->count(5)->create(['status' => 'active']);
        Batch::factory()->count(3)->create(['status' => 'planned']);

        $response = $this->getJson('/api/v1/batches?status=active');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(5, $data);
    }

    #[Test]
    public function it_filters_batches_by_campus()
    {
        Batch::factory()->count(4)->create(['campus_id' => $this->campus->id]);
        Batch::factory()->count(3)->create();

        $response = $this->getJson("/api/v1/batches?campus_id={$this->campus->id}");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(4, $data);
    }

    #[Test]
    public function it_filters_batches_by_trade()
    {
        Batch::factory()->count(6)->create(['trade_id' => $this->trade->id]);
        Batch::factory()->count(2)->create();

        $response = $this->getJson("/api/v1/batches?trade_id={$this->trade->id}");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(6, $data);
    }

    #[Test]
    public function it_searches_batches()
    {
        Batch::factory()->create(['batch_code' => 'BATCH-202501-001']);
        Batch::factory()->create(['batch_code' => 'BATCH-202501-002']);
        Batch::factory()->create(['batch_code' => 'OTHER-202501-003']);

        $response = $this->getJson('/api/v1/batches?search=BATCH-2025');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    #[Test]
    public function it_filters_available_batches_only()
    {
        Batch::factory()->create(['capacity' => 30]); // Has space
        Batch::factory()->create(['capacity' => 5]); // Has space

        $fullBatch = Batch::factory()->create(['capacity' => 10]);
        Candidate::factory()->count(10)->create(['batch_id' => $fullBatch->id]); // Full

        $response = $this->getJson('/api/v1/batches?available=1');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    #[Test]
    public function it_returns_single_batch_details()
    {
        $batch = Batch::factory()->create();

        $response = $this->getJson("/api/v1/batches/{$batch->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'batch_code', 'name', 'capacity', 'status',
                    'enrollment_count', 'available_slots', 'is_full',
                    'statistics'
                ]
            ])
            ->assertJsonPath('data.id', $batch->id);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_batch()
    {
        $response = $this->getJson('/api/v1/batches/9999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    #[Test]
    public function it_creates_new_batch()
    {
        $batchData = [
            'batch_code' => 'BATCH-202501-001',
            'name' => 'Test Batch',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'start_date' => now()->addWeek()->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'capacity' => 30,
            'status' => 'planned',
        ];

        $response = $this->postJson('/api/v1/batches', $batchData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'batch_code', 'capacity']
            ])
            ->assertJsonPath('data.batch_code', 'BATCH-202501-001');

        $this->assertDatabaseHas('batches', [
            'batch_code' => 'BATCH-202501-001',
        ]);
    }

    #[Test]
    public function it_validates_required_fields_on_create()
    {
        $response = $this->postJson('/api/v1/batches', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'batch_code', 'campus_id', 'trade_id',
                'start_date', 'end_date', 'capacity', 'status'
            ]);
    }

    #[Test]
    public function it_validates_unique_batch_code()
    {
        $existingBatch = Batch::factory()->create(['batch_code' => 'BATCH-202501-001']);

        $response = $this->postJson('/api/v1/batches', [
            'batch_code' => 'BATCH-202501-001',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'start_date' => now()->addWeek()->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'capacity' => 30,
            'status' => 'planned',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('batch_code');
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    #[Test]
    public function it_updates_existing_batch()
    {
        $batch = Batch::factory()->create(['capacity' => 30]);

        $response = $this->putJson("/api/v1/batches/{$batch->id}", [
            'name' => 'Updated Batch Name',
            'capacity' => 40,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Batch Name')
            ->assertJsonPath('data.capacity', 40);

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'name' => 'Updated Batch Name',
            'capacity' => 40,
        ]);
    }

    #[Test]
    public function it_prevents_capacity_reduction_below_enrollment()
    {
        $batch = Batch::factory()->create(['capacity' => 30]);
        Candidate::factory()->count(25)->create(['batch_id' => $batch->id]);

        $response = $this->putJson("/api/v1/batches/{$batch->id}", [
            'capacity' => 20,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('capacity');
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    #[Test]
    public function it_deletes_empty_batch()
    {
        $batch = Batch::factory()->create();

        $response = $this->deleteJson("/api/v1/batches/{$batch->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('batches', ['id' => $batch->id]);
    }

    #[Test]
    public function it_prevents_deletion_of_batch_with_candidates()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(5)->create(['batch_id' => $batch->id]);

        $response = $this->deleteJson("/api/v1/batches/{$batch->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_batch_statistics()
    {
        $batch = Batch::factory()->create(['capacity' => 50]);
        Candidate::factory()->count(30)->create(['batch_id' => $batch->id]);

        $response = $this->getJson("/api/v1/batches/{$batch->id}/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_enrolled',
                    'capacity',
                    'available_slots',
                ]
            ]);
    }

    // =========================================================================
    // CANDIDATES
    // =========================================================================

    #[Test]
    public function it_returns_batch_candidates()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(10)->create(['batch_id' => $batch->id]);
        Candidate::factory()->count(5)->create(); // Other batch

        $response = $this->getJson("/api/v1/batches/{$batch->id}/candidates");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(10, $data);
    }

    #[Test]
    public function it_filters_batch_candidates_by_training_status()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(7)->create([
            'batch_id' => $batch->id,
            'training_status' => 'enrolled'
        ]);
        Candidate::factory()->count(3)->create([
            'batch_id' => $batch->id,
            'training_status' => 'in_progress'
        ]);

        $response = $this->getJson("/api/v1/batches/{$batch->id}/candidates?training_status=enrolled");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(7, $data);
    }

    // =========================================================================
    // BULK ASSIGN
    // =========================================================================

    #[Test]
    public function it_bulk_assigns_candidates_to_batch()
    {
        $batch = Batch::factory()->create(['capacity' => 50]);
        $candidates = Candidate::factory()->count(5)->create(['batch_id' => null]);

        $response = $this->postJson('/api/v1/batches/bulk-assign', [
            'batch_id' => $batch->id,
            'candidate_ids' => $candidates->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        foreach ($candidates as $candidate) {
            $this->assertDatabaseHas('candidates', [
                'id' => $candidate->id,
                'batch_id' => $batch->id,
                'training_status' => 'enrolled',
            ]);
        }
    }

    #[Test]
    public function it_validates_bulk_assign_capacity()
    {
        $batch = Batch::factory()->create(['capacity' => 3]);
        $candidates = Candidate::factory()->count(5)->create(['batch_id' => null]);

        $response = $this->postJson('/api/v1/batches/bulk-assign', [
            'batch_id' => $batch->id,
            'candidate_ids' => $candidates->pluck('id')->toArray(),
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_bulk_assign_max_100_candidates()
    {
        $batch = Batch::factory()->create(['capacity' => 200]);
        $candidateIds = range(1, 101);

        $response = $this->postJson('/api/v1/batches/bulk-assign', [
            'batch_id' => $batch->id,
            'candidate_ids' => $candidateIds,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('candidate_ids');
    }

    // =========================================================================
    // CHANGE STATUS
    // =========================================================================

    #[Test]
    public function it_changes_batch_status()
    {
        $batch = Batch::factory()->create(['status' => 'planned']);

        $response = $this->postJson("/api/v1/batches/{$batch->id}/change-status", [
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_validates_status_change_value()
    {
        $batch = Batch::factory()->create();

        $response = $this->postJson("/api/v1/batches/{$batch->id}/change-status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    // =========================================================================
    // ACTIVE BATCHES
    // =========================================================================

    #[Test]
    public function it_returns_active_batches_only()
    {
        Batch::factory()->count(5)->create(['status' => 'active']);
        Batch::factory()->count(3)->create(['status' => 'planned']);
        Batch::factory()->count(2)->create(['status' => 'completed']);

        $response = $this->getJson('/api/v1/batches/active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(5, $data);

        foreach ($data as $batch) {
            $this->assertEquals('active', $batch['status']);
        }
    }

    // =========================================================================
    // BY CAMPUS
    // =========================================================================

    #[Test]
    public function it_returns_batches_by_campus()
    {
        Batch::factory()->count(4)->create([
            'campus_id' => $this->campus->id,
            'status' => 'active'
        ]);
        Batch::factory()->count(2)->create(['status' => 'active']); // Different campus

        $response = $this->getJson("/api/v1/batches/by-campus/{$this->campus->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(4, $data);

        foreach ($data as $batch) {
            $this->assertEquals($this->campus->id, $batch['campus_id']);
        }
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function it_requires_authentication()
    {
        Sanctum::actingAs($this->user, []); // No abilities
        $this->user->tokens()->delete(); // Remove token

        $response = $this->getJson('/api/v1/batches');

        $response->assertStatus(401);
    }

    #[Test]
    public function campus_admin_only_sees_their_batches()
    {
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus->id,
        ]);

        Sanctum::actingAs($campusAdmin, ['*']);

        Batch::factory()->count(3)->create(['campus_id' => $this->campus->id]);
        Batch::factory()->count(2)->create(); // Different campus

        $response = $this->getJson('/api/v1/batches');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(3, $data);
    }
}
