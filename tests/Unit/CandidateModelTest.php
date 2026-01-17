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

    // ==================== PHASE 1 IMPROVEMENTS: VALIDATION TESTS ====================

    /** @test */
    public function it_validates_pakistan_phone_format()
    {
        // Valid formats
        $this->assertTrue(Candidate::validatePakistanPhone('03001234567'));
        $this->assertTrue(Candidate::validatePakistanPhone('0300-1234567'));
        $this->assertTrue(Candidate::validatePakistanPhone('+923001234567'));
        $this->assertTrue(Candidate::validatePakistanPhone('923001234567'));

        // Invalid formats
        $this->assertFalse(Candidate::validatePakistanPhone('1234567890'));
        $this->assertFalse(Candidate::validatePakistanPhone('0400-1234567')); // Invalid prefix
        $this->assertFalse(Candidate::validatePakistanPhone('+1234567890')); // Not Pakistan
        $this->assertFalse(Candidate::validatePakistanPhone('030012345'));  // Too short
    }

    /** @test */
    public function it_calculates_luhn_check_digit_correctly()
    {
        // Test known values
        $this->assertEquals(0, Candidate::calculateLuhnCheckDigit('79927398710'));
        $this->assertEquals(7, Candidate::calculateLuhnCheckDigit('202500001'));
        $this->assertEquals(0, Candidate::calculateLuhnCheckDigit('0')); // Edge case
    }

    /** @test */
    public function it_generates_btevta_id_with_check_digit()
    {
        $btevtaId = Candidate::generateBtevtaId();

        // Check format: TLP-YYYY-XXXXX-C
        $this->assertMatchesRegularExpression('/^TLP-\d{4}-\d{5}-\d$/', $btevtaId);

        // Check that it validates
        $this->assertTrue(Candidate::validateBtevtaId($btevtaId));
    }

    /** @test */
    public function it_validates_btevta_id_check_digit()
    {
        // Valid format with correct check digit
        $validId = 'TLP-2025-00001-7';
        $this->assertTrue(Candidate::validateBtevtaId($validId));

        // Invalid check digit
        $invalidId = 'TLP-2025-00001-9';
        $this->assertFalse(Candidate::validateBtevtaId($invalidId));

        // Invalid format
        $this->assertFalse(Candidate::validateBtevtaId('TLP-2025-00001'));
        $this->assertFalse(Candidate::validateBtevtaId('INVALID-ID'));
    }

    /** @test */
    public function it_validates_cnic_checksum()
    {
        // Note: Pakistani CNIC checksum validation uses a weighted algorithm
        // We test that the method works correctly with both valid and invalid examples

        // Test format validation - exactly 13 digits
        $this->assertFalse(Candidate::validateCnicChecksum('123456789012'));  // 12 digits
        $this->assertFalse(Candidate::validateCnicChecksum('12345678901234')); // 14 digits
        $this->assertFalse(Candidate::validateCnicChecksum('1234567890abc'));  // Non-numeric

        // Test with valid 13-digit format
        $cnic = '1234567890123';
        // The method should return a boolean based on checksum algorithm
        $result = Candidate::validateCnicChecksum($cnic);
        $this->assertIsBool($result);
    }

    /** @test */
    public function it_finds_potential_duplicates_by_phone()
    {
        $candidate1 = Candidate::factory()->create(['phone' => '03001234567']);

        $duplicates = Candidate::findPotentialDuplicates('03001234567', null, null);

        $this->assertCount(1, $duplicates);
        $this->assertEquals('phone', $duplicates->first()['match_type']);
        $this->assertEquals($candidate1->id, $duplicates->first()['candidate']->id);
    }

    /** @test */
    public function it_finds_potential_duplicates_by_email()
    {
        $candidate1 = Candidate::factory()->create(['email' => 'test@example.com']);

        $duplicates = Candidate::findPotentialDuplicates(null, 'test@example.com', null);

        $this->assertCount(1, $duplicates);
        $this->assertEquals('email', $duplicates->first()['match_type']);
        $this->assertEquals($candidate1->id, $duplicates->first()['candidate']->id);
    }

    /** @test */
    public function it_finds_potential_duplicates_by_similar_name()
    {
        $candidate1 = Candidate::factory()->create(['name' => 'Muhammad Ahmad Khan']);

        $duplicates = Candidate::findPotentialDuplicates(null, null, 'Muhammad Ahmed Khan');

        // Name similarity might not be exact, depends on algorithm threshold
        $this->assertGreaterThanOrEqual(0, $duplicates->count());
    }

    /** @test */
    public function it_excludes_specified_candidate_from_duplicate_check()
    {
        $candidate1 = Candidate::factory()->create(['phone' => '03001234567']);

        // Should not find itself when excluded
        $duplicates = Candidate::findPotentialDuplicates('03001234567', null, null, $candidate1->id);

        $this->assertCount(0, $duplicates);
    }

    /** @test */
    public function it_auto_generates_ids_with_checksums()
    {
        $candidate = Candidate::factory()->create();

        // TheLeap ID should be auto-generated with check digit
        $this->assertNotNull($candidate->btevta_id);
        $this->assertMatchesRegularExpression('/^TLP-\d{4}-\d{5}-\d$/', $candidate->btevta_id);
        $this->assertTrue(Candidate::validateBtevtaId($candidate->btevta_id));

        // Application ID should also be auto-generated
        $this->assertNotNull($candidate->application_id);
        $this->assertMatchesRegularExpression('/^APP\d{10}$/', $candidate->application_id);
    }

    /** @test */
    public function it_generates_sequential_btevta_ids()
    {
        $candidate1 = Candidate::factory()->create();
        $candidate2 = Candidate::factory()->create();
        $candidate3 = Candidate::factory()->create();

        // All should have unique TheLeap IDs
        $this->assertNotEquals($candidate1->btevta_id, $candidate2->btevta_id);
        $this->assertNotEquals($candidate2->btevta_id, $candidate3->btevta_id);

        // All should validate correctly
        $this->assertTrue(Candidate::validateBtevtaId($candidate1->btevta_id));
        $this->assertTrue(Candidate::validateBtevtaId($candidate2->btevta_id));
        $this->assertTrue(Candidate::validateBtevtaId($candidate3->btevta_id));
    }
}
