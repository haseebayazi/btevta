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
        $candidates = Candidate::factory()->count(3)->create(['status' => 'visa_process']);
        foreach ($candidates as $candidate) {
            VisaProcess::factory()->create(['candidate_id' => $candidate->id]);
        }

        $response = $this->getJson('/api/v1/visa-processes');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'candidate_id', 'overall_status']
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ]
            ]);
    }

    #[Test]
    public function it_filters_by_overall_status()
    {
        $candidates = Candidate::factory()->count(5)->create();
        foreach ($candidates->take(3) as $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'overall_status' => 'interview',
            ]);
        }
        foreach ($candidates->skip(3) as $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'overall_status' => 'medical',
            ]);
        }

        $response = $this->getJson('/api/v1/visa-processes?status=interview');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }

    #[Test]
    public function it_filters_by_interview_status()
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
        $this->assertCount(5, $response->json('data.data'));
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
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'id', 'candidate_id', 'overall_status',
                    'interview_status', 'interview_date',
                    'medical_status', 'medical_date',
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
    public function it_returns_null_data_for_candidate_without_visa_process()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->getJson("/api/v1/visa-processes/candidate/{$candidate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data', null);
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
            'interview_date' => '2024-06-01',
        ];

        $response = $this->postJson('/api/v1/visa-processes', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('visa_processes', [
            'candidate_id' => $candidate->id,
            'overall_status' => 'interview',
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
            'overall_status' => 'interview',
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
    public function it_validates_update_field_values()
    {
        $candidate = Candidate::factory()->create();
        $visaProcess = VisaProcess::factory()->create([
            'candidate_id' => $candidate->id,
            'overall_status' => 'interview',
            'interview_status' => 'pending',
        ]);

        $response = $this->putJson("/api/v1/visa-processes/{$visaProcess->id}", [
            'interview_status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    #[Test]
    public function it_returns_visa_statistics()
    {
        $candidates = Candidate::factory()->count(4)->create();
        foreach ($candidates as $index => $candidate) {
            VisaProcess::factory()->create([
                'candidate_id' => $candidate->id,
                'overall_status' => ['interview', 'medical', 'takamol', 'completed'][$index % 4],
                'interview_status' => $index % 2 == 0 ? 'passed' : 'pending',
            ]);
        }

        $response = $this->getJson('/api/v1/visa-processes/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total',
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
