<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\CandidateScreening;
use App\Models\RegistrationDocument;
use App\Models\NextOfKin;
use App\Models\Undertaking;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Models\TrainingCertificate;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\DocumentChecklist;
use App\Models\PreDepartureDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Integration tests for the complete candidate lifecycle.
 * Tests the full journey from LISTED → SCREENING → REGISTERED → TRAINING →
 * VISA_PROCESS → READY → DEPARTED
 * 
 * WASL v3: Module 1 (Pre-Departure Documents) → Module 2 (Initial Screening) → Registration
 */
class CandidateLifecycleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $campusAdmin;
    protected Trade $trade;
    protected Campus $campus;
    protected Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->admin()->create();
        $this->campusAdmin = User::factory()->campusAdmin()->create();

        // Create required lookup data
        $this->trade = Trade::factory()->create();
        $this->campus = Campus::factory()->create();
        $this->batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 30,
        ]);

        // Seed document checklists for WASL v3 Module 1 workflow
        $this->seedDocumentChecklists();

        Storage::fake('public');
    }

    /**
     * Seed mandatory document checklists for pre-departure document workflow
     */
    protected function seedDocumentChecklists(): void
    {
        $checklists = [
            ['name' => 'CNIC', 'code' => 'CNIC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 1, 'is_active' => true],
            ['name' => 'Passport', 'code' => 'PASSPORT', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 2, 'is_active' => true],
            ['name' => 'Domicile', 'code' => 'DOMICILE', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 3, 'is_active' => true],
            ['name' => 'FRC', 'code' => 'FRC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 4, 'is_active' => true],
            ['name' => 'PCC', 'code' => 'PCC', 'category' => 'mandatory', 'is_mandatory' => true, 'display_order' => 5, 'is_active' => true],
        ];

        foreach ($checklists as $checklist) {
            DocumentChecklist::create($checklist);
        }
    }

    /**
     * Create verified pre-departure documents for a candidate
     */
    protected function createVerifiedPreDepartureDocuments(Candidate $candidate): void
    {
        $mandatoryChecklists = DocumentChecklist::mandatory()->active()->get();
        foreach ($mandatoryChecklists as $checklist) {
            PreDepartureDocument::create([
                'candidate_id' => $candidate->id,
                'document_checklist_id' => $checklist->id,
                'file_path' => 'test/path.pdf',
                'original_filename' => 'test.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_at' => now(),
                'uploaded_by' => $this->admin->id,
                'verified_at' => now(),
                'verified_by' => $this->admin->id,
            ]);
        }
    }

    // ==================== PHASE 1: REGISTRATION ====================

    #[Test]
    public function it_creates_new_candidate_with_generated_ids()
    {
        $candidateData = [
            'name' => 'Muhammad Ali Khan',
            'cnic' => '3520112345671',
            'father_name' => 'Ahmed Khan',
            'date_of_birth' => '1995-05-15',
            'gender' => 'male',
            'phone' => '03001234567',
            'email' => 'ali.khan@example.com',
            'address' => '123 Main Street, Rawalpindi',
            'district' => 'Rawalpindi',
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
        ];

        $response = $this->actingAs($this->admin)->post('/candidates', $candidateData);

        $candidate = Candidate::where('email', 'ali.khan@example.com')->first();

        $this->assertNotNull($candidate);
        $this->assertEquals(Candidate::STATUS_NEW, $candidate->status);
        $this->assertNotNull($candidate->btevta_id);
        $this->assertNotNull($candidate->application_id);
        $this->assertMatchesRegularExpression('/^TLP-\d{4}-\d{5}-\d$/', $candidate->btevta_id);
    }

    #[Test]
    public function it_validates_cnic_format_on_creation()
    {
        $candidateData = [
            'name' => 'Test Candidate',
            'cnic' => '123', // Invalid CNIC
            'father_name' => 'Father Name',
            'date_of_birth' => '1995-05-15',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => 'Test Address',
            'district' => 'Rawalpindi',
            'trade_id' => $this->trade->id,
        ];

        $response = $this->actingAs($this->admin)->post('/candidates', $candidateData);

        $response->assertSessionHasErrors('cnic');
    }

    #[Test]
    public function it_detects_duplicate_phone_numbers()
    {
        // Create first candidate
        Candidate::factory()->create(['phone' => '03001234567']);

        // Try to check for duplicates
        $response = $this->actingAs($this->admin)->post('/api/check-duplicates', [
            'phone' => '03001234567',
        ]);

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data['duplicates']);
    }

    // ==================== PHASE 2: SCREENING ====================

    #[Test]
    public function it_transitions_from_listed_to_screening_with_verified_documents()
    {
        // WASL v3: Create candidate in 'listed' status (Module 1)
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_LISTED,
            'trade_id' => $this->trade->id,
        ]);

        // Create verified pre-departure documents (Module 1 requirement)
        $this->createVerifiedPreDepartureDocuments($candidate);

        // Validate transition (should pass with all documents verified)
        $validation = $candidate->canTransitionToScreening();
        $this->assertTrue($validation['can_transition']);

        // Transition to screening
        $candidate->updateStatus(Candidate::STATUS_SCREENING);

        $this->assertEquals(Candidate::STATUS_SCREENING, $candidate->fresh()->status);
    }

    #[Test]
    public function it_records_desk_screening()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);

        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/v1/screening/{$candidate->id}/desk", [
            'status' => 'passed',
            'remarks' => 'All documents verified',
        ]);

        $response->assertSuccessful();

        $screening = $candidate->screenings()->where('screening_type', 'desk')->first();
        $this->assertNotNull($screening);
        $this->assertEquals(CandidateScreening::STATUS_PASSED, $screening->status);
    }

    #[Test]
    public function it_records_call_screening_with_attempts()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);

        // Create call screening
        $screening = CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_PENDING,
        ]);

        // Record first call attempt
        $screening->recordCallAttempt(120, true, 'Candidate confirmed interest');

        $this->assertEquals(1, $screening->fresh()->call_count);
    }

    #[Test]
    public function it_records_physical_screening()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);

        Sanctum::actingAs($this->admin);
        $response = $this->postJson("/api/v1/screening/{$candidate->id}/physical", [
            'status' => 'passed',
            'remarks' => 'Physical verification completed',
        ]);

        $response->assertSuccessful();

        $screening = $candidate->screenings()->where('screening_type', 'physical')->first();
        $this->assertNotNull($screening);
    }

    #[Test]
    public function it_auto_progresses_to_registered_when_all_screenings_pass()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);

        // Create and pass desk screening
        $desk = CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PENDING,
        ]);
        $desk->markAsPassed('Verified');

        // Create and pass call screening
        $call = CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_PENDING,
        ]);
        $call->markAsPassed('Confirmed');

        // Create and pass physical screening
        $physical = CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'screening_type' => CandidateScreening::TYPE_PHYSICAL,
            'status' => CandidateScreening::STATUS_PENDING,
        ]);
        $physical->markAsPassed('Verified in person');

        // Candidate should now be registered
        $this->assertEquals(Candidate::STATUS_REGISTERED, $candidate->fresh()->status);
    }

    #[Test]
    public function it_fails_screening_and_rejects_candidate()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);

        $screening = CandidateScreening::create([
            'candidate_id' => $candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PENDING,
        ]);

        $screening->markAsFailed('Invalid documents');

        $this->assertEquals(Candidate::STATUS_REJECTED, $candidate->fresh()->status);
    }

    // ==================== PHASE 3: DOCUMENTS ====================

    #[Test]
    public function it_uploads_required_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
        ]);

        $requiredDocs = ['cnic', 'education', 'photo', 'domicile'];

        foreach ($requiredDocs as $docType) {
            $file = UploadedFile::fake()->create("{$docType}.pdf", 100, 'application/pdf');

            $response = $this->actingAs($this->admin)->post("/registration/{$candidate->id}/documents", [
                'document_type' => $docType,
                'file' => $file,
                'expiry_date' => now()->addYear()->format('Y-m-d'),
            ]);
        }

        $this->assertEquals(4, $candidate->registrationDocuments()->count());
    }

    #[Test]
    public function it_verifies_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
        ]);

        $document = RegistrationDocument::create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/test.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/registration/documents/{$document->id}/verify");

        $this->assertEquals('verified', $document->fresh()->status);
    }

    #[Test]
    public function it_blocks_completion_with_expired_documents()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
        ]);

        // Create expired document
        RegistrationDocument::create([
            'candidate_id' => $candidate->id,
            'document_type' => 'cnic',
            'file_path' => 'documents/test.pdf',
            'status' => 'verified',
            'expiry_date' => now()->subDay(), // Expired
        ]);

        $response = $this->actingAs($this->admin)->post("/registration/{$candidate->id}/complete");

        $response->assertStatus(422);
    }

    // ==================== PHASE 4: TRAINING ====================

    #[Test]
    public function it_transitions_to_training()
    {
        $candidate = $this->createCandidateReadyForTraining();

        $response = $this->actingAs($this->admin)->post("/registration/{$candidate->id}/start-training", [
            'batch_id' => $this->batch->id,
        ]);

        $candidate->refresh();
        $this->assertEquals(Candidate::STATUS_TRAINING, $candidate->status);
        $this->assertEquals($this->batch->id, $candidate->batch_id);
    }

    #[Test]
    public function it_records_training_attendance()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Record 10 days of attendance
        for ($i = 0; $i < 10; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 9 ? 'present' : 'absent', // 90% attendance
            ]);
        }

        $this->assertEquals(90, $candidate->getAttendancePercentage());
    }

    #[Test]
    public function it_records_training_assessments()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        // Record assessments
        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'practical',
            'subject' => 'Basic Skills',
            'score' => 75,
            'result' => 'pass',
            'assessment_date' => now(),
        ]);

        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'subject' => 'Final Exam',
            'score' => 80,
            'result' => 'pass',
            'assessment_date' => now(),
        ]);

        $this->assertEquals(77.5, $candidate->getAverageAssessmentScore());
        $this->assertTrue($candidate->hasPassedAllAssessments());
    }

    #[Test]
    public function it_issues_training_certificate()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);

        TrainingCertificate::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'certificate_number' => 'CERT-2025-0001',
            'issue_date' => now(),
            'trade_id' => $this->trade->id,
        ]);

        $this->assertNotNull($candidate->certificate);
    }

    // ==================== PHASE 5: VISA PROCESS ====================

    #[Test]
    public function it_transitions_to_visa_process()
    {
        $candidate = $this->createCandidateReadyForVisaProcess();

        $candidate->updateStatus(Candidate::STATUS_VISA_PROCESS);

        $this->assertEquals(Candidate::STATUS_VISA_PROCESS, $candidate->fresh()->status);
    }

    #[Test]
    public function it_records_visa_process_steps()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_VISA_PROCESS,
            'trade_id' => $this->trade->id,
        ]);

        $visaProcess = VisaProcess::create([
            'candidate_id' => $candidate->id,
            'oep_id' => 1,
            'destination_country' => 'Saudi Arabia',
            'trade_test_date' => now()->addDays(7),
            'medical_date' => now()->addDays(14),
            'visa_applied_date' => now()->addDays(21),
            'visa_issued' => true,
            'visa_number' => 'VISA-12345',
            'visa_expiry' => now()->addYear(),
        ]);

        $this->assertTrue($visaProcess->visa_issued);
    }

    #[Test]
    public function it_validates_transition_to_ready()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_VISA_PROCESS,
            'trade_id' => $this->trade->id,
        ]);

        // Without visa process record
        $validation = $candidate->canTransitionToReady();
        $this->assertFalse($validation['can_transition']);
        $this->assertContains('Visa process record not found', $validation['issues']);

        // With incomplete visa process
        VisaProcess::create([
            'candidate_id' => $candidate->id,
            'oep_id' => 1,
            'destination_country' => 'Saudi Arabia',
            'visa_issued' => false,
        ]);

        $validation = $candidate->fresh()->canTransitionToReady();
        $this->assertFalse($validation['can_transition']);
    }

    // ==================== PHASE 6: DEPARTURE ====================

    #[Test]
    public function it_transitions_to_ready_status()
    {
        $candidate = $this->createCandidateReadyForDeparture();

        $candidate->updateStatus(Candidate::STATUS_READY);

        $this->assertEquals(Candidate::STATUS_READY, $candidate->fresh()->status);
    }

    #[Test]
    public function it_records_departure()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_READY,
            'trade_id' => $this->trade->id,
        ]);

        $departure = Departure::create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->addDays(7),
            'flight_number' => 'PK-301',
            'destination' => 'Saudi Arabia',
            'pre_departure_briefing' => true,
            'briefing_date' => now(),
        ]);

        $this->assertNotNull($departure);
        $this->assertEquals('PK-301', $departure->flight_number);
    }

    #[Test]
    public function it_transitions_to_departed()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_READY,
            'trade_id' => $this->trade->id,
        ]);

        // Create complete departure record
        Departure::create([
            'candidate_id' => $candidate->id,
            'departure_date' => now(),
            'flight_number' => 'PK-301',
            'destination' => 'Saudi Arabia',
            'pre_departure_briefing' => true,
            'briefing_date' => now()->subDay(),
        ]);

        $validation = $candidate->fresh()->canTransitionToDeparted();
        $this->assertTrue($validation['can_transition']);

        $candidate->updateStatus(Candidate::STATUS_DEPARTED);
        $this->assertEquals(Candidate::STATUS_DEPARTED, $candidate->fresh()->status);
    }

    // ==================== FULL LIFECYCLE TEST ====================

    #[Test]
    public function it_completes_full_candidate_lifecycle()
    {
        // PHASE 1: Create new candidate
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_NEW,
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
        ]);
        $this->assertEquals(Candidate::STATUS_NEW, $candidate->status);

        // PHASE 2: Screening
        $candidate->updateStatus(Candidate::STATUS_SCREENING);

        // Pass all screenings
        foreach (['desk', 'call', 'physical'] as $type) {
            $screening = CandidateScreening::create([
                'candidate_id' => $candidate->id,
                'screening_type' => $type,
                'status' => CandidateScreening::STATUS_PENDING,
            ]);
            $screening->markAsPassed("Passed {$type} screening");
        }

        $candidate->refresh();
        $this->assertEquals(Candidate::STATUS_REGISTERED, $candidate->status);

        // PHASE 3: Documents
        foreach (['cnic', 'education', 'photo'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
                'expiry_date' => now()->addYear(),
            ]);
        }

        // Add next of kin
        $nextOfKin = NextOfKin::create([
            'name' => 'Father Name',
            'relationship' => 'Father',
            'phone' => '03009876543',
            'address' => 'Home Address',
        ]);
        $candidate->update(['next_of_kin_id' => $nextOfKin->id]);

        // Add undertaking
        Undertaking::create([
            'candidate_id' => $candidate->id,
            'type' => 'registration',
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        // PHASE 4: Training
        $candidate->update([
            'batch_id' => $this->batch->id,
        ]);
        $candidate->updateStatus(Candidate::STATUS_TRAINING);
        $candidate->updateTrainingStatus(Candidate::TRAINING_IN_PROGRESS);

        // Record attendance (90%+)
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 85 ? 'present' : 'absent',
            ]);
        }

        // Record assessments
        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'subject' => 'Final Exam',
            'score' => 75,
            'result' => 'pass',
            'assessment_date' => now(),
        ]);

        // Issue certificate
        TrainingCertificate::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'certificate_number' => 'CERT-2025-0001',
            'issue_date' => now(),
            'trade_id' => $this->trade->id,
        ]);

        $candidate->updateTrainingStatus(Candidate::TRAINING_COMPLETED);

        // PHASE 5: Visa Process
        $candidate->updateStatus(Candidate::STATUS_VISA_PROCESS);

        VisaProcess::create([
            'candidate_id' => $candidate->id,
            'oep_id' => 1,
            'destination_country' => 'Saudi Arabia',
            'visa_issued' => true,
            'visa_number' => 'VISA-12345',
            'visa_expiry' => now()->addYear(),
        ]);

        // PHASE 6: Ready for departure
        $candidate->updateStatus(Candidate::STATUS_READY);

        Departure::create([
            'candidate_id' => $candidate->id,
            'departure_date' => now(),
            'flight_number' => 'PK-301',
            'destination' => 'Saudi Arabia',
            'pre_departure_briefing' => true,
            'briefing_date' => now()->subDay(),
        ]);

        // PHASE 7: Departed
        $candidate->updateStatus(Candidate::STATUS_DEPARTED);

        $this->assertEquals(Candidate::STATUS_DEPARTED, $candidate->fresh()->status);
        $this->assertEquals(Candidate::TRAINING_COMPLETED, $candidate->fresh()->training_status);
    }

    // ==================== HELPER METHODS ====================

    protected function createCandidateReadyForTraining(): Candidate
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
        ]);

        // Add required documents
        foreach (['cnic', 'education', 'photo'] as $docType) {
            RegistrationDocument::create([
                'candidate_id' => $candidate->id,
                'document_type' => $docType,
                'file_path' => "documents/{$docType}.pdf",
                'status' => 'verified',
            ]);
        }

        // Add next of kin
        $nextOfKin = NextOfKin::create([
            'name' => 'Father Name',
            'relationship' => 'Father',
            'phone' => '03009876543',
            'address' => 'Home Address',
        ]);
        $candidate->update(['next_of_kin_id' => $nextOfKin->id]);

        // Add undertaking
        Undertaking::create([
            'candidate_id' => $candidate->id,
            'type' => 'registration',
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        return $candidate;
    }

    protected function createCandidateReadyForVisaProcess(): Candidate
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'training_status' => Candidate::TRAINING_COMPLETED,
        ]);

        // Add final assessment
        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'subject' => 'Final Exam',
            'score' => 75,
            'result' => 'pass',
            'assessment_date' => now(),
        ]);

        // Add certificate
        TrainingCertificate::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $this->batch->id,
            'certificate_number' => 'CERT-2025-0001',
            'issue_date' => now(),
            'trade_id' => $this->trade->id,
        ]);

        return $candidate;
    }

    protected function createCandidateReadyForDeparture(): Candidate
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_VISA_PROCESS,
            'trade_id' => $this->trade->id,
        ]);

        VisaProcess::create([
            'candidate_id' => $candidate->id,
            'oep_id' => 1,
            'destination_country' => 'Saudi Arabia',
            'visa_issued' => true,
            'visa_number' => 'VISA-12345',
            'visa_expiry' => now()->addYear(),
        ]);

        return $candidate;
    }
}
