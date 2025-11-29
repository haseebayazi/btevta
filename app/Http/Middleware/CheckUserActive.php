<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * Ensures that only active users can access the application.
     * Logs out inactive users and redirects them to login with a message.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user is inactive
            if (!$user->is_active) {
                // Log the inactive user attempt
                Log::warning('Inactive user attempted to access application', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'route' => $request->route()?->getName(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Log the user out
                Auth::logout();

                // Invalidate the session
                $request->session()->invalidate();

                // Regenerate CSRF token
                $request->session()->regenerateToken();

                // Redirect to login with error message
                return redirect()
                    ->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact the administrator.');
            }
        }

        return $next($request);
    }
}
