<?php

namespace App\Services;

use App\Models\SeoAudit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MokhiiCrawlerService
{
    /**
     * Issue codes used in the issues JSON array.
     */
    public const ISSUE_NO_H1 = 'missing_h1';
    public const ISSUE_NO_META_DESC = 'missing_meta_description';
    public const ISSUE_NO_TITLE = 'missing_title';
    public const ISSUE_TITLE_TOO_LONG = 'title_too_long';
    public const ISSUE_META_DESC_TOO_LONG = 'meta_description_too_long';
    public const ISSUE_NO_SCHEMA = 'missing_schema';
    public const ISSUE_BROKEN_LINK = 'broken_internal_link';
    public const ISSUE_SLOW_RESPONSE = 'slow_response';
    public const ISSUE_DUPLICATE_TITLE = 'duplicate_title';
    public const ISSUE_MULTIPLE_H1 = 'multiple_h1';
    public const ISSUE_NO_LANG_ATTR = 'missing_lang_attribute';
    public const ISSUE_NO_CANONICAL = 'missing_canonical';
    public const ISSUE_NO_HREFLANG = 'missing_hreflang';

    private string $baseUrl;
    private int $slowThresholdMs = 2000;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.url'), '/');
    }

    /**
     * Crawl a single URL and return an SeoAudit model.
     */
    public function crawl(string $url): SeoAudit
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'MokhiiBot/1.0'])
                ->get($url);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);
            $html = $response->body();
            $statusCode = $response->status();
        } catch (\Exception $e) {
            Log::warning("Mokhii crawl failed for {$url}: " . $e->getMessage());

            return $this->saveAudit($url, [
                'status_code' => 0,
                'response_time_ms' => 0,
                'issues' => ['crawl_error'],
                'score' => 0,
            ]);
        }

        return $this->analyzeAndSave($url, $html, $statusCode, $responseTimeMs);
    }

    /**
     * Analyze HTML content and persist audit results.
     */
    private function analyzeAndSave(string $url, string $html, int $statusCode, int $responseTimeMs): SeoAudit
    {
        $issues = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        // ── Title ───────────────────────────────────
        $titleNodes = $xpath->query('//title');
        $metaTitle = null;
        if ($titleNodes->length > 0) {
            $metaTitle = trim($titleNodes->item(0)->textContent);
            if (mb_strlen($metaTitle) > 70) {
                $issues[] = self::ISSUE_TITLE_TOO_LONG;
            }
        } else {
            $issues[] = self::ISSUE_NO_TITLE;
        }

        // ── H1 ──────────────────────────────────────
        $h1Nodes = $xpath->query('//h1');
        $hasH1 = $h1Nodes->length > 0;
        if (!$hasH1) {
            $issues[] = self::ISSUE_NO_H1;
        }
        if ($h1Nodes->length > 1) {
            $issues[] = self::ISSUE_MULTIPLE_H1;
        }

        // ── Meta Description ────────────────────────
        $metaDesc = null;
        $metaNodes = $xpath->query('//meta[@name="description"]');
        $hasMetaDesc = false;
        if ($metaNodes->length > 0) {
            $metaDesc = $metaNodes->item(0)->getAttribute('content');
            $hasMetaDesc = !empty(trim($metaDesc));
            if (mb_strlen($metaDesc) > 160) {
                $issues[] = self::ISSUE_META_DESC_TOO_LONG;
            }
        }
        if (!$hasMetaDesc) {
            $issues[] = self::ISSUE_NO_META_DESC;
        }

        // ── JSON-LD Schema ──────────────────────────
        $schemaTypes = [];
        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        $hasSchema = $scriptNodes->length > 0;
        if ($hasSchema) {
            for ($i = 0; $i < $scriptNodes->length; $i++) {
                $json = json_decode(trim($scriptNodes->item($i)->textContent), true);
                if ($json && isset($json['@type'])) {
                    $schemaTypes[] = $json['@type'];
                }
            }
        } else {
            $issues[] = self::ISSUE_NO_SCHEMA;
        }

        // ── Canonical ───────────────────────────────
        $canonicalNodes = $xpath->query('//link[@rel="canonical"]');
        if ($canonicalNodes->length === 0) {
            $issues[] = self::ISSUE_NO_CANONICAL;
        }

        // ── Hreflang ────────────────────────────────
        $hreflangNodes = $xpath->query('//link[@rel="alternate"][@hreflang]');
        if ($hreflangNodes->length === 0) {
            $issues[] = self::ISSUE_NO_HREFLANG;
        }

        // ── Lang attribute ──────────────────────────
        $htmlNodes = $xpath->query('//html[@lang]');
        if ($htmlNodes->length === 0) {
            $issues[] = self::ISSUE_NO_LANG_ATTR;
        }

        // ── Internal broken links ───────────────────
        $brokenLinks = $this->checkInternalLinks($html, $url);
        if (!empty($brokenLinks)) {
            $issues[] = self::ISSUE_BROKEN_LINK;
        }

        // ── Slow response ───────────────────────────
        if ($responseTimeMs > $this->slowThresholdMs) {
            $issues[] = self::ISSUE_SLOW_RESPONSE;
        }

        // ── Compute Score ───────────────────────────
        $score = $this->computeScore($statusCode, $hasH1, $hasMetaDesc, $hasSchema, $responseTimeMs, $brokenLinks, $issues);

        return $this->saveAudit($url, [
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'has_h1' => $hasH1,
            'has_meta_description' => $hasMetaDesc,
            'meta_title' => $metaTitle ? mb_substr($metaTitle, 0, 500) : null,
            'meta_description' => $metaDesc,
            'has_schema' => $hasSchema,
            'schema_types' => $schemaTypes ?: null,
            'broken_links' => $brokenLinks ?: null,
            'issues' => $issues ?: null,
            'score' => $score,
        ]);
    }

    /**
     * Check internal links in the HTML for broken ones (sample up to 20).
     */
    private function checkInternalLinks(string $html, string $currentUrl): array
    {
        $broken = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $links = $dom->getElementsByTagName('a');

        $checked = 0;
        $maxCheck = 20; // Limit to avoid hammering the server

        for ($i = 0; $i < $links->length && $checked < $maxCheck; $i++) {
            $href = $links->item($i)->getAttribute('href');
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Make relative URLs absolute
            if (str_starts_with($href, '/')) {
                $href = $this->baseUrl . $href;
            }

            // Only check internal links
            if (!str_starts_with($href, $this->baseUrl)) {
                continue;
            }

            // Skip API and admin links
            if (str_contains($href, '/api/') || str_contains($href, '/admin/')) {
                continue;
            }

            try {
                $resp = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'MokhiiBot/1.0'])
                    ->head($href);

                if ($resp->status() >= 400) {
                    $broken[] = ['url' => $href, 'status' => $resp->status()];
                }
            } catch (\Exception $e) {
                $broken[] = ['url' => $href, 'status' => 0];
            }

            $checked++;
        }

        return $broken;
    }

    /**
     * Compute an SEO score from 0-100.
     */
    private function computeScore(
        int $statusCode,
        bool $hasH1,
        bool $hasMetaDesc,
        bool $hasSchema,
        int $responseTimeMs,
        array $brokenLinks,
        array $issues
    ): float {
        $score = 100.0;

        // Status code penalties
        if ($statusCode >= 400)
            $score -= 40;
        elseif ($statusCode >= 300)
            $score -= 10;

        // Core SEO elements
        if (!$hasH1)
            $score -= 15;
        if (!$hasMetaDesc)
            $score -= 15;
        if (!$hasSchema)
            $score -= 10;

        // Performance
        if ($responseTimeMs > 5000)
            $score -= 15;
        elseif ($responseTimeMs > $this->slowThresholdMs)
            $score -= 8;

        // Broken links
        $score -= min(count($brokenLinks) * 5, 15);

        // Minor issues
        if (in_array(self::ISSUE_NO_CANONICAL, $issues))
            $score -= 5;
        if (in_array(self::ISSUE_NO_HREFLANG, $issues))
            $score -= 5;
        if (in_array(self::ISSUE_NO_LANG_ATTR, $issues))
            $score -= 3;
        if (in_array(self::ISSUE_TITLE_TOO_LONG, $issues))
            $score -= 3;
        if (in_array(self::ISSUE_MULTIPLE_H1, $issues))
            $score -= 5;

        return max(0, round($score, 1));
    }

    /**
     * Persist audit results (upsert by URL).
     */
    private function saveAudit(string $url, array $data): SeoAudit
    {
        $data['crawled_at'] = now();

        return SeoAudit::updateOrCreate(
            ['url' => $url],
            $data
        );
    }

    /**
     * Get all crawlable URLs from the sitemap.
     */
    public function getSitemapUrls(): array
    {
        $urls = [];

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'MokhiiBot/1.0'])
                ->get($this->baseUrl . '/sitemap.xml');

            $xml = simplexml_load_string($response->body());
            if (!$xml)
                return $urls;

            // Sitemap index → get child sitemaps
            foreach ($xml->sitemap as $sitemap) {
                $childUrls = $this->parseSitemap((string) $sitemap->loc);
                $urls = array_merge($urls, $childUrls);
            }

            // Direct urlset (if not an index)
            foreach ($xml->url as $urlNode) {
                $urls[] = (string) $urlNode->loc;
            }
        } catch (\Exception $e) {
            Log::warning('Mokhii: Failed to fetch sitemap - ' . $e->getMessage());
        }

        return array_unique($urls);
    }

    /**
     * Parse a single sitemap XML file.
     */
    private function parseSitemap(string $sitemapUrl): array
    {
        $urls = [];

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'MokhiiBot/1.0'])
                ->get($sitemapUrl);

            $xml = simplexml_load_string($response->body());
            if (!$xml)
                return $urls;

            // Could be another sitemap index (nested)
            foreach ($xml->sitemap as $sitemap) {
                $childUrls = $this->parseSitemap((string) $sitemap->loc);
                $urls = array_merge($urls, $childUrls);
            }

            // URL entries
            foreach ($xml->url as $urlNode) {
                $urls[] = (string) $urlNode->loc;
            }
        } catch (\Exception $e) {
            Log::warning("Mokhii: Failed to parse sitemap {$sitemapUrl} - " . $e->getMessage());
        }

        return $urls;
    }
}
