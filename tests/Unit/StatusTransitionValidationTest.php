<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\Candidate;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class StatusTransitionValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Enable status transition validation for these tests
        config(['wasl.enforce_status_transitions' => true]);
    }

    protected function tearDown(): void
    {
        // Disable after tests
        config(['wasl.enforce_status_transitions' => false]);
        parent::tearDown();
    }

    #[Test]
    public function it_allows_valid_forward_transitions()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
        ]);

        // Valid: LISTED -> PRE_DEPARTURE_DOCS
        $candidate->update(['status' => CandidateStatus::PRE_DEPARTURE_DOCS->value]);
        $this->assertEquals(CandidateStatus::PRE_DEPARTURE_DOCS->value, $candidate->fresh()->status);

        // Valid: PRE_DEPARTURE_DOCS -> SCREENING
        $candidate->update(['status' => CandidateStatus::SCREENING->value]);
        $this->assertEquals(CandidateStatus::SCREENING->value, $candidate->fresh()->status);
    }

    #[Test]
    public function it_blocks_invalid_transitions()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
        ]);

        // Invalid: LISTED -> DEPARTED (skipping intermediate statuses)
        $this->expectException(ValidationException::class);
        $candidate->update(['status' => CandidateStatus::DEPARTED->value]);
    }

    #[Test]
    public function it_allows_transitions_to_terminal_states()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::SCREENING->value,
        ]);

        // Valid: SCREENING -> DEFERRED (terminal state always allowed from active status)
        $candidate->update(['status' => CandidateStatus::DEFERRED->value]);
        $this->assertEquals(CandidateStatus::DEFERRED->value, $candidate->fresh()->status);
    }

    #[Test]
    public function it_blocks_transitions_from_terminal_states()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::COMPLETED->value,
        ]);

        // Invalid: COMPLETED -> anything (terminal states have no valid transitions)
        $this->expectException(ValidationException::class);
        $candidate->update(['status' => CandidateStatus::LISTED->value]);
    }

    #[Test]
    public function it_allows_any_transition_when_enforcement_disabled()
    {
        // Disable enforcement
        config(['wasl.enforce_status_transitions' => false]);

        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
        ]);

        // This would be invalid with enforcement, but should work without
        $candidate->update(['status' => CandidateStatus::DEPARTED->value]);
        $this->assertEquals(CandidateStatus::DEPARTED->value, $candidate->fresh()->status);
    }

    #[Test]
    public function it_provides_helpful_error_message_for_invalid_transitions()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::LISTED->value,
        ]);

        try {
            $candidate->update(['status' => CandidateStatus::DEPARTED->value]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
            $this->assertStringContainsString('Invalid status transition', $e->errors()['status'][0]);
            $this->assertStringContainsString('Valid transitions', $e->errors()['status'][0]);
        }
    }
}
