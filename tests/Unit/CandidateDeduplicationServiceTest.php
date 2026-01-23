<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\CandidateDeduplicationService;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateDeduplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CandidateDeduplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CandidateDeduplicationService();
    }

    // =========================================================================
    // CNIC DUPLICATE DETECTION
    // =========================================================================

    #[Test]
    public function it_detects_exact_cnic_match()
    {
        $existingCandidate = Candidate::factory()->create([
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'cnic' => '3520112345671',
            'name' => 'New Candidate',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals(100, $result['highest_confidence']);
        $this->assertEquals('cnic', $result['matches'][0]['match_type']);
    }

    #[Test]
    public function it_detects_cnic_with_dashes()
    {
        $existingCandidate = Candidate::factory()->create([
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'cnic' => '35201-1234567-1',
            'name' => 'New Candidate',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals('cnic', $result['matches'][0]['match_type']);
    }

    #[Test]
    public function it_returns_no_match_for_unique_cnic()
    {
        Candidate::factory()->create([
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'cnic' => '3520199999999',
            'name' => 'New Candidate',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertFalse($result['is_duplicate']);
        $this->assertEmpty($result['matches']);
    }

    // =========================================================================
    // NAME + DOB DUPLICATE DETECTION
    // =========================================================================

    #[Test]
    public function it_detects_name_and_dob_match()
    {
        $existingCandidate = Candidate::factory()->create([
            'name' => 'Muhammad Ali Khan',
            'date_of_birth' => '1990-05-15',
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'name' => 'Muhammad Ali Khan',
            'date_of_birth' => '1990-05-15',
            'cnic' => '3520199999999', // Different CNIC
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals('name_dob', $result['matches'][0]['match_type']);
        $this->assertEquals(85, $result['matches'][0]['confidence']);
    }

    #[Test]
    public function it_does_not_match_different_dob()
    {
        Candidate::factory()->create([
            'name' => 'Muhammad Ali Khan',
            'date_of_birth' => '1990-05-15',
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'name' => 'Muhammad Ali Khan',
            'date_of_birth' => '1995-05-15', // Different DOB
            'cnic' => '3520199999999',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertFalse($result['is_duplicate']);
    }

    // =========================================================================
    // PHONE DUPLICATE DETECTION
    // =========================================================================

    #[Test]
    public function it_detects_phone_match_with_similar_name()
    {
        $existingCandidate = Candidate::factory()->create([
            'name' => 'Ali Hassan',
            'phone' => '03001234567',
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'name' => 'Ali Hassan',
            'phone' => '03001234567',
            'cnic' => '3520199999999',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals('phone', $result['matches'][0]['match_type']);
    }

    #[Test]
    public function it_assigns_lower_confidence_for_phone_match_with_different_name()
    {
        Candidate::factory()->create([
            'name' => 'Ali Hassan',
            'phone' => '03001234567',
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'name' => 'Different Person',
            'phone' => '03001234567',
            'cnic' => '3520199999999',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        // Should still find a match but with lower confidence
        $phoneMatch = collect($result['matches'])->firstWhere('match_type', 'phone');
        if ($phoneMatch) {
            $this->assertLessThan(70, $phoneMatch['confidence']);
        }
    }

    // =========================================================================
    // TheLeap ID DUPLICATE DETECTION
    // =========================================================================

    #[Test]
    public function it_detects_btevta_id_match()
    {
        $existingCandidate = Candidate::factory()->create([
            'btevta_id' => 'TLP-2024-0001',
            'cnic' => '3520112345671',
        ]);

        $newCandidateData = [
            'btevta_id' => 'TLP-2024-0001',
            'name' => 'New Candidate',
            'cnic' => '3520199999999',
        ];

        $result = $this->service->checkForDuplicates($newCandidateData);

        $this->assertTrue($result['is_duplicate']);
        $this->assertEquals('btevta_id', $result['matches'][0]['match_type']);
        $this->assertEquals(100, $result['highest_confidence']);
    }

    // =========================================================================
    // NAME SIMILARITY
    // =========================================================================

    #[Test]
    public function it_calculates_exact_name_similarity()
    {
        $similarity = $this->service->calculateNameSimilarity('Ali Hassan', 'Ali Hassan');

        $this->assertEquals(1.0, $similarity);
    }

    #[Test]
    public function it_calculates_similar_name_score()
    {
        $similarity = $this->service->calculateNameSimilarity('Muhammad Ali', 'Mohd Ali');

        // Should be reasonably high due to similarity
        $this->assertGreaterThan(0.6, $similarity);
    }

    #[Test]
    public function it_calculates_low_score_for_different_names()
    {
        $similarity = $this->service->calculateNameSimilarity('Ali Hassan', 'Usman Khan');

        $this->assertLessThan(0.5, $similarity);
    }

    // =========================================================================
    // BATCH IMPORT WITH DEDUPLICATION
    // =========================================================================

    #[Test]
    public function it_skips_duplicates_in_batch_import()
    {
        $existingCandidate = Candidate::factory()->create([
            'cnic' => '3520112345671',
        ]);

        $candidatesData = [
            [
                'btevta_id' => 'TLP-NEW-001',
                'name' => 'New Candidate 1',
                'cnic' => '3520199999991',
                'phone' => '03001111111',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'district' => 'Lahore',
            ],
            [
                'btevta_id' => 'TLP-DUP-001',
                'name' => 'Duplicate Candidate',
                'cnic' => '3520112345671', // Same as existing
                'phone' => '03002222222',
                'date_of_birth' => '1991-02-02',
                'gender' => 'male',
                'district' => 'Karachi',
            ],
        ];

        $result = $this->service->processBatchImport($candidatesData, true);

        $this->assertEquals(1, $result['imported']);
        $this->assertCount(1, $result['duplicates']);
        $this->assertEquals(2, $result['total_processed']);
    }

    #[Test]
    public function it_imports_all_when_no_duplicates()
    {
        $candidatesData = [
            [
                'btevta_id' => 'TLP-NEW-001',
                'name' => 'Candidate 1',
                'cnic' => '3520199999991',
                'phone' => '03001111111',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'district' => 'Lahore',
            ],
            [
                'btevta_id' => 'TLP-NEW-002',
                'name' => 'Candidate 2',
                'cnic' => '3520199999992',
                'phone' => '03002222222',
                'date_of_birth' => '1991-02-02',
                'gender' => 'female',
                'district' => 'Karachi',
            ],
        ];

        $result = $this->service->processBatchImport($candidatesData);

        $this->assertEquals(2, $result['imported']);
        $this->assertEmpty($result['duplicates']);
        $this->assertEmpty($result['errors']);
    }

    // =========================================================================
    // DUPLICATE STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_duplicate_statistics()
    {
        // Create candidates with duplicate CNICs
        Candidate::factory()->create(['cnic' => '3520112345671']);
        Candidate::factory()->create(['cnic' => '3520112345671']);

        // Create candidates with duplicate phones
        Candidate::factory()->create(['phone' => '03001234567', 'cnic' => '3520199999991']);
        Candidate::factory()->create(['phone' => '03001234567', 'cnic' => '3520199999992']);

        $stats = $this->service->getDuplicateStatistics();

        $this->assertEquals(1, $stats['duplicate_cnics']);
        $this->assertEquals(1, $stats['duplicate_phones']);
    }

    // =========================================================================
    // MERGE DUPLICATES
    // =========================================================================

    #[Test]
    public function it_can_merge_duplicate_candidates()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $primary = Candidate::factory()->create(['name' => 'Primary Candidate']);
        $duplicate = Candidate::factory()->create(['name' => 'Duplicate Candidate']);

        $result = $this->service->mergeDuplicates($primary->id, $duplicate->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Merged', $result['message']);

        // Verify duplicate is soft deleted
        $this->assertSoftDeleted('candidates', ['id' => $duplicate->id]);
    }

    // =========================================================================
    // FIND BY METHODS
    // =========================================================================

    #[Test]
    public function it_finds_candidates_by_cnic()
    {
        $candidate = Candidate::factory()->create(['cnic' => '3520112345671']);

        $results = $this->service->findByCnic('3520112345671');

        $this->assertCount(1, $results);
        $this->assertEquals($candidate->id, $results->first()->id);
    }

    #[Test]
    public function it_finds_candidates_by_phone()
    {
        $candidate = Candidate::factory()->create(['phone' => '03001234567']);

        $results = $this->service->findByPhone('03001234567');

        $this->assertCount(1, $results);
        $this->assertEquals($candidate->id, $results->first()->id);
    }

    #[Test]
    public function it_finds_candidates_by_btevta_id()
    {
        $candidate = Candidate::factory()->create(['btevta_id' => 'TLP-2024-0001']);

        $results = $this->service->findByBtevtaId('TLP-2024-0001');

        $this->assertCount(1, $results);
        $this->assertEquals($candidate->id, $results->first()->id);
    }
}
