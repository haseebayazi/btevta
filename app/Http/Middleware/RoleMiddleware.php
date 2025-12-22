<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Role aliases for backward compatibility.
     * Maps legacy role names to their current equivalents.
     */
    private const ROLE_ALIASES = [
        'admin' => ['admin', 'super_admin'],          // admin also accepts super_admin
        'super_admin' => ['super_admin', 'admin'],    // super_admin also accepts admin
        'instructor' => ['instructor', 'trainer'],     // instructor also accepts trainer
        'trainer' => ['trainer', 'instructor'],        // trainer also accepts instructor
    ];

    /**
     * Handle an incoming request.
     *
     * SECURITY FIX: Added logging, proper type hints, and role alias support
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

        // Expand roles to include aliases
        $expandedRoles = $this->expandRolesWithAliases($roles);

        // Check if user has any of the required roles (using User model's hasAnyRole)
        if (!$user->hasAnyRole($expandedRoles)) {
            Log::warning('RoleMiddleware: Unauthorized role access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'required_roles' => $roles,
                'expanded_roles' => $expandedRoles,
                'route' => $request->route()?->getName(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(403, 'This action is unauthorized. Required role(s): ' . implode(', ', $roles));
        }

        return $next($request);
    }

    /**
     * Expand the given roles to include their aliases.
     *
     * @param array $roles
     * @return array
     */
    private function expandRolesWithAliases(array $roles): array
    {
        $expandedRoles = [];

        foreach ($roles as $role) {
            $roleLower = strtolower(trim($role));
            $expandedRoles[] = $roleLower;

            // Add aliases if they exist
            if (isset(self::ROLE_ALIASES[$roleLower])) {
                foreach (self::ROLE_ALIASES[$roleLower] as $alias) {
                    if (!in_array($alias, $expandedRoles)) {
                        $expandedRoles[] = $alias;
                    }
                }
            }
        }

        return array_unique($expandedRoles);
    }
}