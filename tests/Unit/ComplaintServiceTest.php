<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\Complaint;
use App\Models\Candidate;
use App\Models\User;
use App\Services\ComplaintService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComplaintServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ComplaintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplaintService();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    // =========================================================================
    // COMPLAINT REGISTRATION
    // =========================================================================

    #[Test]
    public function it_can_register_a_complaint()
    {
        $data = [
            'complainant_name' => 'Test Complainant',
            'complainant_contact' => '03001234567',
            'complaint_category' => 'training',
            'subject' => 'Training Issue',
            'description' => 'Detailed description of the issue',
            'priority' => 'normal',
        ];

        $complaint = $this->service->registerComplaint($data);

        $this->assertInstanceOf(Complaint::class, $complaint);
        $this->assertDatabaseHas('complaints', [
            'complainant_name' => 'Test Complainant',
            'status' => 'open',
        ]);
    }

    #[Test]
    public function it_generates_unique_reference_number()
    {
        $data = [
            'complainant_name' => 'Test 1',
            'complainant_contact' => '03001234567',
            'complaint_category' => 'training',
            'subject' => 'Issue 1',
            'description' => 'Description',
            'priority' => 'normal',
        ];

        $complaint1 = $this->service->registerComplaint($data);
        $complaint2 = $this->service->registerComplaint(array_merge($data, ['complainant_name' => 'Test 2']));

        $this->assertNotEquals($complaint1->complaint_reference, $complaint2->complaint_reference);
        $this->assertStringStartsWith('CMP-TRA-', $complaint1->complaint_reference);
    }

    #[Test]
    public function it_sets_sla_based_on_priority()
    {
        $normalComplaint = $this->service->registerComplaint([
            'complainant_name' => 'Test',
            'complainant_contact' => '03001234567',
            'complaint_category' => 'training',
            'subject' => 'Normal Priority',
            'description' => 'Description',
            'priority' => 'normal',
        ]);

        $criticalComplaint = $this->service->registerComplaint([
            'complainant_name' => 'Test',
            'complainant_contact' => '03001234567',
            'complaint_category' => 'training',
            'subject' => 'Critical Priority',
            'description' => 'Description',
            'priority' => 'critical',
        ]);

        $this->assertEquals(7, $normalComplaint->sla_days);
        $this->assertEquals(1, $criticalComplaint->sla_days);
    }

    // =========================================================================
    // STATUS TRANSITIONS - VALID
    // =========================================================================

    #[Test]
    public function open_complaint_can_transition_to_assigned()
    {
        $complaint = Complaint::factory()->create(['status' => 'open']);

        $result = $this->service->updateStatus($complaint->id, 'assigned');

        $this->assertEquals('assigned', $result->status);
    }

    #[Test]
    public function open_complaint_can_transition_to_in_progress()
    {
        $complaint = Complaint::factory()->create(['status' => 'open']);

        $result = $this->service->updateStatus($complaint->id, 'in_progress');

        $this->assertEquals('in_progress', $result->status);
    }

    #[Test]
    public function open_complaint_can_transition_to_resolved()
    {
        $complaint = Complaint::factory()->create(['status' => 'open']);

        $result = $this->service->updateStatus($complaint->id, 'resolved');

        $this->assertEquals('resolved', $result->status);
    }

    #[Test]
    public function assigned_complaint_can_transition_to_in_progress()
    {
        $complaint = Complaint::factory()->create(['status' => 'assigned']);

        $result = $this->service->updateStatus($complaint->id, 'in_progress');

        $this->assertEquals('in_progress', $result->status);
    }

    #[Test]
    public function in_progress_complaint_can_transition_to_resolved()
    {
        $complaint = Complaint::factory()->create(['status' => 'in_progress']);

        $result = $this->service->updateStatus($complaint->id, 'resolved');

        $this->assertEquals('resolved', $result->status);
    }

    #[Test]
    public function resolved_complaint_can_transition_to_closed()
    {
        $complaint = Complaint::factory()->create(['status' => 'resolved']);

        $result = $this->service->updateStatus($complaint->id, 'closed');

        $this->assertEquals('closed', $result->status);
    }

    #[Test]
    public function resolved_complaint_can_be_reopened()
    {
        $complaint = Complaint::factory()->create(['status' => 'resolved']);

        $result = $this->service->updateStatus($complaint->id, 'open');

        $this->assertEquals('open', $result->status);
    }

    #[Test]
    public function closed_complaint_can_be_reopened()
    {
        $complaint = Complaint::factory()->create(['status' => 'closed']);

        $result = $this->service->updateStatus($complaint->id, 'open');

        $this->assertEquals('open', $result->status);
    }

    // =========================================================================
    // STATUS TRANSITIONS - INVALID
    // =========================================================================

    #[Test]
    public function open_complaint_cannot_transition_to_closed()
    {
        $complaint = Complaint::factory()->create(['status' => 'open']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $this->service->updateStatus($complaint->id, 'closed');
    }

    #[Test]
    public function assigned_cannot_transition_to_closed()
    {
        $complaint = Complaint::factory()->create(['status' => 'assigned']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $this->service->updateStatus($complaint->id, 'closed');
    }

    #[Test]
    public function in_progress_cannot_transition_to_closed()
    {
        $complaint = Complaint::factory()->create(['status' => 'in_progress']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $this->service->updateStatus($complaint->id, 'closed');
    }

    #[Test]
    public function in_progress_cannot_transition_to_open()
    {
        $complaint = Complaint::factory()->create(['status' => 'in_progress']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid status transition');

        $this->service->updateStatus($complaint->id, 'open');
    }

    // =========================================================================
    // SLA CALCULATIONS
    // =========================================================================

    #[Test]
    public function escalation_recalculates_sla_from_current_date()
    {
        $complaint = Complaint::factory()->create([
            'status' => 'in_progress',
            'priority' => 'low',
            'registered_at' => Carbon::now()->subDays(15),
            'sla_days' => 10,
            'sla_due_date' => Carbon::now()->subDays(5), // Already overdue
        ]);

        Carbon::setTestNow(Carbon::now());

        $result = $this->service->escalateComplaint($complaint->id, 'Escalated due to delay');

        // Priority should increase from low to normal
        $this->assertEquals('normal', $result->priority);
        // SLA should be recalculated from NOW (7 days for normal)
        $this->assertEquals(7, $result->sla_days);
        // Due date should be in the future (7 days from now)
        $this->assertTrue($result->sla_due_date->isFuture());
    }

    #[Test]
    public function escalation_increases_priority()
    {
        $lowPriority = Complaint::factory()->create(['priority' => 'low', 'escalation_level' => 0]);
        $this->service->escalateComplaint($lowPriority->id);
        $this->assertEquals('normal', $lowPriority->fresh()->priority);

        $normalPriority = Complaint::factory()->create(['priority' => 'normal', 'escalation_level' => 0]);
        $this->service->escalateComplaint($normalPriority->id);
        $this->assertEquals('high', $normalPriority->fresh()->priority);

        $highPriority = Complaint::factory()->create(['priority' => 'high', 'escalation_level' => 0]);
        $this->service->escalateComplaint($highPriority->id);
        $this->assertEquals('urgent', $highPriority->fresh()->priority);

        $urgentPriority = Complaint::factory()->create(['priority' => 'urgent', 'escalation_level' => 0]);
        $this->service->escalateComplaint($urgentPriority->id);
        $this->assertEquals('critical', $urgentPriority->fresh()->priority);
    }

    #[Test]
    public function escalation_increases_level()
    {
        $complaint = Complaint::factory()->create(['escalation_level' => 0]);

        $this->service->escalateComplaint($complaint->id);
        $this->assertEquals(1, $complaint->fresh()->escalation_level);

        $this->service->escalateComplaint($complaint->id);
        $this->assertEquals(2, $complaint->fresh()->escalation_level);
    }

    #[Test]
    public function escalation_level_cannot_exceed_maximum()
    {
        $complaint = Complaint::factory()->create(['escalation_level' => 4]);

        $this->service->escalateComplaint($complaint->id);

        $this->assertEquals(4, $complaint->fresh()->escalation_level);
    }

    #[Test]
    public function check_sla_status_returns_correct_status()
    {
        // On track complaint
        $onTrack = Complaint::factory()->create([
            'sla_due_date' => Carbon::now()->addDays(5),
        ]);
        $status = $this->service->checkSLAStatus($onTrack->id);
        $this->assertEquals('on_track', $status['status']);
        $this->assertFalse($status['is_overdue']);

        // Critical complaint (due within 24 hours)
        $critical = Complaint::factory()->create([
            'sla_due_date' => Carbon::now()->addHours(12),
        ]);
        $status = $this->service->checkSLAStatus($critical->id);
        $this->assertEquals('critical', $status['status']);
        $this->assertFalse($status['is_overdue']);

        // Overdue complaint
        $overdue = Complaint::factory()->create([
            'sla_due_date' => Carbon::now()->subDays(2),
        ]);
        $status = $this->service->checkSLAStatus($overdue->id);
        $this->assertEquals('overdue', $status['status']);
        $this->assertTrue($status['is_overdue']);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_calculates_statistics_correctly()
    {
        // Create various complaints
        Complaint::factory()->count(3)->create(['status' => 'open']);
        Complaint::factory()->count(2)->create(['status' => 'assigned']);
        Complaint::factory()->count(5)->create(['status' => 'resolved']);
        Complaint::factory()->count(1)->create([
            'status' => 'in_progress',
            'sla_due_date' => Carbon::now()->subDay(),
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(11, $stats['total_complaints']);
        $this->assertEquals(3, $stats['open']);
        $this->assertEquals(2, $stats['assigned']);
        $this->assertEquals(5, $stats['resolved']);
        $this->assertEquals(1, $stats['overdue']);
    }

    #[Test]
    public function it_calculates_sla_compliance_rate()
    {
        // Create resolved complaints within SLA
        Complaint::factory()->count(8)->create([
            'status' => 'resolved',
            'registered_at' => Carbon::now()->subDays(3),
            'resolved_at' => Carbon::now()->subDay(),
            'sla_due_date' => Carbon::now(),
        ]);

        // Create resolved complaints outside SLA
        Complaint::factory()->count(2)->create([
            'status' => 'resolved',
            'registered_at' => Carbon::now()->subDays(10),
            'resolved_at' => Carbon::now(),
            'sla_due_date' => Carbon::now()->subDays(3),
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(80.0, $stats['sla_compliance_rate']);
    }

    // =========================================================================
    // RESOLUTION
    // =========================================================================

    #[Test]
    public function resolving_complaint_sets_resolution_details()
    {
        $complaint = Complaint::factory()->create(['status' => 'in_progress']);

        $result = $this->service->resolveComplaint($complaint->id, [
            'resolution_details' => 'Issue was resolved by contacting the instructor.',
            'action_taken' => 'Schedule change',
            'resolution_category' => 'accepted',
        ]);

        $this->assertEquals('resolved', $result->status);
        $this->assertNotNull($result->resolved_at);
        $this->assertEquals('Issue was resolved by contacting the instructor.', $result->resolution_details);
    }

    #[Test]
    public function close_complaint_requires_resolved_status()
    {
        $complaint = Complaint::factory()->create(['status' => 'in_progress']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be resolved before closing');

        $this->service->closeComplaint($complaint->id);
    }

    #[Test]
    public function resolved_complaint_can_be_closed()
    {
        $complaint = Complaint::factory()->create(['status' => 'resolved']);

        $result = $this->service->closeComplaint($complaint->id, 'Complainant satisfied');

        $this->assertEquals('closed', $result->status);
        $this->assertNotNull($result->closed_at);
        $this->assertEquals('Complainant satisfied', $result->closure_remarks);
    }

    // =========================================================================
    // ASSIGNMENT
    // =========================================================================

    #[Test]
    public function it_can_assign_complaint_to_user()
    {
        $complaint = Complaint::factory()->create(['status' => 'open']);
        $user = User::factory()->create();

        $result = $this->service->assignComplaint($complaint->id, $user->id, 'Please handle this');

        $this->assertEquals($user->id, $result->assigned_to);
        $this->assertEquals('assigned', $result->status);
        $this->assertNotNull($result->assigned_at);
    }

    #[Test]
    public function it_can_add_evidence_using_uploaded_file()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $complaint = Complaint::factory()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->create('evidence.pdf', 100);

        // Pass UploadedFile directly
        $result = $this->service->addEvidence($complaint->id, $file, 'Uploaded file test');

        $this->assertArrayHasKey('path', $result);
        $this->assertEquals('Uploaded file test', $result['description']);

        $complaintFresh = Complaint::find($complaint->id);
        $this->assertNotNull($complaintFresh->evidence_files);
        $files = json_decode($complaintFresh->evidence_files, true);
        $this->assertCount(1, $files);
        $this->assertEquals('evidence.pdf', $files[0]['original_name']);
    }

    #[Test]
    public function it_can_add_evidence_using_existing_file_path()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $complaint = Complaint::factory()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->create('evidence2.pdf', 100);
        $path = $file->store('complaints/evidence', 'public');

        // Pass stored path string
        $result = $this->service->addEvidence($complaint->id, $path, 'Path file test');

        $this->assertArrayHasKey('path', $result);
        $this->assertEquals('Path file test', $result['description']);

        $complaintFresh = Complaint::find($complaint->id);
        $this->assertNotNull($complaintFresh->evidence_files);
        $files = json_decode($complaintFresh->evidence_files, true);
        $this->assertCount(1, $files);
        $this->assertEquals('evidence2.pdf', $files[0]['original_name']);
    }
}
