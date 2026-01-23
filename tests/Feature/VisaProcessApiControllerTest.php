<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\VisaProcess;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisaProcessApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($this->user, ['*']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    #[Test]
    public function it_returns_paginated_visa_processes()
    {
        $candidates = Candidate::factory()->count(15)->create(['status' => 'visa_process']);
        foreach ($candidates as $candidate) {
            VisaProcess::factory()->create(['candidate_id' => $candidate->id]);
        }

        $response = $this->getJson('/api/v1/visa-processes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'candidate_id', 'current_stage', 'candidate']
                ],
                'meta' => ['current_page', 'per_page', 'total']
            ]);
    }

    #[Test]
    public function it_filters_by_current_stage()
    {
        $candidates = Candidate::factory()->count(10)->create();
        foreach ($candidates->take(3) as $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'current_stage' => 'interview',
            ]);
        }
        foreach ($candidates->skip(3)->take(5) as $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'current_stage' => 'medical',
            ]);
        }

        $response = $this->getJson('/api/v1/visa-processes?stage=interview');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_filters_by_status()
    {
        $candidates = Candidate::factory()->count(8)->create();
        foreach ($candidates->take(5) as $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'interview_status' => 'passed',
            ]);
        }
        foreach ($candidates->skip(5) as $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'interview_status' => 'pending',
            ]);
        }

        $response = $this->getJson('/api/v1/visa-processes?interview_status=passed');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    #[Test]
    public function it_returns_single_visa_process()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->getJson("/api/v1/visa-processes/{$visaProcess->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'candidate_id', 'current_stage',
                    'interview_status', 'interview_date',
                    'medical_status', 'medical_date',
                    'candidate'
                ]
            ]);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_visa_process()
    {
        $response = $this->getJson('/api/v1/visa-processes/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // BY CANDIDATE
    // =========================================================================

    #[Test]
    public function it_returns_visa_process_by_candidate()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->getJson("/api/v1/visa-processes/candidate/{$candidate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $visaProcess->id);
    }

    #[Test]
    public function it_returns_404_for_candidate_without_visa_process()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->getJson("/api/v1/visa-processes/candidate/{$candidate->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    #[Test]
    public function it_creates_a_visa_process()
    {
        $candidate = Candidate::factory()->create(['status' => 'training']);

        $data = [
            'candidate_id' => $candidate->id,
            'current_stage' => 'interview',
            'interview_date' => '2024-06-01',
        ];

        $response = $this->postJson('/api/v1/visa-processes', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('visa_processes', [
            'candidate_id' => $candidate->id,
            'current_stage' => 'interview',
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/visa-processes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_id']);
    }

    #[Test]
    public function it_prevents_duplicate_visa_process()
    {
        $candidate = Candidate::factory()->create();
        VisaProcess::factory()->create(['candidate_id' => $candidate->id]);

        $response = $this->postJson('/api/v1/visa-processes', [
            'candidate_id' => $candidate->id,
            'current_stage' => 'interview',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    #[Test]
    public function it_updates_visa_process()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'current_stage' => 'interview',
            'interview_status' => 'pending',
        ]);

        $response = $this->putJson("/api/v1/visa-processes/{$visaProcess->id}", [
            'interview_status' => 'passed',
            'interview_date' => '2024-06-15',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.interview_status', 'passed');
    }

    #[Test]
    public function it_validates_stage_prerequisites()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'current_stage' => 'interview',
            'interview_status' => 'pending',
        ]);

        // Try to advance to medical without passing interview
        $response = $this->putJson("/api/v1/visa-processes/{$visaProcess->id}", [
            'current_stage' => 'medical',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_allows_progression_when_prerequisites_met()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'current_stage' => 'interview',
            'interview_status' => 'passed',
        ]);

        $response = $this->putJson("/api/v1/visa-processes/{$visaProcess->id}", [
            'current_stage' => 'takamol',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.current_stage', 'takamol');
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_visa_statistics()
    {
        $candidates = Candidate::factory()->count(20)->create();
        foreach ($candidates as $index => $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'current_stage' => ['interview', 'medical', 'takamol', 'visa_stamping'][$index % 4],
                'interview_status' => $index % 2 == 0 ? 'passed' : 'pending',
            ]);
        }

        $response = $this->getJson('/api/v1/visa-processes/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total',
                    'by_stage',
                    'interview_pass_rate',
                    'avg_processing_days',
                ]
            ]);
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    #[Test]
    public function unauthenticated_user_cannot_access_visa_processes()
    {
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/v1/visa-processes');

        $response->assertStatus(401);
    }
}
