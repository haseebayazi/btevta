<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateModelFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_has_valid_statuses()
    {
        $statuses = Candidate::getValidStatuses();
        $this->assertContains('new', $statuses);
        $this->assertContains('listed', $statuses);
        $this->assertContains('rejected', $statuses);
    }

    public function test_candidate_status_label()
    {
        $candidate = Candidate::factory()->create(['status' => 'screening']);
        $this->assertEquals('Screening', $candidate->status_label);
    }

    public function test_candidate_age_calculation()
    {
        $candidate = Candidate::factory()->create([
            'date_of_birth' => now()->subYears(25)
        ]);
        $this->assertEquals(25, $candidate->age);
    }

    public function test_candidate_is_listed()
    {
        $candidate = Candidate::factory()->create(['status' => 'listed']);
        $this->assertTrue($candidate->isListed());
    }

    public function test_candidate_progress_percentage()
    {
        $candidate = Candidate::factory()->create(['status' => 'visa_processing']);
        $this->assertEquals(80, $candidate->getProgressPercentage());
    }
}
