<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification;

/**
 * Integration tests for bulk operations workflows.
 * Tests concurrent updates, transaction handling, and rollback scenarios.
 */
class BulkOperationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Trade $trade;
    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();
        $this->batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'capacity' => 50,
            'status' => 'active',
        ]);

        Notification::fake();
    }

    // =========================================================================
    // BULK STATUS UPDATE
    // =========================================================================

    #[Test]
    public function it_updates_multiple_candidates_status_atomically()
    {
        $candidates = Candidate::factory()->count(10)->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'screening',
        ]);

        $response->assertOk();
        $this->assertEquals(10, Candidate::where('status', 'screening')->count());
    }

    #[Test]
    public function it_validates_status_transitions_in_bulk()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Try invalid transition (new -> departed)
        $response = $this->actingAs($this->admin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'departed',
        ]);

        $response->assertStatus(422);
        // All should remain unchanged
        $this->assertEquals(5, Candidate::where('status', 'new')->count());
    }

    #[Test]
    public function it_rolls_back_on_partial_failure()
    {
        $validCandidates = Candidate::factory()->count(3)->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $invalidCandidate = Candidate::factory()->create([
            'status' => 'departed', // Cannot transition from departed
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $allIds = $validCandidates->pluck('id')->push($invalidCandidate->id)->toArray();

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $allIds,
            'status' => 'screening',
            'rollback_on_error' => true,
        ]);

        // All should remain unchanged due to rollback
        $this->assertEquals(3, Candidate::where('status', 'new')->count());
        $this->assertEquals(1, Candidate::where('status', 'departed')->count());
    }

    // =========================================================================
    // BULK BATCH ASSIGNMENT
    // =========================================================================

    #[Test]
    public function it_assigns_candidates_to_batch_with_capacity_check()
    {
        $candidates = Candidate::factory()->count(10)->create([
            'status' => 'registered',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/assign-batch', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'batch_id' => $this->batch->id,
        ]);

        $response->assertOk();
        $this->assertEquals(10, Candidate::where('batch_id', $this->batch->id)->count());
    }

    #[Test]
    public function it_rejects_assignment_exceeding_capacity()
    {
        // Fill batch to capacity
        Candidate::factory()->count(50)->create([
            'batch_id' => $this->batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $newCandidates = Candidate::factory()->count(5)->create([
            'status' => 'registered',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/assign-batch', [
            'candidate_ids' => $newCandidates->pluck('id')->toArray(),
            'batch_id' => $this->batch->id,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('capacity', strtolower($response->json('message')));
    }

    #[Test]
    public function it_validates_trade_compatibility_for_batch_assignment()
    {
        $otherTrade = Trade::factory()->create();
        $candidates = Candidate::factory()->count(5)->create([
            'status' => 'registered',
            'trade_id' => $otherTrade->id,
            'campus_id' => $this->campus->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/assign-batch', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'batch_id' => $this->batch->id, // Different trade
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // BULK CAMPUS TRANSFER
    // =========================================================================

    #[Test]
    public function it_transfers_candidates_between_campuses()
    {
        $newCampus = Campus::factory()->create();
        $candidates = Candidate::factory()->count(5)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/assign-campus', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'campus_id' => $newCampus->id,
            'transfer_reason' => 'Operational requirements',
        ]);

        $response->assertOk();
        $this->assertEquals(5, Candidate::where('campus_id', $newCampus->id)->count());
    }

    #[Test]
    public function it_clears_batch_on_campus_transfer()
    {
        $newCampus = Campus::factory()->create();
        $candidates = Candidate::factory()->count(3)->create([
            'campus_id' => $this->campus->id,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/assign-campus', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'campus_id' => $newCampus->id,
        ]);

        $response->assertOk();
        $this->assertEquals(3, Candidate::whereNull('batch_id')->where('campus_id', $newCampus->id)->count());
    }

    // =========================================================================
    // BULK EXPORT
    // =========================================================================

    #[Test]
    public function it_exports_selected_candidates_to_csv()
    {
        $candidates = Candidate::factory()->count(10)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/api/bulk/export', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'format' => 'csv',
            'columns' => ['name', 'cnic', 'phone', 'status'],
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function it_exports_filtered_candidates_to_excel()
    {
        Candidate::factory()->count(20)->create([
            'status' => 'training',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/api/bulk/export', [
            'format' => 'xlsx',
            'filters' => [
                'status' => 'training',
                'campus_id' => $this->campus->id,
            ],
        ]);

        $response->assertOk();
    }

    #[Test]
    public function it_queues_large_exports()
    {
        Queue::fake();

        Candidate::factory()->count(1000)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/api/bulk/export', [
            'format' => 'xlsx',
            'filters' => ['campus_id' => $this->campus->id],
            'async' => true,
        ]);

        Queue::assertPushed(\App\Jobs\ProcessBulkExport::class);
    }

    // =========================================================================
    // BULK DELETE
    // =========================================================================

    #[Test]
    public function it_soft_deletes_candidates_in_bulk()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/delete', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'confirmation' => 'DELETE',
        ]);

        $response->assertOk();
        $this->assertEquals(5, Candidate::onlyTrashed()->count());
    }

    #[Test]
    public function it_prevents_deletion_of_departed_candidates()
    {
        $departed = Candidate::factory()->count(3)->create([
            'status' => 'departed',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/delete', [
            'candidate_ids' => $departed->pluck('id')->toArray(),
            'confirmation' => 'DELETE',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(3, Candidate::where('status', 'departed')->count());
    }

    #[Test]
    public function it_requires_confirmation_for_bulk_delete()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/delete', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            // Missing confirmation
        ]);

        $response->assertSessionHasErrors('confirmation');
    }

    // =========================================================================
    // BULK NOTIFICATIONS
    // =========================================================================

    #[Test]
    public function it_sends_notifications_to_selected_candidates()
    {
        $candidates = Candidate::factory()->count(10)->create([
            'email' => 'test@example.com',
            'phone' => '03001234567',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/notify', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'channel' => 'email',
            'template' => 'training_reminder',
            'message' => 'Your training starts tomorrow.',
        ]);

        $response->assertOk();
        Notification::assertCount(10);
    }

    #[Test]
    public function it_sends_sms_notifications()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'phone' => '03001234567',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/notify', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'channel' => 'sms',
            'message' => 'Important update regarding your application.',
        ]);

        $response->assertOk();
    }

    // =========================================================================
    // CONCURRENT OPERATIONS
    // =========================================================================

    #[Test]
    public function it_handles_optimistic_locking_for_concurrent_updates()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Simulate concurrent update
        $response1 = $this->actingAs($this->admin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => [$candidate->id],
            'status' => 'screening',
        ]);

        $response2 = $this->actingAs($this->admin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => [$candidate->id],
            'status' => 'screening',
        ]);

        // Both should succeed (idempotent operation)
        $response1->assertOk();
        $this->assertEquals('screening', $candidate->fresh()->status);
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    #[Test]
    public function it_logs_bulk_operations()
    {
        $candidates = Candidate::factory()->count(5)->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'screening',
        ]);

        $response->assertOk();

        // Check activity log
        $this->assertDatabaseHas('activity_log', [
            'causer_id' => $this->admin->id,
            'description' => 'Bulk status update',
        ]);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function campus_admin_can_only_operate_on_their_campus()
    {
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus->id,
        ]);

        $otherCampus = Campus::factory()->create();
        $candidates = Candidate::factory()->count(5)->create([
            'campus_id' => $otherCampus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($campusAdmin)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'screening',
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function only_admin_can_perform_bulk_delete()
    {
        $campusAdmin = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $this->campus->id,
        ]);

        $candidates = Candidate::factory()->count(3)->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $response = $this->actingAs($campusAdmin)->postJson('/api/bulk/delete', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'confirmation' => 'DELETE',
        ]);

        $response->assertStatus(403);
    }
}
