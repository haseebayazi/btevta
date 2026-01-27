<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            try {
                $req = request();
                $user = $req->user();

                // Exclude sensitive inputs
                $input = $req->except($this->dontFlash);

                \Log::error('Unhandled exception', [
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'user_id' => $user?->id,
                    'url' => $req->fullUrl(),
                    'method' => $req->method(),
                    'input' => $input,
                    'trace' => \substr($e->getTraceAsString(), 0, 2000),
                ]);
            } catch (\Throwable $logException) {
                // Avoid throwing from the exception handler
                \Log::error('Failed to record structured exception log: ' . $logException->getMessage());
            }
        });
    }
}
