<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    // =========================================================================
    // VALID TRANSITIONS
    // =========================================================================

    /** @test */
    public function new_candidate_can_transition_to_screening()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $result = $candidate->updateStatus(Candidate::STATUS_SCREENING);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_SCREENING, $candidate->fresh()->status);
    }

    /** @test */
    public function new_candidate_can_transition_to_rejected()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $result = $candidate->updateStatus(Candidate::STATUS_REJECTED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_REJECTED, $candidate->fresh()->status);
    }

    /** @test */
    public function screening_candidate_can_transition_to_registered()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_SCREENING]);

        $result = $candidate->updateStatus(Candidate::STATUS_REGISTERED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_REGISTERED, $candidate->fresh()->status);
    }

    /** @test */
    public function screening_candidate_can_transition_to_rejected()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_SCREENING]);

        $result = $candidate->updateStatus(Candidate::STATUS_REJECTED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_REJECTED, $candidate->fresh()->status);
    }

    /** @test */
    public function registered_candidate_can_transition_to_training()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REGISTERED]);

        $result = $candidate->updateStatus(Candidate::STATUS_TRAINING);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_TRAINING, $candidate->fresh()->status);
    }

    /** @test */
    public function registered_candidate_can_transition_to_dropped()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REGISTERED]);

        $result = $candidate->updateStatus(Candidate::STATUS_DROPPED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_DROPPED, $candidate->fresh()->status);
    }

    /** @test */
    public function training_candidate_can_transition_to_visa_process()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_TRAINING]);

        $result = $candidate->updateStatus(Candidate::STATUS_VISA_PROCESS);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_VISA_PROCESS, $candidate->fresh()->status);
    }

    /** @test */
    public function training_candidate_can_transition_to_dropped()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_TRAINING]);

        $result = $candidate->updateStatus(Candidate::STATUS_DROPPED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_DROPPED, $candidate->fresh()->status);
    }

    /** @test */
    public function visa_process_candidate_can_transition_to_ready()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_VISA_PROCESS]);

        $result = $candidate->updateStatus(Candidate::STATUS_READY);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_READY, $candidate->fresh()->status);
    }

    /** @test */
    public function visa_process_candidate_can_transition_to_rejected()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_VISA_PROCESS]);

        $result = $candidate->updateStatus(Candidate::STATUS_REJECTED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_REJECTED, $candidate->fresh()->status);
    }

    /** @test */
    public function ready_candidate_can_transition_to_departed()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_READY]);

        $result = $candidate->updateStatus(Candidate::STATUS_DEPARTED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_DEPARTED, $candidate->fresh()->status);
    }

    /** @test */
    public function departed_candidate_can_transition_to_returned()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_DEPARTED]);

        $result = $candidate->updateStatus(Candidate::STATUS_RETURNED);

        $this->assertTrue($result);
        $this->assertEquals(Candidate::STATUS_RETURNED, $candidate->fresh()->status);
    }

    // =========================================================================
    // INVALID TRANSITIONS
    // =========================================================================

    /** @test */
    public function new_candidate_cannot_transition_directly_to_training()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_TRAINING);
    }

    /** @test */
    public function new_candidate_cannot_transition_directly_to_departed()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
    }

    /** @test */
    public function screening_cannot_transition_to_departed()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_SCREENING]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
    }

    /** @test */
    public function registered_cannot_transition_to_departed()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REGISTERED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
    }

    /** @test */
    public function training_cannot_transition_to_departed()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_TRAINING]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
    }

    /** @test */
    public function visa_process_cannot_transition_directly_to_departed()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_VISA_PROCESS]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
    }

    /** @test */
    public function departed_cannot_transition_to_new()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_DEPARTED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_NEW);
    }

    /** @test */
    public function rejected_cannot_transition_to_any_status()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REJECTED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $candidate->updateStatus(Candidate::STATUS_SCREENING);
    }

    // =========================================================================
    // FULL LIFECYCLE TEST
    // =========================================================================

    /** @test */
    public function candidate_can_complete_full_lifecycle()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        // Progress through all stages
        $candidate->updateStatus(Candidate::STATUS_SCREENING);
        $this->assertEquals(Candidate::STATUS_SCREENING, $candidate->fresh()->status);

        $candidate->updateStatus(Candidate::STATUS_REGISTERED);
        $this->assertEquals(Candidate::STATUS_REGISTERED, $candidate->fresh()->status);

        $candidate->updateStatus(Candidate::STATUS_TRAINING);
        $this->assertEquals(Candidate::STATUS_TRAINING, $candidate->fresh()->status);

        $candidate->updateStatus(Candidate::STATUS_VISA_PROCESS);
        $this->assertEquals(Candidate::STATUS_VISA_PROCESS, $candidate->fresh()->status);

        $candidate->updateStatus(Candidate::STATUS_READY);
        $this->assertEquals(Candidate::STATUS_READY, $candidate->fresh()->status);

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
        $this->assertEquals(Candidate::STATUS_DEPARTED, $candidate->fresh()->status);

        $candidate->updateStatus(Candidate::STATUS_RETURNED);
        $this->assertEquals(Candidate::STATUS_RETURNED, $candidate->fresh()->status);
    }

    /** @test */
    public function status_change_logs_activity()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $candidate->updateStatus(Candidate::STATUS_SCREENING, 'Test remarks');

        // Check activity log exists
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Candidate::class,
            'subject_id' => $candidate->id,
        ]);
    }

    /** @test */
    public function status_change_records_remarks()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $candidate->updateStatus(Candidate::STATUS_SCREENING, 'Passed initial screening call');

        $candidate->refresh();
        $this->assertEquals('Passed initial screening call', $candidate->status_remarks);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /** @test */
    public function can_transition_to_returns_correct_result()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $this->assertTrue($candidate->canTransitionTo(Candidate::STATUS_SCREENING));
        $this->assertTrue($candidate->canTransitionTo(Candidate::STATUS_REJECTED));
        $this->assertFalse($candidate->canTransitionTo(Candidate::STATUS_TRAINING));
        $this->assertFalse($candidate->canTransitionTo(Candidate::STATUS_DEPARTED));
    }

    /** @test */
    public function get_allowed_transitions_returns_correct_statuses()
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_NEW]);

        $allowed = $candidate->getAllowedTransitions();

        $this->assertContains(Candidate::STATUS_SCREENING, $allowed);
        $this->assertContains(Candidate::STATUS_REJECTED, $allowed);
        $this->assertNotContains(Candidate::STATUS_TRAINING, $allowed);
    }
}
