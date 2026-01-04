<?php

namespace App\Providers;

use App\Http\View\Composers\DropdownComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * View Service Provider for registering View Composers.
 *
 * AUDIT FIX (P3): Implements View Composers for common dropdown data
 * to reduce code duplication across controllers and ensure consistent
 * data availability in views.
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register dropdown composer for forms that need common dropdown data
        // These are the views that commonly need campuses, trades, OEPs, batches
        View::composer([
            'candidates.create',
            'candidates.edit',
            'batches.create',
            'batches.edit',
            'training.classes.create',
            'training.classes.edit',
            'screening.create',
            'screening.edit',
            'registration.create',
            'registration.edit',
            'instructors.create',
            'instructors.edit',
        ], DropdownComposer::class);
    }
}
