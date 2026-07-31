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

        // SPA HTML shells embed Vite hashed script URLs — never let browsers/proxies keep stale HTML.
        $contentType = (string) $response->headers->get('Content-Type', '');
        if (
            str_contains($contentType, 'text/html')
            || $request->is('admin', 'admin/*')
            || $request->is('/')
        ) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
