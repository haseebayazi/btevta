<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AllocationService;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Trade;
use App\Models\ImplementingPartner;
use App\Models\Oep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AllocationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AllocationService::class);
    }

    /** @test */
    public function it_can_allocate_candidate_with_all_fields()
    {
        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $partner = ImplementingPartner::factory()->create();
        $trade = Trade::factory()->create();
        $oep = Oep::factory()->create();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'implementing_partner_id' => $partner->id,
            'trade_id' => $trade->id,
            'oep_id' => $oep->id,
        ];

        $result = $this->service->allocate($candidate, $allocationData);

        $this->assertEquals($campus->id, $result->campus_id);
        $this->assertEquals($program->id, $result->program_id);
        $this->assertEquals($partner->id, $result->implementing_partner_id);
        $this->assertEquals($trade->id, $result->trade_id);
        $this->assertEquals($oep->id, $result->oep_id);
    }

    /** @test */
    public function it_can_allocate_without_optional_fields()
    {
        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $result = $this->service->allocate($candidate, $allocationData);

        $this->assertEquals($campus->id, $result->campus_id);
        $this->assertNull($result->implementing_partner_id);
        $this->assertNull($result->oep_id);
    }

    /** @test */
    public function it_validates_campus_exists()
    {
        $this->expectException(\Exception::class);

        $candidate = Candidate::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $allocationData = [
            'campus_id' => 99999, // Non-existent campus
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $this->service->allocate($candidate, $allocationData);
    }

    /** @test */
    public function it_validates_program_exists()
    {
        $this->expectException(\Exception::class);

        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $trade = Trade::factory()->create();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => 99999, // Non-existent program
            'trade_id' => $trade->id,
        ];

        $this->service->allocate($candidate, $allocationData);
    }

    /** @test */
    public function it_validates_trade_exists()
    {
        $this->expectException(\Exception::class);

        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => 99999, // Non-existent trade
        ];

        $this->service->allocate($candidate, $allocationData);
    }

    /** @test */
    public function it_uses_database_transaction()
    {
        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        // This will fail because we're mocking DB, but we can verify transaction was attempted
        try {
            $this->service->allocate($candidate, $allocationData);
        } catch (\Exception $e) {
            // Expected to fail with mocked DB
        }
    }

    /** @test */
    public function it_logs_allocation_activity()
    {
        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $this->service->allocate($candidate, $allocationData);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Candidate::class,
            'subject_id' => $candidate->id,
            'description' => 'Candidate allocation updated',
            'causer_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_returns_allocation_summary()
    {
        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create(['name' => 'Islamabad Campus']);
        $program = Program::factory()->create(['name' => 'Technical Training']);
        $trade = Trade::factory()->create(['name' => 'Welder']);
        $partner = ImplementingPartner::factory()->create(['name' => 'Partner Org']);

        $candidate->update([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
            'implementing_partner_id' => $partner->id,
        ]);

        $summary = $this->service->getAllocationSummary($candidate);

        $this->assertArrayHasKey('campus', $summary);
        $this->assertArrayHasKey('program', $summary);
        $this->assertArrayHasKey('trade', $summary);
        $this->assertArrayHasKey('implementing_partner', $summary);
        $this->assertEquals('Islamabad Campus', $summary['campus']);
        $this->assertEquals('Technical Training', $summary['program']);
    }

    /** @test */
    public function it_handles_null_optional_fields_in_summary()
    {
        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidate->update([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
            'implementing_partner_id' => null,
            'oep_id' => null,
        ]);

        $summary = $this->service->getAllocationSummary($candidate);

        $this->assertNull($summary['implementing_partner']);
        $this->assertNull($summary['oep']);
    }

    /** @test */
    public function it_can_bulk_allocate_multiple_candidates()
    {
        $candidates = Candidate::factory()->count(5)->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $candidateIds = $candidates->pluck('id')->toArray();

        $results = $this->service->bulkAllocate($candidateIds, $allocationData);

        $this->assertCount(5, $results['success']);
        $this->assertCount(0, $results['failed']);

        foreach ($candidates as $candidate) {
            $candidate = $candidate->fresh();
            $this->assertEquals($campus->id, $candidate->campus_id);
            $this->assertEquals($program->id, $candidate->program_id);
            $this->assertEquals($trade->id, $candidate->trade_id);
        }
    }

    /** @test */
    public function it_handles_bulk_allocation_failures_gracefully()
    {
        $validCandidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $candidateIds = [$validCandidate->id, 99999]; // One valid, one invalid

        $results = $this->service->bulkAllocate($candidateIds, $allocationData);

        $this->assertCount(1, $results['success']);
        $this->assertCount(1, $results['failed']);
        $this->assertArrayHasKey(99999, $results['failed']);
    }

    /** @test */
    public function it_validates_all_required_fields_are_present()
    {
        $this->expectException(\Exception::class);

        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();

        // Missing program_id and trade_id
        $allocationData = [
            'campus_id' => $campus->id,
        ];

        $this->service->allocate($candidate, $allocationData);
    }

    /** @test */
    public function it_can_update_existing_allocation()
    {
        $candidate = Candidate::factory()->create();
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        // Initial allocation
        $allocationData1 = [
            'campus_id' => $campus1->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $this->service->allocate($candidate, $allocationData1);
        $this->assertEquals($campus1->id, $candidate->fresh()->campus_id);

        // Update allocation
        $allocationData2 = [
            'campus_id' => $campus2->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ];

        $this->service->allocate($candidate, $allocationData2);
        $this->assertEquals($campus2->id, $candidate->fresh()->campus_id);
    }

    /** @test */
    public function it_rolls_back_on_database_error()
    {
        $this->expectException(\Exception::class);

        $candidate = Candidate::factory()->create();
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        // Force a database error by using an invalid implementing_partner_id
        $allocationData = [
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
            'implementing_partner_id' => 99999,
        ];

        try {
            $this->service->allocate($candidate, $allocationData);
        } catch (\Exception $e) {
            // Verify candidate wasn't partially updated
            $candidate = $candidate->fresh();
            $this->assertNotEquals($campus->id, $candidate->campus_id);
            throw $e;
        }
    }
}
