<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Remittance;
use App\Models\RemittanceAlert;
use App\Models\Batch;
use App\Models\Trade;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Departure;
use App\Models\VisaProcess;
use Illuminate\Support\Facades\Auth;

class GlobalSearchService
{
    /**
     * Search across all modules
     *
     * @param string $term Search term
     * @param array $types Optional array of types to search (default: all)
     * @param int $limit Max results per type
     * @return array Grouped results by type
     */
    public function search(string $term, array $types = [], int $limit = 50): array
    {
        $term = trim($term);

        if (empty($term)) {
            return [];
        }

        // Default to all types if none specified
        if (empty($types)) {
            $types = ['candidates', 'remittances', 'alerts', 'batches', 'trades', 'campuses', 'oeps', 'departures', 'visas'];
        }

        $results = [];
        $user = Auth::user();

        // Candidates
        if (in_array('candidates', $types)) {
            $query = Candidate::search($term)->with(['trade', 'campus']);

            // AUDIT FIX: Enhanced role-based filtering to include OEP users
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->role === 'oep' && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }

            $results['candidates'] = [
                'label' => 'Candidates',
                'icon' => 'fas fa-users',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->name,
                        'subtitle' => "BTEVTA ID: {$item->btevta_id} | CNIC: {$item->formatted_cnic}",
                        'url' => route('candidates.profile', $item->id),
                        'badge' => $item->status,
                        'badge_class' => $this->getStatusBadgeClass($item->status),
                    ];
                })->toArray()
            ];
        }

        // Remittances
        if (in_array('remittances', $types)) {
            // Escape special LIKE characters to prevent SQL LIKE injection
            $escapedTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
            $query = Remittance::with('candidate')
                ->where(function($q) use ($escapedTerm) {
                    $q->where('transaction_reference', 'like', "%{$escapedTerm}%")
                      ->orWhere('sender_name', 'like', "%{$escapedTerm}%")
                      ->orWhereHas('candidate', function($subQ) use ($escapedTerm) {
                          $subQ->where('name', 'like', "%{$escapedTerm}%")
                               ->orWhere('btevta_id', 'like', "%{$escapedTerm}%");
                      });
                });

            // AUDIT FIX: Enhanced role-based filtering to include OEP users
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            } elseif ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
            }

            $results['remittances'] = [
                'label' => 'Remittances',
                'icon' => 'fas fa-money-bill-wave',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->candidate->name ?? 'N/A',
                        'subtitle' => "PKR " . number_format($item->amount, 0) . " | " . $item->transfer_date->format('M d, Y'),
                        'url' => route('remittances.show', $item->id),
                        'badge' => $item->status,
                        'badge_class' => $item->status === 'verified' ? 'bg-success' : 'bg-warning',
                    ];
                })->toArray()
            ];
        }

        // Remittance Alerts
        if (in_array('alerts', $types)) {
            $query = RemittanceAlert::search($term)->with('candidate')->unresolved();

            // AUDIT FIX: Enhanced role-based filtering to include OEP users
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            } elseif ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
            }

            $results['alerts'] = [
                'label' => 'Remittance Alerts',
                'icon' => 'fas fa-exclamation-triangle',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'subtitle' => $item->candidate->name ?? 'N/A',
                        'url' => route('remittance.alerts.show', $item->id),
                        'badge' => $item->severity,
                        'badge_class' => $item->severity === 'critical' ? 'bg-danger' : 'bg-warning',
                    ];
                })->toArray()
            ];
        }

        // Batches
        if (in_array('batches', $types)) {
            $query = Batch::search($term)->with(['trade', 'campus', 'oep']);

            // AUDIT FIX: Enhanced role-based filtering to include OEP users
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->where('campus_id', $user->campus_id);
            } elseif ($user->role === 'oep' && $user->oep_id) {
                $query->where('oep_id', $user->oep_id);
            }

            $results['batches'] = [
                'label' => 'Batches',
                'icon' => 'fas fa-layer-group',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->name,
                        'subtitle' => "Code: {$item->batch_code} | " . ($item->trade->name ?? 'N/A'),
                        'url' => route('batches.show', $item->id),
                        'badge' => $item->status,
                        'badge_class' => $item->status === 'active' ? 'bg-success' : 'bg-secondary',
                    ];
                })->toArray()
            ];
        }

        // Trades
        if (in_array('trades', $types)) {
            $results['trades'] = [
                'label' => 'Trades',
                'icon' => 'fas fa-wrench',
                'items' => Trade::search($term)->active()->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->name,
                        'subtitle' => "Code: {$item->code} | Duration: {$item->duration_months} months",
                        'url' => route('trades.show', $item->id),
                        'badge' => null,
                        'badge_class' => null,
                    ];
                })->toArray()
            ];
        }

        // Campuses
        if (in_array('campuses', $types)) {
            $query = Campus::search($term)->active();

            // Campus admins can only see their own campus
            if ($user->role === 'campus_admin') {
                $query->where('id', $user->campus_id);
            }

            $results['campuses'] = [
                'label' => 'Campuses',
                'icon' => 'fas fa-building',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->name,
                        'subtitle' => "City: {$item->city} | Code: {$item->code}",
                        'url' => route('campuses.show', $item->id),
                        'badge' => null,
                        'badge_class' => null,
                    ];
                })->toArray()
            ];
        }

        // OEPs
        if (in_array('oeps', $types)) {
            $results['oeps'] = [
                'label' => 'OEPs',
                'icon' => 'fas fa-briefcase',
                'items' => Oep::search($term)->active()->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->name,
                        'subtitle' => "{$item->company_name} | {$item->country}",
                        'url' => route('oeps.show', $item->id),
                        'badge' => null,
                        'badge_class' => null,
                    ];
                })->toArray()
            ];
        }

        // Departures
        if (in_array('departures', $types)) {
            $query = Departure::search($term)->with('candidate');

            // AUDIT FIX: Enhanced role-based filtering to include OEP users
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            } elseif ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
            }

            $results['departures'] = [
                'label' => 'Departures',
                'icon' => 'fas fa-plane-departure',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->candidate->name ?? 'N/A',
                        'subtitle' => "Flight: {$item->flight_number} | " . $item->departure_date->format('M d, Y'),
                        'url' => route('departures.show', $item->id),
                        'badge' => null,
                        'badge_class' => null,
                    ];
                })->toArray()
            ];
        }

        // Visa Processes
        if (in_array('visas', $types)) {
            $query = VisaProcess::search($term)->with('candidate');

            // AUDIT FIX: Enhanced role-based filtering to include OEP users
            if ($user->role === 'campus_admin' && $user->campus_id) {
                $query->whereHas('candidate', fn($q) => $q->where('campus_id', $user->campus_id));
            } elseif ($user->role === 'oep' && $user->oep_id) {
                $query->whereHas('candidate', fn($q) => $q->where('oep_id', $user->oep_id));
            }

            $results['visas'] = [
                'label' => 'Visa Processes',
                'icon' => 'fas fa-passport',
                'items' => $query->limit($limit)->get()->map(function($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->candidate->name ?? 'N/A',
                        'subtitle' => "Status: " . ucfirst($item->overall_status),
                        'url' => route('visa.show', $item->id),
                        'badge' => $item->overall_status,
                        'badge_class' => $this->getVisaStatusBadgeClass($item->overall_status),
                    ];
                })->toArray()
            ];
        }

        // Filter out empty result sets
        $results = array_filter($results, function($result) {
            return !empty($result['items']);
        });

        return $results;
    }

    /**
     * Get badge class for candidate status
     */
    private function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'departed' => 'bg-success',
            'training' => 'bg-info',
            'visa_processing' => 'bg-warning',
            'registered' => 'bg-primary',
            'screening' => 'bg-secondary',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get badge class for visa status
     */
    private function getVisaStatusBadgeClass(string $status): string
    {
        return match($status) {
            'completed' => 'bg-success',
            'in_progress' => 'bg-info',
            'pending' => 'bg-warning',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get total count of search results across all types
     */
    public function getResultCount(array $results): int
    {
        $count = 0;
        foreach ($results as $type => $data) {
            $count += count($data['items']);
        }
        return $count;
    }
}
