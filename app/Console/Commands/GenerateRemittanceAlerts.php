<?php

namespace App\Console\Commands;

use App\Services\RemittanceAlertService;
use Illuminate\Console\Command;

class GenerateRemittanceAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remittance:generate-alerts
                            {--auto-resolve : Auto-resolve alerts where conditions are met}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate remittance alerts for missing remittances, proofs, and unusual patterns';

    protected $alertService;

    /**
     * Create a new command instance.
     */
    public function __construct(RemittanceAlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating remittance alerts...');
        $this->newLine();

        // Generate all alerts
        $result = $this->alertService->generateAllAlerts();

        // Display results
        $this->info("Alert Generation Complete:");
        $this->line("- Missing Remittances: {$result['breakdown']['missing_remittances']}");
        $this->line("- Missing Proofs: {$result['breakdown']['missing_proofs']}");
        $this->line("- First Remittance Delays: {$result['breakdown']['first_remittance_delay']}");
        $this->line("- Low Frequency: {$result['breakdown']['low_frequency']}");
        $this->line("- Unusual Amounts: {$result['breakdown']['unusual_amount']}");
        $this->newLine();
        $this->info("Total Alerts Generated: {$result['total_generated']}");

        // Auto-resolve if option is set
        if ($this->option('auto-resolve')) {
            $this->newLine();
            $this->info('Running auto-resolution...');
            $resolved = $this->alertService->autoResolveAlerts();
            $this->info("Auto-resolved {$resolved} alerts");
        }

        // Display current statistics
        $this->newLine();
        $stats = $this->alertService->getAlertStatistics();
        $this->info('Current Alert Statistics:');
        $this->line("- Total Unresolved: {$stats['unresolved_alerts']}");
        $this->line("- Critical: {$stats['critical_alerts']}");
        $this->line("- Unread: {$stats['unread_alerts']}");

        return Command::SUCCESS;
    }
}
