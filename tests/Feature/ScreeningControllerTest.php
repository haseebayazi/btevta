<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ScreeningControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->candidate = Candidate::factory()->create([
            'status' => 'screening',
        ]);
    }

    #[Test]
    public function user_can_view_screening_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('screening.index'));

        $response->assertStatus(200);
        $response->assertViewIs('screening.index');
    }

    #[Test]
    public function user_can_view_create_screening_form()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('screening.create'));

        $response->assertStatus(200);
        $response->assertViewIs('screening.create');
    }

    #[Test]
    public function user_can_create_screening_record()
    {
        $this->actingAs($this->user);

        $screeningData = [
            'candidate_id' => $this->candidate->id,
            'screening_date' => now()->format('Y-m-d'),
            'call_duration' => 15,
            'call_notes' => 'Candidate responded positively',
            'remarks' => 'Good candidate',
        ];

        $response = $this->post(route('screening.store'), $screeningData);

        $response->assertRedirect(route('screening.index'));
        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
        ]);
    }

    #[Test]
    public function user_can_view_edit_screening_form()
    {
        $this->actingAs($this->user);

        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $response = $this->get(route('screening.edit', $this->candidate->id));

        $response->assertStatus(200);
        $response->assertViewIs('screening.edit');
        $response->assertViewHas('candidate');
        $response->assertViewHas('screening');
    }

    #[Test]
    public function user_can_update_screening_record()
    {
        $this->actingAs($this->user);

        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $updateData = [
            'screening_date' => now()->format('Y-m-d'),
            'call_duration' => 20,
            'call_notes' => 'Updated notes',
            'remarks' => 'Updated remarks',
        ];

        $response = $this->put(route('screening.update', $this->candidate->id), $updateData);

        $response->assertRedirect(route('screening.index'));

        $screening->refresh();
        $this->assertEquals('Updated notes', $screening->call_notes);
    }

    #[Test]
    public function screening_requires_candidate_id()
    {
        $this->actingAs($this->user);

        $screeningData = [
            'screening_date' => now()->format('Y-m-d'),
            'call_duration' => 15,
        ];

        $response = $this->post(route('screening.store'), $screeningData);

        $response->assertSessionHasErrors('candidate_id');
    }

    #[Test]
    public function screening_requires_valid_outcome()
    {
        $this->actingAs($this->user);

        $screeningData = [
            'candidate_id' => $this->candidate->id,
            'screening_date' => now()->format('Y-m-d'),
            'call_duration' => 15,
        ];

        $response = $this->post(route('screening.store'), $screeningData);

        $response->assertSessionHasErrors('screening_outcome');
    }

    #[Test]
    public function call_duration_must_be_positive()
    {
        $this->actingAs($this->user);

        $screeningData = [
            'candidate_id' => $this->candidate->id,
            'screening_date' => now()->format('Y-m-d'),
            'call_duration' => -5,
        ];

        $response = $this->post(route('screening.store'), $screeningData);

        $response->assertSessionHasErrors('call_duration');
    }

    #[Test]
    public function edit_redirects_if_no_screening_exists()
    {
        $this->actingAs($this->user);

        $candidateWithoutScreening = Candidate::factory()->create();

        $response = $this->get(route('screening.edit', $candidateWithoutScreening->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function user_can_log_call_for_candidate()
    {
        $this->actingAs($this->user);

        $callData = [
            'call_date' => now()->format('Y-m-d'),
            'call_duration' => 10,
            'call_notes' => 'First call attempt',
        ];

        $response = $this->post(route('screening.log-call', $this->candidate), $callData);

        $response->assertRedirect();
        $this->assertDatabaseHas('candidate_screenings', [
            'candidate_id' => $this->candidate->id,
        ]);
    }

    #[Test]
    public function user_can_record_screening_outcome()
    {
        $this->actingAs($this->user);

        $outcomeData = [
            'remarks' => 'Excellent candidate',
        ];

        $response = $this->post(route('screening.record-outcome', $this->candidate), $outcomeData);

        $response->assertRedirect();

        // Check candidate status updated
        $this->candidate->refresh();
        $this->assertEquals('registered', $this->candidate->status);
    }

    #[Test]
    public function failed_screening_updates_candidate_status_to_rejected()
    {
        $this->actingAs($this->user);

        $outcomeData = [
            'remarks' => 'Does not meet requirements',
        ];

        $response = $this->post(route('screening.record-outcome', $this->candidate), $outcomeData);

        $response->assertRedirect();

        $this->candidate->refresh();
        $this->assertEquals('rejected', $this->candidate->status);
    }

    #[Test]
    public function unauthenticated_users_cannot_access_screening()
    {
        $response = $this->get(route('screening.index'));

        $response->assertRedirect(route('login'));
    }
}
