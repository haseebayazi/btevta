<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // PERFORMANCE FIX: Explicit route model bindings
            // Allows controllers to receive models directly instead of IDs
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
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // SECURITY FIX: Add default throttle limits
        // Prevents abuse and DoS attacks
        $middleware->throttleApi();  // 60 requests/minute for API
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Exception handling can be customized here
    })->create();