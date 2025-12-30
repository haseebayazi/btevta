<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * PurgeOldData Command
 *
 * Implements data retention policy by purging old data:
 * - Soft-deleted records beyond retention period
 * - Old activity logs
 * - Expired sessions
 * - Old temporary files
 *
 * Compliance: Government data retention requirements
 */
class PurgeOldData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:purge
                            {--days=365 : Days to retain data (default: 365)}
                            {--type=all : Type to purge (all, logs, sessions, temp, deleted)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Purge old data according to retention policy (Government compliance)';

    /**
     * Retention periods by data type (in days)
     */
    private array $retentionPeriods = [
        'activity_logs' => 730,      // 2 years for audit trail
        'sessions' => 7,              // 1 week for expired sessions
        'temp_files' => 7,            // 1 week for temporary files
        'soft_deleted' => 365,        // 1 year for soft-deleted records
        'password_history' => 365,    // 1 year (or keep last 5 per user)
        'failed_jobs' => 30,          // 1 month for failed jobs
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  DATA RETENTION PURGE - Government Compliance');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($dryRun) {
            $this->warn('  DRY RUN MODE - No data will be deleted');
        }

        $this->newLine();
        $this->info("Retention period: {$days} days");
        $this->info("Purge type: {$type}");
        $this->newLine();

        // Confirmation unless forced
        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm('This will permanently delete old data. Continue?')) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $results = [];

        try {
            DB::beginTransaction();

            if ($type === 'all' || $type === 'logs') {
                $results['activity_logs'] = $this->purgeActivityLogs($dryRun);
            }

            if ($type === 'all' || $type === 'sessions') {
                $results['sessions'] = $this->purgeSessions($dryRun);
            }

            if ($type === 'all' || $type === 'temp') {
                $results['temp_files'] = $this->purgeTempFiles($dryRun);
            }

            if ($type === 'all' || $type === 'deleted') {
                $results['soft_deleted'] = $this->purgeSoftDeleted($days, $dryRun);
            }

            if ($type === 'all') {
                $results['failed_jobs'] = $this->purgeFailedJobs($dryRun);
                $results['password_history'] = $this->purgePasswordHistory($dryRun);
            }

            if (!$dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            // Display results
            $this->displayResults($results, $dryRun);

            // Log the purge operation
            Log::info('Data purge completed', [
                'type' => $type,
                'days' => $days,
                'dry_run' => $dryRun,
                'results' => $results,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("Purge failed: {$e->getMessage()}");
            Log::error('Data purge failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Purge old activity logs
     */
    private function purgeActivityLogs(bool $dryRun): array
    {
        $days = $this->retentionPeriods['activity_logs'];
        $cutoffDate = Carbon::now()->subDays($days);

        $count = DB::table('activity_log')
            ->where('created_at', '<', $cutoffDate)
            ->count();

        if (!$dryRun && $count > 0) {
            // Delete in chunks to avoid memory issues
            DB::table('activity_log')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        }

        return [
            'type' => 'Activity Logs',
            'retention_days' => $days,
            'cutoff_date' => $cutoffDate->toDateString(),
            'records_affected' => $count,
        ];
    }

    /**
     * Purge expired sessions
     */
    private function purgeSessions(bool $dryRun): array
    {
        $days = $this->retentionPeriods['sessions'];
        $cutoffDate = Carbon::now()->subDays($days);

        // Only if using database sessions
        if (config('session.driver') !== 'database') {
            return [
                'type' => 'Sessions',
                'skipped' => true,
                'reason' => 'Not using database sessions',
            ];
        }

        $count = DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->count();

        if (!$dryRun && $count > 0) {
            DB::table('sessions')
                ->where('last_activity', '<', $cutoffDate->timestamp)
                ->delete();
        }

        return [
            'type' => 'Expired Sessions',
            'retention_days' => $days,
            'records_affected' => $count,
        ];
    }

    /**
     * Purge temporary files
     */
    private function purgeTempFiles(bool $dryRun): array
    {
        $days = $this->retentionPeriods['temp_files'];
        $cutoffDate = Carbon::now()->subDays($days);

        $tempPaths = [
            storage_path('app/temp'),
            storage_path('framework/cache/data'),
        ];

        $deletedCount = 0;
        $freedSpace = 0;

        foreach ($tempPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffDate->timestamp) {
                    $freedSpace += filesize($file);
                    $deletedCount++;

                    if (!$dryRun) {
                        unlink($file);
                    }
                }
            }
        }

        return [
            'type' => 'Temporary Files',
            'retention_days' => $days,
            'files_deleted' => $deletedCount,
            'space_freed_mb' => round($freedSpace / 1024 / 1024, 2),
        ];
    }

    /**
     * Permanently delete soft-deleted records beyond retention
     */
    private function purgeSoftDeleted(int $days, bool $dryRun): array
    {
        $cutoffDate = Carbon::now()->subDays($days);

        $modelsWithSoftDeletes = [
            'users' => \App\Models\User::class,
            'candidates' => \App\Models\Candidate::class,
            'campuses' => \App\Models\Campus::class,
            'trades' => \App\Models\Trade::class,
            'batches' => \App\Models\Batch::class,
            'oeps' => \App\Models\Oep::class,
        ];

        $results = [];

        foreach ($modelsWithSoftDeletes as $name => $modelClass) {
            try {
                $count = $modelClass::onlyTrashed()
                    ->where('deleted_at', '<', $cutoffDate)
                    ->count();

                if (!$dryRun && $count > 0) {
                    $modelClass::onlyTrashed()
                        ->where('deleted_at', '<', $cutoffDate)
                        ->forceDelete();
                }

                $results[$name] = $count;
            } catch (\Exception $e) {
                $results[$name] = "Error: {$e->getMessage()}";
            }
        }

        return [
            'type' => 'Soft-Deleted Records',
            'retention_days' => $days,
            'cutoff_date' => $cutoffDate->toDateString(),
            'by_model' => $results,
            'total_affected' => collect($results)->filter(fn($v) => is_int($v))->sum(),
        ];
    }

    /**
     * Purge old failed jobs
     */
    private function purgeFailedJobs(bool $dryRun): array
    {
        $days = $this->retentionPeriods['failed_jobs'];
        $cutoffDate = Carbon::now()->subDays($days);

        try {
            $count = DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->count();

            if (!$dryRun && $count > 0) {
                DB::table('failed_jobs')
                    ->where('failed_at', '<', $cutoffDate)
                    ->delete();
            }

            return [
                'type' => 'Failed Jobs',
                'retention_days' => $days,
                'records_affected' => $count,
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'Failed Jobs',
                'skipped' => true,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prune old password history entries (keep last 5 per user)
     */
    private function purgePasswordHistory(bool $dryRun): array
    {
        $historyCount = config('password.history_count', 5);

        try {
            // Get users with more than historyCount password entries
            $usersToClean = DB::table('password_histories')
                ->select('user_id', DB::raw('COUNT(*) as count'))
                ->groupBy('user_id')
                ->having('count', '>', $historyCount)
                ->pluck('count', 'user_id');

            $totalDeleted = 0;

            foreach ($usersToClean as $userId => $count) {
                $excess = $count - $historyCount;

                if (!$dryRun) {
                    // Delete oldest entries beyond the limit
                    $idsToDelete = DB::table('password_histories')
                        ->where('user_id', $userId)
                        ->orderBy('created_at', 'asc')
                        ->limit($excess)
                        ->pluck('id');

                    DB::table('password_histories')
                        ->whereIn('id', $idsToDelete)
                        ->delete();
                }

                $totalDeleted += $excess;
            }

            return [
                'type' => 'Password History',
                'keep_per_user' => $historyCount,
                'users_cleaned' => count($usersToClean),
                'records_affected' => $totalDeleted,
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'Password History',
                'skipped' => true,
                'reason' => $e->getMessage(),
            ];
        }
    }

    /**
     * Display results in a table
     */
    private function displayResults(array $results, bool $dryRun): void
    {
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info($dryRun ? '  DRY RUN RESULTS' : '  PURGE RESULTS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach ($results as $key => $result) {
            $this->newLine();
            $this->info("📁 {$result['type']}");

            if (isset($result['skipped']) && $result['skipped']) {
                $this->warn("   Skipped: {$result['reason']}");
                continue;
            }

            if (isset($result['retention_days'])) {
                $this->line("   Retention: {$result['retention_days']} days");
            }

            if (isset($result['cutoff_date'])) {
                $this->line("   Cutoff: {$result['cutoff_date']}");
            }

            if (isset($result['records_affected'])) {
                $this->line("   Records: {$result['records_affected']}");
            }

            if (isset($result['files_deleted'])) {
                $this->line("   Files: {$result['files_deleted']}");
            }

            if (isset($result['space_freed_mb'])) {
                $this->line("   Space freed: {$result['space_freed_mb']} MB");
            }

            if (isset($result['by_model'])) {
                foreach ($result['by_model'] as $model => $count) {
                    $this->line("   - {$model}: {$count}");
                }
            }
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($dryRun) {
            $this->warn('No data was deleted (dry run mode)');
        } else {
            $this->info('Purge completed successfully');
        }
    }
}
