<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * Adds security headers to all HTTP responses to protect against common web vulnerabilities:
     * - Clickjacking (X-Frame-Options)
     * - MIME-sniffing (X-Content-Type-Options)
     * - XSS attacks (X-XSS-Protection)
     * - Information leakage (Referrer-Policy)
     * - Man-in-the-middle attacks (Strict-Transport-Security when HTTPS)
     * - XSS and injection attacks (Content-Security-Policy)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking by disallowing iframe embedding from other domains
        // SAMEORIGIN allows same-origin iframes (e.g., for admin dashboards)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME-sniffing which can lead to security vulnerabilities
        // Forces browser to respect declared Content-Type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS filter in older browsers (legacy protection)
        // Modern browsers rely on CSP instead
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control how much referrer information is sent with requests
        // 'strict-origin-when-cross-origin' sends full URL for same-origin, origin only for cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS: Enforce HTTPS for 1 year (only when using HTTPS)
        // includeSubDomains applies to all subdomains
        // Remove this if not using HTTPS in production
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy (CSP) - Mitigates XSS, clickjacking, and other injection attacks
        //
        // AUDIT NOTE: 'unsafe-inline' is currently required for:
        // - Blade templates with inline onclick handlers
        // - Alpine.js x-on directives
        // - Inline styles from Tailwind utilities
        //
        // FUTURE HARDENING (P2):
        // 1. Use nonce-based CSP: Generate per-request nonces in middleware
        // 2. Move inline scripts to external files
        // 3. Use CSP-compatible JS frameworks
        // 4. Replace inline styles with CSS classes
        //
        // Generate a nonce for strict CSP (for future use)
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('csp_nonce', $nonce);

        $cspDirectives = [
            "default-src 'self'",  // By default, only allow resources from same origin
            // AUDIT FIX: Added nonce support alongside unsafe-inline for gradual migration
            "script-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",  // Allow fonts from Google and data URIs
            "img-src 'self' data: https:",  // Allow images from same origin, data URIs, and HTTPS sources
            "connect-src 'self'",  // AJAX, WebSocket, EventSource only to same origin
            "frame-ancestors 'self'",  // Prevent embedding in iframes from other domains
            "base-uri 'self'",  // Prevent base tag hijacking
            "form-action 'self'",  // Forms can only submit to same origin
            "upgrade-insecure-requests",  // Upgrade HTTP to HTTPS for mixed content
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $cspDirectives));

        // Permissions Policy (formerly Feature Policy) - Control browser features
        // Disable features that aren't needed to reduce attack surface
        $permissionsPolicy = [
            'geolocation=()',  // Disable geolocation API
            'microphone=()',   // Disable microphone access
            'camera=()',       // Disable camera access
            'payment=()',      // Disable payment API
            'usb=()',          // Disable USB API
            'magnetometer=()', // Disable magnetometer
            'accelerometer=()',// Disable accelerometer
            'gyroscope=()',    // Disable gyroscope
        ];

        $response->headers->set('Permissions-Policy', implode(', ', $permissionsPolicy));

        return $response;
    }
}
