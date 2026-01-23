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
use Illuminate\Foundation\Testing\RefreshDatabase;

class BatchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Trade $trade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus->id,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    #[Test]
    public function admin_can_view_batches_index()
    {
        Batch::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.index'));

        $response->assertStatus(200)
            ->assertViewIs('admin.batches.index')
            ->assertViewHas('batches');
    }

    #[Test]
    public function campus_admin_only_sees_their_campus_batches()
    {
        Batch::factory()->count(3)->create(['campus_id' => $this->campus->id]);
        Batch::factory()->count(2)->create(); // Different campus

        $response = $this->actingAs($this->campusAdmin)
            ->get(route('admin.batches.index'));

        $response->assertStatus(200);
        $batches = $response->viewData('batches');
        $this->assertCount(3, $batches);
    }

    #[Test]
    public function batches_can_be_filtered_by_status()
    {
        Batch::factory()->count(3)->create(['status' => 'active']);
        Batch::factory()->count(2)->create(['status' => 'planned']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.index', ['status' => 'active']));

        $response->assertStatus(200);
        $batches = $response->viewData('batches');
        $this->assertCount(3, $batches);
    }

    #[Test]
    public function batches_can_be_searched()
    {
        Batch::factory()->create(['batch_code' => 'BATCH-202501-001']);
        Batch::factory()->create(['batch_code' => 'BATCH-202501-002']);
        Batch::factory()->create(['batch_code' => 'OTHER-202501-003']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.index', ['search' => 'BATCH-2025']));

        $response->assertStatus(200);
        $batches = $response->viewData('batches');
        $this->assertCount(2, $batches);
    }

    // =========================================================================
    // CREATE & STORE
    // =========================================================================

    #[Test]
    public function admin_can_view_create_batch_form()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.create'));

        $response->assertStatus(200)
            ->assertViewIs('admin.batches.create')
            ->assertViewHas(['campuses', 'trades', 'oeps', 'users', 'statuses']);
    }

    #[Test]
    public function admin_can_create_batch()
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

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), $batchData);

        $response->assertRedirect(route('admin.batches.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('batches', [
            'batch_code' => 'BATCH-202501-001',
            'capacity' => 30,
        ]);
    }

    #[Test]
    public function batch_creation_requires_valid_data()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), []);

        $response->assertSessionHasErrors(['batch_code', 'campus_id', 'trade_id', 'start_date', 'end_date', 'capacity', 'status']);
    }

    #[Test]
    public function batch_code_must_be_unique()
    {
        $batch = Batch::factory()->create(['batch_code' => 'BATCH-202501-001']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), [
                'batch_code' => 'BATCH-202501-001',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
                'start_date' => now()->addWeek()->format('Y-m-d'),
                'end_date' => now()->addMonths(3)->format('Y-m-d'),
                'capacity' => 30,
                'status' => 'planned',
            ]);

        $response->assertSessionHasErrors('batch_code');
    }

    #[Test]
    public function start_date_must_be_today_or_future()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), [
                'batch_code' => 'BATCH-202501-001',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date' => now()->addMonths(3)->format('Y-m-d'),
                'capacity' => 30,
                'status' => 'planned',
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    #[Test]
    public function end_date_must_be_after_start_date()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.store'), [
                'batch_code' => 'BATCH-202501-001',
                'campus_id' => $this->campus->id,
                'trade_id' => $this->trade->id,
                'start_date' => now()->addMonths(3)->format('Y-m-d'),
                'end_date' => now()->addWeek()->format('Y-m-d'),
                'capacity' => 30,
                'status' => 'planned',
            ]);

        $response->assertSessionHasErrors('end_date');
    }

    // =========================================================================
    // EDIT & UPDATE
    // =========================================================================

    #[Test]
    public function admin_can_view_edit_batch_form()
    {
        $batch = Batch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.edit', $batch));

        $response->assertStatus(200)
            ->assertViewIs('admin.batches.edit')
            ->assertViewHas('batch', $batch);
    }

    #[Test]
    public function admin_can_update_batch()
    {
        $batch = Batch::factory()->create(['capacity' => 30]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.batches.update', $batch), [
                'batch_code' => $batch->batch_code,
                'name' => 'Updated Batch Name',
                'campus_id' => $batch->campus_id,
                'trade_id' => $batch->trade_id,
                'start_date' => $batch->start_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'capacity' => 40,
                'status' => $batch->status,
            ]);

        $response->assertRedirect(route('admin.batches.index'));

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'name' => 'Updated Batch Name',
            'capacity' => 40,
        ]);
    }

    #[Test]
    public function cannot_reduce_capacity_below_enrollment()
    {
        $batch = Batch::factory()->create(['capacity' => 30]);
        Candidate::factory()->count(25)->create(['batch_id' => $batch->id]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.batches.update', $batch), [
                'batch_code' => $batch->batch_code,
                'campus_id' => $batch->campus_id,
                'trade_id' => $batch->trade_id,
                'start_date' => $batch->start_date->format('Y-m-d'),
                'end_date' => $batch->end_date->format('Y-m-d'),
                'capacity' => 20, // Less than 25 enrolled
                'status' => $batch->status,
            ]);

        $response->assertSessionHasErrors('capacity');
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    #[Test]
    public function admin_can_view_batch_details()
    {
        $batch = Batch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.show', $batch));

        $response->assertStatus(200)
            ->assertViewIs('admin.batches.show')
            ->assertViewHas('batch', $batch);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    #[Test]
    public function admin_can_delete_empty_batch()
    {
        $batch = Batch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.batches.destroy', $batch));

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('batches', ['id' => $batch->id]);
    }

    #[Test]
    public function cannot_delete_batch_with_candidates()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(5)->create(['batch_id' => $batch->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.batches.destroy', $batch));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    // =========================================================================
    // CANDIDATES
    // =========================================================================

    #[Test]
    public function admin_can_view_batch_candidates()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(10)->create(['batch_id' => $batch->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.candidates', $batch));

        $response->assertStatus(200)
            ->assertViewIs('admin.batches.candidates')
            ->assertViewHas('batch', $batch);
    }

    #[Test]
    public function batch_candidates_can_be_filtered()
    {
        $batch = Batch::factory()->create();
        Candidate::factory()->count(5)->create([
            'batch_id' => $batch->id,
            'training_status' => 'enrolled'
        ]);
        Candidate::factory()->count(3)->create([
            'batch_id' => $batch->id,
            'training_status' => 'in_progress'
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.batches.candidates', ['batch' => $batch->id, 'training_status' => 'enrolled']));

        $response->assertStatus(200);
        $candidates = $response->viewData('candidates');
        $this->assertCount(5, $candidates);
    }

    // =========================================================================
    // BULK ASSIGN
    // =========================================================================

    #[Test]
    public function admin_can_bulk_assign_candidates_to_batch()
    {
        $batch = Batch::factory()->create(['capacity' => 50]);
        $candidates = Candidate::factory()->count(5)->create(['batch_id' => null]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.bulk-assign'), [
                'batch_id' => $batch->id,
                'candidate_ids' => $candidates->pluck('id')->toArray(),
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        foreach ($candidates as $candidate) {
            $this->assertDatabaseHas('candidates', [
                'id' => $candidate->id,
                'batch_id' => $batch->id,
                'training_status' => 'enrolled',
            ]);
        }
    }

    #[Test]
    public function cannot_assign_more_candidates_than_capacity()
    {
        $batch = Batch::factory()->create(['capacity' => 3]);
        $candidates = Candidate::factory()->count(5)->create(['batch_id' => null]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.batches.bulk-assign'), [
                'batch_id' => $batch->id,
                'candidate_ids' => $candidates->pluck('id')->toArray(),
            ]);

        $response->assertSessionHasErrors('batch_id');
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function guest_cannot_access_batches()
    {
        $response = $this->get(route('admin.batches.index'));
        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function campus_admin_cannot_edit_other_campus_batch()
    {
        $otherCampus = Campus::factory()->create();
        $batch = Batch::factory()->create(['campus_id' => $otherCampus->id]);

        $response = $this->actingAs($this->campusAdmin)
            ->get(route('admin.batches.edit', $batch));

        $response->assertStatus(403);
    }
}
