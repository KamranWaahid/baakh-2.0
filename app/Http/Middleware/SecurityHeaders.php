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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enable XSS filtering (usually enabled by default in modern browsers, but good practice)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control Referrer Information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Enforce HTTPS (HSTS) - Only acts if site is loaded via HTTPS
        // Max-age: 1 year. includeSubDomains ensures subdomains also use HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy (CSP)
        // Start with a permissive policy or report-only to avoid breaking the app immediately.
        // Ideally, this should be tightened over time.
        // $response->headers->set('Content-Security-Policy', "default-src 'self' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' data: https:;");

        return $response;
    }
}
