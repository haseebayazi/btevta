<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Campus;
use App\Models\Candidate;
use App\Models\TrainingAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression coverage for the "Undefined array key 'present'" error that the
 * campus-admin dashboard threw when there were no training attendance records
 * for the current day.
 *
 * The campus-admin dashboard data is shaped by
 * DashboardController::getCampusAdminDashboardData(). That method is pure
 * Eloquent (SQLite-portable), so we invoke it directly rather than hitting the
 * full /dashboard route, which also runs MySQL-specific raw SQL in
 * getStatistics() that cannot execute on the SQLite test database.
 */
class CampusAdminDashboardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // getCampusAdminDashboardData() caches per campus id. With
        // RefreshDatabase the campus id can repeat across tests while the array
        // cache store persists for the process, so flush to avoid stale data.
        Cache::flush();
    }

    private function campusAdminData($campusId): array
    {
        $method = new ReflectionMethod(DashboardController::class, 'getCampusAdminDashboardData');
        $method->setAccessible(true);

        return $method->invoke(app(DashboardController::class), $campusId);
    }

    public function test_attendance_today_has_all_status_keys_when_no_records_exist(): void
    {
        $campus = Campus::factory()->create();

        $data = $this->campusAdminData($campus->id);

        // Every status key must always be present so the Blade view never hits
        // an undefined array key, regardless of whether any attendance exists.
        $this->assertArrayHasKey('present', $data['attendance_today']);
        $this->assertArrayHasKey('absent', $data['attendance_today']);
        $this->assertArrayHasKey('late', $data['attendance_today']);
        $this->assertArrayHasKey('leave', $data['attendance_today']);

        $this->assertSame(0, $data['attendance_today']['present']);
        $this->assertSame(0, $data['attendance_today']['absent']);
        $this->assertSame(0, $data['attendance_today']['late']);
        $this->assertSame(0, $data['attendance_today']['leave']);
    }

    public function test_attendance_today_counts_each_status_for_the_campus(): void
    {
        $campus = Campus::factory()->create();

        $this->makeAttendance($campus, 'present', 2);
        $this->makeAttendance($campus, 'absent', 1);
        $this->makeAttendance($campus, 'late', 3);
        $this->makeAttendance($campus, 'leave', 1);

        $data = $this->campusAdminData($campus->id);

        $this->assertSame(2, $data['attendance_today']['present']);
        $this->assertSame(1, $data['attendance_today']['absent']);
        $this->assertSame(3, $data['attendance_today']['late']);
        // The "leave" status is what the app records; the dashboard surfaces it
        // even though the box was previously (incorrectly) keyed as "excused".
        $this->assertSame(1, $data['attendance_today']['leave']);
    }

    public function test_attendance_today_ignores_other_campuses_and_other_days(): void
    {
        $campus = Campus::factory()->create();
        $otherCampus = Campus::factory()->create();

        // Counts for this campus, today.
        $this->makeAttendance($campus, 'present', 1);

        // Different campus today — must not be counted.
        $this->makeAttendance($otherCampus, 'present', 5);

        // Same campus but yesterday — must not be counted.
        $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);
        TrainingAttendance::factory()->create([
            'candidate_id' => $candidate->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'present',
        ]);

        $data = $this->campusAdminData($campus->id);

        $this->assertSame(1, $data['attendance_today']['present']);
    }

    private function makeAttendance(Campus $campus, string $status, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $candidate = Candidate::factory()->create(['campus_id' => $campus->id]);

            TrainingAttendance::factory()->create([
                'candidate_id' => $candidate->id,
                'date' => today()->toDateString(),
                'status' => $status,
            ]);
        }
    }
}
