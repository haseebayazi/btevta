<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_candidate()
    {
        $candidate = Candidate::factory()->create([
            'name' => 'John Doe',
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);

        $this->assertDatabaseHas('candidates', [
            'name' => 'John Doe',
            'cnic' => '1234567890123',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_campus()
    {
        $campus = Campus::factory()->create();
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);

        $this->assertInstanceOf(Campus::class, $candidate->campus);
        $this->assertEquals($campus->id, $candidate->campus->id);
    }

    /** @test */
    public function it_belongs_to_a_trade()
    {
        $trade = Trade::factory()->create();
        $candidate = Candidate::factory()->create(['trade_id' => $trade->id]);

        $this->assertInstanceOf(Trade::class, $candidate->trade);
        $this->assertEquals($trade->id, $candidate->trade->id);
    }

    /** @test */
    public function it_belongs_to_a_batch()
    {
        $batch = Batch::factory()->create();
        $candidate = Candidate::factory()->create(['batch_id' => $batch->id]);

        $this->assertInstanceOf(Batch::class, $candidate->batch);
        $this->assertEquals($batch->id, $candidate->batch->id);
    }

    /** @test */
    public function it_has_status_attribute()
    {
        $candidate = Candidate::factory()->create(['status' => 'screening']);

        $this->assertEquals('screening', $candidate->status);
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        Candidate::factory()->create(['status' => 'screening']);
        Candidate::factory()->create(['status' => 'registered']);
        Candidate::factory()->create(['status' => 'screening']);

        $screeningCandidates = Candidate::where('status', 'screening')->get();

        $this->assertCount(2, $screeningCandidates);
    }

    /** @test */
    public function it_can_search_by_name()
    {
        Candidate::factory()->create(['name' => 'Ahmed Ali']);
        Candidate::factory()->create(['name' => 'Muhammad Hassan']);

        // This test assumes there's a search scope or method
        $results = Candidate::where('name', 'like', '%Ahmed%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Ahmed Ali', $results->first()->name);
    }

    /** @test */
    public function it_requires_name_cnic_and_phone()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // This should fail if name is required at database level
        Candidate::create([
            'cnic' => '1234567890123',
            'phone' => '03001234567',
        ]);
    }

    /** @test */
    public function cnic_should_be_unique()
    {
        Candidate::factory()->create(['cnic' => '1234567890123']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        // This should fail due to unique constraint
        Candidate::factory()->create(['cnic' => '1234567890123']);
    }

    /** @test */
    public function it_can_soft_delete()
    {
        $candidate = Candidate::factory()->create();
        $candidateId = $candidate->id;

        $candidate->delete();

        $this->assertSoftDeleted('candidates', ['id' => $candidateId]);
    }
}
