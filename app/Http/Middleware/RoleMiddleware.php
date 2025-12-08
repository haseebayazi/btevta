<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * SECURITY FIX: Added logging and proper type hints
     * Assumes 'auth' middleware already verified authentication
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // User should already be authenticated by 'auth' middleware
        // This check is redundant but kept for defense-in-depth
        if (!auth()->check()) {
            Log::warning('RoleMiddleware: Unauthenticated access attempt', [
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(401, 'Unauthenticated.');
        }

        $user = auth()->user();

        // Check if user has any of the required roles (case-insensitive comparison)
        $userRoleLower = strtolower($user->role);
        $rolesLower = array_map('strtolower', $roles);
        if (!in_array($userRoleLower, $rolesLower)) {
            Log::warning('RoleMiddleware: Unauthorized role access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(403, 'This action is unauthorized. Required role(s): ' . implode(', ', $roles));
        }

        return $next($request);
    }
}