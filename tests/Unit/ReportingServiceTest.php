<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\ReportingService;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Oep;
use App\Models\Batch;
use App\Models\VisaProcess;
use App\Models\Departure;
use App\Models\Complaint;
use App\Models\Remittance;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportingService();
        Cache::flush();
    }

    // =========================================================================
    // CANDIDATE PIPELINE REPORT
    // =========================================================================

    #[Test]
    public function it_generates_candidate_pipeline_report()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->count(5)->create();

        $report = $this->service->getCandidatePipelineReport();

        $this->assertArrayHasKey('total_candidates', $report);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('by_campus', $report);
        $this->assertArrayHasKey('by_trade', $report);
        $this->assertArrayHasKey('conversion_rates', $report);
        $this->assertArrayHasKey('generated_at', $report);
    }

    #[Test]
    public function it_filters_pipeline_report_by_campus()
    {
        $campus = Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->count(3)->create(['campus_id' => $campus->id]);
        Candidate::factory()->count(2)->create();

        $report = $this->service->getCandidatePipelineReport([
            'campus_id' => $campus->id,
        ]);

        $this->assertEquals(3, $report['total_candidates']);
    }

    #[Test]
    public function it_calculates_conversion_rates()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->create(['status' => 'new']);
        Candidate::factory()->create(['status' => 'screening']);
        Candidate::factory()->create(['status' => 'registered']);
        Candidate::factory()->create(['status' => 'training']);
        Candidate::factory()->create(['status' => 'departed']);

        $report = $this->service->getCandidatePipelineReport();

        $this->assertArrayHasKey('screening_rate', $report['conversion_rates']);
        $this->assertArrayHasKey('registration_rate', $report['conversion_rates']);
        $this->assertArrayHasKey('departure_rate', $report['conversion_rates']);
        $this->assertArrayHasKey('overall_success_rate', $report['conversion_rates']);
    }

    // =========================================================================
    // TRAINING REPORT
    // =========================================================================

    #[Test]
    public function it_generates_training_report()
    {
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();
        Batch::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);

        $report = $this->service->getTrainingReport();

        $this->assertArrayHasKey('batch_statistics', $report);
        $this->assertArrayHasKey('attendance_summary', $report);
        $this->assertArrayHasKey('assessment_summary', $report);
        $this->assertArrayHasKey('campus_comparison', $report);
        $this->assertArrayHasKey('completion_rates', $report);
    }

    #[Test]
    public function it_calculates_batch_statistics()
    {
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        Batch::factory()->count(3)->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'status' => 'active',
        ]);

        Batch::factory()->count(2)->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'status' => 'completed',
        ]);

        $report = $this->service->getTrainingReport();

        $this->assertEquals(5, $report['batch_statistics']['total_batches']);
        $this->assertEquals(3, $report['batch_statistics']['active_batches']);
        $this->assertEquals(2, $report['batch_statistics']['completed_batches']);
    }

    #[Test]
    public function it_calculates_attendance_summary()
    {
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();
        $batch = Batch::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
        ]);
        $candidate = Candidate::factory()->create(['batch_id' => $batch->id]);

        TrainingAttendance::factory()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'status' => 'present',
        ]);

        TrainingAttendance::factory()->create([
            'candidate_id' => $candidate->id,
            'batch_id' => $batch->id,
            'status' => 'absent',
        ]);

        $report = $this->service->getTrainingReport();

        $this->assertArrayHasKey('total_records', $report['attendance_summary']);
        $this->assertArrayHasKey('present', $report['attendance_summary']);
        $this->assertArrayHasKey('absent', $report['attendance_summary']);
        $this->assertArrayHasKey('attendance_rate', $report['attendance_summary']);
    }

    // =========================================================================
    // VISA PROCESSING REPORT
    // =========================================================================

    #[Test]
    public function it_generates_visa_processing_report()
    {
        $candidate = Candidate::factory()->create();
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $report = $this->service->getVisaProcessingReport();

        $this->assertArrayHasKey('total_processes', $report);
        $this->assertArrayHasKey('by_status', $report);
        $this->assertArrayHasKey('stage_statistics', $report);
        $this->assertArrayHasKey('average_processing_time', $report);
        $this->assertArrayHasKey('oep_performance', $report);
        $this->assertArrayHasKey('bottleneck_analysis', $report);
    }

    #[Test]
    public function it_calculates_average_processing_time()
    {
        $candidate = Candidate::factory()->create();
        VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'overall_status' => 'completed',
            'interview_date' => now()->subDays(60),
            'ticket_date' => now(),
        ]);

        $report = $this->service->getVisaProcessingReport();

        $this->assertArrayHasKey('average_days', $report['average_processing_time']);
        $this->assertArrayHasKey('fastest', $report['average_processing_time']);
        $this->assertArrayHasKey('slowest', $report['average_processing_time']);
    }

    // =========================================================================
    // COMPLIANCE REPORT
    // =========================================================================

    #[Test]
    public function it_generates_compliance_report()
    {
        $report = $this->service->getComplianceReport();

        $this->assertArrayHasKey('departure_compliance', $report);
        $this->assertArrayHasKey('remittance_compliance', $report);
        $this->assertArrayHasKey('complaint_resolution', $report);
        $this->assertArrayHasKey('sla_performance', $report);
    }

    #[Test]
    public function it_calculates_departure_compliance()
    {
        $candidate1 = Candidate::factory()->create();
        $candidate2 = Candidate::factory()->create();

        Departure::factory()->create([
            'candidate_id' => $candidate1->id,
            'ninety_day_compliance' => true,
        ]);

        Departure::factory()->create([
            'candidate_id' => $candidate2->id,
            'ninety_day_compliance' => false,
        ]);

        $report = $this->service->getComplianceReport();

        $this->assertEquals(2, $report['departure_compliance']['total_departures']);
        $this->assertEquals(1, $report['departure_compliance']['compliant']);
        $this->assertEquals(1, $report['departure_compliance']['non_compliant']);
    }

    #[Test]
    public function it_calculates_remittance_compliance()
    {
        $candidate = Candidate::factory()->create();

        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'verified',
            'amount' => 100000,
        ]);

        Remittance::factory()->create([
            'candidate_id' => $candidate->id,
            'status' => 'pending',
            'amount' => 50000,
        ]);

        $report = $this->service->getComplianceReport();

        $this->assertEquals(2, $report['remittance_compliance']['total_remittances']);
        $this->assertEquals(1, $report['remittance_compliance']['verified']);
        $this->assertArrayHasKey('total_amount', $report['remittance_compliance']);
    }

    #[Test]
    public function it_calculates_complaint_resolution()
    {
        Complaint::factory()->create(['status' => 'registered']);
        Complaint::factory()->create(['status' => 'resolved']);
        Complaint::factory()->create(['status' => 'closed']);

        $report = $this->service->getComplianceReport();

        $this->assertEquals(3, $report['complaint_resolution']['total_complaints']);
        $this->assertEquals(2, $report['complaint_resolution']['resolved']);
        $this->assertEquals(1, $report['complaint_resolution']['pending']);
    }

    // =========================================================================
    // CUSTOM REPORT BUILDER
    // =========================================================================

    #[Test]
    public function it_builds_custom_report_for_candidates()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->count(5)->create();

        $report = $this->service->buildCustomReport('candidates', [], ['id', 'name', 'status']);

        $this->assertArrayHasKey('data', $report);
        $this->assertArrayHasKey('count', $report);
        $this->assertArrayHasKey('report_type', $report);
        $this->assertEquals('candidates', $report['report_type']);
    }

    #[Test]
    public function it_applies_filters_to_custom_report()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->count(3)->create(['status' => 'training']);
        Candidate::factory()->count(2)->create(['status' => 'departed']);

        $report = $this->service->buildCustomReport('candidates', [
            ['field' => 'status', 'operator' => 'equals', 'value' => 'training'],
        ]);

        $this->assertEquals(3, $report['data']->count());
    }

    #[Test]
    public function it_throws_exception_for_unknown_report_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown report type');

        $this->service->buildCustomReport('invalid_type');
    }

    // =========================================================================
    // FILTER OPERATORS
    // =========================================================================

    #[Test]
    public function it_applies_equals_filter()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->create(['name' => 'Test Person']);
        Candidate::factory()->create(['name' => 'Other Person']);

        $report = $this->service->buildCustomReport('candidates', [
            ['field' => 'name', 'operator' => 'equals', 'value' => 'Test Person'],
        ]);

        $this->assertEquals(1, $report['data']->count());
    }

    #[Test]
    public function it_applies_contains_filter()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->create(['name' => 'Muhammad Ali Khan']);
        Candidate::factory()->create(['name' => 'Ahmad Khan']);
        Candidate::factory()->create(['name' => 'Other Person']);

        $report = $this->service->buildCustomReport('candidates', [
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Khan'],
        ]);

        $this->assertEquals(2, $report['data']->count());
    }

    #[Test]
    public function it_applies_in_filter()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->create(['status' => 'new']);
        Candidate::factory()->create(['status' => 'training']);
        Candidate::factory()->create(['status' => 'departed']);

        $report = $this->service->buildCustomReport('candidates', [
            ['field' => 'status', 'operator' => 'in', 'value' => ['new', 'training']],
        ]);

        $this->assertEquals(2, $report['data']->count());
    }

    #[Test]
    public function it_applies_between_filter()
    {
        Campus::factory()->create();
        Trade::factory()->create();

        Candidate::factory()->create(['created_at' => now()->subDays(5)]);
        Candidate::factory()->create(['created_at' => now()->subDays(15)]);
        Candidate::factory()->create(['created_at' => now()->subDays(25)]);

        $report = $this->service->buildCustomReport('candidates', [
            [
                'field' => 'created_at',
                'operator' => 'between',
                'value' => [now()->subDays(20)->toDateString(), now()->toDateString()],
            ],
        ]);

        $this->assertEquals(2, $report['data']->count());
    }

    // =========================================================================
    // AVAILABLE FILTERS
    // =========================================================================

    #[Test]
    public function it_returns_available_filters_for_candidates()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Oep::factory()->create();

        $filters = $this->service->getAvailableFilters('candidates');

        $this->assertArrayHasKey('from_date', $filters);
        $this->assertArrayHasKey('to_date', $filters);
        $this->assertArrayHasKey('campus_id', $filters);
        $this->assertArrayHasKey('trade_id', $filters);
        $this->assertArrayHasKey('status', $filters);
    }

    #[Test]
    public function it_returns_available_filters_for_training()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Batch::factory()->create();

        $filters = $this->service->getAvailableFilters('training');

        $this->assertArrayHasKey('campus_id', $filters);
        $this->assertArrayHasKey('trade_id', $filters);
        $this->assertArrayHasKey('batch_id', $filters);
    }

    // =========================================================================
    // CACHING
    // =========================================================================

    #[Test]
    public function it_caches_report_results()
    {
        Campus::factory()->create();
        Trade::factory()->create();
        Candidate::factory()->count(3)->create();

        // First call - should cache
        $report1 = $this->service->getCandidatePipelineReport();

        // Create more candidates
        Candidate::factory()->count(2)->create();

        // Second call - should return cached result
        $report2 = $this->service->getCandidatePipelineReport();

        // Should be same due to caching
        $this->assertEquals($report1['total_candidates'], $report2['total_candidates']);
    }

    #[Test]
    public function it_can_clear_cache()
    {
        $this->service->clearCache();

        // No exception means success
        $this->assertTrue(true);
    }
}
