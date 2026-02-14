<?php

namespace App\Http\Middleware;

use App\Models\MokhiiPageMeta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mokhii GEO Middleware
 *
 * Intercepts HTML responses and injects auto-generated SEO fixes
 * (meta descriptions, canonical, hreflang, schema, H1) from mokhii_page_meta.
 */
class MokhiiSeoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only modify HTML responses
        if (!$this->isHtmlResponse($response)) {
            return $response;
        }

        $html = $response->getContent();
        if (empty($html) || !str_contains($html, '</head>')) {
            return $response;
        }

        // Look up fixes for this URL
        $url = $request->fullUrl();
        $meta = MokhiiPageMeta::where('url', $url)->first();

        if (!$meta || empty($meta->mokhii_fixes)) {
            return $response;
        }

        $fixes = $meta->mokhii_fixes;
        $html = $this->applyFixes($html, $fixes);

        $response->setContent($html);
        return $response;
    }

    private function applyFixes(string $html, array $fixes): string
    {
        $headInjections = [];

        // ── Title Fix ──────────────────────────────
        if (isset($fixes['missing_title']['title']) || isset($fixes['title_too_long']['title']) || isset($fixes['duplicate_title']['title'])) {
            $title = $fixes['duplicate_title']['title']
                ?? $fixes['missing_title']['title']
                ?? $fixes['title_too_long']['title'];

            // Replace existing <title> or inject before </head>
            if (preg_match('#<title>.*?</title>#s', $html)) {
                $html = preg_replace('#<title>.*?</title>#s', '<title>' . e($title) . '</title>', $html, 1);
            } else {
                $headInjections[] = '<title>' . e($title) . '</title>';
            }
        }

        // ── Meta Description Fix ────────────────────
        if (isset($fixes['missing_meta_description']['meta_description']) || isset($fixes['meta_description_too_long']['meta_description'])) {
            $desc = $fixes['missing_meta_description']['meta_description']
                ?? $fixes['meta_description_too_long']['meta_description'];

            // Replace existing or inject new
            if (preg_match('#<meta\s+name=["\']description["\'][^>]*>#i', $html)) {
                $html = preg_replace(
                    '#<meta\s+name=["\']description["\'][^>]*>#i',
                    '<meta name="description" content="' . e($desc) . '">',
                    $html,
                    1
                );
            } else {
                $headInjections[] = '<meta name="description" content="' . e($desc) . '">';
            }
        }

        // ── Canonical Fix ──────────────────────────
        if (isset($fixes['missing_canonical']['canonical'])) {
            $canonical = $fixes['missing_canonical']['canonical'];
            if (!preg_match('#<link\s+rel=["\']canonical["\'][^>]*>#i', $html)) {
                $headInjections[] = '<link rel="canonical" href="' . e($canonical) . '">';
            }
        }

        // ── Hreflang Fix ───────────────────────────
        if (isset($fixes['missing_hreflang']['hreflang'])) {
            if (!preg_match('#<link\s+rel=["\']alternate["\'].*?hreflang#i', $html)) {
                foreach ($fixes['missing_hreflang']['hreflang'] as $alt) {
                    $headInjections[] = '<link rel="alternate" hreflang="' . e($alt['lang']) . '" href="' . e($alt['url']) . '">';
                }
            }
        }

        // ── Schema Fix ─────────────────────────────
        if (isset($fixes['missing_schema']['schema'])) {
            if (!preg_match('#<script\s+type=["\']application/ld\+json["\']#i', $html)) {
                $schemaJson = json_encode($fixes['missing_schema']['schema'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $headInjections[] = '<script type="application/ld+json">' . $schemaJson . '</script>';
            }
        }

        // ── H1 Fix ─────────────────────────────────
        if (isset($fixes['missing_h1']['h1'])) {
            if (!preg_match('#<h1[^>]*>.*?</h1>#si', $html)) {
                // Inject a visually hidden but crawlable H1 right after <body>
                $h1Tag = '<h1 style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0">'
                    . e($fixes['missing_h1']['h1'])
                    . '</h1>';

                $html = preg_replace('#(<body[^>]*>)#i', '$1' . $h1Tag, $html, 1);
            }
        }

        // Inject all head additions before </head>
        if (!empty($headInjections)) {
            $injection = "\n    <!-- Mokhii GEO Fixes -->\n    " . implode("\n    ", $headInjections) . "\n";
            $html = str_replace('</head>', $injection . '</head>', $html);
        }

        return $html;
    }

    private function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html') || empty($contentType);
    }
}
