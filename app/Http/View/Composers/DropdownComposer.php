<?php

namespace App\Http\View\Composers;

use App\Models\Batch;
use App\Models\Campus;
use App\Models\Oep;
use App\Models\Trade;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * View Composer for common dropdown data.
 *
 * AUDIT FIX (P3): Centralizes dropdown data that was previously
 * duplicated across multiple controllers. Uses caching to reduce
 * database queries and improve performance.
 */
class DropdownComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // Only add data if not already present (allows controller override)
        $data = $view->getData();

        if (!isset($data['campuses'])) {
            $view->with('campuses', $this->getCampuses());
        }

        if (!isset($data['trades'])) {
            $view->with('trades', $this->getTrades());
        }

        if (!isset($data['oeps'])) {
            $view->with('oeps', $this->getOeps());
        }

        if (!isset($data['activeBatches'])) {
            $view->with('activeBatches', $this->getActiveBatches());
        }

        // Add status options from centralized config
        if (!isset($data['statusOptions'])) {
            $view->with('statusOptions', config('statuses'));
        }
    }

    /**
     * Get cached list of active campuses.
     */
    protected function getCampuses(): array
    {
        return Cache::remember('dropdown_campuses', 300, function () {
            return Campus::where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get cached list of active trades.
     */
    protected function getTrades(): array
    {
        return Cache::remember('dropdown_trades', 300, function () {
            return Trade::where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get cached list of active OEPs.
     */
    protected function getOeps(): array
    {
        return Cache::remember('dropdown_oeps', 300, function () {
            return Oep::where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get cached list of active/planned batches.
     */
    protected function getActiveBatches(): array
    {
        return Cache::remember('dropdown_batches', 60, function () {
            return Batch::whereIn('status', ['planned', 'active'])
                ->orderBy('batch_code')
                ->get()
                ->mapWithKeys(function ($batch) {
                    return [$batch->id => $batch->batch_code . ' - ' . ($batch->trade->name ?? 'N/A')];
                })
                ->toArray();
        });
    }

    /**
     * Clear the dropdown cache (call when entities are updated).
     */
    public static function clearCache(): void
    {
        Cache::forget('dropdown_campuses');
        Cache::forget('dropdown_trades');
        Cache::forget('dropdown_oeps');
        Cache::forget('dropdown_batches');
    }
}
