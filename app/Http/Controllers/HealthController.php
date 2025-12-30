<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * HealthController
 *
 * Provides health check endpoints for monitoring and load balancers.
 * Used by infrastructure to determine application availability.
 */
class HealthController extends Controller
{
    /**
     * Basic health check - returns 200 if application is running.
     *
     * Used by load balancers and simple monitoring.
     *
     * @return JsonResponse
     */
    public function check(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Detailed health check - checks all system components.
     *
     * Returns detailed status of each component for monitoring dashboards.
     * Requires authentication (admin only) due to sensitive information.
     *
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        $response = [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.3.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
            'metrics' => $this->getMetrics(),
        ];

        $statusCode = $allHealthy ? 200 : 503;

        // Log degraded status for alerting
        if (!$allHealthy) {
            Log::warning('Health check degraded', $response);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Check database connectivity and performance.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            // Get connection info (without sensitive data)
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection' => $connection,
                'database' => $database,
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'error' => 'Connection failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Database unavailable',
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_' . time();

            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved !== $testValue) {
                throw new \Exception('Cache read/write mismatch');
            }

            return [
                'status' => 'healthy',
                'driver' => config('cache.default'),
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            Log::error('Cache health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => config('app.debug') ? $e->getMessage() : 'Cache unavailable',
            ];
        }
    }

    /**
     * Check storage availability and writability.
     */
    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_' . uniqid() . '.txt';
            $testContent = 'Health check at ' . now()->toISOString();

            // Write test
            $disk->put($testFile, $testContent);

            // Read test
            $readContent = $disk->get($testFile);

            // Delete test file
            $disk->delete($testFile);

            if ($readContent !== $testContent) {
                throw new \Exception('Storage read/write mismatch');
            }

            // Get disk space info
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);

            return [
                'status' => 'healthy',
                'disk' => 'local',
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_percent' => $usedPercent,
                'warning' => $usedPercent > 85 ? 'Disk space running low' : null,
            ];
        } catch (\Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'error' => config('app.debug') ? $e->getMessage() : 'Storage unavailable',
            ];
        }
    }

    /**
     * Check queue connectivity (if using database/redis queue).
     */
    private function checkQueue(): array
    {
        $driver = config('queue.default');

        // For sync driver, always healthy
        if ($driver === 'sync') {
            return [
                'status' => 'healthy',
                'driver' => 'sync',
                'note' => 'Synchronous processing (no worker needed)',
            ];
        }

        try {
            if ($driver === 'database') {
                // Check if jobs table exists and is accessible
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                return [
                    'status' => 'healthy',
                    'driver' => 'database',
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'warning' => $failedJobs > 10 ? 'Multiple failed jobs detected' : null,
                ];
            }

            if ($driver === 'redis') {
                // Basic Redis connectivity check
                $redis = app('redis');
                $redis->ping();

                return [
                    'status' => 'healthy',
                    'driver' => 'redis',
                ];
            }

            return [
                'status' => 'healthy',
                'driver' => $driver,
            ];
        } catch (\Exception $e) {
            Log::error('Queue health check failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'unhealthy',
                'driver' => $driver,
                'error' => config('app.debug') ? $e->getMessage() : 'Queue unavailable',
            ];
        }
    }

    /**
     * Get application metrics for monitoring.
     */
    private function getMetrics(): array
    {
        try {
            return [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'uptime' => $this->getUptime(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Could not retrieve metrics',
            ];
        }
    }

    /**
     * Get system uptime (if available).
     */
    private function getUptime(): ?string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime) {
                $seconds = (int) explode(' ', $uptime)[0];
                $days = floor($seconds / 86400);
                $hours = floor(($seconds % 86400) / 3600);
                $minutes = floor(($seconds % 3600) / 60);

                return "{$days}d {$hours}h {$minutes}m";
            }
        }

        return null;
    }
}
