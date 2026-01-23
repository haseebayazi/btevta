<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\Batch;
use App\Models\TrainingAssessment;
use App\Models\TrainingAttendance;
use App\Models\TrainingCertificate;
use App\Models\TrainingSchedule;
use App\Policies\TrainingAssessmentPolicy;
use App\Policies\TrainingAttendancePolicy;
use App\Policies\TrainingCertificatePolicy;
use App\Policies\TrainingSchedulePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrainingPoliciesTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // TRAINING ASSESSMENT POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_assessment()
    {
        $policy = new TrainingAssessmentPolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function campus_admin_can_view_assessments_from_their_campus()
    {
        $policy = new TrainingAssessmentPolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $assessment = TrainingAssessment::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($policy->view($user, $assessment));
    }

    #[Test]
    public function instructor_can_create_assessments()
    {
        $policy = new TrainingAssessmentPolicy();
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function instructor_can_update_their_own_assessments()
    {
        $policy = new TrainingAssessmentPolicy();
        $user = User::factory()->create(['role' => 'instructor']);
        $assessment = TrainingAssessment::factory()->create(['trainer_id' => $user->id]);

        $this->assertTrue($policy->update($user, $assessment));
    }

    #[Test]
    public function instructor_cannot_update_other_instructors_assessments()
    {
        $policy = new TrainingAssessmentPolicy();
        $user = User::factory()->create(['role' => 'instructor']);
        $otherUser = User::factory()->create(['role' => 'instructor']);
        $assessment = TrainingAssessment::factory()->create(['trainer_id' => $otherUser->id]);

        $this->assertFalse($policy->update($user, $assessment));
    }

    #[Test]
    public function only_super_admin_can_delete_assessments()
    {
        $policy = new TrainingAssessmentPolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $assessment = TrainingAssessment::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $assessment));
        $this->assertFalse($policy->delete($campusAdmin, $assessment));
    }

    // =========================================================================
    // TRAINING ATTENDANCE POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_view_any_attendance()
    {
        $policy = new TrainingAttendancePolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function instructor_can_record_attendance()
    {
        $policy = new TrainingAttendancePolicy();
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function instructor_can_bulk_record_attendance()
    {
        $policy = new TrainingAttendancePolicy();
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($policy->bulkRecord($user));
    }

    #[Test]
    public function campus_admin_can_view_attendance_from_their_campus()
    {
        $policy = new TrainingAttendancePolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        $attendance = TrainingAttendance::factory()->create(['candidate_id' => $candidate->id]);

        $this->assertTrue($policy->view($user, $attendance));
    }

    #[Test]
    public function viewer_cannot_create_attendance()
    {
        $policy = new TrainingAttendancePolicy();
        $user = User::factory()->create(['role' => 'viewer']);

        $this->assertFalse($policy->create($user));
    }

    // =========================================================================
    // TRAINING CERTIFICATE POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_issue_certificates()
    {
        $policy = new TrainingCertificatePolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function campus_admin_can_issue_certificates()
    {
        $policy = new TrainingCertificatePolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function only_super_admin_can_revoke_certificates()
    {
        $policy = new TrainingCertificatePolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $certificate = TrainingCertificate::factory()->create();

        $this->assertTrue($policy->revoke($superAdmin, $certificate));
        $this->assertFalse($policy->revoke($campusAdmin, $certificate));
    }

    #[Test]
    public function viewer_can_download_certificates()
    {
        $policy = new TrainingCertificatePolicy();
        $user = User::factory()->create(['role' => 'viewer']);
        $certificate = TrainingCertificate::factory()->create();

        $this->assertTrue($policy->download($user, $certificate));
    }

    // =========================================================================
    // TRAINING SCHEDULE POLICY
    // =========================================================================

    #[Test]
    public function super_admin_can_create_schedules()
    {
        $policy = new TrainingSchedulePolicy();
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function campus_admin_can_create_schedules()
    {
        $policy = new TrainingSchedulePolicy();
        $campus = Campus::factory()->create();
        $user = User::factory()->create([
            'role' => 'campus_admin',
            'campus_id' => $campus->id,
        ]);

        $this->assertTrue($policy->create($user));
    }

    #[Test]
    public function instructor_can_view_schedules()
    {
        $policy = new TrainingSchedulePolicy();
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertTrue($policy->viewAny($user));
    }

    #[Test]
    public function instructor_cannot_create_schedules()
    {
        $policy = new TrainingSchedulePolicy();
        $user = User::factory()->create(['role' => 'instructor']);

        $this->assertFalse($policy->create($user));
    }

    #[Test]
    public function only_super_admin_can_delete_schedules()
    {
        $policy = new TrainingSchedulePolicy();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $campusAdmin = User::factory()->create(['role' => 'campus_admin']);
        $schedule = TrainingSchedule::factory()->create();

        $this->assertTrue($policy->delete($superAdmin, $schedule));
        $this->assertFalse($policy->delete($campusAdmin, $schedule));
    }
}
