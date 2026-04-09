<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\User;
use App\Enums\CandidateStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPipelineDailySummary extends Command
{
    protected $signature = 'pipeline:send-daily-summary';

    protected $description = 'Send a daily pipeline summary email to all admin users';

    public function handle(): int
    {
        $summary = $this->buildSummary();

        $admins = User::whereIn('role', ['super_admin', 'admin'])
            ->whereNotNull('email')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found to notify.');
            return self::SUCCESS;
        }

        foreach ($admins as $admin) {
            try {
                Mail::send(
                    'emails.pipeline-summary',
                    ['summary' => $summary, 'user' => $admin],
                    function ($m) use ($admin, $summary) {
                        $m->to($admin->email, $admin->name)
                          ->subject('Daily Pipeline Summary - ' . now()->format('d M Y') . ' (' . $summary['total'] . ' active)');
                    }
                );
            } catch (\Throwable $e) {
                $this->error("Failed to send summary to {$admin->email}: {$e->getMessage()}");
            }
        }

        $this->info("Pipeline summary sent to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }

    private function buildSummary(): array
    {
        $counts = Candidate::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $active = array_sum(
            array_filter(
                $counts,
                fn($status) => !in_array($status, ['completed', 'rejected', 'withdrawn', 'deferred']),
                ARRAY_FILTER_USE_KEY
            )
        );

        // At-risk: stuck for 30+ days in a non-terminal, non-completed state
        $atRiskCount = Candidate::whereNotIn('status', ['completed', 'rejected', 'withdrawn', 'deferred', 'departed', 'post_departure'])
            ->where('updated_at', '<', now()->subDays(30))
            ->count();

        return [
            'date'          => now()->format('d M Y'),
            'total'         => $active,
            'by_status'     => $counts,
            'at_risk_count' => $atRiskCount,
            'completed_today' => Candidate::where('status', 'completed')
                ->whereDate('updated_at', today())
                ->count(),
        ];
    }
}
