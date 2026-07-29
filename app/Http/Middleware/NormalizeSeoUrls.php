<?php

namespace App\Http\Middleware;

use App\Models\Couplets;
use App\Models\Poetry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalize crawlable URLs so Google stops accumulating redirect / soft-404 / duplicate signals.
 *
 * Handles: www→apex, legacy beta host→apex, trailing/double slashes, ?lang=, and legacy unprefixed routes.
 */
class NormalizeSeoUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = '/' . ltrim($request->getPathInfo(), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        // Never rewrite API, admin, assets, or health endpoints.
        if ($this->shouldSkip($path)) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $lyricsHosts = array_map('strtolower', config('app.lyrics_hosts', []));
        if (in_array($host, $lyricsHosts, true) || str_starts_with($host, 'lyrics.')) {
            return $next($request);
        }

        $scheme = 'https';
        $canonicalHost = $this->canonicalHost();

        // www.baakh.com → baakh.com
        if (str_starts_with($host, 'www.')) {
            return $this->redirectAway($scheme, $canonicalHost, $path, $request);
        }

        // Legacy staging host → canonical apex (baakh.com)
        // Never redirect the lyrics subdomain into the archive apex.
        if (($host === 'beta.baakh.com' || str_starts_with($host, 'beta.')) && !str_starts_with($host, 'lyrics.')) {
            return $this->redirectAway($scheme, $canonicalHost, $path, $request);
        }

        // Collapse accidental // in the path (e.g. /sd/poet/name//slug)
        if (str_contains($path, '//')) {
            $collapsed = preg_replace('#/+#', '/', $path) ?: '/';
            return redirect($this->withQuery($collapsed, $request), 301);
        }

        // Trailing slash (root excluded) — Laravel may still receive "/" only.
        $rawPath = $request->getPathInfo();
        if ($rawPath !== '/' && str_ends_with($rawPath, '/')) {
            return redirect($this->withQuery($path, $request), 301);
        }

        // /{lang}/periods → /{lang}/period (SPA route is singular)
        if (preg_match('#^/(en|sd)/periods$#', $path, $m)) {
            return redirect($this->withQuery('/' . $m[1] . '/period', $request), 301);
        }

        // ?lang=en|sd → /{lang}/... (and drop the query param)
        if ($request->query->has('lang')) {
            $lang = strtolower((string) $request->query('lang'));
            if (in_array($lang, ['en', 'sd'], true)) {
                $query = $request->query();
                unset($query['lang']);

                $prefixed = $this->ensureLangPrefix($path, $lang);
                $target = $prefixed;
                if ($query !== []) {
                    $target .= '?' . http_build_query($query);
                }

                return redirect($target, 301);
            }
        }

        // Apex home → primary Sindhi locale (aligns canonical, hreflang, and client routing)
        if ($path === '/') {
            return redirect($this->withQuery('/sd', $request), 301);
        }

        // Legacy unprefixed content URLs → /sd/...
        $legacy = $this->legacyRedirectTarget($path);
        if ($legacy !== null) {
            return redirect($legacy, 301);
        }

        return $next($request);
    }

    private function shouldSkip(string $path): bool
    {
        $prefixes = [
            '/api',
            '/admin',
            '/build',
            '/assets',
            '/storage',
            '/vendor',
            '/robots.txt',
            '/sitemap',
            '/favicon',
            '/_health',
            '/sanctum',
            '/auth/',
            '/livewire',
            '/og-image/',
            '/lyrics-site',
        ];

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function canonicalHost(): string
    {
        $appUrl = (string) config('app.url', 'https://baakh.com');
        $host = parse_url($appUrl, PHP_URL_HOST);

        return $host ?: 'baakh.com';
    }

    private function redirectAway(string $scheme, string $host, string $path, Request $request)
    {
        $target = $scheme . '://' . $host . $path;
        $qs = $request->getQueryString();
        if ($qs) {
            $target .= '?' . $qs;
        }

        return redirect()->away($target, 301);
    }

    private function withQuery(string $path, Request $request): string
    {
        $qs = $request->getQueryString();

        return $qs ? ($path . '?' . $qs) : $path;
    }

    private function ensureLangPrefix(string $path, string $lang): string
    {
        if (preg_match('#^/(en|sd)(/|$)#', $path)) {
            // Replace existing lang segment.
            return preg_replace('#^/(en|sd)#', '/' . $lang, $path, 1) ?: ('/' . $lang);
        }

        if ($path === '/') {
            return '/' . $lang;
        }

        return '/' . $lang . $path;
    }

    private function legacyRedirectTarget(string $path): ?string
    {
        // Already language-prefixed modern routes.
        if (preg_match('#^/(en|sd)(/|$)#', $path)) {
            return null;
        }

        // /poetry/{category}/{slug} → /sd/poet/{poet}/{category}/{slug}
        if (preg_match('#^/poetry/([^/]+)/([^/]+)$#', $path, $m)) {
            $category = $m[1];
            $slug = $m[2];
            try {
                $poetry = Poetry::query()
                    ->with('poet:id,poet_slug')
                    ->where('poetry_slug', $slug)
                    ->first();
            } catch (\Throwable) {
                $poetry = null;
            }
            if ($poetry?->poet?->poet_slug) {
                return '/sd/poet/' . $poetry->poet->poet_slug . '/' . $category . '/' . $slug;
            }

            return '/sd/' . $category;
        }

        // /poetry/{category}
        if (preg_match('#^/poetry/([^/]+)$#', $path, $m)) {
            return '/sd/' . $m[1];
        }
        if ($path === '/poetry') {
            return '/sd/poetry';
        }

        // /couplets/{slug} → poet profile (no couplet detail route)
        if (preg_match('#^/couplets/([^/]+)$#', $path, $m)) {
            try {
                $couplet = Couplets::query()
                    ->with('poet:id,poet_slug')
                    ->where('couplet_slug', $m[1])
                    ->first();
            } catch (\Throwable) {
                $couplet = null;
            }
            if ($couplet?->poet?->poet_slug) {
                return '/sd/poet/' . $couplet->poet->poet_slug;
            }

            return '/sd/couplets';
        }
        if ($path === '/couplets') {
            return '/sd/couplets';
        }

        // /poets/{slug}/... → /sd/poet/{slug}
        if (preg_match('#^/poets/([^/]+)(/.*)?$#', $path, $m)) {
            return '/sd/poet/' . $m[1];
        }
        if ($path === '/poets') {
            return '/sd/poets';
        }

        // /poet/{slug}/... without lang
        if (preg_match('#^/poet(/.+)$#', $path, $m)) {
            return '/sd/poet' . $m[1];
        }

        // /tags/... → /sd/tag/...
        if (preg_match('#^/tags/(.+)$#', $path, $m)) {
            // Old URLs sometimes included category: /tags/foo/ghazal
            $parts = explode('/', $m[1]);
            return '/sd/tag/' . $parts[0];
        }
        if ($path === '/tags') {
            return '/sd/explore';
        }
        if (preg_match('#^/tag/(.+)$#', $path, $m)) {
            return '/sd/tag/' . $m[1];
        }

        // Misc legacy
        $simple = [
            '/periods' => '/sd/period',
            '/privacy' => '/sd/privacy',
            '/prosody' => '/sd/prosody',
            '/genres' => '/sd/explore',
            '/help' => '/sd/help',
            '/about' => '/sd/about',
        ];
        if (isset($simple[$path])) {
            return $simple[$path];
        }
        if (str_starts_with($path, '/genres/') || str_starts_with($path, '/bundles/')) {
            return '/sd';
        }

        return null;
    }
}
