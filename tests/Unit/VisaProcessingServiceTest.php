<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\VisaProcess;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Campus;
use App\Services\VisaProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class VisaProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VisaProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VisaProcessingService();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Storage::fake('public');
    }

    // =========================================================================
    // STAGES
    // =========================================================================

    #[Test]
    public function it_returns_all_visa_processing_stages()
    {
        $stages = $this->service->getStages();

        $this->assertIsArray($stages);
        $this->assertArrayHasKey('interview', $stages);
        $this->assertArrayHasKey('trade_test', $stages);
        $this->assertArrayHasKey('takamol', $stages);
        $this->assertArrayHasKey('medical', $stages);
        $this->assertArrayHasKey('biometrics', $stages);
        $this->assertArrayHasKey('visa_applied', $stages);
        $this->assertArrayHasKey('ptn_issuance', $stages);
        $this->assertArrayHasKey('ticket', $stages);
        $this->assertArrayHasKey('completed', $stages);
    }

    // =========================================================================
    // E-NUMBER GENERATION
    // =========================================================================

    #[Test]
    public function it_generates_unique_enumber()
    {
        $oep = Oep::factory()->create(['code' => 'TST']);
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);

        $enumber = $this->service->generateEnumber($candidate);

        $this->assertStringStartsWith('TST-' . date('Y') . '-', $enumber);
        $this->assertMatchesRegularExpression('/^TST-\d{4}-\d{4}$/', $enumber);
    }

    #[Test]
    public function it_increments_enumber_sequence()
    {
        $oep = Oep::factory()->create(['code' => 'OEP']);
        $candidate = Candidate::factory()->create(['oep_id' => $oep->id]);

        // Create existing visa process with enumber
        $year = date('Y');
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'enumber' => "OEP-{$year}-0005",
        ]);

        $candidate2 = Candidate::factory()->create(['oep_id' => $oep->id]);
        $enumber = $this->service->generateEnumber($candidate2);

        $this->assertEquals("OEP-{$year}-0006", $enumber);
    }

    #[Test]
    public function it_handles_missing_oep_for_enumber()
    {
        $candidate = Candidate::factory()->create(['oep_id' => null]);

        $enumber = $this->service->generateEnumber($candidate);

        $this->assertStringStartsWith('OEP-' . date('Y'), $enumber);
    }

    // =========================================================================
    // PTN GENERATION
    // =========================================================================

    #[Test]
    public function it_generates_unique_ptn_number()
    {
        $trade = Trade::factory()->create(['code' => 'PLM']);
        $candidate = Candidate::factory()->create(['trade_id' => $trade->id]);

        $ptn = $this->service->generatePTN($candidate);

        $this->assertStringStartsWith('PTN-' . date('Y') . '-PLM-', $ptn);
        $this->assertMatchesRegularExpression('/^PTN-\d{4}-PLM-\d{5}$/', $ptn);
    }

    #[Test]
    public function it_increments_ptn_sequence()
    {
        $trade = Trade::factory()->create(['code' => 'ELC']);
        $candidate = Candidate::factory()->create(['trade_id' => $trade->id]);

        // Create existing visa process with PTN
        $year = date('Y');
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'ptn_number' => "PTN-{$year}-ELC-00010",
        ]);

        $candidate2 = Candidate::factory()->create(['trade_id' => $trade->id]);
        $ptn = $this->service->generatePTN($candidate2);

        $this->assertEquals("PTN-{$year}-ELC-00011", $ptn);
    }

    // =========================================================================
    // INTERVIEW SCHEDULING
    // =========================================================================

    #[Test]
    public function it_schedules_interview_for_candidate()
    {
        $candidate = Candidate::factory()->create();

        $visaProcess = $this->service->scheduleInterview($candidate->id, [
            'interview_date' => '2024-07-15',
            'interview_remarks' => 'Initial screening interview',
        ]);

        $this->assertInstanceOf(VisaProcess::class, $visaProcess);
        $this->assertEquals('2024-07-15', $visaProcess->interview_date?->format('Y-m-d'));

        $candidate->refresh();
        $this->assertEquals('interview_scheduled', $candidate->status);
    }

    #[Test]
    public function it_updates_existing_interview_schedule()
    {
        $candidate = Candidate::factory()->create();

        // Schedule first interview
        $this->service->scheduleInterview($candidate->id, [
            'interview_date' => '2024-07-15',
        ]);

        // Reschedule
        $visaProcess = $this->service->scheduleInterview($candidate->id, [
            'interview_date' => '2024-07-20',
        ]);

        $this->assertEquals('2024-07-20', $visaProcess->interview_date?->format('Y-m-d'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_candidate()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Candidate not found');

        $this->service->scheduleInterview(99999, [
            'interview_date' => '2024-07-15',
        ]);
    }

    // =========================================================================
    // INTERVIEW RESULT
    // =========================================================================

    #[Test]
    public function it_records_passed_interview_status()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'overall_status' => 'interview',
        ]);

        $result = $this->service->recordInterviewResult(
            $visaProcess->id,
            'pass',
            'Excellent communication skills'
        );

        $this->assertEquals('pass', $result->interview_status);
        $this->assertEquals('Excellent communication skills', $result->interview_remarks);
        $this->assertEquals('trade_test', $result->overall_status);

        $candidate->refresh();
        $this->assertEquals('interview_passed', $candidate->status);
    }

    #[Test]
    public function it_records_failed_interview_status()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'overall_status' => 'interview',
        ]);

        $result = $this->service->recordInterviewResult(
            $visaProcess->id,
            'fail',
            'Did not meet requirements'
        );

        $this->assertEquals('fail', $result->interview_status);

        $candidate->refresh();
        $this->assertEquals('interview_failed', $candidate->status);
    }

    // =========================================================================
    // TAKAMOL
    // =========================================================================

    #[Test]
    public function it_schedules_takamol_test()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->scheduleTakamol($visaProcess->id, [
            'booking_date' => '2024-07-20',
            'test_date' => '2024-07-25',
            'center' => 'Islamabad Center',
        ]);

        $this->assertEquals('2024-07-25', $result->takamol_date?->format('Y-m-d'));
        $this->assertEquals('takamol', $result->overall_status);
    }

    #[Test]
    public function it_uploads_takamol_status_and_moves_to_next_stage()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'takamol']);
        $file = UploadedFile::fake()->create('takamol_certificate.pdf', 500);

        $result = $this->service->uploadTakamolResult(
            $visaProcess->id,
            $file,
            'pass',
            85
        );

        $this->assertEquals('pass', $result->takamol_status);
        $this->assertEquals('medical', $result->overall_status);

    }

    #[Test]
    public function it_does_not_advance_on_failed_takamol()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'takamol']);
        $file = UploadedFile::fake()->create('takamol_certificate.pdf', 500);

        $result = $this->service->uploadTakamolResult(
            $visaProcess->id,
            $file,
            'fail',
            40
        );

        $this->assertEquals('fail', $result->takamol_status);
        $this->assertEquals('takamol', $result->overall_status); // Stays at takamol
    }

    // =========================================================================
    // GAMCA MEDICAL
    // =========================================================================

    #[Test]
    public function it_schedules_gamca_medical()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->scheduleGAMCA($visaProcess->id, [
            'booking_date' => '2024-08-01',
            'test_date' => '2024-08-05',
            'center' => 'Lahore GAMCA Center',
            'barcode' => 'GAMCA123456',
        ]);

        $this->assertEquals('medical', $result->overall_status);
    }

    #[Test]
    public function it_uploads_gamca_certificate_and_advances()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'medical']);
        $file = UploadedFile::fake()->create('gamca_certificate.pdf', 500);

        $result = $this->service->uploadGAMCACertificate(
            $visaProcess->id,
            $file,
            'fit',
            '2025-08-05'
        );

        $this->assertEquals('fit', $result->medical_status);
        $this->assertEquals('biometrics', $result->overall_status);
    }

    #[Test]
    public function it_does_not_advance_on_unfit_medical()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'medical']);
        $file = UploadedFile::fake()->create('gamca_certificate.pdf', 500);

        $result = $this->service->uploadGAMCACertificate(
            $visaProcess->id,
            $file,
            'unfit',
            null
        );

        $this->assertEquals('unfit', $result->medical_status);
        $this->assertEquals('medical', $result->overall_status);
    }

    // =========================================================================
    // ETIMAD BIOMETRICS
    // =========================================================================

    #[Test]
    public function it_schedules_etimad_biometrics()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->scheduleEtimad($visaProcess->id, [
            'appointment_id' => 'ETM-20240810-ABC123',
            'appointment_date' => '2024-08-10',
            'center' => 'Islamabad Etimad Center',
        ]);

        $this->assertEquals('ETM-20240810-ABC123', $result->etimad_appointment_id);
        $this->assertEquals('2024-08-10', $result->biometric_date?->format('Y-m-d'));
        $this->assertEquals('biometrics', $result->overall_status);
    }

    #[Test]
    public function it_generates_etimad_id_if_not_provided()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->scheduleEtimad($visaProcess->id, [
            'appointment_date' => '2024-08-10',
        ]);

        $this->assertNotNull($result->etimad_appointment_id);
        $this->assertStringStartsWith('ETM-', $result->etimad_appointment_id);
    }

    #[Test]
    public function it_records_biometrics_completion()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'biometrics']);

        $result = $this->service->recordBiometricsCompletion($visaProcess->id, [
            'completion_date' => '2024-08-10',
            'remarks' => 'Completed successfully',
        ]);

        $this->assertTrue($result->biometric_completed);
        $this->assertEquals('visa_applied', $result->overall_status);
    }

    // =========================================================================
    // VISA SUBMISSION
    // =========================================================================

    #[Test]
    public function it_records_visa_submission()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'biometrics']);

        $result = $this->service->recordVisaSubmission($visaProcess->id, [
            'submission_date' => '2024-08-15',
            'application_number' => 'VIS-2024-12345',
        ]);

        $this->assertEquals('visa_applied', $result->overall_status);
    }

    // =========================================================================
    // PTN ISSUANCE
    // =========================================================================

    #[Test]
    public function it_records_ptn_issuance()
    {
        $trade = Trade::factory()->create(['code' => 'WLD']);
        $candidate = Candidate::factory()->create(['trade_id' => $trade->id]);
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'overall_status' => 'visa_applied',
        ]);

        $result = $this->service->recordPTNIssuance(
            $visaProcess->id,
            null, // Auto-generate
            '2024-08-20'
        );

        $this->assertNotNull($result->ptn_number);
        $this->assertStringStartsWith('PTN-', $result->ptn_number);
        $this->assertEquals('approved', $result->visa_status);
        $this->assertEquals('ptn_issuance', $result->overall_status);
    }

    #[Test]
    public function it_uses_provided_ptn_number()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'visa_applied']);

        $result = $this->service->recordPTNIssuance(
            $visaProcess->id,
            'PTN-2024-MANUAL-001',
            '2024-08-20'
        );

        $this->assertEquals('PTN-2024-MANUAL-001', $result->ptn_number);
    }

    // =========================================================================
    // TRAVEL PLAN
    // =========================================================================

    #[Test]
    public function it_uploads_travel_plan_and_completes_process()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'ptn_issuance']);
        $file = UploadedFile::fake()->create('ticket.pdf', 500);

        $result = $this->service->uploadTravelPlan($visaProcess->id, $file, [
            'flight_number' => 'PK-302',
            'arrival_date' => '2024-09-01',
        ]);

        $this->assertNotNull($result->travel_plan_path);
        $this->assertEquals('PK-302', $result->flight_number);
        $this->assertEquals('ticket', $result->overall_status);

        Storage::disk('public')->assertExists($result->travel_plan_path);
    }

    // =========================================================================
    // TIMELINE CALCULATION
    // =========================================================================

    #[Test]
    public function it_calculates_visa_processing_timeline()
    {
        $visaProcess = VisaProcess::factory()->create([
            'interview_date' => '2024-07-01',
            'takamol_date' => '2024-07-15',
            'medical_date' => '2024-07-25',
            'biometric_date' => '2024-08-05',
        ]);

        $timeline = $this->service->calculateTimeline($visaProcess->id);

        $this->assertArrayHasKey('timeline', $timeline);
        $this->assertArrayHasKey('total_days', $timeline);
        $this->assertArrayHasKey('completed_stages', $timeline);
        $this->assertArrayHasKey('total_stages', $timeline);
        $this->assertEquals(7, $timeline['completed_stages']);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_visa_processing_statistics()
    {
        VisaProcess::factory()->count(5)->create(['overall_status' => 'completed']);
        VisaProcess::factory()->count(3)->create(['overall_status' => 'interview']);
        VisaProcess::factory()->count(2)->create(['overall_status' => 'medical']);

        $stats = $this->service->getStatistics();

        $this->assertEquals(10, $stats['total_processes']);
        $this->assertEquals(5, $stats['completed']);
        $this->assertEquals(5, $stats['in_progress']);
        $this->assertEquals(3, $stats['interview_stage']);
        $this->assertEquals(2, $stats['medical_stage']);
    }

    #[Test]
    public function it_filters_statistics_by_date_range()
    {
        VisaProcess::factory()->count(3)->create([
            'created_at' => Carbon::now()->subDays(5),
        ]);
        VisaProcess::factory()->count(2)->create([
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $stats = $this->service->getStatistics([
            'from_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'to_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->assertEquals(3, $stats['total_processes']);
    }

    #[Test]
    public function it_filters_statistics_by_oep()
    {
        $oep = Oep::factory()->create();
        $candidate1 = Candidate::factory()->create(['oep_id' => $oep->id]);
        $candidate2 = Candidate::factory()->create(['oep_id' => $oep->id]);
        $candidate3 = Candidate::factory()->create(); // Different OEP

        VisaProcess::factory()->create(['candidate_id' => $candidate1->id]);
        VisaProcess::factory()->create(['candidate_id' => $candidate2->id]);
        VisaProcess::factory()->create(['candidate_id' => $candidate3->id]);

        $stats = $this->service->getStatistics(['oep_id' => $oep->id]);

        $this->assertEquals(2, $stats['total_processes']);
    }

    // =========================================================================
    // PENDING & EXPIRING
    // =========================================================================

    #[Test]
    public function it_returns_pending_medical_biometric_candidates()
    {
        VisaProcess::factory()->count(3)->create([
            'overall_status' => 'medical',
            'overall_status' => 'in_progress',
        ]);
        VisaProcess::factory()->count(2)->create([
            'overall_status' => 'biometrics',
            'overall_status' => 'in_progress',
        ]);
        VisaProcess::factory()->count(4)->create([
            'overall_status' => 'interview',
            'overall_status' => 'in_progress',
        ]);

        $pending = $this->service->getPendingMedicalBiometric();

        $this->assertCount(5, $pending);
    }

    #[Test]
    public function it_returns_expiring_documents()
    {
        VisaProcess::factory()->create([
        ]);
        VisaProcess::factory()->create([
            'passport_expiry_date' => Carbon::now()->addDays(20),
        ]);
        VisaProcess::factory()->create([
        ]);

        $expiring = $this->service->getExpiringDocuments(30);

        $this->assertCount(2, $expiring);
    }

    // =========================================================================
    // CREATE & UPDATE VISA PROCESS
    // =========================================================================

    #[Test]
    public function it_creates_new_visa_process()
    {
        $candidate = Candidate::factory()->create(['status' => 'training']);

        $visaProcess = $this->service->createVisaProcess($candidate->id, [
            'interview_date' => '2024-07-15',
            'interview_status' => 'scheduled',
        ]);

        $this->assertInstanceOf(VisaProcess::class, $visaProcess);
        $this->assertEquals('2024-07-15', $visaProcess->interview_date?->format('Y-m-d'));
        $this->assertEquals('initiated', $visaProcess->overall_status);

        $candidate->refresh();
        $this->assertEquals('visa_processing', $candidate->status);
    }

    #[Test]
    public function it_updates_visa_process()
    {
        $visaProcess = VisaProcess::factory()->create();

        $updated = $this->service->updateVisaProcess($visaProcess->id, [
            'interview_remarks' => 'Updated remarks',
        ]);

        $this->assertEquals('Updated remarks', $updated->interview_remarks);
    }

    // =========================================================================
    // STAGE-SPECIFIC UPDATES
    // =========================================================================

    #[Test]
    public function it_updates_interview_stage()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->updateInterview($visaProcess->id, [
            'interview_date' => '2024-07-20',
            'interview_status' => 'passed',
            'interview_remarks' => 'Good performance',
        ]);

        $this->assertEquals('passed', $result->interview_status);
        $this->assertTrue($result->interview_completed);
        $this->assertEquals('interview_completed', $result->overall_status);
    }

    #[Test]
    public function it_updates_trade_test_stage()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->updateTradeTest($visaProcess->id, [
            'trade_test_date' => '2024-07-25',
            'trade_test_status' => 'passed',
            'trade_test_remarks' => 'Skilled worker',
        ]);

        $this->assertEquals('passed', $result->trade_test_status);
        $this->assertTrue($result->trade_test_completed);
        $this->assertEquals('trade_test_completed', $result->overall_status);
    }

    #[Test]
    public function it_updates_takamol_stage()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->updateTakamol($visaProcess->id, [
            'takamol_date' => '2024-08-01',
            'takamol_status' => 'completed',
            'takamol_remarks' => 'Score: 85',
        ]);

        $this->assertEquals('completed', $result->takamol_status);
        $this->assertEquals('takamol_completed', $result->overall_status);
    }

    #[Test]
    public function it_updates_medical_stage()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->updateMedical($visaProcess->id, [
            'medical_date' => '2024-08-05',
            'medical_status' => 'fit',
            'medical_remarks' => 'All clear',
        ]);

        $this->assertEquals('fit', $result->medical_status);
        $this->assertTrue($result->medical_completed);
        $this->assertEquals('medical_completed', $result->overall_status);
    }

    #[Test]
    public function it_updates_biometric_stage()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->updateBiometric($visaProcess->id, [
            'biometric_date' => '2024-08-10',
            'biometric_status' => 'completed',
        ]);

        $this->assertEquals('completed', $result->biometric_status);
        $this->assertTrue($result->biometric_completed);
        $this->assertEquals('biometric_completed', $result->overall_status);
    }

    #[Test]
    public function it_updates_visa_issuance()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->updateVisaIssuance($visaProcess->id, [
            'visa_date' => '2024-08-20',
            'visa_number' => 'VISA-2024-12345',
            'visa_status' => 'issued',
        ]);

        $this->assertEquals('issued', $result->visa_status);
        $this->assertEquals('VISA-2024-12345', $result->visa_number);
        $this->assertTrue($result->visa_issued);
        $this->assertEquals('visa_issued', $result->overall_status);
    }

    // =========================================================================
    // TICKET UPLOAD
    // =========================================================================

    #[Test]
    public function it_uploads_ticket()
    {
        $visaProcess = VisaProcess::factory()->create();
        $file = UploadedFile::fake()->create('ticket.pdf', 500);

        $result = $this->service->uploadTicket($visaProcess->id, $file, '2024-09-01');

        $this->assertNotNull($result->ticket_path);
        $this->assertEquals('2024-09-01', $result->ticket_date?->format('Y-m-d'));
        $this->assertTrue($result->ticket_uploaded);
        $this->assertEquals('ticket_uploaded', $result->overall_status);

        Storage::disk('public')->assertExists($result->ticket_path);
    }

    // =========================================================================
    // TIMELINE
    // =========================================================================

    #[Test]
    public function it_returns_visa_process_timeline()
    {
        $visaProcess = VisaProcess::factory()->create([
            'interview_date' => '2024-07-01',
            'interview_status' => 'passed',
            'interview_completed' => true,
            'trade_test_date' => '2024-07-10',
            'trade_test_status' => 'passed',
            'trade_test_completed' => true,
        ]);

        $timeline = $this->service->getTimeline($visaProcess->id);

        $this->assertIsArray($timeline);
        $this->assertCount(2, $timeline);
        $this->assertEquals('Interview', $timeline[0]['stage']);
        $this->assertEquals('Trade Test', $timeline[1]['stage']);
    }

    // =========================================================================
    // OVERDUE PROCESSES
    // =========================================================================

    #[Test]
    public function it_returns_overdue_processes()
    {
        // Create overdue process (>90 days)
        $candidate1 = Candidate::factory()->create(['status' => 'visa_processing']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate1->id,
            'created_at' => Carbon::now()->subDays(100),
            'overall_status' => 'in_progress',
        ]);

        // Create recent process
        $candidate2 = Candidate::factory()->create(['status' => 'visa_processing']);
        VisaProcess::factory()->create([
            'candidate_id' => $candidate2->id,
            'created_at' => Carbon::now()->subDays(30),
            'overall_status' => 'in_progress',
        ]);

        $overdue = $this->service->getOverdueProcesses();

        $this->assertCount(1, $overdue);
        $this->assertEquals($candidate1->id, $overdue->first()->id);
    }

    // =========================================================================
    // COMPLETE & DELETE
    // =========================================================================

    #[Test]
    public function it_completes_visa_process()
    {
        $visaProcess = VisaProcess::factory()->create(['overall_status' => 'ticket_uploaded']);

        $result = $this->service->completeVisaProcess($visaProcess->id);

        $this->assertEquals('completed', $result->overall_status);
    }

    #[Test]
    public function it_deletes_visa_process()
    {
        $visaProcess = VisaProcess::factory()->create();

        $result = $this->service->deleteVisaProcess($visaProcess->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('visa_processes', ['id' => $visaProcess->id]);
    }

    // =========================================================================
    // REPORT GENERATION
    // =========================================================================

    #[Test]
    public function it_generates_visa_processing_report()
    {
        $campus = Campus::factory()->create();
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);

        VisaProcess::factory()->count(3)->create([
            'candidate_id' => $candidate->id,
            'created_at' => Carbon::now()->subDays(5),
            'overall_status' => 'completed',
        ]);

        $report = $this->service->generateReport(
            Carbon::now()->subDays(10)->format('Y-m-d'),
            Carbon::now()->format('Y-m-d'),
            $campus->id
        );

        $this->assertArrayHasKey('processes', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('period', $report);
        $this->assertEquals(3, $report['statistics']['completed']);
    }

    #[Test]
    public function it_generates_report_without_campus_filter()
    {
        VisaProcess::factory()->count(5)->create([
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $report = $this->service->generateReport(
            Carbon::now()->subDays(10)->format('Y-m-d'),
            Carbon::now()->format('Y-m-d')
        );

        $this->assertEquals(5, $report['statistics']['total']);
    }
}
