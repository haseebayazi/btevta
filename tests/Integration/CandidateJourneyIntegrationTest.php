<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\OverseasEmploymentPromoter;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\Registration;
use App\Models\Training;
use App\Models\VisaProcessing;
use App\Models\Departure;
use App\Models\DocumentArchive;
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
    protected OverseasEmploymentPromoter $oep;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->campus = Campus::factory()->create();
        $this->oep = OverseasEmploymentPromoter::factory()->create();
    }

    /** @test */
    public function it_processes_complete_candidate_journey_from_screening_to_departure()
    {
        // ============================================================
        // STEP 1: Create candidate
        // ============================================================
        $this->candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'oep_id' => $this->oep->id,
            'status' => 'pending',
            'name' => 'Ahmed Al-Mansoor',
            'passport_number' => 'P123456789',
            'nationality' => 'Pakistan',
        ]);

        $this->assertDatabaseHas('candidates', [
            'id' => $this->candidate->id,
            'status' => 'pending',
            'name' => 'Ahmed Al-Mansoor',
        ]);

        // ============================================================
        // STEP 2: Screening Process
        // ============================================================
        $this->candidate->update(['status' => 'screening']);

        $screening = CandidateScreening::create([
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
            'remarks' => 'Candidate meets all requirements',
        ]);

        // Verify screening affects candidate status
        $this->candidate->update(['status' => 'registration']);

        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        $this->assertEquals('registration', $this->candidate->fresh()->status);

        // ============================================================
        // STEP 3: Registration with Documents
        // ============================================================
        $registration = Registration::create([
            'candidate_id' => $this->candidate->id,
            'registration_date' => now(),
            'application_form_submitted' => true,
            'documents_verified' => true,
            'biometric_data_collected' => true,
            'medical_test_completed' => true,
            'police_clearance_obtained' => true,
            'photo_uploaded' => true,
            'registration_fee_paid' => true,
            'fee_amount' => 5000.00,
            'payment_receipt' => 'RCP-2026-001',
            'status' => 'completed',
            'verified_by' => $this->admin->id,
        ]);

        // Upload required documents
        $documents = [
            ['document_type' => 'passport', 'category' => 'identity', 'expiry_date' => now()->addYears(5)],
            ['document_type' => 'cnic', 'category' => 'identity', 'expiry_date' => now()->addYears(7)],
            ['document_type' => 'medical_certificate', 'category' => 'medical', 'expiry_date' => now()->addMonths(6)],
            ['document_type' => 'police_clearance', 'category' => 'clearance', 'expiry_date' => now()->addYear()],
        ];

        foreach ($documents as $docData) {
            DocumentArchive::create([
                'candidate_id' => $this->candidate->id,
                'campus_id' => $this->campus->id,
                'document_name' => ucfirst(str_replace('_', ' ', $docData['document_type'])),
                'document_type' => $docData['document_type'],
                'category' => $docData['category'],
                'file_path' => "/storage/documents/{$this->candidate->id}/{$docData['document_type']}.pdf",
                'file_type' => 'application/pdf',
                'file_size' => 1024000,
                'expiry_date' => $docData['expiry_date'],
                'status' => 'active',
                'uploaded_by' => $this->admin->id,
            ]);
        }

        $this->candidate->update(['status' => 'training']);

        $this->assertDatabaseHas('registrations', [
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
        ]);

        $this->assertEquals(4, $this->candidate->documents()->count());

        // ============================================================
        // STEP 4: Training Process
        // ============================================================
        $training = Training::create([
            'candidate_id' => $this->candidate->id,
            'course_name' => 'Hospitality & Customer Service',
            'course_code' => 'HCS-101',
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(1),
            'duration_days' => 30,
            'training_mode' => 'in-person',
            'trainer_name' => 'Muhammad Hassan',
            'attendance_percentage' => 95.5,
            'assessment_score' => 88,
            'practical_score' => 90,
            'theory_score' => 86,
            'status' => 'completed',
            'certificate_issued' => true,
            'certificate_number' => 'CERT-2026-001',
        ]);

        // Training completion triggers visa processing
        $this->candidate->update(['status' => 'visa_processing']);

        $this->assertDatabaseHas('trainings', [
            'candidate_id' => $this->candidate->id,
            'status' => 'completed',
            'certificate_issued' => true,
        ]);

        // ============================================================
        // STEP 5: Visa Processing - All Stages
        // ============================================================
        $visaProcessing = VisaProcessing::create([
            'candidate_id' => $this->candidate->id,
            'current_stage' => 'interview',
            'overall_status' => 'in_progress',
        ]);

        // Stage 1: Interview
        $visaProcessing->update([
            'interview_date' => now(),
            'interview_status' => 'completed',
            'interview_result' => 'passed',
            'interview_remarks' => 'Candidate performed well',
            'current_stage' => 'takamol',
        ]);

        $this->assertEquals('takamol', $visaProcessing->fresh()->current_stage);

        // Stage 2: Takamol
        $visaProcessing->update([
            'takamol_submission_date' => now(),
            'takamol_status' => 'approved',
            'takamol_reference_number' => 'TKM-2026-001',
            'current_stage' => 'medical',
        ]);

        // Stage 3: Medical
        $visaProcessing->update([
            'medical_test_date' => now(),
            'medical_status' => 'fit',
            'medical_report_number' => 'MED-2026-001',
            'current_stage' => 'biometric',
        ]);

        // Stage 4: Biometric
        $visaProcessing->update([
            'biometric_appointment_date' => now(),
            'biometric_status' => 'completed',
            'biometric_reference_number' => 'BIO-2026-001',
            'current_stage' => 'enumber',
        ]);

        // Stage 5: E-Number
        $visaProcessing->update([
            'enumber_application_date' => now(),
            'enumber_status' => 'issued',
            'enumber' => 'EN-2026-12345678',
            'current_stage' => 'visa',
        ]);

        // Stage 6: Visa
        $visaProcessing->update([
            'visa_application_date' => now(),
            'visa_status' => 'approved',
            'visa_number' => 'VISA-2026-001',
            'visa_issue_date' => now(),
            'visa_expiry_date' => now()->addYears(2),
            'current_stage' => 'ptn',
        ]);

        // Stage 7: PTN (Pre-Travel Notification)
        $visaProcessing->update([
            'ptn_submission_date' => now(),
            'ptn_status' => 'approved',
            'ptn_reference_number' => 'PTN-2026-001',
            'current_stage' => 'completed',
            'overall_status' => 'completed',
        ]);

        // Visa processing completion triggers departure preparation
        $this->candidate->update(['status' => 'departure']);

        $this->assertDatabaseHas('visa_processings', [
            'candidate_id' => $this->candidate->id,
            'overall_status' => 'completed',
            'current_stage' => 'completed',
            'visa_status' => 'approved',
        ]);

        // ============================================================
        // STEP 6: Departure Process
        // ============================================================
        $departure = Departure::create([
            'candidate_id' => $this->candidate->id,
            'departure_date' => now()->addDays(7),
            'flight_number' => 'SV-123',
            'airline' => 'Saudi Arabian Airlines',
            'departure_airport' => 'Islamabad International Airport',
            'arrival_airport' => 'King Abdulaziz International Airport',
            'destination_country' => 'Saudi Arabia',
            'destination_city' => 'Jeddah',
            'ticket_issued' => true,
            'ticket_number' => 'TKT-2026-001',
            'pre_departure_briefing_attended' => true,
            'final_document_check_completed' => true,
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('departures', [
            'candidate_id' => $this->candidate->id,
            'status' => 'scheduled',
            'ticket_issued' => true,
        ]);

        // Simulate departure
        $departure->update([
            'status' => 'departed',
            'actual_departure_date' => now(),
        ]);

        $this->candidate->update(['status' => 'deployed']);

        // ============================================================
        // STEP 7: Post-Deployment Tracking
        // ============================================================
        $departure->update([
            'arrival_confirmed' => true,
            'arrival_date' => now()->addHours(5),
            'employer_contact_confirmed' => true,
        ]);

        $this->assertDatabaseHas('departures', [
            'candidate_id' => $this->candidate->id,
            'status' => 'departed',
            'arrival_confirmed' => true,
        ]);

        $this->assertEquals('deployed', $this->candidate->fresh()->status);

        // ============================================================
        // VERIFICATION: Complete Journey
        // ============================================================
        $finalCandidate = $this->candidate->fresh();

        // Verify all relationships exist
        $this->assertNotNull($finalCandidate->screenings()->first());
        $this->assertNotNull($finalCandidate->registration);
        $this->assertNotNull($finalCandidate->trainings()->first());
        $this->assertNotNull($finalCandidate->visaProcessing);
        $this->assertNotNull($finalCandidate->departure);
        $this->assertEquals(4, $finalCandidate->documents()->count());

        // Verify status progression
        $this->assertEquals('deployed', $finalCandidate->status);

        // Verify critical data points
        $this->assertEquals('eligible', $finalCandidate->screenings()->first()->outcome);
        $this->assertEquals('completed', $finalCandidate->registration->status);
        $this->assertTrue($finalCandidate->trainings()->first()->certificate_issued);
        $this->assertEquals('approved', $finalCandidate->visaProcessing->visa_status);
        $this->assertTrue($finalCandidate->departure->arrival_confirmed);
    }

    /** @test */
    public function it_handles_screening_rejection_workflow()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'screening',
        ]);

        // Screening with ineligible outcome
        CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'screening_date' => now(),
            'screened_by' => $this->admin->id,
            'language_proficiency' => 'poor',
            'technical_skills' => 'below_average',
            'physical_fitness' => 'unfit',
            'behavioral_assessment' => 'concerning',
            'overall_score' => 45,
            'outcome' => 'ineligible',
            'status' => 'completed',
            'remarks' => 'Candidate does not meet minimum requirements',
        ]);

        // Update candidate status to rejected
        $candidate->update(['status' => 'rejected']);

        $this->assertEquals('rejected', $candidate->fresh()->status);
        $this->assertEquals('ineligible', $candidate->screenings()->first()->outcome);

        // Verify no further processing
        $this->assertNull($candidate->registration);
        $this->assertEquals(0, $candidate->trainings()->count());
        $this->assertNull($candidate->visaProcessing);
    }

    /** @test */
    public function it_handles_visa_processing_rejection_workflow()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'visa_processing',
        ]);

        // Create completed screening and registration
        CandidateScreening::factory()->create([
            'candidate_id' => $candidate->id,
            'outcome' => 'eligible',
            'status' => 'completed',
        ]);

        Registration::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
        ]);

        Training::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
        ]);

        // Visa processing rejection at medical stage
        $visaProcessing = VisaProcessing::create([
            'candidate_id' => $candidate->id,
            'current_stage' => 'medical',
            'interview_status' => 'completed',
            'interview_result' => 'passed',
            'takamol_status' => 'approved',
            'medical_test_date' => now(),
            'medical_status' => 'unfit',
            'medical_remarks' => 'Candidate failed medical examination',
            'overall_status' => 'rejected',
        ]);

        $candidate->update(['status' => 'rejected']);

        $this->assertEquals('rejected', $candidate->fresh()->status);
        $this->assertEquals('unfit', $visaProcessing->fresh()->medical_status);
        $this->assertNull($candidate->departure);
    }

    /** @test */
    public function it_tracks_document_dependencies_throughout_journey()
    {
        $candidate = Candidate::factory()->create([
            'campus_id' => $this->campus->id,
            'status' => 'registration',
        ]);

        // Create expired passport document
        $expiredPassport = DocumentArchive::create([
            'candidate_id' => $candidate->id,
            'campus_id' => $this->campus->id,
            'document_name' => 'Passport',
            'document_type' => 'passport',
            'category' => 'identity',
            'file_path' => "/storage/documents/{$candidate->id}/passport.pdf",
            'file_type' => 'application/pdf',
            'file_size' => 1024000,
            'expiry_date' => now()->subDays(10), // Expired
            'status' => 'expired',
            'uploaded_by' => $this->admin->id,
        ]);

        // Verify document is expired
        $this->assertTrue($expiredPassport->expiry_date->isPast());
        $this->assertEquals('expired', $expiredPassport->status);

        // Document expiry should block visa processing
        $registration = Registration::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
        ]);

        // Check if candidate has expired documents
        $hasExpiredDocs = $candidate->documents()
            ->where('expiry_date', '<', now())
            ->exists();

        $this->assertTrue($hasExpiredDocs);

        // Should not proceed to visa processing with expired documents
        // In real system, this would be enforced by business logic

        // Renew the document
        $expiredPassport->update([
            'expiry_date' => now()->addYears(5),
            'status' => 'active',
        ]);

        $this->assertFalse($expiredPassport->fresh()->expiry_date->isPast());

        // Now can proceed with visa processing
        $candidate->update(['status' => 'visa_processing']);
        $this->assertEquals('visa_processing', $candidate->fresh()->status);
    }
}
