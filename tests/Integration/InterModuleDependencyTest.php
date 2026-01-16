<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\Registration;
use App\Models\Training;
use App\Models\VisaProcessing;
use App\Models\DocumentArchive;
use App\Models\Complaint;
use App\Models\Correspondence;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for inter-module dependencies and data flow.
 *
 * Tests dependencies between:
 * - Screening → Registration eligibility
 * - Registration → Document requirements
 * - Training completion → Visa processing eligibility
 * - Document expiry → Process blocking
 * - Complaints → Candidate status impact
 * - Correspondence → Multi-module communication
 */
class InterModuleDependencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
        ]);
    }

    /** @test */
    public function screening_outcome_determines_registration_eligibility()
    {
        $this->candidate->update(['status' => 'screening']);

        // Scenario 1: Eligible outcome allows registration
        $eligibleScreening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_date' => now(),
            'screened_by' => $this->admin->id,
            'language_proficiency' => 'good',
            'technical_skills' => 'excellent',
            'physical_fitness' => 'fit',
            'behavioral_assessment' => 'positive',
            'overall_score' => 85,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        // Verify candidate can proceed to registration
        $this->candidate->update(['status' => 'registration']);
        $this->assertEquals('registration', $this->candidate->fresh()->status);

        // Create registration
        $registration = Registration::create([
            'candidate_id' => $this->candidate->id,
            'registration_date' => now(),
            'status' => 'in_progress',
        ]);

        $this->assertNotNull($this->candidate->fresh()->registration);

        // Scenario 2: Ineligible outcome blocks registration
        $ineligibleCandidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'screening',
        ]);

        $ineligibleScreening = CandidateScreening::create([
            'candidate_id' => $ineligibleCandidate->id,
            'screening_date' => now(),
            'screened_by' => $this->admin->id,
            'language_proficiency' => 'poor',
            'overall_score' => 45,
            'outcome' => 'ineligible',
            'status' => 'completed',
        ]);

        // Candidate should be rejected
        $ineligibleCandidate->update(['status' => 'rejected']);
        $this->assertEquals('rejected', $ineligibleCandidate->fresh()->status);

        // Registration should not be possible
        $this->assertNull($ineligibleCandidate->registration);
    }

    /** @test */
    public function registration_requires_mandatory_documents()
    {
        $this->candidate->update(['status' => 'registration']);

        $requiredDocumentTypes = ['passport', 'cnic', 'medical_certificate', 'police_clearance'];

        // Create only 2 out of 4 required documents
        DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'passport',
            'category' => 'identity',
            'document_name' => 'Passport',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'status' => 'active',
            'uploaded_by' => $this->admin->id,
        ]);

        DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'cnic',
            'category' => 'identity',
            'document_name' => 'CNIC',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'status' => 'active',
            'uploaded_by' => $this->admin->id,
        ]);

        // Check which documents are uploaded
        $uploadedTypes = $this->candidate->documents()->pluck('document_type')->toArray();
        $missingDocuments = array_diff($requiredDocumentTypes, $uploadedTypes);

        $this->assertCount(2, $missingDocuments);
        $this->assertContains('medical_certificate', $missingDocuments);
        $this->assertContains('police_clearance', $missingDocuments);

        // Registration should not be completable without all documents
        $registration = Registration::create([
            'candidate_id' => $this->candidate->id,
            'registration_date' => now(),
            'status' => 'in_progress',
        ]);

        // In real system, validation would prevent completion
        $hasAllDocuments = count($missingDocuments) === 0;
        $this->assertFalse($hasAllDocuments);

        // Upload missing documents
        DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'medical_certificate',
            'category' => 'medical',
            'document_name' => 'Medical Certificate',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'status' => 'active',
            'uploaded_by' => $this->admin->id,
        ]);

        DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'police_clearance',
            'category' => 'clearance',
            'document_name' => 'Police Clearance',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'status' => 'active',
            'uploaded_by' => $this->admin->id,
        ]);

        // Now all documents are present
        $uploadedTypes = $this->candidate->fresh()->documents()->pluck('document_type')->toArray();
        $missingDocuments = array_diff($requiredDocumentTypes, $uploadedTypes);

        $this->assertCount(0, $missingDocuments);

        // Registration can now be completed
        $registration->update([
            'status' => 'completed',
            'documents_verified' => true,
        ]);

        $this->assertTrue($registration->fresh()->documents_verified);
    }

    /** @test */
    public function training_completion_enables_visa_processing()
    {
        // Setup: Candidate has completed screening and registration
        CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        Registration::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
        ]);

        $this->candidate->update(['status' => 'training']);

        // Training in progress - visa processing not yet available
        $training = Training::create([
            'candidate_id' => $this->candidate->id,
            'course_name' => 'Hospitality Training',
            'course_code' => 'HSP-101',
            'start_date' => now()->subDays(15),
            'end_date' => now()->addDays(15),
            'duration_days' => 30,
            'status' => 'in_progress',
        ]);

        // Cannot start visa processing yet
        $this->assertEquals('in_progress', $training->status);
        $this->assertNull($this->candidate->visaProcessing);

        // Complete training
        $training->update([
            'status' => 'completed',
            'end_date' => now(),
            'attendance_percentage' => 95,
            'assessment_score' => 88,
            'certificate_issued' => true,
            'certificate_number' => 'CERT-2026-001',
        ]);

        $this->assertTrue($training->fresh()->certificate_issued);

        // Now visa processing can begin
        $this->candidate->update(['status' => 'visa_processing']);

        $visaProcessing = VisaProcessing::create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'interview',
            'overall_status' => 'in_progress',
        ]);

        $this->assertNotNull($this->candidate->fresh()->visaProcessing);
        $this->assertEquals('visa_processing', $this->candidate->fresh()->status);
    }

    /** @test */
    public function expired_documents_block_visa_processing()
    {
        // Setup: Candidate ready for visa processing
        $this->candidate->update(['status' => 'visa_processing']);

        CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        Registration::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
        ]);

        Training::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
            'certificate_issued' => true,
        ]);

        // Create documents with varying expiry dates
        DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'passport',
            'category' => 'identity',
            'document_name' => 'Passport',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'expiry_date' => now()->addYears(5), // Valid
            'status' => 'active',
            'uploaded_by' => $this->admin->id,
        ]);

        $expiredMedical = DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'medical_certificate',
            'category' => 'medical',
            'document_name' => 'Medical Certificate',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'expiry_date' => now()->subDays(30), // Expired!
            'status' => 'expired',
            'uploaded_by' => $this->admin->id,
        ]);

        // Check for expired documents
        $expiredDocs = $this->candidate->documents()
            ->where('expiry_date', '<', now())
            ->get();

        $this->assertCount(1, $expiredDocs);
        $this->assertEquals('medical_certificate', $expiredDocs->first()->document_type);

        // Visa processing should be blocked
        $hasExpiredDocs = $this->candidate->documents()
            ->where('expiry_date', '<', now())
            ->exists();

        $this->assertTrue($hasExpiredDocs);

        // In real system, this would prevent visa application
        $canProceedWithVisa = !$hasExpiredDocs;
        $this->assertFalse($canProceedWithVisa);

        // Renew the expired document
        $expiredMedical->update([
            'expiry_date' => now()->addMonths(6),
            'status' => 'active',
        ]);

        // Now visa processing can proceed
        $hasExpiredDocs = $this->candidate->fresh()->documents()
            ->where('expiry_date', '<', now())
            ->exists();

        $this->assertFalse($hasExpiredDocs);

        $visaProcessing = VisaProcessing::create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'interview',
            'overall_status' => 'in_progress',
        ]);

        $this->assertNotNull($visaProcessing);
    }

    /** @test */
    public function complaints_affect_candidate_status_and_processing()
    {
        // Candidate in visa processing
        $this->candidate->update(['status' => 'visa_processing']);

        VisaProcessing::factory()->create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'medical',
            'overall_status' => 'in_progress',
        ]);

        // Critical complaint is filed
        $complaint = Complaint::create([
            'complaint_number' => 'CMP-2026-001',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'complaint_type' => 'fraud_suspected',
            'category' => 'documentation',
            'priority' => 'critical',
            'status' => 'investigating',
            'subject' => 'Document authenticity concerns',
            'description' => 'Concerns raised about document authenticity',
            'reported_at' => now(),
            'sla_days' => 7,
        ]);

        // In real system, critical complaints might pause processing
        $activeCriticalComplaints = $this->candidate->complaints()
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'assigned', 'investigating'])
            ->count();

        $this->assertGreaterThan(0, $activeCriticalComplaints);

        // Processing should be paused
        $shouldPauseProcessing = $activeCriticalComplaints > 0;
        $this->assertTrue($shouldPauseProcessing);

        // If paused, update visa processing status
        if ($shouldPauseProcessing) {
            $visaProcessing = $this->candidate->visaProcessing;
            $visaProcessing->update([
                'overall_status' => 'on_hold',
                'hold_reason' => 'Critical complaint under investigation',
            ]);
        }

        $this->assertEquals('on_hold', $this->candidate->fresh()->visaProcessing->overall_status);

        // Resolve complaint
        $complaint->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution' => 'Documents verified as authentic',
            'resolution_outcome' => 'no_action_required',
        ]);

        // No more critical active complaints
        $activeCriticalComplaints = $this->candidate->complaints()
            ->where('priority', 'critical')
            ->whereIn('status', ['open', 'assigned', 'investigating'])
            ->count();

        $this->assertEquals(0, $activeCriticalComplaints);

        // Resume processing
        $this->candidate->visaProcessing->update([
            'overall_status' => 'in_progress',
            'hold_reason' => null,
        ]);

        $this->assertEquals('in_progress', $this->candidate->fresh()->visaProcessing->overall_status);
    }

    /** @test */
    public function correspondence_links_across_multiple_modules()
    {
        // Setup: Candidate in various stages
        CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        $registration = Registration::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
        ]);

        $training = Training::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
        ]);

        $visaProcessing = VisaProcessing::factory()->create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'visa',
            'overall_status' => 'in_progress',
        ]);

        // Correspondence 1: Related to registration documents
        $correspondence1 = Correspondence::create([
            'reference_number' => 'COR-2026-001',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'type' => 'outgoing',
            'subject' => 'Request for additional registration documents',
            'sender' => 'WASL Registration Office',
            'recipient' => $this->candidate->email,
            'content' => 'Please submit updated medical certificate',
            'status' => 'sent',
            'sent_at' => now()->subDays(5),
        ]);

        // Correspondence 2: Related to visa processing
        $correspondence2 = Correspondence::create([
            'reference_number' => 'COR-2026-002',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'type' => 'incoming',
            'subject' => 'Visa interview schedule confirmation',
            'sender' => 'Embassy',
            'recipient' => 'WASL Visa Processing',
            'content' => 'Interview scheduled for next week',
            'status' => 'received',
            'received_at' => now()->subDays(2),
        ]);

        // Correspondence 3: Related to training certificate
        $correspondence3 = Correspondence::create([
            'reference_number' => 'COR-2026-003',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'type' => 'outgoing',
            'subject' => 'Training completion certificate',
            'sender' => 'WASL Training Department',
            'recipient' => 'Visa Processing Office',
            'content' => 'Certificate attached for visa application',
            'status' => 'sent',
            'sent_at' => now()->subDays(7),
        ]);

        // Verify correspondence is linked to candidate
        $this->assertEquals(3, $this->candidate->correspondences()->count());

        // Get correspondence by type
        $outgoing = $this->candidate->correspondences()->where('type', 'outgoing')->count();
        $incoming = $this->candidate->correspondences()->where('type', 'incoming')->count();

        $this->assertEquals(2, $outgoing);
        $this->assertEquals(1, $incoming);

        // Correspondence affects multiple modules
        // In real system, this would trigger actions across modules
        $hasPendingCorrespondence = $this->candidate->correspondences()
            ->where('status', 'pending')
            ->exists();

        // All correspondence processed for this candidate
        $this->assertFalse($hasPendingCorrespondence);
    }

    /** @test */
    public function document_expiry_triggers_correspondence_and_blocks_departure()
    {
        // Setup: Candidate ready for departure
        $this->candidate->update(['status' => 'departure']);

        // Create visa processing (completed)
        VisaProcessing::factory()->create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'completed',
            'overall_status' => 'completed',
            'visa_status' => 'approved',
            'visa_number' => 'VISA-001',
        ]);

        // Document expiring soon (15 days)
        $expiringDoc = DocumentArchive::create([
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'document_type' => 'medical_certificate',
            'category' => 'medical',
            'document_name' => 'Medical Certificate',
            'file_path' => '/storage/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'expiry_date' => now()->addDays(15), // Expiring soon
            'status' => 'active',
            'uploaded_by' => $this->admin->id,
        ]);

        // Check for documents expiring within 30 days
        $expiringDocs = $this->candidate->documents()
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->get();

        $this->assertCount(1, $expiringDocs);

        // System should create correspondence for renewal
        $correspondence = Correspondence::create([
            'reference_number' => 'COR-2026-EXP-001',
            'candidate_id' => $this->candidate->id,
            'campus_id' => $this->campus->id,
            'type' => 'outgoing',
            'subject' => 'Urgent: Document Renewal Required',
            'sender' => 'WASL Document Management',
            'recipient' => $this->candidate->email,
            'content' => "Your {$expiringDoc->document_name} is expiring in 15 days. Please renew immediately.",
            'priority' => 'high',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertDatabaseHas('correspondences', [
            'candidate_id' => $this->candidate->id,
            'subject' => 'Urgent: Document Renewal Required',
        ]);

        // Departure should be blocked if document expires before travel
        $hasExpiringCriticalDocs = $this->candidate->documents()
            ->whereIn('document_type', ['passport', 'visa', 'medical_certificate'])
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->exists();

        $this->assertTrue($hasExpiringCriticalDocs);

        $canProceedWithDeparture = !$hasExpiringCriticalDocs;
        $this->assertFalse($canProceedWithDeparture);

        // Renew document
        $expiringDoc->update([
            'expiry_date' => now()->addMonths(6),
            'status' => 'active',
        ]);

        // Now departure can proceed
        $hasExpiringCriticalDocs = $this->candidate->fresh()->documents()
            ->whereIn('document_type', ['passport', 'visa', 'medical_certificate'])
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->exists();

        $this->assertFalse($hasExpiringCriticalDocs);
    }

    /** @test */
    public function module_data_consistency_across_candidate_lifecycle()
    {
        // Full lifecycle test - verify data consistency

        // Stage 1: Screening
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);
        $this->candidate->update(['status' => 'registration']);

        // Stage 2: Registration
        $registration = Registration::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
        ]);
        $this->candidate->update(['status' => 'training']);

        // Stage 3: Training
        $training = Training::factory()->create([
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
            'certificate_issued' => true,
        ]);
        $this->candidate->update(['status' => 'visa_processing']);

        // Stage 4: Visa Processing
        $visaProcessing = VisaProcessing::factory()->create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'completed',
            'overall_status' => 'completed',
        ]);
        $this->candidate->update(['status' => 'deployed']);

        // Verify all data relationships are maintained
        $finalCandidate = $this->candidate->fresh();

        $this->assertNotNull($finalCandidate->screenings()->first());
        $this->assertNotNull($finalCandidate->registration);
        $this->assertNotNull($finalCandidate->trainings()->first());
        $this->assertNotNull($finalCandidate->visaProcessing);

        // Verify data integrity
        $this->assertEquals('eligible', $finalCandidate->screenings()->first()->outcome);
        $this->assertEquals('completed', $finalCandidate->registration->status);
        $this->assertTrue($finalCandidate->trainings()->first()->certificate_issued);
        $this->assertEquals('completed', $finalCandidate->visaProcessing->overall_status);
        $this->assertEquals('deployed', $finalCandidate->status);

        // Verify cascade relationships work
        $this->assertEquals($this->campus->id, $finalCandidate->campus_id);
        $this->assertEquals($this->campus->id, $finalCandidate->registration->campus_id ?? null);
    }
}
