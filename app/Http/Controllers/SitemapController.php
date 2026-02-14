<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Models\Tags;
use App\Models\TopicCategory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Cache duration in seconds (6 hours)
     */
    private const CACHE_TTL = 21600;

    /**
     * Max URLs per individual sitemap file
     */
    private const MAX_URLS_PER_SITEMAP = 2000;

    /**
     * Supported languages for hreflang alternates
     */
    private const LANGUAGES = ['sd', 'en'];

    // ─────────────────────────────────────────────
    //  SITEMAP INDEX (master file)
    // ─────────────────────────────────────────────

    public function index()
    {
        $xml = Cache::remember('sitemap:index', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            // Gather lastmod dates for each sub-sitemap
            $poetsMod = Poets::max('updated_at');
            $poetryMod = Poetry::max('updated_at');
            $coupletMod = Couplets::max('updated_at');
            $catMod = Categories::max('updated_at');
            $tagMod = Tags::max('updated_at');
            $topicMod = TopicCategory::max('updated_at');

            $sitemaps = [
                ['loc' => "{$baseUrl}/sitemap/pages.xml", 'lastmod' => now()],
                ['loc' => "{$baseUrl}/sitemap/poets.xml", 'lastmod' => $poetsMod],
                ['loc' => "{$baseUrl}/sitemap/poetry.xml", 'lastmod' => $poetryMod],
                ['loc' => "{$baseUrl}/sitemap/couplets.xml", 'lastmod' => $coupletMod],
                ['loc' => "{$baseUrl}/sitemap/categories.xml", 'lastmod' => $catMod],
                ['loc' => "{$baseUrl}/sitemap/tags.xml", 'lastmod' => $tagMod],
                ['loc' => "{$baseUrl}/sitemap/topics.xml", 'lastmod' => $topicMod],
            ];

            return $this->renderSitemapIndex($sitemaps);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  STATIC PAGES
    // ─────────────────────────────────────────────

    public function pages()
    {
        $xml = Cache::remember('sitemap:pages', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $pages = [
                ['path' => '', 'priority' => '1.0', 'changefreq' => 'daily'],
                ['path' => '/poets', 'priority' => '0.9', 'changefreq' => 'daily'],
                ['path' => '/poetry', 'priority' => '0.9', 'changefreq' => 'daily'],
                ['path' => '/couplets', 'priority' => '0.8', 'changefreq' => 'daily'],
                ['path' => '/genre', 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['path' => '/period', 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['path' => '/prosody', 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['path' => '/explore', 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['path' => '/about', 'priority' => '0.4', 'changefreq' => 'monthly'],
                ['path' => '/privacy', 'priority' => '0.2', 'changefreq' => 'yearly'],
                ['path' => '/terms', 'priority' => '0.2', 'changefreq' => 'yearly'],
                ['path' => '/help', 'priority' => '0.3', 'changefreq' => 'monthly'],
                ['path' => '/status', 'priority' => '0.3', 'changefreq' => 'monthly'],
            ];

            $urls = [];
            foreach ($pages as $page) {
                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    $page['path'],
                    now()->toW3cString(),
                    $page['changefreq'],
                    $page['priority']
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  POETS INDEX → monthly sub-sitemaps
    // ─────────────────────────────────────────────

    public function poets()
    {
        $xml = Cache::remember('sitemap:poets', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $months = Poets::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, MAX(updated_at) as last_mod')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $sitemaps = [];
            foreach ($months as $m) {
                $sitemaps[] = [
                    'loc' => "{$baseUrl}/sitemap/poets-{$m->year}-{$m->month}.xml",
                    'lastmod' => $m->last_mod,
                ];
            }

            return $this->renderSitemapIndex($sitemaps);
        });

        return $this->xmlResponse($xml);
    }

    public function poetsByMonth($year, $month)
    {
        $cacheKey = "sitemap:poets:{$year}:{$month}";

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {
            $baseUrl = rtrim(config('app.url'), '/');

            $poets = Poets::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            $urls = [];
            foreach ($poets as $poet) {
                // Build image tags if poet has an avatar
                $images = [];
                $detail = PoetsDetail::where('poet_id', $poet->id)->first();
                if ($detail && $detail->poet_image) {
                    $imageUrl = $detail->poet_image;
                    // Make absolute if relative
                    if (!str_starts_with($imageUrl, 'http')) {
                        $imageUrl = $baseUrl . '/' . ltrim($imageUrl, '/');
                    }
                    $images[] = [
                        'loc' => $imageUrl,
                        'caption' => $detail->poet_name ?? $poet->poet_slug,
                    ];
                }

                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    '/poet/' . $poet->poet_slug,
                    $poet->updated_at ? $poet->updated_at->toW3cString() : null,
                    'weekly',
                    '0.8',
                    $images
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  POETRY INDEX → monthly sub-sitemaps
    // ─────────────────────────────────────────────

    public function poetry()
    {
        $xml = Cache::remember('sitemap:poetry', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $months = Poetry::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total, MAX(updated_at) as last_mod')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $sitemaps = [];
            foreach ($months as $m) {
                if (!$m->month)
                    continue;

                $pages = ceil($m->total / self::MAX_URLS_PER_SITEMAP);
                if ($pages > 1) {
                    for ($p = 1; $p <= $pages; $p++) {
                        $sitemaps[] = [
                            'loc' => "{$baseUrl}/sitemap/poetry-{$m->year}-{$m->month}.xml?page={$p}",
                            'lastmod' => $m->last_mod,
                        ];
                    }
                } else {
                    $sitemaps[] = [
                        'loc' => "{$baseUrl}/sitemap/poetry-{$m->year}-{$m->month}.xml",
                        'lastmod' => $m->last_mod,
                    ];
                }
            }

            return $this->renderSitemapIndex($sitemaps);
        });

        return $this->xmlResponse($xml);
    }

    public function poetryByMonth($year, $month)
    {
        $page = max(1, (int) request()->get('page', 1));
        $cacheKey = "sitemap:poetry:{$year}:{$month}:{$page}";

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month, $page) {
            $baseUrl = rtrim(config('app.url'), '/');
            $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;

            $items = Poetry::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->select('poetry_slug', 'category_id', 'poet_id', 'updated_at')
                ->with(['category:id,slug', 'poet:id,poet_slug'])
                ->skip($offset)
                ->take(self::MAX_URLS_PER_SITEMAP)
                ->get();

            $urls = [];
            foreach ($items as $item) {
                $catSlug = $item->category->slug ?? 'uncategorized';
                $poetSlug = $item->poet->poet_slug ?? 'unknown';

                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    "/poet/{$poetSlug}/{$catSlug}/{$item->poetry_slug}",
                    $item->updated_at ? $item->updated_at->toW3cString() : null,
                    'weekly',
                    '0.7'
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  COUPLETS INDEX → monthly sub-sitemaps
    // ─────────────────────────────────────────────

    public function couplets()
    {
        $xml = Cache::remember('sitemap:couplets', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $months = Couplets::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total, MAX(updated_at) as last_mod')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $sitemaps = [];
            foreach ($months as $m) {
                if (!$m->month)
                    continue;

                $pages = ceil($m->total / self::MAX_URLS_PER_SITEMAP);
                if ($pages > 1) {
                    for ($p = 1; $p <= $pages; $p++) {
                        $sitemaps[] = [
                            'loc' => "{$baseUrl}/sitemap/couplets-{$m->year}-{$m->month}.xml?page={$p}",
                            'lastmod' => $m->last_mod,
                        ];
                    }
                } else {
                    $sitemaps[] = [
                        'loc' => "{$baseUrl}/sitemap/couplets-{$m->year}-{$m->month}.xml",
                        'lastmod' => $m->last_mod,
                    ];
                }
            }

            return $this->renderSitemapIndex($sitemaps);
        });

        return $this->xmlResponse($xml);
    }

    public function coupletsByMonth($year, $month)
    {
        $page = max(1, (int) request()->get('page', 1));
        $cacheKey = "sitemap:couplets:{$year}:{$month}:{$page}";

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month, $page) {
            $baseUrl = rtrim(config('app.url'), '/');
            $offset = ($page - 1) * self::MAX_URLS_PER_SITEMAP;

            $items = Couplets::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->select('couplet_slug', 'updated_at')
                ->skip($offset)
                ->take(self::MAX_URLS_PER_SITEMAP)
                ->get();

            $urls = [];
            foreach ($items as $item) {
                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    '/couplets/' . $item->couplet_slug,
                    $item->updated_at ? $item->updated_at->toW3cString() : null,
                    'monthly',
                    '0.6'
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  CATEGORIES (flat, no index needed)
    // ─────────────────────────────────────────────

    public function categories()
    {
        $xml = Cache::remember('sitemap:categories', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');
            $categories = Categories::select('slug', 'updated_at')->get();

            $urls = [];
            foreach ($categories as $cat) {
                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    '/' . $cat->slug,
                    $cat->updated_at ? $cat->updated_at->toW3cString() : null,
                    'weekly',
                    '0.6'
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  TAGS INDEX → monthly sub-sitemaps
    // ─────────────────────────────────────────────

    public function tags()
    {
        $xml = Cache::remember('sitemap:tags', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $months = Tags::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, MAX(updated_at) as last_mod')
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();

            $sitemaps = [];
            foreach ($months as $m) {
                $sitemaps[] = [
                    'loc' => "{$baseUrl}/sitemap/tags-{$m->year}-{$m->month}.xml",
                    'lastmod' => $m->last_mod,
                ];
            }

            return $this->renderSitemapIndex($sitemaps);
        });

        return $this->xmlResponse($xml);
    }

    public function tagsByMonth($year, $month)
    {
        $cacheKey = "sitemap:tags:{$year}:{$month}";

        $xml = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($year, $month) {
            $baseUrl = rtrim(config('app.url'), '/');

            $items = Tags::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->select('slug', 'updated_at')
                ->get();

            $urls = [];
            foreach ($items as $item) {
                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    '/tag/' . $item->slug,
                    $item->updated_at ? $item->updated_at->toW3cString() : null,
                    'monthly',
                    '0.5'
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ─────────────────────────────────────────────
    //  TOPIC CATEGORIES (flat)
    // ─────────────────────────────────────────────

    public function topics()
    {
        $xml = Cache::remember('sitemap:topics', self::CACHE_TTL, function () {
            $baseUrl = rtrim(config('app.url'), '/');

            $items = TopicCategory::select('slug', 'updated_at')->get();

            $urls = [];
            foreach ($items as $item) {
                $urls[] = $this->buildUrlEntry(
                    $baseUrl,
                    '/topic/' . $item->slug,
                    $item->updated_at ? $item->updated_at->toW3cString() : null,
                    'weekly',
                    '0.6'
                );
            }

            return $this->renderUrlSet($urls);
        });

        return $this->xmlResponse($xml);
    }

    // ═════════════════════════════════════════════
    //  PRIVATE HELPERS — XML rendering
    // ═════════════════════════════════════════════

    /**
     * Build a single <url> entry array with hreflang alternates.
     */
    private function buildUrlEntry(
        string $baseUrl,
        string $path,
        ?string $lastmod,
        string $changefreq,
        string $priority,
        array $images = []
    ): array {
        return [
            'baseUrl' => $baseUrl,
            'path' => $path,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'images' => $images,
        ];
    }

    /**
     * Render a <sitemapindex> XML document.
     */
    private function renderSitemapIndex(array $sitemaps): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemaps as $s) {
            $lastmod = $s['lastmod'] ? Carbon::parse($s['lastmod'])->toW3cString() : now()->toW3cString();
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . htmlspecialchars($s['loc']) . "</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Render a <urlset> XML document with hreflang, image, priority & changefreq.
     */
    private function renderUrlSet(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $entry) {
            $baseUrl = $entry['baseUrl'];
            $path = $entry['path'];

            // Primary URL uses Sindhi (sd) as default language
            $primaryUrl = $baseUrl . '/sd' . $path;

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($primaryUrl) . "</loc>\n";

            // hreflang alternates for each supported language
            foreach (self::LANGUAGES as $lang) {
                $altUrl = $baseUrl . '/' . $lang . $path;
                $xml .= '    <xhtml:link rel="alternate" hreflang="' . $lang . '" href="' . htmlspecialchars($altUrl) . '" />' . "\n";
            }
            // x-default points to Sindhi
            $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($primaryUrl) . '" />' . "\n";

            if ($entry['lastmod']) {
                $xml .= "    <lastmod>{$entry['lastmod']}</lastmod>\n";
            }
            $xml .= "    <changefreq>{$entry['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$entry['priority']}</priority>\n";

            // Image sitemap tags
            foreach ($entry['images'] as $img) {
                $xml .= "    <image:image>\n";
                $xml .= "      <image:loc>" . htmlspecialchars($img['loc']) . "</image:loc>\n";
                if (!empty($img['caption'])) {
                    $xml .= "      <image:caption>" . htmlspecialchars($img['caption']) . "</image:caption>\n";
                }
                $xml .= "    </image:image>\n";
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Return XML response with proper headers.
     */
    private function xmlResponse(string $xml)
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=21600',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
