<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\CandidateScreening;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for screening workflow.
 */
class ScreeningWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected Trade $trade;
    protected Campus $campus;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@theleap.org',
            'password' => bcrypt('password'),
        ]);
        $this->trade = Trade::factory()->create();
        $this->campus = Campus::factory()->create();
        $this->candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
        ]);
    }

    /** @test */
    public function it_can_access_screening_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/screening')
                    ->assertSee('Candidate Screening')
                    ->assertSee('Pending Screenings');
        });
    }

    /** @test */
    public function it_shows_candidate_screening_status()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->assertSee($this->candidate->name)
                    ->assertSee('Desk Screening')
                    ->assertSee('Call Screening')
                    ->assertSee('Physical Screening');
        });
    }

    /** @test */
    public function it_can_perform_desk_screening()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@desk-screening-tab')
                    ->waitFor('@desk-screening-form')
                    ->select('status', 'passed')
                    ->type('remarks', 'All documents verified correctly')
                    ->press('Submit Desk Screening')
                    ->waitForText('Screening recorded successfully')
                    ->assertSee('Passed');
        });
    }

    /** @test */
    public function it_can_perform_call_screening()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@call-screening-tab')
                    ->waitFor('@call-screening-form')
                    ->type('call_duration', '120')
                    ->check('call_answered')
                    ->select('status', 'passed')
                    ->type('remarks', 'Candidate confirmed all details')
                    ->press('Submit Call Screening')
                    ->waitForText('Call screening recorded')
                    ->assertSee('1/3'); // Call attempts
        });
    }

    /** @test */
    public function it_can_perform_physical_screening()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@physical-screening-tab')
                    ->waitFor('@physical-screening-form')
                    ->select('status', 'passed')
                    ->type('remarks', 'Physical verification completed successfully')
                    ->press('Submit Physical Screening')
                    ->waitForText('Screening recorded successfully')
                    ->assertSee('Passed');
        });
    }

    /** @test */
    public function it_uploads_evidence_for_screening()
    {
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@upload-evidence-btn')
                    ->waitFor('@evidence-upload-modal')
                    ->attach('evidence_file', __DIR__ . '/fixtures/sample.pdf')
                    ->press('Upload Evidence')
                    ->waitForText('Evidence uploaded successfully')
                    ->assertSee('Evidence uploaded');
        });
    }

    /** @test */
    public function it_shows_screening_progress()
    {
        // Create passed screenings
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->assertSee('66%') // 2 out of 3
                    ->assertSee('Desk Screening: Passed')
                    ->assertSee('Call Screening: Passed')
                    ->assertSee('Physical Screening: Pending');
        });
    }

    /** @test */
    public function it_auto_progresses_when_all_pass()
    {
        // Create passed screenings for all types
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_DESK,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_PASSED,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@physical-screening-tab')
                    ->select('status', 'passed')
                    ->type('remarks', 'Verified')
                    ->press('Submit Physical Screening')
                    ->waitForText('All screenings passed')
                    ->assertSee('Status: Registered');
        });
    }

    /** @test */
    public function it_shows_rejection_on_failure()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@desk-screening-tab')
                    ->select('status', 'failed')
                    ->type('remarks', 'Documents found to be invalid')
                    ->press('Submit Desk Screening')
                    ->waitForText('Candidate rejected')
                    ->assertSee('Status: Rejected');
        });
    }

    /** @test */
    public function it_shows_call_attempts_counter()
    {
        CandidateScreening::create([
            'candidate_id' => $this->candidate->id,
            'screening_type' => CandidateScreening::TYPE_CALL,
            'status' => CandidateScreening::STATUS_IN_PROGRESS,
            'call_count' => 2,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/screening/{$this->candidate->id}")
                    ->click('@call-screening-tab')
                    ->assertSee('Attempts: 2/3')
                    ->assertSee('1 attempt remaining');
        });
    }

    /** @test */
    public function it_filters_screening_list_by_status()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/screening')
                    ->select('@status-filter', 'pending')
                    ->waitForReload()
                    ->assertQueryStringHas('status', 'pending')
                    ->assertSee($this->candidate->name);
        });
    }
}
