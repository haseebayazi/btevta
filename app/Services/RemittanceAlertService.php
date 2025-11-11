<?php

namespace App\Services;

use App\Models\Remittance;
use App\Models\RemittanceAlert;
use App\Models\Candidate;
use App\Models\Departure;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemittanceAlertService
{
    /**
     * Generate all remittance alerts
     */
    public function generateAllAlerts()
    {
        $alerts = [
            'missing_remittances' => $this->generateMissingRemittanceAlerts(),
            'missing_proofs' => $this->generateMissingProofAlerts(),
            'first_remittance_delay' => $this->generateFirstRemittanceDelayAlerts(),
            'low_frequency' => $this->generateLowFrequencyAlerts(),
            'unusual_amount' => $this->generateUnusualAmountAlerts(),
        ];

        return [
            'total_generated' => array_sum($alerts),
            'breakdown' => $alerts,
        ];
    }

    /**
     * Generate alerts for candidates who haven't sent remittances
     */
    public function generateMissingRemittanceAlerts()
    {
        $alertsCreated = 0;
        $daysThreshold = config('remittance.alert_thresholds.missing_remittance_days', 90);

        // Find deployed candidates who haven't sent remittances in the threshold period
        $candidates = Candidate::whereHas('departure', function($q) use ($daysThreshold) {
                $q->where('departure_date', '<=', now()->subDays($daysThreshold));
            })
            ->whereDoesntHave('remittances', function($q) use ($daysThreshold) {
                $q->where('transfer_date', '>=', now()->subDays($daysThreshold));
            })
            ->with('departure')
            ->get();

        foreach ($candidates as $candidate) {
            // Check if alert already exists and is not resolved
            $existingAlert = RemittanceAlert::where('candidate_id', $candidate->id)
                ->where('alert_type', 'missing_remittance')
                ->where('is_resolved', false)
                ->first();

            if (!$existingAlert) {
                $daysSinceDeparture = Carbon::parse($candidate->departure->departure_date)->diffInDays(now());

                RemittanceAlert::create([
                    'candidate_id' => $candidate->id,
                    'alert_type' => 'missing_remittance',
                    'severity' => $daysSinceDeparture > 180 ? 'critical' : 'warning',
                    'title' => 'No Recent Remittances',
                    'message' => "Candidate {$candidate->full_name} has not sent any remittances in the last {$daysThreshold} days. Deployed since {$candidate->departure->departure_date->format('M d, Y')} ({$daysSinceDeparture} days ago).",
                    'metadata' => [
                        'days_since_departure' => $daysSinceDeparture,
                        'last_remittance_date' => null,
                        'destination_country' => $candidate->departure->destination_country ?? null,
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Generate alerts for remittances without proof
     */
    public function generateMissingProofAlerts()
    {
        $alertsCreated = 0;
        $daysThreshold = config('remittance.alert_thresholds.proof_upload_days', 30);

        // Find remittances without proof that are older than threshold
        $remittances = Remittance::where('has_proof', false)
            ->where('transfer_date', '<=', now()->subDays($daysThreshold))
            ->with('candidate')
            ->get();

        foreach ($remittances as $remittance) {
            // Check if alert already exists and is not resolved
            $existingAlert = RemittanceAlert::where('remittance_id', $remittance->id)
                ->where('alert_type', 'missing_proof')
                ->where('is_resolved', false)
                ->first();

            if (!$existingAlert) {
                $daysOverdue = Carbon::parse($remittance->transfer_date)->diffInDays(now());

                RemittanceAlert::create([
                    'candidate_id' => $remittance->candidate_id,
                    'remittance_id' => $remittance->id,
                    'alert_type' => 'missing_proof',
                    'severity' => $daysOverdue > 60 ? 'critical' : 'warning',
                    'title' => 'Missing Proof of Transfer',
                    'message' => "Remittance {$remittance->transaction_reference} (PKR {$remittance->amount}) is missing proof documentation. Transfer date: {$remittance->transfer_date->format('M d, Y')} ({$daysOverdue} days ago).",
                    'metadata' => [
                        'transaction_reference' => $remittance->transaction_reference,
                        'amount' => $remittance->amount,
                        'transfer_date' => $remittance->transfer_date->format('Y-m-d'),
                        'days_overdue' => $daysOverdue,
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Generate alerts for delayed first remittances
     */
    public function generateFirstRemittanceDelayAlerts()
    {
        $alertsCreated = 0;
        $daysThreshold = config('remittance.alert_thresholds.first_remittance_days', 60);

        // Find candidates who haven't sent their first remittance
        $candidates = Candidate::whereHas('departure', function($q) use ($daysThreshold) {
                $q->where('departure_date', '<=', now()->subDays($daysThreshold));
            })
            ->whereDoesntHave('remittances')
            ->with('departure')
            ->get();

        foreach ($candidates as $candidate) {
            // Check if alert already exists and is not resolved
            $existingAlert = RemittanceAlert::where('candidate_id', $candidate->id)
                ->where('alert_type', 'first_remittance_delay')
                ->where('is_resolved', false)
                ->first();

            if (!$existingAlert) {
                $daysSinceDeparture = Carbon::parse($candidate->departure->departure_date)->diffInDays(now());

                RemittanceAlert::create([
                    'candidate_id' => $candidate->id,
                    'alert_type' => 'first_remittance_delay',
                    'severity' => $daysSinceDeparture > 90 ? 'critical' : 'warning',
                    'title' => 'Delayed First Remittance',
                    'message' => "Candidate {$candidate->full_name} has not sent their first remittance yet. Deployed on {$candidate->departure->departure_date->format('M d, Y')} ({$daysSinceDeparture} days ago).",
                    'metadata' => [
                        'days_since_departure' => $daysSinceDeparture,
                        'departure_date' => $candidate->departure->departure_date->format('Y-m-d'),
                        'destination_country' => $candidate->departure->destination_country ?? null,
                    ],
                ]);

                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Generate alerts for candidates with low remittance frequency
     */
    public function generateLowFrequencyAlerts()
    {
        $alertsCreated = 0;
        $monthsThreshold = 6; // Check candidates deployed for at least 6 months
        $minExpectedRemittances = 3; // Expect at least 3 remittances in 6 months

        $candidates = Candidate::whereHas('departure', function($q) use ($monthsThreshold) {
                $q->where('departure_date', '<=', now()->subMonths($monthsThreshold));
            })
            ->with(['departure', 'remittances'])
            ->get();

        foreach ($candidates as $candidate) {
            $remittanceCount = $candidate->remittances->count();

            if ($remittanceCount > 0 && $remittanceCount < $minExpectedRemittances) {
                // Check if alert already exists and is not resolved
                $existingAlert = RemittanceAlert::where('candidate_id', $candidate->id)
                    ->where('alert_type', 'low_frequency')
                    ->where('is_resolved', false)
                    ->first();

                if (!$existingAlert) {
                    $monthsSinceDeparture = Carbon::parse($candidate->departure->departure_date)->diffInMonths(now());

                    RemittanceAlert::create([
                        'candidate_id' => $candidate->id,
                        'alert_type' => 'low_frequency',
                        'severity' => 'info',
                        'title' => 'Low Remittance Frequency',
                        'message' => "Candidate {$candidate->full_name} has only sent {$remittanceCount} remittance(s) in {$monthsSinceDeparture} months since deployment.",
                        'metadata' => [
                            'remittance_count' => $remittanceCount,
                            'months_since_departure' => $monthsSinceDeparture,
                            'expected_minimum' => $minExpectedRemittances,
                        ],
                    ]);

                    $alertsCreated++;
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Generate alerts for unusual remittance amounts
     */
    public function generateUnusualAmountAlerts()
    {
        $alertsCreated = 0;

        // Get average remittance amount per candidate
        $candidates = Candidate::whereHas('remittances')
            ->with('remittances')
            ->get();

        foreach ($candidates as $candidate) {
            if ($candidate->remittances->count() < 3) {
                continue; // Need at least 3 remittances to detect unusual patterns
            }

            $amounts = $candidate->remittances->pluck('amount');
            $avgAmount = $amounts->avg();
            $stdDev = $this->calculateStandardDeviation($amounts->toArray());

            // Check recent remittances for unusual amounts (3 standard deviations from mean)
            $recentRemittances = $candidate->remittances()
                ->where('transfer_date', '>=', now()->subMonths(1))
                ->get();

            foreach ($recentRemittances as $remittance) {
                $deviation = abs($remittance->amount - $avgAmount);

                if ($deviation > (3 * $stdDev)) {
                    // Check if alert already exists and is not resolved
                    $existingAlert = RemittanceAlert::where('remittance_id', $remittance->id)
                        ->where('alert_type', 'unusual_amount')
                        ->where('is_resolved', false)
                        ->first();

                    if (!$existingAlert) {
                        $percentageDiff = round((($remittance->amount - $avgAmount) / $avgAmount) * 100, 2);

                        RemittanceAlert::create([
                            'candidate_id' => $candidate->id,
                            'remittance_id' => $remittance->id,
                            'alert_type' => 'unusual_amount',
                            'severity' => 'info',
                            'title' => 'Unusual Remittance Amount',
                            'message' => "Remittance {$remittance->transaction_reference} has an unusual amount of PKR {$remittance->amount} (typically PKR {$avgAmount}). This is {$percentageDiff}% " . ($percentageDiff > 0 ? 'higher' : 'lower') . " than average.",
                            'metadata' => [
                                'amount' => $remittance->amount,
                                'average_amount' => round($avgAmount, 2),
                                'percentage_difference' => $percentageDiff,
                                'standard_deviation' => round($stdDev, 2),
                            ],
                        ]);

                        $alertsCreated++;
                    }
                }
            }
        }

        return $alertsCreated;
    }

    /**
     * Get unresolved alerts count
     */
    public function getUnresolvedAlertsCount($candidateId = null)
    {
        $query = RemittanceAlert::where('is_resolved', false);

        if ($candidateId) {
            $query->where('candidate_id', $candidateId);
        }

        return $query->count();
    }

    /**
     * Get critical alerts count
     */
    public function getCriticalAlertsCount()
    {
        return RemittanceAlert::where('is_resolved', false)
            ->where('severity', 'critical')
            ->count();
    }

    /**
     * Mark old alerts as read
     */
    public function markOldAlertsAsRead($daysOld = 30)
    {
        return RemittanceAlert::where('is_read', false)
            ->where('created_at', '<=', now()->subDays($daysOld))
            ->update(['is_read' => true]);
    }

    /**
     * Auto-resolve alerts when conditions are met
     */
    public function autoResolveAlerts()
    {
        $resolved = 0;

        // Resolve missing remittance alerts when remittance is added
        $missingRemittanceAlerts = RemittanceAlert::where('alert_type', 'missing_remittance')
            ->where('is_resolved', false)
            ->get();

        foreach ($missingRemittanceAlerts as $alert) {
            $hasRecentRemittance = Remittance::where('candidate_id', $alert->candidate_id)
                ->where('transfer_date', '>=', now()->subDays(90))
                ->exists();

            if ($hasRecentRemittance) {
                $alert->resolve(null, 'Auto-resolved: Candidate has sent recent remittance');
                $resolved++;
            }
        }

        // Resolve missing proof alerts when proof is uploaded
        $missingProofAlerts = RemittanceAlert::where('alert_type', 'missing_proof')
            ->where('is_resolved', false)
            ->whereNotNull('remittance_id')
            ->get();

        foreach ($missingProofAlerts as $alert) {
            $remittance = Remittance::find($alert->remittance_id);
            if ($remittance && $remittance->has_proof) {
                $alert->resolve(null, 'Auto-resolved: Proof of transfer uploaded');
                $resolved++;
            }
        }

        // Resolve first remittance delay alerts when first remittance is sent
        $firstRemittanceAlerts = RemittanceAlert::where('alert_type', 'first_remittance_delay')
            ->where('is_resolved', false)
            ->get();

        foreach ($firstRemittanceAlerts as $alert) {
            $hasRemittance = Remittance::where('candidate_id', $alert->candidate_id)->exists();
            if ($hasRemittance) {
                $alert->resolve(null, 'Auto-resolved: First remittance recorded');
                $resolved++;
            }
        }

        return $resolved;
    }

    /**
     * Calculate standard deviation
     */
    protected function calculateStandardDeviation($values)
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics()
    {
        return [
            'total_alerts' => RemittanceAlert::count(),
            'unresolved_alerts' => RemittanceAlert::where('is_resolved', false)->count(),
            'critical_alerts' => RemittanceAlert::where('severity', 'critical')->where('is_resolved', false)->count(),
            'unread_alerts' => RemittanceAlert::where('is_read', false)->count(),
            'by_type' => RemittanceAlert::select('alert_type', DB::raw('count(*) as count'))
                ->where('is_resolved', false)
                ->groupBy('alert_type')
                ->get()
                ->pluck('count', 'alert_type')
                ->toArray(),
            'by_severity' => RemittanceAlert::select('severity', DB::raw('count(*) as count'))
                ->where('is_resolved', false)
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity')
                ->toArray(),
        ];
    }
}
