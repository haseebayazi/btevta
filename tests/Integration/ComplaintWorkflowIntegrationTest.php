<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Complaint;
use App\Models\ComplaintFollowUp;
use App\Services\ComplaintService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration test for complete complaint workflow.
 *
 * Tests the full workflow:
 * 1. Complaint registration
 * 2. Assignment to handler
 * 3. Investigation and follow-ups
 * 4. Escalation when needed
 * 5. Resolution and closure
 * 6. SLA tracking throughout
 */
class ComplaintWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Campus $campus;
    protected Candidate $candidate;
    protected ComplaintService $complaintService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->campusAdmin = User::factory()->campusAdmin()->create([
            'campus_id' => $this->campus->id,
        ]);
        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
        ]);

        $this->complaintService = app(ComplaintService::class);
    }

    /** @test */
    public function it_processes_complete_complaint_workflow_from_registration_to_resolution()
    {
        // ============================================================
        // STEP 1: Complaint Registration
        // ============================================================
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-2026-001',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'complaint_type' => 'contract_violation',
            'category' => 'employer_related',
            'priority' => 'high',
            'status' => 'open',
            'subject' => 'Salary not paid for 3 months',
            'description' => 'The employer has not paid my salary for the last 3 months despite multiple reminders.',
            'complainant_name' => $this->candidate->name,
            'complainant_contact' => $this->candidate->phone,
            'complainant_email' => $this->candidate->email,
            'reported_by' => $this->candidate->id,
            'reported_at' => now(),
            'sla_days' => 15, // High priority gets 15 days SLA
            'sla_due_date' => now()->addDays(15),
        ]);

        $this->assertDatabaseHas('complaints', [
            'complaint_number' => 'CMP-2026-001',
            'status' => 'open',
            'priority' => 'high',
        ]);

        // Verify SLA is set correctly
        $this->assertEquals(15, $complaint->sla_days);
        $this->assertFalse($complaint->sla_breached);

        // ============================================================
        // STEP 2: Assignment to Handler
        // ============================================================
        $complaint = $this->complaintService->assignComplaint(
            $complaint->id,
            $this->campusAdmin->id,
            $this->admin->id,
            'Assigning to campus admin for investigation'
        );

        $this->assertEquals('assigned', $complaint->status);
        $this->assertEquals($this->campusAdmin->id, $complaint->assigned_to);
        $this->assertNotNull($complaint->assigned_at);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'assigned',
            'assigned_to' => $this->campusAdmin->id,
        ]);

        // ============================================================
        // STEP 3: Investigation - Add Follow-ups
        // ============================================================
        $complaint->update(['status' => 'investigating']);

        // Follow-up 1: Contact employer
        ComplaintFollowUp::create([
            'complaint_id' => $complaint->id,
            'follow_up_date' => now(),
            'follow_up_by' => $this->campusAdmin->id,
            'action_taken' => 'Contacted employer via email regarding unpaid salary',
            'findings' => 'Employer acknowledged the issue, claims financial difficulties',
            'next_action' => 'Schedule meeting with employer and candidate',
            'next_action_date' => now()->addDays(3),
            'status' => 'in_progress',
        ]);

        $this->assertEquals(1, $complaint->followUps()->count());

        // Follow-up 2: Meeting conducted
        sleep(1); // Ensure different timestamp
        ComplaintFollowUp::create([
            'complaint_id' => $complaint->id,
            'follow_up_date' => now(),
            'follow_up_by' => $this->campusAdmin->id,
            'action_taken' => 'Conducted meeting with employer and candidate',
            'findings' => 'Employer agreed to pay 2 months salary immediately, remaining next month',
            'next_action' => 'Monitor payment and get confirmation from candidate',
            'next_action_date' => now()->addDays(7),
            'status' => 'in_progress',
        ]);

        $this->assertEquals(2, $complaint->followUps()->count());

        // ============================================================
        // STEP 4: Escalation (If needed)
        // ============================================================
        // Simulate: Employer doesn't pay as promised, needs escalation
        sleep(1);
        $complaint = $this->complaintService->escalateComplaint(
            $complaint->id,
            $this->campusAdmin->id,
            'Employer failed to pay as promised, escalating to higher authority'
        );

        $this->assertEquals(1, $complaint->escalation_level);
        $this->assertNotNull($complaint->escalated_at);

        // Add escalation follow-up
        ComplaintFollowUp::create([
            'complaint_id' => $complaint->id,
            'follow_up_date' => now(),
            'follow_up_by' => $this->admin->id,
            'action_taken' => 'Escalated to Ministry of Labor, formal notice sent to employer',
            'findings' => 'Ministry intervention initiated, employer summoned',
            'next_action' => 'Await ministry decision and payment confirmation',
            'next_action_date' => now()->addDays(5),
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'escalation_level' => 1,
        ]);

        // ============================================================
        // STEP 5: Resolution
        // ============================================================
        sleep(1);
        // Employer pays full amount after ministry intervention
        $complaint = $this->complaintService->resolveComplaint(
            $complaint->id,
            $this->admin->id,
            'Full salary paid to candidate after ministry intervention. Candidate confirmed receipt of all pending amounts.',
            'employer_complied'
        );

        $this->assertEquals('resolved', $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertNotNull($complaint->resolved_by);
        $this->assertEquals('employer_complied', $complaint->resolution_outcome);

        // Calculate resolution time
        $resolutionTime = $complaint->reported_at->diffInDays($complaint->resolved_at);
        $this->assertLessThan(15, $resolutionTime); // Resolved within SLA

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolution_outcome' => 'employer_complied',
        ]);

        // ============================================================
        // STEP 6: Closure
        // ============================================================
        $complaint->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => $this->admin->id,
        ]);

        $this->assertEquals('closed', $complaint->status);

        // ============================================================
        // VERIFICATION: Complete Workflow
        // ============================================================
        $finalComplaint = $complaint->fresh();

        // Verify status progression
        $this->assertEquals('closed', $finalComplaint->status);

        // Verify assignment
        $this->assertEquals($this->campusAdmin->id, $finalComplaint->assigned_to);

        // Verify escalation
        $this->assertEquals(1, $finalComplaint->escalation_level);

        // Verify resolution
        $this->assertEquals('resolved', $finalComplaint->status);
        $this->assertNotNull($finalComplaint->resolved_at);

        // Verify follow-ups
        $this->assertEquals(3, $finalComplaint->followUps()->count());

        // Verify SLA compliance
        $slaStatus = $this->complaintService->checkSLAStatus($finalComplaint->id);
        $this->assertFalse($slaStatus['breached']);
        $this->assertGreaterThan(0, $slaStatus['remaining_days']);
    }

    /** @test */
    public function it_handles_sla_breach_and_auto_escalation()
    {
        // Create complaint that's already past SLA
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-2026-002',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'complaint_type' => 'accommodation_issue',
            'category' => 'living_conditions',
            'priority' => 'medium',
            'status' => 'assigned',
            'subject' => 'Poor accommodation conditions',
            'description' => 'Living conditions are not as promised',
            'assigned_to' => $this->campusAdmin->id,
            'assigned_at' => now()->subDays(25),
            'reported_at' => now()->subDays(25),
            'sla_days' => 20, // Medium priority gets 20 days
            'sla_due_date' => now()->subDays(5), // Already breached
            'sla_breached' => true,
        ]);

        // Check SLA status
        $slaStatus = $this->complaintService->checkSLAStatus($complaint->id);

        $this->assertTrue($slaStatus['breached']);
        $this->assertLessThan(0, $slaStatus['remaining_days']);
        $this->assertEquals('critical', $slaStatus['status']);

        // Auto-escalate breached complaint
        if ($slaStatus['breached'] && $complaint->escalation_level < 3) {
            $complaint = $this->complaintService->escalateComplaint(
                $complaint->id,
                $this->admin->id,
                'Auto-escalation due to SLA breach'
            );
        }

        $this->assertEquals(1, $complaint->escalation_level);

        // Add urgent follow-up
        ComplaintFollowUp::create([
            'complaint_id' => $complaint->id,
            'follow_up_date' => now(),
            'follow_up_by' => $this->admin->id,
            'action_taken' => 'Urgent intervention - SLA breached',
            'findings' => 'Immediate action required',
            'next_action' => 'Fast-track resolution',
            'next_action_date' => now()->addDays(1),
            'status' => 'urgent',
        ]);

        $this->assertTrue($complaint->fresh()->sla_breached);
    }

    /** @test */
    public function it_handles_multiple_escalation_levels()
    {
        $complaint = Complaint::factory()->create([
            'campus_id' => $this->campus->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'assigned',
            'escalation_level' => 0,
        ]);

        // Level 1 escalation
        $complaint = $this->complaintService->escalateComplaint(
            $complaint->id,
            $this->campusAdmin->id,
            'Initial escalation'
        );
        $this->assertEquals(1, $complaint->escalation_level);

        // Level 2 escalation
        $complaint = $this->complaintService->escalateComplaint(
            $complaint->id,
            $this->admin->id,
            'Second level escalation'
        );
        $this->assertEquals(2, $complaint->escalation_level);

        // Level 3 escalation (maximum)
        $complaint = $this->complaintService->escalateComplaint(
            $complaint->id,
            $this->admin->id,
            'Final escalation to highest authority'
        );
        $this->assertEquals(3, $complaint->escalation_level);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'escalation_level' => 3,
        ]);
    }

    /** @test */
    public function it_tracks_complaint_resolution_time()
    {
        $reportedDate = now()->subDays(10);

        $complaint = Complaint::create([
            'complaint_number' => 'CMP-2026-003',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'complaint_type' => 'delayed_salary',
            'category' => 'financial',
            'priority' => 'high',
            'status' => 'open',
            'subject' => 'Salary delay',
            'description' => 'Salary payment delayed',
            'reported_at' => $reportedDate,
            'sla_days' => 15,
            'sla_due_date' => $reportedDate->copy()->addDays(15),
        ]);

        // Resolve complaint
        $complaint = $this->complaintService->resolveComplaint(
            $complaint->id,
            $this->admin->id,
            'Issue resolved',
            'employer_complied'
        );

        $resolutionTime = $complaint->reported_at->diffInDays($complaint->resolved_at);

        $this->assertNotNull($complaint->resolved_at);
        $this->assertGreaterThanOrEqual(10, $resolutionTime);
        $this->assertLessThan(15, $resolutionTime); // Within SLA

        // Store resolution time in complaint
        $complaint->update(['resolution_time_days' => $resolutionTime]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'resolution_time_days' => $resolutionTime,
        ]);
    }

    /** @test */
    public function it_links_complaints_to_candidate_journey()
    {
        // Create candidate with visa processing
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'deployed',
        ]);

        // Multiple complaints during different stages
        $complaints = [
            Complaint::factory()->create([
                'candidate_id' => $candidate->id,
                'campus_id' => $this->campus->id,
                'complaint_type' => 'documentation_issue',
                'status' => 'resolved',
                'reported_at' => now()->subMonths(3),
                'resolved_at' => now()->subMonths(3)->addDays(5),
            ]),
            Complaint::factory()->create([
                'candidate_id' => $candidate->id,
                'campus_id' => $this->campus->id,
                'complaint_type' => 'accommodation_issue',
                'status' => 'resolved',
                'reported_at' => now()->subMonth(),
                'resolved_at' => now()->subMonth()->addDays(10),
            ]),
            Complaint::factory()->create([
                'candidate_id' => $candidate->id,
                'campus_id' => $this->campus->id,
                'complaint_type' => 'contract_violation',
                'status' => 'investigating',
                'reported_at' => now()->subDays(5),
            ]),
        ];

        // Verify complaint history
        $this->assertEquals(3, $candidate->complaints()->count());

        $resolved = $candidate->complaints()->where('status', 'resolved')->count();
        $this->assertEquals(2, $resolved);

        $active = $candidate->complaints()->whereIn('status', ['open', 'assigned', 'investigating'])->count();
        $this->assertEquals(1, $active);

        // Get complaint statistics
        $totalComplaints = $candidate->complaints()->count();
        $resolvedComplaints = $candidate->complaints()->where('status', 'resolved')->count();
        $resolutionRate = $totalComplaints > 0 ? ($resolvedComplaints / $totalComplaints) * 100 : 0;

        $this->assertEquals(66.67, round($resolutionRate, 2));
    }

    /** @test */
    public function it_handles_complaint_reopening()
    {
        $complaint = Complaint::factory()->create([
            'campus_id' => $this->campus->id,
            'candidate_id' => $this->candidate->id,
            'status' => 'resolved',
            'resolved_at' => now()->subDays(5),
            'resolved_by' => $this->admin->id,
            'resolution' => 'Initial resolution',
            'resolution_outcome' => 'employer_complied',
        ]);

        // Candidate reports issue persists - reopen complaint
        $complaint->update([
            'status' => 'reopened',
            'reopened_at' => now(),
            'reopened_by' => $this->candidate->id,
            'reopening_reason' => 'Issue persists - employer did not fully comply',
        ]);

        $this->assertEquals('reopened', $complaint->fresh()->status);
        $this->assertNotNull($complaint->reopened_at);

        // Add new follow-up for reopened complaint
        ComplaintFollowUp::create([
            'complaint_id' => $complaint->id,
            'follow_up_date' => now(),
            'follow_up_by' => $this->admin->id,
            'action_taken' => 'Complaint reopened - investigating further',
            'findings' => 'Original resolution not effective',
            'next_action' => 'Re-engage with employer',
            'next_action_date' => now()->addDays(2),
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'reopened',
        ]);
    }
}
