<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * SECURITY POLICY:
     * - All POST, PUT, PATCH, DELETE requests require CSRF tokens by default
     * - All routes are protected unless explicitly added to $except array
     * - Only add exceptions for: webhooks, third-party callbacks, or public APIs
     * - NEVER add admin routes or user-facing forms to exceptions
     *
     * CURRENT STATUS:
     * - No exceptions defined (all routes protected) ✅
     * - This is the correct and secure configuration
     * - Laravel automatically verifies CSRF tokens for all state-changing requests
     *
     * IF YOU NEED TO ADD EXCEPTIONS:
     * 1. Document the reason (e.g., "Stripe webhook endpoint")
     * 2. Use specific paths, not wildcards (e.g., 'api/webhooks/stripe', not 'api/*')
     * 3. Get security approval before deploying
     * 4. Consider using signed URLs or API tokens instead
     *
     * @var array<int, string>
     */
    protected $except = [
        // No exceptions - all routes protected ✅
        // Example format if needed:
        // 'api/webhooks/stripe',  // Stripe webhook callback
        // 'api/webhooks/paypal',  // PayPal IPN callback
    ];
}