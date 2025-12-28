<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for training workflow.
 */
class TrainingWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected Trade $trade;
    protected Campus $campus;
    protected Batch $batch;
    protected Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@btevta.gov.pk',
            'password' => bcrypt('password'),
        ]);
        $this->trade = Trade::factory()->create();
        $this->campus = Campus::factory()->create();
        $this->batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 30,
        ]);
        $this->candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
            'training_status' => Candidate::TRAINING_IN_PROGRESS,
        ]);
    }

    /** @test */
    public function it_can_access_training_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit('/training')
                    ->assertSee('Training Management')
                    ->assertSee('Active Batches');
        });
    }

    /** @test */
    public function it_can_view_batch_details()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/batches/{$this->batch->id}")
                    ->assertSee($this->batch->name)
                    ->assertSee('Candidates')
                    ->assertSee($this->candidate->name);
        });
    }

    /** @test */
    public function it_can_record_attendance()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/batches/{$this->batch->id}/attendance")
                    ->assertSee('Record Attendance')
                    ->type('date', now()->format('Y-m-d'))
                    ->check("attendance[{$this->candidate->id}]")
                    ->press('Submit Attendance')
                    ->waitForText('Attendance recorded')
                    ->assertSee('Attendance saved successfully');
        });
    }

    /** @test */
    public function it_shows_attendance_percentage()
    {
        // Create attendance records
        for ($i = 0; $i < 10; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $this->candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 8 ? 'present' : 'absent',
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/candidates/{$this->candidate->id}")
                    ->assertSee('Attendance: 80%')
                    ->assertSee('Present: 8')
                    ->assertSee('Absent: 2');
        });
    }

    /** @test */
    public function it_can_record_assessment()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/candidates/{$this->candidate->id}/assessment")
                    ->select('assessment_type', 'practical')
                    ->type('subject', 'Basic Skills')
                    ->type('score', '75')
                    ->select('result', 'pass')
                    ->type('remarks', 'Good performance')
                    ->press('Record Assessment')
                    ->waitForText('Assessment recorded')
                    ->assertSee('75');
        });
    }

    /** @test */
    public function it_shows_assessment_history()
    {
        TrainingAssessment::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'practical',
            'subject' => 'Skill Test 1',
            'score' => 70,
            'result' => 'pass',
            'assessed_at' => now()->subDays(5),
        ]);

        TrainingAssessment::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'subject' => 'Final Exam',
            'score' => 80,
            'result' => 'pass',
            'assessed_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/candidates/{$this->candidate->id}")
                    ->assertSee('Assessments')
                    ->assertSee('Skill Test 1')
                    ->assertSee('70')
                    ->assertSee('Final Exam')
                    ->assertSee('80')
                    ->assertSee('Average: 75');
        });
    }

    /** @test */
    public function it_can_issue_certificate()
    {
        // Setup - add required attendance and assessment
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $this->candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => 'present',
            ]);
        }

        TrainingAssessment::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'subject' => 'Final Exam',
            'score' => 75,
            'result' => 'pass',
            'assessed_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/candidates/{$this->candidate->id}")
                    ->click('@issue-certificate-btn')
                    ->waitFor('@certificate-modal')
                    ->type('certificate_number', 'CERT-2025-0001')
                    ->press('Issue Certificate')
                    ->waitForText('Certificate issued')
                    ->assertSee('CERT-2025-0001');
        });
    }

    /** @test */
    public function it_validates_certificate_requirements()
    {
        // Candidate with low attendance
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $this->candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 50 ? 'present' : 'absent', // Only 50%
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/candidates/{$this->candidate->id}")
                    ->click('@issue-certificate-btn')
                    ->assertSee('Cannot issue certificate')
                    ->assertSee('Attendance below 80%');
        });
    }

    /** @test */
    public function it_can_complete_training()
    {
        // Setup complete training requirements
        for ($i = 0; $i < 100; $i++) {
            TrainingAttendance::create([
                'candidate_id' => $this->candidate->id,
                'batch_id' => $this->batch->id,
                'date' => now()->subDays($i),
                'status' => $i < 85 ? 'present' : 'absent',
            ]);
        }

        TrainingAssessment::create([
            'candidate_id' => $this->candidate->id,
            'batch_id' => $this->batch->id,
            'assessment_type' => 'final',
            'subject' => 'Final Exam',
            'score' => 75,
            'result' => 'pass',
            'assessed_at' => now(),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/candidates/{$this->candidate->id}")
                    ->click('@complete-training-btn')
                    ->waitFor('@confirm-modal')
                    ->press('Confirm Completion')
                    ->waitForText('Training completed')
                    ->assertSee('Status: Visa Process');
        });
    }

    /** @test */
    public function it_shows_batch_performance_summary()
    {
        // Add multiple candidates with training data
        $candidates = Candidate::factory()->count(5)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $this->batch->id,
            'trade_id' => $this->trade->id,
        ]);

        foreach ($candidates as $candidate) {
            for ($i = 0; $i < 10; $i++) {
                TrainingAttendance::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $this->batch->id,
                    'date' => now()->subDays($i),
                    'status' => 'present',
                ]);
            }

            TrainingAssessment::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $this->batch->id,
                'assessment_type' => 'final',
                'subject' => 'Final',
                'score' => rand(60, 100),
                'result' => 'pass',
                'assessed_at' => now(),
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                    ->visit("/training/batches/{$this->batch->id}/performance")
                    ->assertSee('Batch Performance')
                    ->assertSee('Average Attendance')
                    ->assertSee('Average Score')
                    ->assertSee('Pass Rate');
        });
    }

    /** @test */
    public function it_can_assign_candidates_to_batch()
    {
        $newCandidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
            'campus_id' => $this->campus->id,
        ]);

        $this->browse(function (Browser $browser) use ($newCandidate) {
            $browser->loginAs($this->admin)
                    ->visit("/training/batches/{$this->batch->id}")
                    ->click('@assign-candidates-btn')
                    ->waitFor('@candidate-selection-modal')
                    ->check("candidates[{$newCandidate->id}]")
                    ->press('Assign to Batch')
                    ->waitForText('Candidates assigned')
                    ->assertSee($newCandidate->name);
        });
    }

    /** @test */
    public function it_shows_batch_capacity_warning()
    {
        $fullBatch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 2,
        ]);

        Candidate::factory()->count(2)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $fullBatch->id,
            'trade_id' => $this->trade->id,
        ]);

        $this->browse(function (Browser $browser) use ($fullBatch) {
            $browser->loginAs($this->admin)
                    ->visit("/training/batches/{$fullBatch->id}")
                    ->assertSee('Batch Full')
                    ->assertSee('2/2 Candidates');
        });
    }
}
