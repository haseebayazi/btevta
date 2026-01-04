<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\ActivityLog\Models\Activity;
use Carbon\Carbon;

/**
 * AUDIT FIX: Added environment protection and confirmation for production safety.
 */
class CleanupOldLogs extends Command
{
    protected $signature = 'app:cleanup-old-logs
                            {--days=90 : Days to retain logs (default: 90)}
                            {--force : Skip confirmation in production}';

    protected $description = 'Clean up activity logs older than specified days';

    public function handle()
    {
        $days = (int) $this->option('days');

        // AUDIT FIX: Production environment protection
        if (app()->environment('production')) {
            $this->warn('⚠️  WARNING: Running in PRODUCTION environment!');

            if (!$this->option('force')) {
                $count = Activity::where('created_at', '<', Carbon::now()->subDays($days))->count();
                $this->info("This will delete {$count} activity logs older than {$days} days.");

                if (!$this->confirm('Are you sure you want to proceed?')) {
                    $this->info('Operation cancelled.');
                    return Command::SUCCESS;
                }
            }
        }

        $this->info("Cleaning up activity logs older than {$days} days...");

        $deleted = Activity::where('created_at', '<', Carbon::now()->subDays($days))->delete();

        $this->info("✓ Deleted {$deleted} old activity logs");

        // Log the cleanup for audit purposes
        activity()
            ->causedBy(null)
            ->withProperties([
                'deleted_count' => $deleted,
                'retention_days' => $days,
                'environment' => app()->environment(),
            ])
            ->log('Activity logs cleanup executed');

        return Command::SUCCESS;
    }
}
