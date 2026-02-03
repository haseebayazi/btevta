<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\DocumentArchive;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration test for complete candidate journey from screening to departure.
 *
 * Tests the full workflow:
 * 1. Candidate creation
 * 2. Screening process → eligible outcome
 * 3. Registration with document upload
 * 4. Training assignment and completion
 * 5. Visa processing through all stages
 * 6. Departure processing
 * 7. Post-deployment tracking
 */
class CandidateJourneyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Campus $campus;
    protected Oep $oep;
    protected Trade $trade;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->campus = Campus::factory()->create();
        $this->trade = Trade::factory()->create();
        $this->oep = Oep::factory()->create();
    }

    #[Test]
    public function it_processes_complete_candidate_journey_from_screening_to_departure()
    {
        // ============================================================
        // STEP 1: Create candidate
        // ============================================================
        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'oep_id' => $this->oep->id,
            'status' => CandidateStatus::LISTED->value,
            'name' => 'Ahmed Al-Mansoor',
        ]);

        $this->assertDatabaseHas('candidates', [
            'id' => $this->candidate->id,
            'status' => CandidateStatus::LISTED->value,
            'name' => 'Ahmed Al-Mansoor',
        ]);

        // ============================================================
        // STEP 2: Move to Screening
        // ============================================================
        $this->candidate->update(['status' => CandidateStatus::SCREENING->value]);

        $screening = CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'international',
            'screening_status' => 'screened',
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);

        // Move to screened status
        $this->candidate->update(['status' => CandidateStatus::SCREENED->value]);

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'screening_status' => 'screened',
        ]);

        $this->assertEquals(CandidateStatus::SCREENED->value, $this->candidate->fresh()->status);

        // ============================================================
        // STEP 3: Registration
        // ============================================================
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $this->candidate->update([
            'status' => CandidateStatus::REGISTERED->value,
            'batch_id' => $batch->id,
            'registered_at' => now(),
        ]);

        $this->assertEquals(CandidateStatus::REGISTERED->value, $this->candidate->fresh()->status);

        // ============================================================
        // STEP 4: Training Process
        // ============================================================
        $this->candidate->update(['status' => CandidateStatus::TRAINING->value]);

        // Create assessments
        TrainingAssessment::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $batch->id,
            'assessment_date' => now(),
            'assessment_type' => 'midterm',
            'score' => 85,
            'result' => 'pass',
        ]);

        TrainingAssessment::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $batch->id,
            'assessment_date' => now(),
            'assessment_type' => 'final',
            'score' => 90,
            'result' => 'pass',
        ]);

        // Issue certificate
        $certificate = TrainingCertificate::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $batch->id,
            'certificate_number' => 'CERT-2026-001',
            'issue_date' => now(),
            'issued_by' => $this->admin->id,
        ]);

        $this->candidate->update(['status' => CandidateStatus::TRAINING_COMPLETED->value]);

        $this->assertDatabaseHas('training_certificates', [
            'candidate_id' => $this->candidate->id,
            'certificate_number' => 'CERT-2026-001',
        ]);

        // ============================================================
        // STEP 5: Visa Processing
        // ============================================================
        $this->candidate->update(['status' => CandidateStatus::VISA_PROCESS->value]);

        $visaProcess = VisaProcess::create([
            'candidate_id' => $this->candidate->id,
        ]);

        // Progress through stages
        $visaProcess->update([
            'interview_date' => now(),
            'interview_status' => 'passed',
        ]);

        $visaProcess->update([
            'trade_test_date' => now(),
            'trade_test_status' => 'passed',
        ]);

        $visaProcess->update([
            'medical_date' => now(),
            'medical_status' => 'fit',
        ]);

        $visaProcess->update([
            'visa_number' => 'VISA-2026-001',
            'visa_status' => 'approved',
        ]);

        $visaProcess->update([
            'ptn_number' => 'PTN-2026-001',
            'overall_status' => 'completed',
        ]);

        $this->candidate->update(['status' => CandidateStatus::VISA_APPROVED->value]);

        $this->assertDatabaseHas('visa_processes', [
            'candidate_id' => $this->candidate->id,
            'visa_number' => 'VISA-2026-001',
        ]);

        // ============================================================
        // STEP 6: Departure Process
        // ============================================================
        $this->candidate->update(['status' => CandidateStatus::DEPARTURE_PROCESSING->value]);

        $departure = Departure::create([
            'candidate_id' => $this->candidate->id,
            'scheduled_departure' => now()->addDays(7),
            'flight_number' => 'SV-123',
            'airline' => 'Saudi Arabian Airlines',
        ]);

        $this->candidate->update(['status' => CandidateStatus::READY_TO_DEPART->value]);

        // Simulate departure
        $departure->update([
            'actual_departure_date' => now(),
        ]);

        $this->candidate->update(['status' => CandidateStatus::DEPARTED->value]);

        $this->assertDatabaseHas('departures', [
            'candidate_id' => $this->candidate->id,
        ]);

        // ============================================================
        // STEP 7: Post-Deployment Tracking
        // ============================================================
        $departure->update([
            'arrival_confirmation_date' => now()->addHours(5),
        ]);

        $this->candidate->update(['status' => CandidateStatus::POST_DEPARTURE->value]);

        // ============================================================
        // VERIFICATION: Complete Journey
        // ============================================================
        $finalCandidate = $this->candidate->fresh();

        // Verify all relationships exist
        $this->assertNotNull($finalCandidate->screenings()->first());
        $this->assertNotNull($finalCandidate->batch);
        $this->assertNotNull($finalCandidate->trainingCertificates()->first());
        $this->assertNotNull($finalCandidate->visaProcess);
        $this->assertNotNull($finalCandidate->departure);

        // Verify status progression
        $this->assertEquals(CandidateStatus::POST_DEPARTURE->value, $finalCandidate->status);
    }

    #[Test]
    public function it_handles_screening_rejection_workflow()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => CandidateStatus::SCREENING->value,
        ]);

        // Screening with deferred outcome
        CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'consent_for_work' => true,
            'placement_interest' => 'local',
            'screening_status' => 'deferred',
            'notes' => 'Candidate does not meet minimum requirements',
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);

        // Update candidate status to deferred
        $candidate->update(['status' => CandidateStatus::DEFERRED->value]);

        $this->assertEquals(CandidateStatus::DEFERRED->value, $candidate->fresh()->status);
        $this->assertEquals('deferred', $candidate->screenings()->first()->screening_status);

        // Verify no further processing
        $this->assertNull($candidate->batch_id);
        $this->assertEquals(0, $candidate->trainingCertificates()->count());
        $this->assertNull($candidate->visaProcess);
    }

    #[Test]
    public function it_handles_visa_processing_rejection_workflow()
    {
        $batch = Batch::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
        ]);

        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'trade_id' => $this->trade->id,
            'batch_id' => $batch->id,
            'status' => CandidateStatus::VISA_PROCESS->value,
        ]);

        // Create completed screening
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'screening_status' => 'screened',
        ]);

        // Create certificate
        TrainingCertificate::factory()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
        ]);

        // Visa processing rejection at medical stage
        $visaProcess = VisaProcess::create([
            'candidate_id' => $candidate->id,
            'interview_status' => 'passed',
            'trade_test_status' => 'passed',
            'medical_date' => now(),
            'medical_status' => 'unfit',
            'overall_status' => 'rejected',
        ]);

        $candidate->update(['status' => CandidateStatus::REJECTED->value]);

        $this->assertEquals(CandidateStatus::REJECTED->value, $candidate->fresh()->status);
        $this->assertEquals('unfit', $visaProcess->fresh()->medical_status);
        $this->assertNull($candidate->departure);
    }

    #[Test]
    public function it_tracks_document_dependencies_throughout_journey()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => CandidateStatus::REGISTERED->value,
        ]);

        // Create expired passport document
        $expiredDocument = DocumentArchive::create([
            'candidate_id' => $candidate->id,
            'document_name' => 'Passport',
            'document_type' => 'passport',
            'document_category' => 'identity',
            'file_path' => "/storage/documents/{$candidate->id}/passport.pdf",
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'expiry_date' => now()->subDays(10), // Expired
            'upload_date' => now()->subMonths(2),
            'uploaded_by' => $this->admin->id,
        ]);

        // Verify document is expired
        $this->assertTrue($expiredDocument->expiry_date->isPast());

        // Check if candidate has expired documents
        $hasExpiredDocs = DocumentArchive::where('candidate_id', $candidate->id)
            ->where('expiry_date', '<', now())
            ->exists();

        $this->assertTrue($hasExpiredDocs);

        // Renew the document
        $expiredDocument->update([
            'expiry_date' => now()->addYears(5),
        ]);

        $this->assertFalse($expiredDocument->fresh()->expiry_date->isPast());

        // Now can proceed with visa processing
        $candidate->update(['status' => CandidateStatus::VISA_PROCESS->value]);
        $this->assertEquals(CandidateStatus::VISA_PROCESS->value, $candidate->fresh()->status);
    }
}
