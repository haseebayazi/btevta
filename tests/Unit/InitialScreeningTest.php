<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\CandidateScreening;
use App\Models\Country;
use App\Models\Campus;
use App\Models\Trade;
use App\Enums\CandidateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InitialScreeningTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Login the user for activity logging
        $this->actingAs($this->user);

        // Create candidate
        $this->candidate = Candidate::factory()->create([
            'status' => 'screening',
            'campus_id' => Campus::factory()->create()->id,
            'trade_id' => Trade::factory()->create()->id,
        ]);
    }

    #[Test]
    public function screening_can_be_marked_as_screened()
    {
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => 'initial',
            'screening_status' => 'pending',
        ]);

        $result = $screening->markAsScreened('Candidate approved');

        $this->assertTrue($result);
        $this->assertEquals('screened', $screening->screening_status);
        $this->assertEquals($this->user->id, $screening->reviewer_id);
        $this->assertNotNull($screening->reviewed_at);
        $this->assertEquals('Candidate approved', $screening->remarks);

        // Verify candidate status updated
        $this->candidate->refresh();
        $this->assertEquals('screened', $this->candidate->status);
    }

    #[Test]
    public function screening_can_be_marked_as_deferred()
    {
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => 'initial',
            'screening_status' => 'pending',
        ]);

        $result = $screening->markAsDeferred('Not ready at this time');

        $this->assertTrue($result);
        $this->assertEquals('deferred', $screening->screening_status);
        $this->assertEquals($this->user->id, $screening->reviewer_id);
        $this->assertNotNull($screening->reviewed_at);
        $this->assertEquals('Not ready at this time', $screening->remarks);

        // Verify candidate status updated
        $this->candidate->refresh();
        $this->assertEquals('deferred', $this->candidate->status);
    }

    #[Test]
    public function screening_has_target_country_relationship()
    {
        $country = Country::factory()->create([
            'is_destination' => true,
            'is_active' => true,
        ]);

        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'target_country_id' => $country->id,
        ]);

        $this->assertNotNull($screening->targetCountry);
        $this->assertEquals($country->id, $screening->targetCountry->id);
        $this->assertEquals($country->name, $screening->targetCountry->name);
    }

    #[Test]
    public function screening_has_reviewer_relationship()
    {
        $reviewer = User::factory()->create([
            'role' => 'admin',
        ]);

        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $this->assertNotNull($screening->reviewer);
        $this->assertEquals($reviewer->id, $screening->reviewer->id);
        $this->assertEquals($reviewer->name, $screening->reviewer->name);
    }

    #[Test]
    public function screening_casts_consent_for_work_as_boolean()
    {
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'consent_for_work' => 1,
        ]);

        $this->assertIsBool($screening->consent_for_work);
        $this->assertTrue($screening->consent_for_work);
    }

    #[Test]
    public function screening_casts_reviewed_at_as_datetime()
    {
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'reviewed_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $screening->reviewed_at);
    }

    #[Test]
    public function screening_outcome_constants_are_defined()
    {
        $this->assertEquals('pending', CandidateScreening::OUTCOME_PENDING);
        $this->assertEquals('screened', CandidateScreening::OUTCOME_SCREENED);
        $this->assertEquals('deferred', CandidateScreening::OUTCOME_DEFERRED);
    }

    #[Test]
    public function candidate_status_can_transition_from_screening_to_screened()
    {
        $status = CandidateStatus::SCREENING;
        
        $this->assertTrue($status->canTransitionTo(CandidateStatus::SCREENED));
    }

    #[Test]
    public function candidate_status_can_transition_from_screened_to_registered()
    {
        $status = CandidateStatus::SCREENED;
        
        $this->assertTrue($status->canTransitionTo(CandidateStatus::REGISTERED));
    }

    #[Test]
    public function candidate_status_screened_has_correct_label()
    {
        $status = CandidateStatus::SCREENED;
        
        $this->assertEquals('Screened', $status->label());
    }

    #[Test]
    public function candidate_status_screened_has_correct_color()
    {
        $status = CandidateStatus::SCREENED;
        
        $this->assertEquals('primary', $status->color());
    }

    #[Test]
    public function placement_interest_field_accepts_valid_values()
    {
        $screeningLocal = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
            'placement_interest' => 'local',
        ]);

        $screeningInternational = CandidateScreening::factory()->create([
            'candidate_id' => Candidate::factory()->create(['campus_id' => Campus::factory()->create()->id])->id,
            'placement_interest' => 'international',
        ]);

        $this->assertEquals('local', $screeningLocal->placement_interest);
        $this->assertEquals('international', $screeningInternational->placement_interest);
    }

    #[Test]
    public function screening_tracks_created_by_and_updated_by()
    {
        $screening = CandidateScreening::factory()->create([
            'candidate_id' => $this->candidate->id,
        ]);

        $this->assertEquals($this->user->id, $screening->created_by);

        // Update the screening
        $screening->remarks = 'Updated remarks';
        $screening->save();

        $this->assertEquals($this->user->id, $screening->updated_by);
    }
}
