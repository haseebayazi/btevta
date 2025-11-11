<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\Remittance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemittanceApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $candidate;
    protected $departure;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->candidate = Candidate::factory()->create();
        $this->departure = Departure::factory()->create(['candidate_id' => $this->candidate->id]);
    }

    public function test_api_index_returns_paginated_remittances()
    {
        Remittance::factory()->count(5)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/remittances/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'candidate_id',
                        'transaction_reference',
                        'amount',
                        'currency',
                        'transfer_date',
                        'status',
                    ]
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_api_show_returns_single_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/remittances/{$remittance->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $remittance->id,
                'transaction_reference' => $remittance->transaction_reference,
                'amount' => (float)$remittance->amount,
            ]);
    }

    public function test_api_show_returns_404_for_nonexistent_remittance()
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/remittances/99999');

        $response->assertStatus(404)
            ->assertJson(['error' => 'Remittance not found']);
    }

    public function test_api_by_candidate_returns_candidate_remittances()
    {
        Remittance::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittances/candidate/{$this->candidate->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'candidate',
                'remittances',
                'summary' => [
                    'total_count',
                    'total_amount',
                    'average_amount',
                    'latest_remittance',
                ],
            ])
            ->assertJson([
                'summary' => [
                    'total_count' => 3,
                ],
            ]);
    }

    public function test_api_store_creates_new_remittance()
    {
        $data = [
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'transaction_reference' => 'TXN_API_123',
            'amount' => 50000,
            'currency' => 'PKR',
            'transfer_date' => '2025-11-01',
            'transfer_method' => 'bank_transfer',
            'sender_name' => 'API Sender',
            'receiver_name' => 'API Receiver',
            'bank_name' => 'HBL',
            'primary_purpose' => 'family_support',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/remittances/', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'remittance' => [
                    'id',
                    'transaction_reference',
                    'amount',
                ],
            ])
            ->assertJson([
                'message' => 'Remittance created successfully',
            ]);

        $this->assertDatabaseHas('remittances', [
            'transaction_reference' => 'TXN_API_123',
            'amount' => 50000,
        ]);
    }

    public function test_api_store_validates_required_fields()
    {
        $data = [];

        $response = $this->actingAs($this->user)->postJson('/api/v1/remittances/', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_id', 'amount', 'transfer_date']);
    }

    public function test_api_update_modifies_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'amount' => 50000,
        ]);

        $data = [
            'amount' => 75000,
            'notes' => 'Updated via API',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/remittances/{$remittance->id}", $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'remittance',
            ]);

        $this->assertDatabaseHas('remittances', [
            'id' => $remittance->id,
            'amount' => 75000,
            'notes' => 'Updated via API',
        ]);
    }

    public function test_api_destroy_deletes_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/remittances/{$remittance->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Remittance deleted successfully']);

        $this->assertSoftDeleted('remittances', ['id' => $remittance->id]);
    }

    public function test_api_search_finds_remittances_by_transaction_reference()
    {
        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'transaction_reference' => 'TXN_SEARCH_123',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittances/search/query?transaction_reference=SEARCH');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'count',
                'results',
            ])
            ->assertJsonPath('count', 1);
    }

    public function test_api_search_finds_remittances_by_amount_range()
    {
        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'amount' => 50000,
        ]);

        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'amount' => 100000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittances/search/query?min_amount=45000&max_amount=55000');

        $response->assertStatus(200)
            ->assertJsonPath('count', 1);
    }

    public function test_api_statistics_returns_overview()
    {
        Remittance::factory()->count(10)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'year' => date('Y'),
            'has_proof' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/remittances/stats/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_remittances',
                'total_amount',
                'average_amount',
                'total_candidates',
                'with_proof',
                'proof_compliance_rate',
                'by_status',
                'current_year',
                'current_month',
            ]);
    }

    public function test_api_verify_marks_remittance_as_verified()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/remittances/{$remittance->id}/verify");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Remittance verified successfully']);

        $this->assertDatabaseHas('remittances', [
            'id' => $remittance->id,
            'status' => 'verified',
            'verified_by' => $this->user->id,
        ]);
    }

    public function test_api_index_supports_filtering_by_candidate()
    {
        $candidate2 = Candidate::factory()->create();
        $departure2 = Departure::factory()->create(['candidate_id' => $candidate2->id]);

        Remittance::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        Remittance::factory()->count(2)->create([
            'candidate_id' => $candidate2->id,
            'departure_id' => $departure2->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/remittances/?candidate_id={$this->candidate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('total', 3);
    }

    public function test_api_index_supports_filtering_by_status()
    {
        Remittance::factory()->count(2)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'status' => 'verified',
        ]);

        Remittance::factory()->count(3)->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/remittances/?status=verified');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    public function test_api_requires_authentication()
    {
        $response = $this->getJson('/api/v1/remittances/');

        $response->assertStatus(401);
    }
}
