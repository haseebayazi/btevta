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
        $this->withoutExceptionHandling();

        $trade = Trade::factory()->create();
        $campus = Campus::factory()->create();

        $data = [
            'btevta_id' => 'TLP-12345',
            'cnic' => '1234567890123',
            'name' => 'Test Candidate',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'email' => 'test@example.com',
            'address' => 'Test Address',
            'district' => 'Rawalpindi',
            'trade_id' => $trade->id,
            'campus_id' => $campus->id,
        ];

        $response = $this->actingAs($this->user)->post('/candidates', $data);

        $this->assertDatabaseHas('candidates', [
            'btevta_id' => 'TLP-12345',
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

    public function test_can_create_candidate_with_dashed_cnic()
    {
        $trade = Trade::factory()->create();
        $campus = Campus::factory()->create();

        $data = [
            'btevta_id' => 'TLP-54321',
            'cnic' => '21509-4361096-5',
            'name' => 'Samsher Ullah',
            'father_name' => 'Atta Ullah Jan',
            'date_of_birth' => '1999-02-02',
            'gender' => 'male',
            'phone' => '03001234567',
            'address' => 'Test Address',
            'district' => 'Rawalpindi',
            'trade_id' => $trade->id,
            'campus_id' => $campus->id,
        ];

        $response = $this->actingAs($this->user)->post('/candidates', $data);

        $response->assertSessionHasNoErrors();

        // The dashed CNIC must be normalized and stored as 13 plain digits.
        $this->assertDatabaseHas('candidates', [
            'name' => 'Samsher Ullah',
            'cnic' => '2150943610965',
        ]);
    }

    public function test_dashed_and_plain_cnic_collide_as_duplicate()
    {
        Candidate::factory()->create(['cnic' => '2150943610965']);

        $trade = Trade::factory()->create();

        $response = $this->actingAs($this->user)->post('/candidates', [
            'btevta_id' => 'TLP-99999',
            'cnic' => '21509-4361096-5',
            'name' => 'Duplicate Person',
            'father_name' => 'Father Name',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'phone' => '03001234567',
            'district' => 'Rawalpindi',
            'trade_id' => $trade->id,
        ]);

        $response->assertSessionHasErrors('cnic');
    }

    public function test_campus_admin_can_view_candidate_they_create()
    {
        $trade = Trade::factory()->create();
        $ownCampus = Campus::factory()->create();
        $otherCampus = Campus::factory()->create();

        $campusAdmin = User::factory()->campusAdmin()->create([
            'campus_id' => $ownCampus->id,
        ]);

        // The campus admin submits a *different* campus, but the candidate must be
        // forced onto their own campus so they can still access the record.
        $response = $this->actingAs($campusAdmin)->post('/candidates', [
            'btevta_id' => 'TLP-77777',
            'cnic' => '35202-1234567-1',
            'name' => 'Campus Owned Candidate',
            'father_name' => 'Father Name',
            'date_of_birth' => '1995-05-05',
            'gender' => 'male',
            'phone' => '03009876543',
            'district' => 'Islamabad',
            'trade_id' => $trade->id,
            'campus_id' => $otherCampus->id,
        ]);

        $response->assertSessionHasNoErrors();

        $candidate = Candidate::where('cnic', '3520212345671')->firstOrFail();
        $this->assertEquals($ownCampus->id, $candidate->campus_id);

        // No 403 when the creating campus admin opens the candidate afterwards.
        $this->actingAs($campusAdmin)
            ->get('/candidates/' . $candidate->id)
            ->assertStatus(200);
    }
}

