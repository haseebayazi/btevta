<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\Candidate;
use App\Models\Batch;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Complaint;
use App\Models\User;
use App\Models\DocumentArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    /**
     * Test notification types constant
     */
    public function test_notification_types_are_defined(): void
    {
        $types = $this->service->getTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('screening_scheduled', $types);
        $this->assertArrayHasKey('remittance_recorded', $types);
        $this->assertArrayHasKey('document_expiry', $types);
    }

    /**
     * Test notification channels constant
     */
    public function test_notification_channels_are_defined(): void
    {
        $channels = $this->service->getChannels();

        $this->assertIsArray($channels);
        $this->assertArrayHasKey('email', $channels);
        $this->assertArrayHasKey('sms', $channels);
        $this->assertArrayHasKey('whatsapp', $channels);
        $this->assertArrayHasKey('in_app', $channels);
    }

    /**
     * Test sendDocumentUploaded handles missing candidate gracefully
     */
    public function test_send_document_uploaded_handles_missing_candidate(): void
    {
        // Create a mock document without candidate
        $document = Mockery::mock(DocumentArchive::class);
        $document->shouldReceive('getAttribute')->with('candidate')->andReturn(null);
        $document->shouldReceive('getAttribute')->with('id')->andReturn(1);

        $result = $this->service->sendDocumentUploaded($document);

        $this->assertTrue($result['success']);
        $this->assertEquals('No candidate linked to document', $result['skipped']);
    }

    /**
     * Test sendTrainingAssigned returns proper structure
     */
    public function test_send_training_assigned_returns_proper_structure(): void
    {
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();
        $batch = Batch::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);
        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'email' => 'test@example.com',
        ]);

        $result = $this->service->sendTrainingAssigned($candidate, $batch);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('email', $result);
    }

    /**
     * Test sendCertificateIssued handles candidate without certificate
     */
    public function test_send_certificate_issued_handles_no_certificate(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->service->sendCertificateIssued($candidate);

        $this->assertIsArray($result);
    }

    /**
     * Test sendComplaintRegistered works with candidate
     */
    public function test_send_complaint_registered_with_candidate(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $complaint = Complaint::factory()->create([
            'candidate_id' => $candidate->id,
            'category' => 'general',
        ]);

        $result = $this->service->sendComplaintRegistered($complaint);

        $this->assertIsArray($result);
    }

    /**
     * Test sendComplaintAssigned sends to user
     */
    public function test_send_complaint_assigned_to_user(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $complaint = Complaint::factory()->create([
            'category' => 'general',
            'priority' => 'normal',
        ]);

        $result = $this->service->sendComplaintAssigned($complaint, $user);

        $this->assertIsArray($result);
    }

    /**
     * Test sendVisaProcessInitiated returns result
     */
    public function test_send_visa_process_initiated(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->service->sendVisaProcessInitiated($candidate);

        $this->assertIsArray($result);
    }

    /**
     * Test sendVisaStageCompleted with stage name
     */
    public function test_send_visa_stage_completed(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->service->sendVisaStageCompleted($candidate, 'Interview');

        $this->assertIsArray($result);
    }

    /**
     * Test sendBriefingCompleted
     */
    public function test_send_briefing_completed(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->service->sendBriefingCompleted($candidate);

        $this->assertIsArray($result);
    }

    /**
     * Test sendComplianceAchieved
     */
    public function test_send_compliance_achieved(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $result = $this->service->sendComplianceAchieved($candidate);

        $this->assertIsArray($result);
    }

    /**
     * Test sendIssueReported with issue array
     */
    public function test_send_issue_reported(): void
    {
        $candidate = Candidate::factory()->create([
            'email' => 'test@example.com',
        ]);

        $issue = [
            'type' => 'documentation',
            'description' => 'Missing passport copy',
        ];

        $result = $this->service->sendIssueReported($candidate, $issue);

        $this->assertIsArray($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
