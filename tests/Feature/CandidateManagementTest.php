<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CandidateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
    }

    public function test_candidates_list_loads()
    {
        $response = $this->actingAs($this->user)->get('/candidates');
        $response->assertStatus(200);
    }

    public function test_can_create_candidate()
    {
        $trade = Trade::factory()->create();
        $campus = Campus::factory()->create();

        $data = [
            'btevta_id' => 'BTEVTA-12345',
            'cnic' => '1234567890123',
            'name' => 'Test Candidate',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => 'Test Address',
            'district' => 'Rawalpindi',
            'trade_id' => $trade->id,
            'campus_id' => $campus->id,
        ];

        $response = $this->actingAs($this->user)->post('/candidates', $data);

        $this->assertDatabaseHas('candidates', [
            'btevta_id' => 'BTEVTA-12345',
            'name' => 'Test Candidate'
        ]);
    }

    public function test_can_update_candidate()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->actingAs($this->user)->patch('/candidates/' . $candidate->id, [
            'name' => 'Updated Name',
            'btevta_id' => $candidate->btevta_id,
            'cnic' => $candidate->cnic,
            'father_name' => $candidate->father_name,
            'date_of_birth' => $candidate->date_of_birth,
            'gender' => $candidate->gender,
            'phone' => $candidate->phone,
            'address' => $candidate->address,
            'district' => $candidate->district,
            'trade_id' => $candidate->trade_id,
        ]);

        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
            'name' => 'Updated Name'
        ]);
    }

    public function test_can_delete_candidate()
    {
        $candidate = Candidate::factory()->create();

        $response = $this->actingAs($this->user)->delete('/candidates/' . $candidate->id);

        $this->assertSoftDeleted('candidates', ['id' => $candidate->id]);
    }
}

