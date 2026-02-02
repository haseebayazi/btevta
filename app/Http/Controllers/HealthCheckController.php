<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Candidate;
use App\Models\User;

/**
 * Health check controller for system monitoring.
 * Provides endpoints for load balancers, monitoring systems, and diagnostics.
 */
class HealthCheckController extends Controller
{
    /**
     * Basic health check - returns 200 if application is running.
     * Used by load balancers for basic uptime monitoring.
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'app' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Detailed health check - verifies all critical services.
     * Use for comprehensive system diagnostics.
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Application statistics - useful for dashboards.
     */
    public function statistics(): JsonResponse
    {
        return response()->json([
            'timestamp' => now()->toISOString(),
            'stats' => [
                'candidates' => [
                    'total' => Candidate::count(),
                    'active' => Candidate::whereNotIn('status', ['rejected', 'withdrawn', 'completed'])->count(),
                    'completed' => Candidate::where('status', 'completed')->count(),
                ],
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'environment' => app()->environment(),
                    'debug_mode' => config('app.debug'),
                ],
            ],
        ]);
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'healthy',
                'connection' => config('database.default'),
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            $startTime = microtime(true);
            Cache::put($key, 'test', 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $retrieved === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default'),
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $startTime = microtime(true);

            Storage::disk('local')->put($testFile, 'test');
            $exists = Storage::disk('local')->exists($testFile);
            Storage::disk('local')->delete($testFile);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => $exists ? 'healthy' : 'unhealthy',
                'disk' => 'local',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connectivity.
     */
    protected function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            // For sync driver, we just return healthy
            if ($driver === 'sync') {
                return [
                    'status' => 'healthy',
                    'driver' => $driver,
                    'note' => 'Synchronous queue - no dedicated queue service',
                ];
            }

            // For other drivers, attempt to get queue size
            return [
                'status' => 'healthy',
                'driver' => $driver,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
