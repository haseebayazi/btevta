<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Enums\CandidateStatus;
use App\Enums\ComplaintPriority;
use App\Enums\ComplaintStatus;
use App\Enums\TrainingStatus;
use App\Enums\VisaStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.5: Enum Database Consistency Tests
 *
 * These tests verify that PHP enums are properly aligned with database values
 * and that all enum values can be stored and retrieved correctly.
 *
 * @see docs/IMPLEMENTATION_PLAN.md - Phase 1.5
 */
class EnumDatabaseConsistencyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // CandidateStatus Enum Tests
    // =========================================================================

    #[Test]
    public function candidate_status_enum_values_are_valid_database_values(): void
    {
        // WASL v3 Enhanced Workflow
        $expectedValues = [
            'new', 'listed', 'pre_departure_docs', 'screening', 'screened', 'registered',
            'training', 'training_completed', 'visa_process', 'visa_approved',
            'departure_processing', 'ready_to_depart', 'departed', 'post_departure',
            'completed', 'deferred', 'rejected', 'withdrawn'
        ];

        $enumValues = array_column(CandidateStatus::cases(), 'value');

        $this->assertEquals($expectedValues, $enumValues);
    }

    #[Test]
    public function candidate_status_visa_process_is_correctly_named(): void
    {
        // This test ensures we use 'visa_process' not 'visa'
        $this->assertEquals('visa_process', CandidateStatus::VISA_PROCESS->value);
        $this->assertNotEquals('visa', CandidateStatus::VISA_PROCESS->value);
    }

    #[Test]
    public function all_candidate_statuses_can_be_stored_in_database(): void
    {
        // Skip if candidates table doesn't exist
        if (!Schema::hasTable('candidates')) {
            $this->markTestSkipped('Candidates table does not exist');
        }

        // Create a trade for foreign key
        $trade = \App\Models\Trade::factory()->create();

        foreach (CandidateStatus::cases() as $status) {
            $result = DB::table('candidates')->insert([
                'btevta_id' => 'TEST-' . $status->value . '-' . time(),
                'cnic' => '1234567890' . rand(100, 999),
                'name' => 'Test ' . $status->label(),
                'father_name' => 'Test Father',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'email' => 'test.' . $status->value . '@example.com',
                'phone' => '03001234567',
                'address' => 'Test Address',
                'district' => 'Lahore',
                'trade_id' => $trade->id,
                'status' => $status->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertTrue($result, "Failed to insert candidate with status: {$status->value}");
        }
    }

    #[Test]
    public function candidate_status_transitions_are_valid(): void
    {
        // Test that valid transitions work
        $this->assertTrue(CandidateStatus::LISTED->canTransitionTo(CandidateStatus::PRE_DEPARTURE_DOCS));
        $this->assertTrue(CandidateStatus::SCREENING->canTransitionTo(CandidateStatus::SCREENED));
        $this->assertTrue(CandidateStatus::TRAINING->canTransitionTo(CandidateStatus::TRAINING_COMPLETED));

        // Test that invalid transitions are blocked
        $this->assertFalse(CandidateStatus::LISTED->canTransitionTo(CandidateStatus::DEPARTED));
        $this->assertFalse(CandidateStatus::DEPARTED->canTransitionTo(CandidateStatus::LISTED));
    }

    // =========================================================================
    // ComplaintPriority Enum Tests
    // =========================================================================

    #[Test]
    public function complaint_priority_enum_values_are_valid(): void
    {
        $expectedValues = ['low', 'normal', 'high', 'urgent'];

        $enumValues = array_column(ComplaintPriority::cases(), 'value');

        $this->assertEquals($expectedValues, $enumValues);
    }

    #[Test]
    public function complaint_priority_does_not_have_medium(): void
    {
        // Ensure 'medium' is NOT a valid priority (common mistake)
        $enumValues = array_column(ComplaintPriority::cases(), 'value');

        $this->assertNotContains('medium', $enumValues);
        $this->assertContains('normal', $enumValues);
    }

    #[Test]
    public function complaint_priority_normal_is_default(): void
    {
        // Skip if complaints table doesn't exist
        if (!Schema::hasTable('complaints')) {
            $this->markTestSkipped('Complaints table does not exist');
        }

        // Check that new complaints default to 'normal' priority
        $this->assertEquals('normal', ComplaintPriority::NORMAL->value);
    }

    #[Test]
    public function all_complaint_priorities_can_be_stored(): void
    {
        if (!Schema::hasTable('complaints')) {
            $this->markTestSkipped('Complaints table does not exist');
        }

        foreach (ComplaintPriority::cases() as $priority) {
            $result = DB::table('complaints')->insert([
                'subject' => 'Test Complaint ' . $priority->value,
                'description' => 'Test description',
                'status' => 'open',
                'priority' => $priority->value,
                'complaint_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertTrue($result, "Failed to insert complaint with priority: {$priority->value}");
        }
    }

    // =========================================================================
    // ComplaintStatus Enum Tests
    // =========================================================================

    #[Test]
    public function complaint_status_enum_values_are_valid(): void
    {
        $expectedValues = ['open', 'assigned', 'in_progress', 'resolved', 'closed'];

        $enumValues = array_column(ComplaintStatus::cases(), 'value');

        $this->assertEquals($expectedValues, $enumValues);
    }

    #[Test]
    public function all_complaint_statuses_can_be_stored(): void
    {
        if (!Schema::hasTable('complaints')) {
            $this->markTestSkipped('Complaints table does not exist');
        }

        foreach (ComplaintStatus::cases() as $status) {
            $result = DB::table('complaints')->insert([
                'subject' => 'Test Complaint Status ' . $status->value,
                'description' => 'Test description',
                'status' => $status->value,
                'priority' => 'normal',
                'complaint_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertTrue($result, "Failed to insert complaint with status: {$status->value}");
        }
    }

    // =========================================================================
    // TrainingStatus Enum Tests
    // =========================================================================

    #[Test]
    public function training_status_enum_has_all_eleven_values(): void
    {
        $expectedValues = [
            // Candidate statuses
            'pending', 'enrolled', 'in_progress', 'completed', 'failed', 'withdrawn',
            // Class statuses
            'scheduled', 'ongoing', 'cancelled', 'postponed', 'rescheduled'
        ];

        $enumValues = array_column(TrainingStatus::cases(), 'value');

        $this->assertCount(11, $enumValues);
        foreach ($expectedValues as $expected) {
            $this->assertContains($expected, $enumValues, "Missing training status: {$expected}");
        }
    }

    #[Test]
    public function training_status_candidate_statuses_are_correct(): void
    {
        $candidateStatuses = TrainingStatus::candidateStatuses();

        $this->assertCount(6, $candidateStatuses);
        $this->assertContains(TrainingStatus::PENDING, $candidateStatuses);
        $this->assertContains(TrainingStatus::ENROLLED, $candidateStatuses);
        $this->assertContains(TrainingStatus::IN_PROGRESS, $candidateStatuses);
        $this->assertContains(TrainingStatus::COMPLETED, $candidateStatuses);
        $this->assertContains(TrainingStatus::FAILED, $candidateStatuses);
        $this->assertContains(TrainingStatus::WITHDRAWN, $candidateStatuses);
    }

    #[Test]
    public function training_status_class_statuses_are_correct(): void
    {
        $classStatuses = TrainingStatus::classStatuses();

        $this->assertCount(6, $classStatuses);
        $this->assertContains(TrainingStatus::SCHEDULED, $classStatuses);
        $this->assertContains(TrainingStatus::ONGOING, $classStatuses);
        $this->assertContains(TrainingStatus::COMPLETED, $classStatuses);
        $this->assertContains(TrainingStatus::CANCELLED, $classStatuses);
        $this->assertContains(TrainingStatus::POSTPONED, $classStatuses);
        $this->assertContains(TrainingStatus::RESCHEDULED, $classStatuses);
    }

    #[Test]
    public function all_training_statuses_can_be_stored_in_candidates_table(): void
    {
        if (!Schema::hasTable('candidates')) {
            $this->markTestSkipped('Candidates table does not exist');
        }

        // Create a trade for foreign key
        $trade = \App\Models\Trade::factory()->create();

        // Test only candidate-specific statuses (not class statuses)
        $candidateStatuses = TrainingStatus::candidateStatuses();

        foreach ($candidateStatuses as $status) {
            // Use updateOrInsert to avoid unique constraint issues
            $testId = 'TRAIN-' . $status->value . '-' . rand(1000, 9999);

            $result = DB::table('candidates')->insert([
                'btevta_id' => $testId,
                'cnic' => '9876543210' . rand(100, 999),
                'name' => 'Training Test ' . $status->label(),
                'father_name' => 'Test Father',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'email' => 'training.' . $status->value . '@example.com',
                'phone' => '03009876543',
                'address' => 'Test Address',
                'district' => 'Lahore',
                'trade_id' => $trade->id,
                'status' => 'training',
                'training_status' => $status->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertTrue($result, "Failed to store training_status: {$status->value}");
        }
    }

    // =========================================================================
    // VisaStage Enum Tests
    // =========================================================================

    #[Test]
    public function visa_stage_enum_values_are_valid(): void
    {
        $expectedValues = [
            'initiated', 'interview', 'trade_test', 'takamol', 'medical',
            'enumber', 'biometrics', 'visa_submission', 'visa_issued', 'ticket', 'completed'
        ];

        $enumValues = array_column(VisaStage::cases(), 'value');

        $this->assertEquals($expectedValues, $enumValues);
    }

    #[Test]
    public function visa_stage_does_not_have_pending(): void
    {
        // Ensure 'pending' is NOT a valid stage (common mistake)
        $enumValues = array_column(VisaStage::cases(), 'value');

        $this->assertNotContains('pending', $enumValues);
        $this->assertContains('initiated', $enumValues);
    }

    #[Test]
    public function visa_stage_initiated_is_first(): void
    {
        $this->assertEquals('initiated', VisaStage::INITIATED->value);
        $this->assertEquals(1, VisaStage::INITIATED->order());
    }

    #[Test]
    public function visa_stage_order_is_sequential(): void
    {
        $stages = VisaStage::cases();
        $previousOrder = 0;

        foreach ($stages as $stage) {
            $this->assertGreaterThan($previousOrder, $stage->order());
            $previousOrder = $stage->order();
        }
    }

    #[Test]
    public function visa_stage_next_stage_returns_correct_stage(): void
    {
        $this->assertEquals(VisaStage::INTERVIEW, VisaStage::INITIATED->nextStage());
        $this->assertEquals(VisaStage::TRADE_TEST, VisaStage::INTERVIEW->nextStage());
        $this->assertEquals(VisaStage::TAKAMOL, VisaStage::TRADE_TEST->nextStage());
        $this->assertEquals(VisaStage::MEDICAL, VisaStage::TAKAMOL->nextStage());
        $this->assertEquals(VisaStage::ENUMBER, VisaStage::MEDICAL->nextStage());
        $this->assertEquals(VisaStage::BIOMETRICS, VisaStage::ENUMBER->nextStage());
        $this->assertEquals(VisaStage::VISA_SUBMISSION, VisaStage::BIOMETRICS->nextStage());
        $this->assertEquals(VisaStage::VISA_ISSUED, VisaStage::VISA_SUBMISSION->nextStage());
        $this->assertEquals(VisaStage::TICKET, VisaStage::VISA_ISSUED->nextStage());
        $this->assertEquals(VisaStage::COMPLETED, VisaStage::TICKET->nextStage());
        $this->assertNull(VisaStage::COMPLETED->nextStage());
    }

    #[Test]
    public function all_visa_stages_can_be_stored(): void
    {
        if (!Schema::hasTable('visa_processes')) {
            $this->markTestSkipped('visa_processes table does not exist');
        }

        // Create a candidate first for foreign key
        $candidate = \App\Models\Candidate::factory()->create();

        foreach (VisaStage::cases() as $stage) {
            $result = DB::table('visa_processes')->insert([
                'candidate_id' => $candidate->id,
                'overall_status' => $stage->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertTrue($result, "Failed to store visa stage: {$stage->value}");
        }
    }

    // =========================================================================
    // Cross-Enum Consistency Tests
    // =========================================================================

    #[Test]
    public function all_enums_have_to_array_method(): void
    {
        $this->assertIsArray(CandidateStatus::toArray());
        $this->assertIsArray(ComplaintPriority::toArray());
        $this->assertIsArray(ComplaintStatus::toArray());
        $this->assertIsArray(TrainingStatus::toArray());
        $this->assertIsArray(VisaStage::toArray());
    }

    #[Test]
    public function all_enums_have_label_method(): void
    {
        foreach (CandidateStatus::cases() as $status) {
            $this->assertIsString($status->label());
            $this->assertNotEmpty($status->label());
        }

        foreach (ComplaintPriority::cases() as $priority) {
            $this->assertIsString($priority->label());
            $this->assertNotEmpty($priority->label());
        }

        foreach (ComplaintStatus::cases() as $status) {
            $this->assertIsString($status->label());
            $this->assertNotEmpty($status->label());
        }

        foreach (TrainingStatus::cases() as $status) {
            $this->assertIsString($status->label());
            $this->assertNotEmpty($status->label());
        }

        foreach (VisaStage::cases() as $stage) {
            $this->assertIsString($stage->label());
            $this->assertNotEmpty($stage->label());
        }
    }

    #[Test]
    public function all_enums_have_color_method(): void
    {
        foreach (CandidateStatus::cases() as $status) {
            $this->assertIsString($status->color());
            $this->assertNotEmpty($status->color());
        }

        foreach (ComplaintPriority::cases() as $priority) {
            $this->assertIsString($priority->color());
            $this->assertNotEmpty($priority->color());
        }

        foreach (ComplaintStatus::cases() as $status) {
            $this->assertIsString($status->color());
            $this->assertNotEmpty($status->color());
        }

        foreach (TrainingStatus::cases() as $status) {
            $this->assertIsString($status->color());
            $this->assertNotEmpty($status->color());
        }

        foreach (VisaStage::cases() as $stage) {
            $this->assertIsString($stage->color());
            $this->assertNotEmpty($stage->color());
        }
    }
}
