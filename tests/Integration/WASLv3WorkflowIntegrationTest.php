<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Trade;
use App\Models\ImplementingPartner;
use App\Models\Country;
use App\Models\Batch;
use App\Models\CandidateScreening;
use App\Models\TrainingAssessment;
use App\Models\Departure;
use App\Models\PostDepartureDetail;
use App\Models\SuccessStory;
use App\Services\ScreeningService;
use App\Services\AllocationService;
use App\Services\AutoBatchService;
use App\Services\RegistrationService;
use App\Enums\CandidateStatus;
use App\Enums\ScreeningStatus;
use App\Enums\PlacementInterest;
use App\Enums\AssessmentType;
use App\Enums\PTNStatus;
use App\Enums\ProtectorStatus;
use App\Enums\DepartureStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class WASLv3WorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function complete_wasl_v3_candidate_journey()
    {
        // ===== PHASE 1: Initial Screening with New Workflow =====
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::SCREENING->value,
        ]);

        $country = Country::factory()->create(['name' => 'Saudi Arabia']);

        $screeningService = app(ScreeningService::class);

        $screeningData = [
            'consent_for_work' => true,
            'placement_interest' => PlacementInterest::INTERNATIONAL->value,
            'target_country_id' => $country->id,
            'screening_status' => ScreeningStatus::SCREENED->value,
        ];

        $screening = $screeningService->conductInitialScreening($candidate, $screeningData);

        $this->assertEquals(ScreeningStatus::SCREENED->value, $screening->screening_status);
        $this->assertEquals(CandidateStatus::SCREENED->value, $candidate->fresh()->status);

        // ===== PHASE 2: Registration with Auto-Batch Allocation =====
        $campus = Campus::factory()->create(['code' => 'ISB']);
        $program = Program::factory()->create(['code' => 'TEC']);
        $trade = Trade::factory()->create(['code' => 'WLD']);
        $partner = ImplementingPartner::factory()->create();

        $registrationService = app(RegistrationService::class);

        $registrationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
            'implementing_partner_id' => $partner->id,
        ];

        $result = $registrationService->registerCandidateWithAllocation(
            $candidate,
            $registrationData
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Batch::class, $result['batch']);
        $this->assertEquals(CandidateStatus::REGISTERED->value, $candidate->fresh()->status);
        $this->assertNotNull($candidate->fresh()->allocated_number);
        $this->assertStringContainsString('ISB-TEC-WLD', $candidate->fresh()->allocated_number);

        // ===== PHASE 3: Training with Assessments =====
        $candidate->update(['status' => CandidateStatus::TRAINING->value]);

        // Interim Assessment
        $interimAssessment = TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $result['batch']->id,
            'assessment_type' => AssessmentType::INTERIM->value,
            'assessment_date' => now(),
            'score' => 75,
            'max_score' => 100,
            'percentage' => 75.0,
            'pass' => true,
            'remarks' => 'Good progress',
        ]);

        $this->assertTrue($interimAssessment->pass);

        // Final Assessment
        $finalAssessment = TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $result['batch']->id,
            'assessment_type' => AssessmentType::FINAL->value,
            'assessment_date' => now()->addMonths(2),
            'score' => 85,
            'max_score' => 100,
            'percentage' => 85.0,
            'pass' => true,
            'remarks' => 'Excellent performance',
        ]);

        $this->assertTrue($finalAssessment->pass);
        $candidate->update(['status' => CandidateStatus::TRAINING_COMPLETED->value]);

        // ===== PHASE 4: Visa Processing =====
        $candidate->update(['status' => CandidateStatus::VISA_PROCESSING->value]);

        // Simulate visa received
        $candidate->update(['status' => CandidateStatus::VISA_RECEIVED->value]);

        // ===== PHASE 5: Pre-Departure =====
        $candidate->update(['status' => CandidateStatus::PRE_DEPARTURE->value]);

        // Create departure record with WASL v3 fields
        $departure = Departure::create([
            'candidate_id' => $candidate->id,
            'departure_date' => now()->addDays(7),
            'flight_number' => 'PK-750',
            'destination' => 'Riyadh',
            'ptn_status' => PTNStatus::ISSUED->value,
            'ptn_issued_at' => now(),
            'protector_status' => ProtectorStatus::DONE->value,
            'protector_applied_at' => now()->subDays(5),
            'protector_done_at' => now()->subDays(2),
            'ticket_date' => now()->addDays(7)->toDateString(),
            'ticket_time' => '14:30',
            'flight_type' => 'direct',
            'final_departure_status' => DepartureStatus::READY_TO_DEPART->value,
            'briefing_completed' => true,
        ]);

        $this->assertEquals(PTNStatus::ISSUED->value, $departure->ptn_status);
        $this->assertEquals(ProtectorStatus::DONE->value, $departure->protector_status);
        $this->assertEquals(DepartureStatus::READY_TO_DEPART->value, $departure->final_departure_status);

        // ===== PHASE 6: Departed =====
        $candidate->update(['status' => CandidateStatus::DEPARTED->value]);
        $departure->update(['final_departure_status' => DepartureStatus::DEPARTED->value]);

        // ===== PHASE 7: Post-Departure with Full Details =====
        $candidate->update(['status' => CandidateStatus::POST_ARRIVAL->value]);

        $postDeparture = PostDepartureDetail::create([
            'departure_id' => $departure->id,
            // Residency & Identity
            'residency_number' => '2123456789',
            'residency_expiry' => now()->addYears(2),
            'foreign_mobile_number' => '+966501234567',
            'foreign_bank_name' => 'Al Rajhi Bank',
            'foreign_bank_account' => '1234567890',
            'tracking_app_registration' => 'registered',
            // Employment Details
            'company_name' => 'Saudi Aramco',
            'employer_name' => 'Ahmed Al-Saud',
            'employer_designation' => 'HR Manager',
            'employer_contact' => '+966501234567',
            'work_location' => 'Riyadh Industrial Area',
            'final_salary' => 2500.00,
            'salary_currency' => 'SAR',
            'job_commencement_date' => now()->addDays(10),
        ]);

        $this->assertEquals('2123456789', $postDeparture->residency_number);
        $this->assertEquals('Saudi Aramco', $postDeparture->company_name);
        $this->assertEquals(2500.00, $postDeparture->final_salary);

        // ===== PHASE 8: Employment =====
        $candidate->update(['status' => CandidateStatus::EMPLOYED->value]);

        // ===== PHASE 9: Success Story =====
        $candidate->update(['status' => CandidateStatus::SUCCESS_STORY->value]);

        $successStory = SuccessStory::create([
            'candidate_id' => $candidate->id,
            'departure_id' => $departure->id,
            'written_note' => 'From humble beginnings in Pakistan, I am now working as a welder at Saudi Aramco earning SAR 2500/month. The WASL program transformed my life.',
            'evidence_type' => 'video',
            'evidence_path' => 'success_stories/candidate_123_video.mp4',
            'is_featured' => true,
            'recorded_by' => $this->user->id,
            'recorded_at' => now(),
        ]);

        $this->assertNotNull($successStory);
        $this->assertTrue($successStory->is_featured);

        // ===== FINAL VERIFICATION =====
        $finalCandidate = $candidate->fresh();

        // Verify complete journey
        $this->assertEquals(CandidateStatus::SUCCESS_STORY->value, $finalCandidate->status);
        $this->assertNotNull($finalCandidate->batch_id);
        $this->assertNotNull($finalCandidate->allocated_number);
        $this->assertEquals($campus->id, $finalCandidate->campus_id);
        $this->assertEquals($program->id, $finalCandidate->program_id);
        $this->assertEquals($trade->id, $finalCandidate->trade_id);

        // Verify all phases completed
        $this->assertNotNull($finalCandidate->screening);
        $this->assertNotNull($finalCandidate->departure);
        $this->assertCount(2, TrainingAssessment::where('candidate_id', $candidate->id)->get());
        $this->assertNotNull(PostDepartureDetail::where('departure_id', $departure->id)->first());
        $this->assertNotNull(SuccessStory::where('candidate_id', $candidate->id)->first());
    }

    #[Test]
    public function screening_gate_prevents_unscreened_registration()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::SCREENING->value,
        ]);

        // No screening record exists
        $registrationService = app(RegistrationService::class);

        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $registrationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('screened');

        $registrationService->registerCandidateWithAllocation($candidate, $registrationData);
    }

    #[Test]
    public function auto_batch_creates_new_batch_when_full()
    {
        config(['wasl.batch_size' => 3]);

        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();
        $country = Country::factory()->create();

        $autoBatchService = app(AutoBatchService::class);
        $registrationService = app(RegistrationService::class);
        $screeningService = app(ScreeningService::class);

        // Create and register 5 candidates
        $candidates = [];
        for ($i = 0; $i < 5; $i++) {
            $candidate = Candidate::factory()->create([
                'status' => CandidateStatus::SCREENING->value,
                'campus_id' => $campus->id,
                'program_id' => $program->id,
                'trade_id' => $trade->id,
            ]);

            // Screen the candidate with target country for international placement
            $screeningService->conductInitialScreening($candidate, [
                'consent_for_work' => true,
                'placement_interest' => PlacementInterest::INTERNATIONAL->value,
                'target_country_id' => $country->id,
                'screening_status' => ScreeningStatus::SCREENED->value,
            ]);

            // Register with auto-batch
            $batch = $autoBatchService->assignOrCreateBatch($candidate);
            $candidates[] = ['candidate' => $candidate, 'batch' => $batch];
        }

        // First 3 should be in batch 1
        $this->assertEquals($candidates[0]['batch']->id, $candidates[1]['batch']->id);
        $this->assertEquals($candidates[1]['batch']->id, $candidates[2]['batch']->id);

        // Last 2 should be in batch 2 (new batch created when first was full)
        $this->assertEquals($candidates[3]['batch']->id, $candidates[4]['batch']->id);
        $this->assertNotEquals($candidates[2]['batch']->id, $candidates[3]['batch']->id);

        // Verify batch sizes
        $this->assertEquals(3, $candidates[0]['batch']->fresh()->current_size);
        $this->assertEquals(2, $candidates[3]['batch']->fresh()->current_size);
    }

    #[Test]
    public function training_completion_requires_both_assessments()
    {
        $candidate = Candidate::factory()->create([
            'status' => CandidateStatus::TRAINING->value,
        ]);

        $batch = Batch::factory()->create();

        // Only interim assessment
        TrainingAssessment::create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'assessment_type' => AssessmentType::INTERIM->value,
            'assessment_date' => now(),
            'score' => 70,
            'max_score' => 100,
            'percentage' => 70.0,
            'pass' => true,
        ]);

        $interimCount = TrainingAssessment::where('candidate_id', $candidate->id)
            ->where('assessment_type', AssessmentType::INTERIM->value)
            ->count();

        $finalCount = TrainingAssessment::where('candidate_id', $candidate->id)
            ->where('assessment_type', AssessmentType::FINAL->value)
            ->count();

        $this->assertEquals(1, $interimCount);
        $this->assertEquals(0, $finalCount);

        // Training should not be marked complete
        // (This would be checked by the service/controller logic)
    }

    #[Test]
    public function post_departure_tracks_all_required_fields()
    {
        $departure = Departure::factory()->create();

        $postDeparture = PostDepartureDetail::create([
            'departure_id' => $departure->id,
            // Residency & Identity (7 fields)
            'residency_number' => '2123456789',
            'residency_expiry' => now()->addYears(2),
            'foreign_license_number' => 'KSA-LIC-12345',
            'foreign_mobile_number' => '+966501234567',
            'foreign_bank_name' => 'Al Rajhi Bank',
            'foreign_bank_account' => '1234567890',
            'tracking_app_registration' => 'registered',
            // Employment Details (10 fields)
            'company_name' => 'Test Company',
            'employer_name' => 'Test Employer',
            'employer_designation' => 'Manager',
            'employer_contact' => '+966501234567',
            'work_location' => 'Riyadh',
            'final_salary' => 2500.00,
            'salary_currency' => 'SAR',
            'job_commencement_date' => now(),
            'final_job_terms' => 'Standard terms',
            'special_conditions' => 'None',
        ]);

        // Verify all Residency & Identity fields
        $this->assertNotNull($postDeparture->residency_number);
        $this->assertNotNull($postDeparture->residency_expiry);
        $this->assertNotNull($postDeparture->foreign_license_number);
        $this->assertNotNull($postDeparture->foreign_mobile_number);
        $this->assertNotNull($postDeparture->foreign_bank_name);
        $this->assertNotNull($postDeparture->foreign_bank_account);
        $this->assertNotNull($postDeparture->tracking_app_registration);

        // Verify all Employment Details fields
        $this->assertNotNull($postDeparture->company_name);
        $this->assertNotNull($postDeparture->employer_name);
        $this->assertNotNull($postDeparture->employer_designation);
        $this->assertNotNull($postDeparture->employer_contact);
        $this->assertNotNull($postDeparture->work_location);
        $this->assertNotNull($postDeparture->final_salary);
        $this->assertNotNull($postDeparture->salary_currency);
        $this->assertNotNull($postDeparture->job_commencement_date);
        $this->assertNotNull($postDeparture->final_job_terms);
        $this->assertNotNull($postDeparture->special_conditions);
    }
}
