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
 * Handles: www→apex, legacy beta host→apex, trailing/double slashes, ?lang=,
 * /home aliases, and legacy path shapes (/poets, /tags, /poetry, /couplets, …).
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

        $host = strtolower($request->getHost());
        $lyricsHosts = array_map('strtolower', config('app.lyrics_hosts', []));
        if (in_array($host, $lyricsHosts, true) || str_starts_with($host, 'lyrics.')) {
            return $next($request);
        }

        $scheme = 'https';
        $canonicalHost = $this->canonicalHost();

        // Host redirects run before shouldSkip so robots.txt / sitemap also consolidate.
        // www.baakh.com → baakh.com
        if (str_starts_with($host, 'www.')) {
            return $this->redirectAway($scheme, $canonicalHost, $path, $request);
        }

        // Legacy staging host → canonical apex (baakh.com)
        if (($host === 'beta.baakh.com' || str_starts_with($host, 'beta.')) && !str_starts_with($host, 'lyrics.')) {
            return $this->redirectAway($scheme, $canonicalHost, $path, $request);
        }

        // Never rewrite API, admin, assets, or health endpoints.
        if ($this->shouldSkip($path)) {
            return $next($request);
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

        $langFromQuery = null;
        if ($request->query->has('lang')) {
            $candidate = strtolower((string) $request->query('lang'));
            if (in_array($candidate, ['en', 'sd'], true)) {
                $langFromQuery = $candidate;
            }
        }

        [$pathLang, $rest] = $this->splitLang($path);
        $lang = $langFromQuery ?? $pathLang ?? 'sd';

        // Single-hop canonicalization: lang preference + legacy path shapes.
        $canonical = $this->canonicalPath($rest, $lang);
        if ($canonical !== null) {
            $needsRedirect = $canonical !== $path || $langFromQuery !== null;
            if ($needsRedirect) {
                return redirect($this->appendQueryWithoutLang($canonical, $request), 301);
            }
        } elseif ($langFromQuery !== null) {
            // Modern path; only swap/add locale prefix and drop ?lang=
            $prefixed = $this->ensureLangPrefix($path, $langFromQuery);
            return redirect($this->appendQueryWithoutLang($prefixed, $request), 301);
        } elseif ($path === '/') {
            return redirect($this->withQuery('/sd', $request), 301);
        }

        return $next($request);
    }

    /**
     * @return array{0: ?string, 1: string} [lang|null, path without lang prefix]
     */
    private function splitLang(string $path): array
    {
        if (preg_match('#^/(en|sd)(/.*)?$#', $path, $m)) {
            $rest = $m[2] ?? '';
            if ($rest === '') {
                $rest = '/';
            }

            return [$m[1], $rest];
        }

        return [null, $path];
    }

    /**
     * Map a locale-stripped path to its canonical localized form, or null if unchanged structurally
     * and already correctly localized by the caller.
     */
    private function canonicalPath(string $rest, string $lang): ?string
    {
        // /{lang}/periods → /{lang}/period
        if ($rest === '/periods') {
            return '/' . $lang . '/period';
        }

        // Home aliases
        if ($rest === '/' || $rest === '/home') {
            return '/' . $lang;
        }

        // /poetry/{category}/{slug} → /{lang}/poet/{poet}/{category}/{slug}
        if (preg_match('#^/poetry/([^/]+)/([^/]+)$#', $rest, $m)) {
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
                return '/' . $lang . '/poet/' . $poetry->poet->poet_slug . '/' . $category . '/' . $slug;
            }

            return '/' . $lang . '/' . $category;
        }

        // /poetry/{category}
        if (preg_match('#^/poetry/([^/]+)$#', $rest, $m)) {
            return '/' . $lang . '/' . $m[1];
        }
        if ($rest === '/poetry') {
            return '/' . $lang . '/poetry';
        }

        // /couplets/{slug} → poet profile (no couplet detail route)
        if (preg_match('#^/couplets/([^/]+)$#', $rest, $m)) {
            try {
                $couplet = Couplets::query()
                    ->with('poet:id,poet_slug')
                    ->where('couplet_slug', $m[1])
                    ->first();
            } catch (\Throwable) {
                $couplet = null;
            }
            if ($couplet?->poet?->poet_slug) {
                return '/' . $lang . '/poet/' . $couplet->poet->poet_slug;
            }

            return '/' . $lang . '/couplets';
        }
        if ($rest === '/couplets') {
            return '/' . $lang . '/couplets';
        }

        // /poets/{slug}/... → /{lang}/poet/{slug}
        if (preg_match('#^/poets/([^/]+)(/.*)?$#', $rest, $m)) {
            return '/' . $lang . '/poet/' . $m[1];
        }
        if ($rest === '/poets') {
            return '/' . $lang . '/poets';
        }

        // /poet/{slug}/... without lang (or already under wrong plural parents handled above)
        if (preg_match('#^/poet(/.+)$#', $rest, $m)) {
            return '/' . $lang . '/poet' . $m[1];
        }

        // /tags/... → /{lang}/tag/...
        if (preg_match('#^/tags/(.+)$#', $rest, $m)) {
            // Old URLs sometimes included category: /tags/foo/ghazal
            $parts = explode('/', $m[1]);

            return '/' . $lang . '/tag/' . $parts[0];
        }
        if ($rest === '/tags') {
            return '/' . $lang . '/explore';
        }

        // Unprefixed /tag/... → add locale
        if (preg_match('#^/tag/(.+)$#', $rest, $m)) {
            return '/' . $lang . '/tag/' . $m[1];
        }

        // Misc legacy
        $simple = [
            '/privacy' => '/privacy',
            '/prosody' => '/prosody',
            '/genres' => '/explore',
            '/help' => '/help',
            '/about' => '/about',
        ];
        if (isset($simple[$rest])) {
            return '/' . $lang . $simple[$rest];
        }
        if (str_starts_with($rest, '/genres/') || str_starts_with($rest, '/bundles/')) {
            return '/' . $lang;
        }

        return null;
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

    private function appendQueryWithoutLang(string $path, Request $request): string
    {
        $query = $request->query();
        unset($query['lang']);
        if ($query === []) {
            return $path;
        }

        return $path . '?' . http_build_query($query);
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
}
