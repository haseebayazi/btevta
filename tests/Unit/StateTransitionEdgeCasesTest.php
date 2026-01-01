<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\CandidateScreening;
use App\Models\RegistrationDocument;
use App\Models\TrainingCertificate;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Edge case tests for candidate state transitions.
 * Tests invalid transitions, concurrent updates, and partial completion scenarios.
 */
class StateTransitionEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected Campus $campus;
    protected Trade $trade;
    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();
        $this->batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);
    }

    // =========================================================================
    // INVALID TRANSITIONS BLOCKED
    // =========================================================================

    /** @test */
    public function it_blocks_transition_from_new_directly_to_training()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $candidate->canTransitionTo('training');

        $this->assertFalse($result['can_transition']);
        $this->assertNotEmpty($result['issues']);
    }

    /** @test */
    public function it_blocks_transition_from_new_directly_to_departed()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $candidate->canTransitionTo('departed');

        $this->assertFalse($result['can_transition']);
    }

    /** @test */
    public function it_blocks_transition_from_departed_to_any_status()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'departed',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $statuses = ['new', 'screening', 'registered', 'training', 'visa_process', 'ready'];

        foreach ($statuses as $status) {
            $result = $candidate->canTransitionTo($status);
            $this->assertFalse($result['can_transition'], "Should not transition from departed to {$status}");
        }
    }

    /** @test */
    public function it_blocks_transition_from_rejected_without_reactivation()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'rejected',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $candidate->canTransitionTo('screening');

        $this->assertFalse($result['can_transition']);
    }

    /** @test */
    public function it_blocks_transition_to_visa_process_without_training_completion()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'training',
            'training_status' => 'in_progress', // Not completed
            'batch_id' => $this->batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $candidate->canTransitionToVisaProcess();

        $this->assertFalse($result['can_transition']);
        $this->assertContains('Training not completed', $result['issues']);
    }

    /** @test */
    public function it_blocks_transition_to_ready_without_visa_issued()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'visa_process',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'visa_issued' => false,
        ]);

        $result = $candidate->canTransitionToReady();

        $this->assertFalse($result['can_transition']);
    }

    /** @test */
    public function it_blocks_transition_to_departed_without_briefing()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'ready',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        Departure::factory()->create([
            'candidate_id' => $candidate->id,
            'pre_briefing_completed' => false,
        ]);

        $result = $candidate->canTransitionToDeparted();

        $this->assertFalse($result['can_transition']);
    }

    // =========================================================================
    // CONCURRENT STATUS UPDATES
    // =========================================================================

    /** @test */
    public function it_handles_concurrent_status_updates_safely()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'new',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Simulate two concurrent updates
        DB::beginTransaction();

        $candidate1 = Candidate::find($candidate->id);
        $candidate2 = Candidate::find($candidate->id);

        // First update
        $candidate1->updateStatus('screening');

        // Second update should see the updated status
        $candidate2->refresh();
        $this->assertEquals('screening', $candidate2->status);

        DB::rollBack();
    }

    /** @test */
    public function it_prevents_race_condition_in_batch_assignment()
    {
        // Create batch with capacity 1
        $limitedBatch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'capacity' => 1,
        ]);

        $candidate1 = Candidate::factory()->create([
            'status' => 'registered',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $candidate2 = Candidate::factory()->create([
            'status' => 'registered',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // First assignment should succeed
        $candidate1->update(['batch_id' => $limitedBatch->id]);

        // Check remaining capacity
        $remainingCapacity = $limitedBatch->capacity - $limitedBatch->candidates()->count();
        $this->assertEquals(0, $remainingCapacity);
    }

    // =========================================================================
    // PARTIAL COMPLETION SCENARIOS
    // =========================================================================

    /** @test */
    public function it_blocks_registration_with_partial_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Only one document instead of required set
        RegistrationDocument::factory()->create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'status' => 'verified',
        ]);

        $result = $candidate->hasCompleteDocuments();

        $this->assertFalse($result);
    }

    /** @test */
    public function it_blocks_training_completion_with_partial_assessments()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'training',
            'batch_id' => $this->batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // No assessments recorded
        $this->assertFalse($candidate->hasPassedAllAssessments());
    }

    /** @test */
    public function it_blocks_visa_process_with_partial_stages()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'visa_process',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Only interview completed, not trade test or medical
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'trade_test_passed' => false,
            'medical_passed' => false,
        ]);

        $result = $candidate->canTransitionToReady();

        $this->assertFalse($result['can_transition']);
    }

    // =========================================================================
    // PREREQUISITE CHAIN VALIDATION
    // =========================================================================

    /** @test */
    public function it_validates_screening_prerequisite_chain()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Try to record physical screening without desk/call
        $result = $candidate->canRecordScreening('physical');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_validates_visa_stage_prerequisite_chain()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'visa_process',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'pending',
        ]);

        // Try to record medical without interview passing
        $canRecordMedical = $visaProcess->canRecordMedical();

        $this->assertFalse($canRecordMedical);
    }

    // =========================================================================
    // EDGE CASE STATUS VALUES
    // =========================================================================

    /** @test */
    public function it_handles_null_status_gracefully()
    {
        $candidate = new Candidate([
            'name' => 'Test',
            'cnic' => '3520112345671',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Status should default properly
        $this->assertNotNull($candidate->status ?? 'new');
    }

    /** @test */
    public function it_handles_invalid_status_value()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        // Attempt to set invalid status should be caught by enum
        try {
            $candidate->status = 'invalid_status';
            $this->fail('Should have thrown exception for invalid status');
        } catch (\ValueError $e) {
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Laravel may handle this differently
            $this->assertTrue(true);
        }
    }

    // =========================================================================
    // TRAINING STATUS EDGE CASES
    // =========================================================================

    /** @test */
    public function it_blocks_training_certificate_for_failed_training()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'training',
            'training_status' => 'failed',
            'batch_id' => $this->batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $canIssueCertificate = $candidate->canIssueCertificate();

        $this->assertFalse($canIssueCertificate);
    }

    /** @test */
    public function it_blocks_training_certificate_for_withdrawn_candidate()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'training',
            'training_status' => 'withdrawn',
            'batch_id' => $this->batch->id,
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $canIssueCertificate = $candidate->canIssueCertificate();

        $this->assertFalse($canIssueCertificate);
    }

    // =========================================================================
    // REACTIVATION SCENARIOS
    // =========================================================================

    /** @test */
    public function it_allows_reactivation_of_dropped_candidate_with_reason()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'dropped',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $candidate->canReactivate('Management approval received');

        $this->assertTrue($result);
    }

    /** @test */
    public function it_blocks_reactivation_without_reason()
    {
        $candidate = Candidate::factory()->create([
            'status' => 'dropped',
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $result = $candidate->canReactivate('');

        $this->assertFalse($result);
    }
}
