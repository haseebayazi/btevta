<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Complaint;
use Carbon\Carbon;

class CheckComplaintSLA extends Command
{
    protected $signature = 'app:check-complaint-sla';
    protected $description = 'Check complaints for SLA violations';

    public function handle()
    {
        $this->info('Checking complaint SLAs...');

        $overdueComplaints = Complaint::where('status', '!=', 'resolved')
            ->where('created_at', '<=', Carbon::now()->subHours(72))
            ->get();

        foreach ($overdueComplaints as $complaint) {
            $complaint->update(['is_overdue' => true]);
            $this->line('SLA Violated: ' . $complaint->id);
        }

        $this->info('SLA check completed! Found ' . count($overdueComplaints) . ' overdue complaints');
    }
}
