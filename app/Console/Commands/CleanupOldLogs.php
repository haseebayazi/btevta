<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\ActivityLog\Models\Activity;
use Carbon\Carbon;

class CleanupOldLogs extends Command
{
    protected $signature = 'app:cleanup-old-logs';
    protected $description = 'Clean up activity logs older than 90 days';

    public function handle()
    {
        $this->info('Cleaning up old activity logs...');

        $deleted = Activity::where('created_at', '<', Carbon::now()->subDays(90))->delete();

        $this->info('Deleted ' . $deleted . ' old activity logs');
    }
}
