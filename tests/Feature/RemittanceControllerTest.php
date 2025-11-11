<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Departure;
use App\Models\Remittance;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemittanceControllerTest extends TestCase
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

    public function test_remittances_index_loads_successfully()
    {
        $response = $this->actingAs($this->user)->get('/remittances');
        $response->assertStatus(200);
    }

    public function test_remittances_create_page_loads()
    {
        $response = $this->actingAs($this->user)->get('/remittances/create');
        $response->assertStatus(200);
    }

    public function test_can_create_remittance()
    {
        $data = [
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'transaction_reference' => 'TXN123456789',
            'amount' => 50000,
            'currency' => 'PKR',
            'amount_foreign' => 150,
            'foreign_currency' => 'USD',
            'exchange_rate' => 333.33,
            'transfer_date' => '2025-11-01',
            'transfer_method' => 'bank_transfer',
            'sender_name' => 'John Doe',
            'sender_location' => 'Dubai, UAE',
            'receiver_name' => 'Jane Doe',
            'receiver_account' => 'PK36MEZN0000001234567890',
            'bank_name' => 'HBL',
            'primary_purpose' => 'family_support',
            'purpose_description' => 'Monthly family support',
            'has_proof' => true,
            'notes' => 'Test remittance',
        ];

        $response = $this->actingAs($this->user)->post('/remittances', $data);

        $this->assertDatabaseHas('remittances', [
            'transaction_reference' => 'TXN123456789',
            'amount' => 50000,
            'candidate_id' => $this->candidate->id,
        ]);
    }

    public function test_can_view_remittance_details()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get("/remittances/{$remittance->id}");
        $response->assertStatus(200);
    }

    public function test_can_edit_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get("/remittances/{$remittance->id}/edit");
        $response->assertStatus(200);
    }

    public function test_can_update_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'amount' => 50000,
            'transaction_reference' => 'TXN12345',
        ]);

        $updateData = [
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'transaction_reference' => 'TXN12345',
            'amount' => 75000,
            'currency' => 'PKR',
            'transfer_date' => $remittance->transfer_date->format('Y-m-d'),
            'transfer_method' => $remittance->transfer_method,
            'sender_name' => $remittance->sender_name,
            'receiver_name' => $remittance->receiver_name,
            'bank_name' => $remittance->bank_name,
            'primary_purpose' => $remittance->primary_purpose,
            'notes' => 'Updated amount',
        ];

        $response = $this->actingAs($this->user)->put("/remittances/{$remittance->id}", $updateData);

        $this->assertDatabaseHas('remittances', [
            'id' => $remittance->id,
            'amount' => 75000,
            'notes' => 'Updated amount',
        ]);
    }

    public function test_can_delete_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete("/remittances/{$remittance->id}");

        $this->assertSoftDeleted('remittances', ['id' => $remittance->id]);
    }

    public function test_can_verify_remittance()
    {
        $remittance = Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->post("/remittances/{$remittance->id}/verify");

        $this->assertDatabaseHas('remittances', [
            'id' => $remittance->id,
            'status' => 'verified',
            'verified_by' => $this->user->id,
        ]);
    }

    public function test_transaction_reference_must_be_unique()
    {
        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'transaction_reference' => 'TXN_DUPLICATE',
        ]);

        $data = [
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'transaction_reference' => 'TXN_DUPLICATE',
            'amount' => 50000,
            'currency' => 'PKR',
            'transfer_date' => '2025-11-01',
            'transfer_method' => 'bank_transfer',
            'sender_name' => 'John Doe',
            'receiver_name' => 'Jane Doe',
            'bank_name' => 'HBL',
            'primary_purpose' => 'family_support',
        ];

        $response = $this->actingAs($this->user)->post('/remittances', $data);

        $response->assertSessionHasErrors('transaction_reference');
    }

    public function test_amount_must_be_positive()
    {
        $data = [
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'transaction_reference' => 'TXN123456',
            'amount' => -5000, // Negative amount
            'currency' => 'PKR',
            'transfer_date' => '2025-11-01',
            'transfer_method' => 'bank_transfer',
            'sender_name' => 'John Doe',
            'receiver_name' => 'Jane Doe',
            'bank_name' => 'HBL',
            'primary_purpose' => 'family_support',
        ];

        $response = $this->actingAs($this->user)->post('/remittances', $data);

        $response->assertSessionHasErrors('amount');
    }

    public function test_required_fields_validation()
    {
        $data = [
            // Missing required fields
        ];

        $response = $this->actingAs($this->user)->post('/remittances', $data);

        $response->assertSessionHasErrors([
            'candidate_id',
            'amount',
            'transfer_date',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_remittances()
    {
        $response = $this->get('/remittances');
        $response->assertRedirect('/login');
    }

    public function test_can_filter_remittances_by_candidate()
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
            ->get("/remittances?candidate_id={$this->candidate->id}");

        $response->assertStatus(200);
    }

    public function test_can_filter_remittances_by_status()
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

        $response = $this->actingAs($this->user)->get('/remittances?status=verified');

        $response->assertStatus(200);
    }

    public function test_can_filter_remittances_by_year()
    {
        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'year' => 2025,
        ]);

        Remittance::factory()->create([
            'candidate_id' => $this->candidate->id,
            'departure_id' => $this->departure->id,
            'recorded_by' => $this->user->id,
            'year' => 2024,
        ]);

        $response = $this->actingAs($this->user)->get('/remittances?year=2025');

        $response->assertStatus(200);
    }
}
