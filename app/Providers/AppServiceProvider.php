<?php

namespace App\Providers;

use App\Models\Departure;
use App\Models\PostDepartureDetail;
use App\Models\Training;
use App\Models\VisaProcess;
use App\Observers\ActivityLoggingObserver;
use App\Observers\CandidateStatusObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        // ── Module 10: Activity logging for key workflow models ──────────────
        Training::observe(ActivityLoggingObserver::class);
        VisaProcess::observe(ActivityLoggingObserver::class);
        Departure::observe(ActivityLoggingObserver::class);
        PostDepartureDetail::observe(ActivityLoggingObserver::class);
        // Note: Candidate status changes are handled by CandidateStatusObserver
        //       registered via the #[ObservedBy] attribute on the Candidate model.

        // Register policies
        Gate::policy(\App\Models\PreDepartureDocument::class, \App\Policies\PreDepartureDocumentPolicy::class);
        Gate::policy(\App\Models\CandidateLicense::class, \App\Policies\CandidateLicensePolicy::class);
        Gate::policy(\App\Models\PostDepartureDetail::class, \App\Policies\PostDepartureDetailPolicy::class);
        Gate::policy(\App\Models\CompanySwitchLog::class, \App\Policies\CompanySwitchLogPolicy::class);

        // Register event listeners
        Event::listen(
            \App\Events\TrainingCompleted::class,
            \App\Listeners\HandleTrainingCompleted::class
        );

        Event::listen(
            \App\Events\CandidateDeparted::class,
            \App\Listeners\HandleCandidateDeparted::class
        );

        // Define standard API rate limiter
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Define User::api rate limiter (used by routes)
        RateLimiter::for('App\Models\User::api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
