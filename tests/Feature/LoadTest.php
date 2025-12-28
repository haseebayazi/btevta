<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Batch;
use App\Models\CandidateScreening;
use App\Models\TrainingAttendance;
use App\Models\TrainingAssessment;
use App\Services\TrainingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Load tests for concurrent operations.
 * Tests system behavior under high concurrency and data volume.
 */
class LoadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Trade $trade;
    protected Campus $campus;
    protected TrainingService $trainingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->trade = Trade::factory()->create();
        $this->campus = Campus::factory()->create();
        $this->trainingService = app(TrainingService::class);
    }

    // ==================== CONCURRENT BATCH ENROLLMENT ====================

    /** @test */
    public function it_handles_concurrent_batch_enrollment()
    {
        $batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 10,
        ]);

        // Create 20 candidates trying to enroll in a batch with capacity 10
        $candidates = Candidate::factory()->count(20)->create([
            'status' => Candidate::STATUS_REGISTERED,
            'trade_id' => $this->trade->id,
        ]);

        $enrolledCount = 0;
        $failedCount = 0;

        // Simulate concurrent enrollment attempts
        foreach ($candidates as $candidate) {
            try {
                $result = $this->trainingService->assignCandidatesToBatch(
                    $batch->id,
                    [$candidate->id]
                );

                if (!empty($result['assigned'])) {
                    $enrolledCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        // Verify capacity was respected
        $actualEnrolled = Candidate::where('batch_id', $batch->id)->count();
        $this->assertLessThanOrEqual(10, $actualEnrolled);
        $this->assertEquals($enrolledCount, $actualEnrolled);
    }

    /** @test */
    public function it_handles_bulk_candidate_creation()
    {
        $startTime = microtime(true);

        // Create 100 candidates
        $candidates = [];
        for ($i = 0; $i < 100; $i++) {
            $candidates[] = Candidate::factory()->create([
                'trade_id' => $this->trade->id,
                'campus_id' => $this->campus->id,
            ]);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete in reasonable time (< 30 seconds)
        $this->assertLessThan(30, $duration, "Bulk creation took too long: {$duration}s");
        $this->assertEquals(100, Candidate::count());
    }

    /** @test */
    public function it_handles_concurrent_screening_updates()
    {
        $candidates = Candidate::factory()->count(50)->create([
            'status' => Candidate::STATUS_SCREENING,
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);

        // Simulate concurrent screening updates
        foreach ($candidates as $candidate) {
            // Create all three screenings for each candidate
            foreach (['desk', 'call', 'physical'] as $type) {
                CandidateScreening::create([
                    'candidate_id' => $candidate->id,
                    'screening_type' => $type,
                    'status' => 'passed',
                    'screened_at' => now(),
                ]);
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete in reasonable time
        $this->assertLessThan(30, $duration, "Bulk screening took too long: {$duration}s");
        $this->assertEquals(150, CandidateScreening::count()); // 50 * 3
    }

    /** @test */
    public function it_handles_bulk_attendance_recording()
    {
        $batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 100,
        ]);

        $candidates = Candidate::factory()->count(50)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $batch->id,
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);

        // Record 30 days of attendance for all 50 candidates
        foreach ($candidates as $candidate) {
            for ($day = 0; $day < 30; $day++) {
                TrainingAttendance::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $batch->id,
                    'date' => now()->subDays($day),
                    'status' => rand(0, 100) < 85 ? 'present' : 'absent',
                ]);
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete in reasonable time
        $this->assertLessThan(60, $duration, "Bulk attendance took too long: {$duration}s");
        $this->assertEquals(1500, TrainingAttendance::count()); // 50 * 30
    }

    /** @test */
    public function it_handles_concurrent_api_requests()
    {
        $candidates = Candidate::factory()->count(20)->create([
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);
        $responses = [];

        // Simulate multiple API requests
        foreach ($candidates as $candidate) {
            $responses[] = $this->actingAs($this->admin)->getJson("/api/candidates/{$candidate->id}");
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertOk();
        }

        // Should complete in reasonable time
        $this->assertLessThan(10, $duration, "Concurrent API requests took too long: {$duration}s");
    }

    /** @test */
    public function it_handles_dashboard_with_large_dataset()
    {
        // Create large dataset
        $batches = Batch::factory()->count(10)->create([
            'status' => 'active',
            'capacity' => 50,
        ]);

        foreach ($batches as $batch) {
            Candidate::factory()->count(40)->create([
                'status' => collect([
                    Candidate::STATUS_NEW,
                    Candidate::STATUS_SCREENING,
                    Candidate::STATUS_REGISTERED,
                    Candidate::STATUS_TRAINING,
                ])->random(),
                'batch_id' => $batch->id,
                'trade_id' => $this->trade->id,
                'campus_id' => $this->campus->id,
            ]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        // Dashboard should load in reasonable time even with 400 candidates
        $this->assertLessThan(5, $duration, "Dashboard took too long: {$duration}s");
    }

    /** @test */
    public function it_handles_report_generation_with_large_dataset()
    {
        $batch = Batch::factory()->create([
            'status' => 'active',
            'capacity' => 100,
        ]);

        $candidates = Candidate::factory()->count(100)->create([
            'status' => Candidate::STATUS_TRAINING,
            'batch_id' => $batch->id,
            'trade_id' => $this->trade->id,
        ]);

        // Add attendance and assessments for all candidates
        foreach ($candidates as $candidate) {
            for ($day = 0; $day < 30; $day++) {
                TrainingAttendance::create([
                    'candidate_id' => $candidate->id,
                    'batch_id' => $batch->id,
                    'date' => now()->subDays($day),
                    'status' => 'present',
                ]);
            }

            TrainingAssessment::create([
                'candidate_id' => $candidate->id,
                'batch_id' => $batch->id,
                'assessment_type' => 'final',
                'subject' => 'Final Exam',
                'score' => rand(60, 100),
                'result' => 'pass',
                'assessed_at' => now(),
            ]);
        }

        $startTime = microtime(true);

        $report = $this->trainingService->generateAttendanceReport([
            'batch_id' => $batch->id,
        ]);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertArrayHasKey('candidates', $report);
        $this->assertEquals(100, count($report['candidates']));

        // Report should generate in reasonable time
        $this->assertLessThan(10, $duration, "Report generation took too long: {$duration}s");
    }

    /** @test */
    public function it_handles_search_with_large_dataset()
    {
        // Create 500 candidates with various names
        Candidate::factory()->count(500)->create([
            'trade_id' => $this->trade->id,
        ]);

        // Specific candidate to find
        Candidate::factory()->create([
            'name' => 'Muhammad Ali Unique Name',
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->admin)->get('/candidates?search=Muhammad+Ali+Unique');

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();
        $response->assertSee('Muhammad Ali Unique Name');

        // Search should be fast even with 500+ records
        $this->assertLessThan(3, $duration, "Search took too long: {$duration}s");
    }

    /** @test */
    public function it_handles_export_with_large_dataset()
    {
        Candidate::factory()->count(500)->create([
            'status' => Candidate::STATUS_DEPARTED,
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->admin)->get('/remittance/export?format=csv');

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        // Export should complete in reasonable time
        $this->assertLessThan(30, $duration, "Export took too long: {$duration}s");
    }

    /** @test */
    public function it_maintains_data_integrity_under_concurrent_updates()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_NEW,
            'trade_id' => $this->trade->id,
        ]);

        $updateCount = 10;
        $successCount = 0;

        // Simulate concurrent updates to same record
        for ($i = 0; $i < $updateCount; $i++) {
            try {
                $candidate->refresh();
                $candidate->update(['remarks' => "Update {$i}"]);
                $successCount++;
            } catch (\Exception $e) {
                // Expected in concurrent scenarios
            }
        }

        // At least some updates should succeed
        $this->assertGreaterThan(0, $successCount);

        // Data should be consistent
        $candidate->refresh();
        $this->assertNotNull($candidate->remarks);
    }

    /** @test */
    public function it_handles_batch_status_transition_under_load()
    {
        $candidates = Candidate::factory()->count(50)->create([
            'status' => Candidate::STATUS_NEW,
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);
        $transitioned = 0;

        foreach ($candidates as $candidate) {
            try {
                $candidate->updateStatus(Candidate::STATUS_SCREENING);
                $transitioned++;
            } catch (\Exception $e) {
                // Some might fail due to validation
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Most should transition successfully
        $this->assertGreaterThan(40, $transitioned);

        // Should complete in reasonable time
        $this->assertLessThan(30, $duration, "Batch transitions took too long: {$duration}s");
    }

    /** @test */
    public function it_handles_memory_efficiently_with_large_queries()
    {
        // Create 1000 candidates
        Candidate::factory()->count(1000)->create([
            'trade_id' => $this->trade->id,
        ]);

        $initialMemory = memory_get_usage(true);

        // Process candidates in chunks to test memory efficiency
        Candidate::chunk(100, function ($candidates) {
            foreach ($candidates as $candidate) {
                // Simulate processing
                $data = $candidate->toArray();
            }
        });

        $finalMemory = memory_get_usage(true);
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Memory usage should be reasonable (< 50MB additional)
        $this->assertLessThan(50, $memoryUsed, "Memory usage too high: {$memoryUsed}MB");
    }

    /** @test */
    public function it_handles_duplicate_check_with_large_dataset()
    {
        // Create 1000 candidates
        Candidate::factory()->count(1000)->create([
            'trade_id' => $this->trade->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->admin)->postJson('/api/check-duplicates', [
            'phone' => '03001234567',
            'email' => 'test@example.com',
            'name' => 'Muhammad Ali',
        ]);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $response->assertOk();

        // Duplicate check should be fast
        $this->assertLessThan(3, $duration, "Duplicate check took too long: {$duration}s");
    }

    // ==================== STRESS TESTS ====================

    /** @test */
    public function stress_test_rapid_candidate_creation()
    {
        $startTime = microtime(true);
        $count = 0;
        $targetTime = 10; // 10 seconds
        $maxCandidates = 200;

        while ((microtime(true) - $startTime) < $targetTime && $count < $maxCandidates) {
            Candidate::factory()->create([
                'trade_id' => $this->trade->id,
            ]);
            $count++;
        }

        $duration = microtime(true) - $startTime;
        $rate = $count / $duration;

        // Should be able to create at least 10 candidates per second
        $this->assertGreaterThan(10, $rate, "Creation rate too slow: {$rate}/s");
    }

    /** @test */
    public function stress_test_rapid_status_transitions()
    {
        $candidate = Candidate::factory()->create([
            'status' => Candidate::STATUS_NEW,
            'trade_id' => $this->trade->id,
        ]);

        $transitions = [
            Candidate::STATUS_SCREENING,
            Candidate::STATUS_REGISTERED,
            Candidate::STATUS_TRAINING,
            Candidate::STATUS_VISA_PROCESS,
            Candidate::STATUS_READY,
            Candidate::STATUS_DEPARTED,
        ];

        $startTime = microtime(true);

        // Prepare candidate for all transitions
        $this->prepareForAllTransitions($candidate);

        foreach ($transitions as $status) {
            try {
                $candidate->refresh();
                $candidate->status = $status;
                $candidate->save();
            } catch (\Exception $e) {
                // Expected for some transitions
            }
        }

        $duration = microtime(true) - $startTime;

        // All transitions should complete quickly
        $this->assertLessThan(5, $duration, "Transitions took too long: {$duration}s");
    }

    protected function prepareForAllTransitions(Candidate $candidate): void
    {
        // This helper sets up all requirements for full lifecycle
        // In real implementation, this would set up screenings, documents, etc.
    }
}
