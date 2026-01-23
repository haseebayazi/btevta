<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\AutoBatchService;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Trade;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AutoBatchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AutoBatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AutoBatchService::class);
    }

    #[Test]
    public function it_generates_correct_batch_number_format()
    {
        $campus = Campus::factory()->create(['code' => 'ISB']);
        $program = Program::factory()->create(['code' => 'TEC']);
        $trade = Trade::factory()->create(['code' => 'WLD']);

        $batchNumber = $this->service->generateBatchNumber($campus, $program, $trade);

        // Format: CAMPUS-PROGRAM-TRADE-YEAR-SEQUENCE
        $year = now()->format('Y');
        $this->assertMatchesRegularExpression("/^ISB-TEC-WLD-$year-\d{4}$/", $batchNumber);
    }

    #[Test]
    public function it_generates_sequential_batch_numbers()
    {
        $campus = Campus::factory()->create(['code' => 'LHR']);
        $program = Program::factory()->create(['code' => 'SKL']);
        $trade = Trade::factory()->create(['code' => 'ELC']);

        $batch1Number = $this->service->generateBatchNumber($campus, $program, $trade);

        // Create a batch with this number
        Batch::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'batch_code' => $batch1Number,
        ]);

        $batch2Number = $this->service->generateBatchNumber($campus, $program, $trade);

        // Extract sequence numbers
        $seq1 = (int) substr($batch1Number, -4);
        $seq2 = (int) substr($batch2Number, -4);

        $this->assertEquals($seq2, $seq1 + 1);
    }

    #[Test]
    public function it_starts_sequence_at_0001_for_new_combination()
    {
        $campus = Campus::factory()->create(['code' => 'KHI']);
        $program = Program::factory()->create(['code' => 'ADV']);
        $trade = Trade::factory()->create(['code' => 'PLB']);

        $batchNumber = $this->service->generateBatchNumber($campus, $program, $trade);

        $this->assertStringEndsWith('-0001', $batchNumber);
    }

    #[Test]
    public function it_generates_correct_allocated_number_format()
    {
        $batchNumber = 'ISB-TEC-WLD-2026-0001';
        $position = 15;

        $allocatedNumber = $this->service->generateAllocatedNumber($batchNumber, $position);

        $this->assertEquals('ISB-TEC-WLD-2026-0001-015', $allocatedNumber);
    }

    #[Test]
    public function it_pads_position_with_leading_zeros()
    {
        $batchNumber = 'LHR-SKL-ELC-2026-0002';

        $allocated1 = $this->service->generateAllocatedNumber($batchNumber, 1);
        $allocated2 = $this->service->generateAllocatedNumber($batchNumber, 10);
        $allocated3 = $this->service->generateAllocatedNumber($batchNumber, 100);

        $this->assertStringEndsWith('-001', $allocated1);
        $this->assertStringEndsWith('-010', $allocated2);
        $this->assertStringEndsWith('-100', $allocated3);
    }

    #[Test]
    public function it_creates_new_batch_when_none_exists()
    {
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batch = $this->service->assignOrCreateBatch($candidate);

        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertEquals($campus->id, $batch->campus_id);
        $this->assertEquals($trade->id, $batch->trade_id);
        $this->assertEquals(1, $batch->current_size);
    }

    #[Test]
    public function it_assigns_to_existing_batch_when_available()
    {
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $existingBatch = Batch::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'current_size' => 10,
            'max_size' => 25,
        ]);

        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batch = $this->service->assignOrCreateBatch($candidate);

        $this->assertEquals($existingBatch->id, $batch->id);
        $this->assertEquals(11, $batch->fresh()->current_size);
    }

    #[Test]
    public function it_creates_new_batch_when_existing_is_full()
    {
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $fullBatch = Batch::factory()->create([
            'campus_id' => $campus->id,
            'trade_id' => $trade->id,
            'current_size' => 25,
            'max_size' => 25,
        ]);

        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batch = $this->service->assignOrCreateBatch($candidate);

        $this->assertNotEquals($fullBatch->id, $batch->id);
        $this->assertEquals(1, $batch->current_size);
    }

    #[Test]
    public function it_respects_configured_batch_size()
    {
        config(['wasl.batch_size' => 30]);

        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batch = $this->service->assignOrCreateBatch($candidate);

        $this->assertEquals(30, $batch->max_size);
    }

    #[Test]
    public function it_updates_candidate_with_batch_and_allocated_number()
    {
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidate = Candidate::factory()->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batch = $this->service->assignOrCreateBatch($candidate);

        $candidate = $candidate->fresh();

        $this->assertEquals($batch->id, $candidate->batch_id);
        $this->assertNotNull($candidate->allocated_number);
        $this->assertStringContainsString($batch->batch_code, $candidate->allocated_number);
    }

    #[Test]
    public function it_handles_multiple_candidates_in_sequence()
    {
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidates = Candidate::factory()->count(3)->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batches = [];
        foreach ($candidates as $candidate) {
            $batches[] = $this->service->assignOrCreateBatch($candidate);
        }

        // All should be in the same batch
        $this->assertEquals($batches[0]->id, $batches[1]->id);
        $this->assertEquals($batches[1]->id, $batches[2]->id);

        // Batch size should be 3
        $this->assertEquals(3, $batches[0]->fresh()->current_size);
    }

    #[Test]
    public function it_generates_unique_allocated_numbers_within_batch()
    {
        $campus = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidates = Candidate::factory()->count(5)->create([
            'campus_id' => $campus->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $allocatedNumbers = [];
        foreach ($candidates as $candidate) {
            $this->service->assignOrCreateBatch($candidate);
            $allocatedNumbers[] = $candidate->fresh()->allocated_number;
        }

        // All allocated numbers should be unique
        $this->assertCount(5, array_unique($allocatedNumbers));
    }

    #[Test]
    public function it_groups_by_campus_program_trade_combination()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();
        $program = Program::factory()->create();
        $trade = Trade::factory()->create();

        $candidate1 = Candidate::factory()->create([
            'campus_id' => $campus1->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $candidate2 = Candidate::factory()->create([
            'campus_id' => $campus2->id,
            'program_id' => $program->id,
            'trade_id' => $trade->id,
        ]);

        $batch1 = $this->service->assignOrCreateBatch($candidate1);
        $batch2 = $this->service->assignOrCreateBatch($candidate2);

        // Different campuses should create different batches
        $this->assertNotEquals($batch1->id, $batch2->id);
    }
}
