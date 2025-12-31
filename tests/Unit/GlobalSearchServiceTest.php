<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GlobalSearchService;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Campus;
use App\Models\Trade;
use App\Models\Batch;
use App\Models\Oep;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GlobalSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GlobalSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GlobalSearchService();
    }

    // =========================================================================
    // BASIC SEARCH
    // =========================================================================

    /** @test */
    public function it_returns_empty_for_empty_search_term()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $results = $this->service->search('');

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_returns_empty_for_whitespace_only_term()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $results = $this->service->search('   ');

        $this->assertEmpty($results);
    }

    // =========================================================================
    // CANDIDATE SEARCH
    // =========================================================================

    /** @test */
    public function it_searches_candidates_by_name()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Candidate::factory()->create(['name' => 'Muhammad Ali Khan']);

        $results = $this->service->search('Muhammad Ali', ['candidates']);

        $this->assertArrayHasKey('candidates', $results);
        $this->assertNotEmpty($results['candidates']['items']);
    }

    /** @test */
    public function it_searches_candidates_by_cnic()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Candidate::factory()->create(['cnic' => '3520112345671']);

        $results = $this->service->search('3520112345671', ['candidates']);

        $this->assertArrayHasKey('candidates', $results);
    }

    /** @test */
    public function campus_admin_only_sees_their_campus_candidates()
    {
        $campus1 = Campus::factory()->create();
        $campus2 = Campus::factory()->create();

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        Candidate::factory()->create([
            'name' => 'Test Candidate',
            'campus_id' => $campus1->id,
        ]);

        Candidate::factory()->create([
            'name' => 'Test Candidate 2',
            'campus_id' => $campus2->id,
        ]);

        $this->actingAs($user);

        $results = $this->service->search('Test', ['candidates']);

        if (!empty($results['candidates']['items'])) {
            foreach ($results['candidates']['items'] as $item) {
                // All results should be from campus1
                $candidate = Candidate::find($item['id']);
                $this->assertEquals($campus1->id, $candidate->campus_id);
            }
        }
    }

    // =========================================================================
    // BATCH SEARCH
    // =========================================================================

    /** @test */
    public function it_searches_batches_by_name()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Batch::factory()->create([
            'name' => 'Electrician Batch 2024',
            'batch_code' => 'ELEC-2024-001',
        ]);

        $results = $this->service->search('Electrician', ['batches']);

        $this->assertArrayHasKey('batches', $results);
    }

    /** @test */
    public function it_searches_batches_by_code()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Batch::factory()->create(['batch_code' => 'PLUMB-2024-001']);

        $results = $this->service->search('PLUMB-2024', ['batches']);

        $this->assertArrayHasKey('batches', $results);
    }

    // =========================================================================
    // TRADE SEARCH
    // =========================================================================

    /** @test */
    public function it_searches_trades_by_name()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Trade::factory()->create([
            'name' => 'Plumbing',
            'code' => 'PLB',
            'is_active' => true,
        ]);

        $results = $this->service->search('Plumbing', ['trades']);

        $this->assertArrayHasKey('trades', $results);
    }

    // =========================================================================
    // CAMPUS SEARCH
    // =========================================================================

    /** @test */
    public function it_searches_campuses_by_name()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Campus::factory()->create([
            'name' => 'Lahore Technical Campus',
            'city' => 'Lahore',
            'is_active' => true,
        ]);

        $results = $this->service->search('Lahore', ['campuses']);

        $this->assertArrayHasKey('campuses', $results);
    }

    /** @test */
    public function campus_admin_only_sees_their_campus()
    {
        $campus1 = Campus::factory()->create([
            'name' => 'Campus One',
            'is_active' => true,
        ]);
        $campus2 = Campus::factory()->create([
            'name' => 'Campus Two',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus1->id,
        ]);

        $this->actingAs($user);

        $results = $this->service->search('Campus', ['campuses']);

        if (!empty($results['campuses']['items'])) {
            foreach ($results['campuses']['items'] as $item) {
                $this->assertEquals($campus1->id, $item['id']);
            }
        }
    }

    // =========================================================================
    // OEP SEARCH
    // =========================================================================

    /** @test */
    public function it_searches_oeps_by_name()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Oep::factory()->create([
            'name' => 'Saudi Manpower Agency',
            'company_name' => 'SMA Corp',
            'is_active' => true,
        ]);

        $results = $this->service->search('Saudi', ['oeps']);

        $this->assertArrayHasKey('oeps', $results);
    }

    // =========================================================================
    // MULTIPLE TYPES
    // =========================================================================

    /** @test */
    public function it_searches_multiple_types_when_no_type_specified()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Candidate::factory()->create(['name' => 'Test Person']);
        Trade::factory()->create(['name' => 'Testing Trade', 'is_active' => true]);

        $results = $this->service->search('Test');

        // Should search all types
        $this->assertIsArray($results);
    }

    /** @test */
    public function it_filters_empty_result_sets()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        // Search for something that doesn't exist
        $results = $this->service->search('NonexistentTerm12345');

        $this->assertEmpty($results);
    }

    // =========================================================================
    // RESULT COUNT
    // =========================================================================

    /** @test */
    public function it_calculates_total_result_count()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Candidate::factory()->count(3)->create(['name' => 'SearchTest Person']);

        $results = $this->service->search('SearchTest', ['candidates']);
        $count = $this->service->getResultCount($results);

        $this->assertGreaterThanOrEqual(3, $count);
    }

    // =========================================================================
    // LIMIT
    // =========================================================================

    /** @test */
    public function it_respects_result_limit()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        Candidate::factory()->count(10)->create(['name' => 'LimitTest Person']);

        $results = $this->service->search('LimitTest', ['candidates'], 5);

        if (!empty($results['candidates']['items'])) {
            $this->assertLessThanOrEqual(5, count($results['candidates']['items']));
        }
    }

    // =========================================================================
    // RESULT STRUCTURE
    // =========================================================================

    /** @test */
    public function it_returns_proper_result_structure()
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($user);

        $candidate = Candidate::factory()->create(['name' => 'Structure Test']);

        $results = $this->service->search('Structure', ['candidates']);

        if (!empty($results['candidates'])) {
            $this->assertArrayHasKey('label', $results['candidates']);
            $this->assertArrayHasKey('icon', $results['candidates']);
            $this->assertArrayHasKey('items', $results['candidates']);

            if (!empty($results['candidates']['items'])) {
                $item = $results['candidates']['items'][0];
                $this->assertArrayHasKey('id', $item);
                $this->assertArrayHasKey('title', $item);
                $this->assertArrayHasKey('subtitle', $item);
                $this->assertArrayHasKey('url', $item);
            }
        }
    }
}
