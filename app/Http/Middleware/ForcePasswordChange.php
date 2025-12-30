<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForcePasswordChange Middleware
 *
 * Security middleware that enforces mandatory password changes.
 * Users with force_password_change=true are redirected to the
 * password change page and cannot access any other routes.
 *
 * Exceptions:
 * - Logout route (users can still log out)
 * - Password change routes (where they need to go)
 * - API requests return 403 with instructions
 *
 * @package App\Http\Middleware
 */
class ForcePasswordChange
{
    /**
     * Routes that should be accessible even when password change is required.
     */
    protected array $exceptRoutes = [
        'logout',
        'password.change',
        'password.change.update',
        'password.force-change',
        'password.force-change.update',
    ];

    /**
     * Route prefixes that should be accessible.
     */
    protected array $exceptPrefixes = [
        'password/',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Check if password change is required
        if (!$user->mustChangePassword()) {
            return $next($request);
        }

        // Allow access to excepted routes
        if ($this->isExceptedRoute($request)) {
            return $next($request);
        }

        // Log the redirect for security audit
        Log::info('ForcePasswordChange: Redirecting user to password change', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'attempted_route' => $request->route()?->getName() ?? $request->path(),
            'ip' => $request->ip(),
        ]);

        // Handle API requests differently
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'password_change_required',
                'message' => 'You must change your password before continuing.',
                'action_required' => 'POST /password/force-change',
            ], 403);
        }

        // Redirect to password change page with flash message
        return redirect()
            ->route('password.force-change')
            ->with('warning', 'You must change your password before continuing. This is required for security compliance.');
    }

    /**
     * Check if the current route is excepted from password change enforcement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isExceptedRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Check route names
        if ($routeName && in_array($routeName, $this->exceptRoutes)) {
            return true;
        }

        // Check path prefixes
        foreach ($this->exceptPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        // Allow POST to logout
        if ($request->isMethod('POST') && str_contains($path, 'logout')) {
            return true;
        }

        return false;
    }
}
