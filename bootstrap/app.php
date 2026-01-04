<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\CheckUserActive;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        // AUDIT FIX (P3): View Composers for common dropdown data
        \App\Providers\ViewServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
        then: function () {
            // PERFORMANCE FIX: Explicit route model bindings
            // Allows controllers to receive models directly instead of IDs
            // NOTE: These bindings automatically handle soft-deleted models
            // Use ->withTrashed() in controllers if you need to include soft-deleted records
            \Illuminate\Support\Facades\Route::model('candidate', \App\Models\Candidate::class);
            \Illuminate\Support\Facades\Route::model('campus', \App\Models\Campus::class);
            \Illuminate\Support\Facades\Route::model('oep', \App\Models\Oep::class);
            \Illuminate\Support\Facades\Route::model('batch', \App\Models\Batch::class);
            \Illuminate\Support\Facades\Route::model('trade', \App\Models\Trade::class);
            \Illuminate\Support\Facades\Route::model('user', \App\Models\User::class);
            \Illuminate\Support\Facades\Route::model('complaint', \App\Models\Complaint::class);
            \Illuminate\Support\Facades\Route::model('document', \App\Models\DocumentArchive::class);
            \Illuminate\Support\Facades\Route::model('instructor', \App\Models\Instructor::class);
            \Illuminate\Support\Facades\Route::model('class', \App\Models\TrainingClass::class);
            \Illuminate\Support\Facades\Route::model('correspondence', \App\Models\Correspondence::class);

            // ADVANCED: Custom binding example for specialized lookups
            // Uncomment and customize if you need to bind by fields other than ID:
            /*
            \Illuminate\Support\Facades\Route::bind('candidate', function ($value) {
                // Example: Look up by BTEVTA ID instead of primary key
                return \App\Models\Candidate::where('btevta_id', $value)
                    ->firstOrFail();
            });
            */

            // SECURITY FIX: Global route parameter constraints
            // Ensures IDs are numeric, prevents injection attempts
            \Illuminate\Support\Facades\Route::pattern('id', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('candidate', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('campus', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('oep', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('batch', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('trade', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('user', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('complaint', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('document', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('instructor', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('class', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('correspondence', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('notification', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('assessment', '[0-9]+');
            \Illuminate\Support\Facades\Route::pattern('issue', '[0-9]+');
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'active' => CheckUserActive::class,
        ]);

        // SECURITY: Add active user check to web middleware group
        // Ensures deactivated users are logged out automatically
        $middleware->web(append: [
            CheckUserActive::class,
        ]);

        // PERFORMANCE: Define middleware groups for common patterns
        // These combine auth + role checks for routes outside the main auth group
        $middleware->group('admin', [
            'auth',
            'role:admin',
        ]);

        $middleware->group('staff', [
            'auth',
            'role:admin,staff',
        ]);

        // SECURITY FIX: Add default throttle limits
        // Prevents abuse and DoS attacks
        $middleware->throttleApi();  // 60 requests/minute for API
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling can be customized here
    })->create();