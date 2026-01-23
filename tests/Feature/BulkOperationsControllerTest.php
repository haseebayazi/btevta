<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Campus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkOperationsControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // BULK STATUS UPDATE
    // =========================================================================

    #[Test]
    public function super_admin_can_bulk_update_candidate_status()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(3)->create(['status' => 'new']);

        $response = $this->actingAs($user)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'screening',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated' => 3,
            ]);
    }

    #[Test]
    public function campus_admin_can_only_update_their_campus_candidates()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $ownCandidate = Candidate::factory()->create([
            'campus_id' => $campus1->id,
            'status' => 'new',
        ]);

        $otherCandidate = Candidate::factory()->create([
            'campus_id' => $campus2->id,
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->postJson('/api/bulk/update-status', [
            'candidate_ids' => [$ownCandidate->id, $otherCandidate->id],
            'status' => 'screening',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Only own candidate should be updated
        $this->assertEquals(1, $data['updated']);
        $this->assertEquals(1, $data['failed']);
    }

    #[Test]
    public function bulk_update_validates_status_transitions()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidate = Candidate::factory()->create(['status' => 'new']);

        // Try invalid transition (new -> departed directly)
        $response = $this->actingAs($user)->postJson('/api/bulk/update-status', [
            'candidate_ids' => [$candidate->id],
            'status' => 'departed',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Should fail due to invalid transition
        $this->assertEquals(0, $data['updated']);
        $this->assertEquals(1, $data['failed']);
    }

    #[Test]
    public function bulk_update_requires_authentication()
    {
        $candidates = Candidate::factory()->count(2)->create();

        $response = $this->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'screening',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function bulk_update_validates_required_fields()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->postJson('/api/bulk/update-status', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_ids', 'status']);
    }

    #[Test]
    public function bulk_update_limits_to_100_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(101)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/update-status', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'status' => 'screening',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_ids']);
    }

    // =========================================================================
    // BULK BATCH ASSIGNMENT
    // =========================================================================

    #[Test]
    public function admin_can_bulk_assign_candidates_to_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create(['capacity' => 50]);
        $candidates = Candidate::factory()->count(3)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/assign-batch', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'batch_id' => $batch->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated' => 3,
            ]);

        // Verify candidates are assigned
        foreach ($candidates as $candidate) {
            $candidate->refresh();
            $this->assertEquals($batch->id, $candidate->batch_id);
        }
    }

    #[Test]
    public function batch_assignment_respects_capacity()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $batch = Batch::factory()->create(['capacity' => 2]);

        // Pre-fill batch with 1 candidate
        Candidate::factory()->create(['batch_id' => $batch->id]);

        $newCandidates = Candidate::factory()->count(3)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/assign-batch', [
            'candidate_ids' => $newCandidates->pluck('id')->toArray(),
            'batch_id' => $batch->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Only 1 should be assigned (capacity is 2, already has 1)
        $this->assertEquals(1, $data['updated']);
        $this->assertNotEmpty($data['errors']);
    }

    #[Test]
    public function batch_assignment_requires_valid_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/assign-batch', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'batch_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['batch_id']);
    }

    // =========================================================================
    // BULK CAMPUS ASSIGNMENT
    // =========================================================================

    #[Test]
    public function admin_can_bulk_assign_candidates_to_campus()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $campus = Campus::factory()->create();
        $candidates = Candidate::factory()->count(3)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/assign-campus', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'campus_id' => $campus->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'updated' => 3,
            ]);

        // Verify candidates are assigned
        foreach ($candidates as $candidate) {
            $candidate->refresh();
            $this->assertEquals($campus->id, $candidate->campus_id);
        }
    }

    #[Test]
    public function campus_assignment_clears_batch()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $oldCampus = Campus::factory()->create();
        $newCampus = Campus::factory()->create();
        $batch = Batch::factory()->create(['campus_id' => $oldCampus->id]);

        $candidate = Candidate::factory()->create([
            'campus_id' => $oldCampus->id,
            'batch_id' => $batch->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/bulk/assign-campus', [
            'candidate_ids' => [$candidate->id],
            'campus_id' => $newCampus->id,
        ]);

        $response->assertStatus(200);

        $candidate->refresh();
        $this->assertEquals($newCampus->id, $candidate->campus_id);
        $this->assertNull($candidate->batch_id);
    }

    // =========================================================================
    // BULK EXPORT
    // =========================================================================

    #[Test]
    public function admin_can_export_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(3)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/export', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'format' => 'csv',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['download_url']);
    }

    #[Test]
    public function export_validates_format()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/export', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'format' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    #[Test]
    public function export_filters_by_user_permissions()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $ownCandidate = Candidate::factory()->create(['campus_id' => $campus1->id]);
        $otherCandidate = Candidate::factory()->create(['campus_id' => $campus2->id]);

        $response = $this->actingAs($user)->postJson('/api/bulk/export', [
            'candidate_ids' => [$ownCandidate->id, $otherCandidate->id],
            'format' => 'csv',
        ]);

        $response->assertStatus(200);
        // Export should only include accessible candidates
    }

    // =========================================================================
    // BULK DELETE
    // =========================================================================

    #[Test]
    public function only_admin_can_bulk_delete()
    {
        $user = User::factory()->create(['role' => 'campus_admin']);
        $candidates = Candidate::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/delete', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_bulk_delete_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(3)->create(['status' => 'new']);

        $response = $this->actingAs($user)->postJson('/api/bulk/delete', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'deleted' => 3,
            ]);

        // Verify soft deleted
        foreach ($candidates as $candidate) {
            $this->assertSoftDeleted('candidates', ['id' => $candidate->id]);
        }
    }

    #[Test]
    public function cannot_delete_departed_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $departedCandidate = Candidate::factory()->create(['status' => 'departed']);
        $normalCandidate = Candidate::factory()->create(['status' => 'new']);

        $response = $this->actingAs($user)->postJson('/api/bulk/delete', [
            'candidate_ids' => [$departedCandidate->id, $normalCandidate->id],
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Only non-departed should be deleted
        $this->assertEquals(1, $data['deleted']);

        // Departed candidate should still exist
        $this->assertDatabaseHas('candidates', ['id' => $departedCandidate->id, 'deleted_at' => null]);
    }

    #[Test]
    public function bulk_delete_limits_to_50_candidates()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(51)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/delete', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_ids']);
    }

    // =========================================================================
    // BULK NOTIFICATION
    // =========================================================================

    #[Test]
    public function admin_can_send_bulk_notifications()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(3)->create(['phone' => '03001234567']);

        $response = $this->actingAs($user)->postJson('/api/bulk/send-notification', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'notification_type' => 'sms',
            'message' => 'Test notification message',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'queued' => 3,
            ]);
    }

    #[Test]
    public function email_notification_requires_subject()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $candidates = Candidate::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson('/api/bulk/send-notification', [
            'candidate_ids' => $candidates->pluck('id')->toArray(),
            'notification_type' => 'email',
            'message' => 'Test message',
            // Missing subject
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }
}
