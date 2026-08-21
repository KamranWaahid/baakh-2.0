<?php

namespace App\Http\Controllers;

use App\Http\Concerns\NegotiatesMarkdown;
use App\Models\Poets;
use App\Models\Poetry;
use App\Models\Tags;
use App\Models\TopicCategory;
use App\Services\StaticCacheService;
use App\Support\AeoPlatformFaq;
use App\Support\SeoMarkdown;
use App\Traits\BaakhSeoTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    use BaakhSeoTrait;
    use NegotiatesMarkdown;

    public function index(Request $request, $any = null)
    {
        $locale = app()->getLocale();
        $path = $request->path();

        // Handle Language Prefix (if present)
        $segments = explode('/', $path);
        if (($segments[0] ?? '') === 'en' || ($segments[0] ?? '') === 'sd') {
            app()->setLocale($segments[0]);
            $locale = $segments[0];
            array_shift($segments);
        }

        $isSd = $locale === 'sd';
        $title = $isSd ? 'باک - سنڌي شاعريءَ جو آرڪائيو' : 'Baakh - Archive of Sindhi Poetry';
        $description = $isSd
            ? 'باک سنڌي شاعريءَ جو ھڪ ڊجيٽل آرڪائيو آھي، جيڪو ڪلاسيڪي ۽ جديد شاعريءَ کي محفوظ ڪري ٿو.'
            : 'Baakh is a digital archive of Sindhi poetry, preserving classical and modern literary works for future generations.';

        $ogDescription = $isSd
            ? 'سنڌي شاعريءَ جو ڊجيٽل آرڪائيو — ڪلاسيڪي کان جديد دور تائين.'
            : 'A digital archive dedicated to preserving Sindhi poetry from classical to modern eras.';

        $ogImageAlt = $isSd ? 'باک - سنڌي شاعريءَ جو آرڪائيو' : 'Baakh - Archive of Sindhi Poetry';

        // Detect Route Type for dynamic SEO
        if (count($segments) >= 2) {
            $type = $segments[0];

            if ($type === 'poet' && isset($segments[1])) {
                $poetSlug = $segments[1];

                // Case 1: /:lang/poet/:slug/:category/:poemSlug
                if (count($segments) >= 4) {
                    $categorySlug = $segments[2];
                    $poemSlug = $segments[3];
                    $poetry = $this->safeFind(
                        fn () => Poetry::where('poetry_slug', $poemSlug)
                            ->with(['poet.all_details', 'category.details', 'translations', 'all_couplets', 'topicCategory.details'])
                            ->first()
                    );
                    $poet = $poetry?->poet;
                    if ($poetry && $poet) {
                        // Link previews (WhatsApp, etc.) use brand logo on white —
                        // not the designed poetry share card (that is only for in-app share/download).
                        $ogImageUrl = asset('assets/og/baakh-og-v2-1200x630.png');
                        try {
                            $fallback = $this->SEO_Poetry($poetry, $categorySlug, $poet, $ogImageUrl);
                        } catch (\Throwable) {
                            // Never 500 for crawlers — fall back to generic meta.
                            $fallback = $this->SEO_General($title, $description, null, null, [
                                'og_description' => $ogDescription,
                                'og_image_alt' => $ogImageAlt,
                            ]);
                        }
                        return $this->renderSpa($fallback);
                    }

                    // Only emit a hard 404 when the lookup succeeded and found nothing.
                    if ($poetry === null && $this->contentTablesAvailable()) {
                        return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
                    }
                } elseif (count($segments) === 2) {
                    // Case 2: /:lang/poet/:slug
                    $poet = $this->safeFind(
                        fn () => Poets::where('poet_slug', $poetSlug)->with('all_details')->first()
                    );
                    if ($poet) {
                        try {
                            $fallback = $this->SEO_Poet($poet, '');
                        } catch (\Throwable) {
                            $fallback = $this->SEO_General($title, $description, null, null, [
                                'og_description' => $ogDescription,
                                'og_image_alt' => $ogImageAlt,
                            ]);
                        }
                        return $this->renderSpa($fallback);
                    }

                    if ($this->contentTablesAvailable()) {
                        return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
                    }
                } elseif ($this->contentTablesAvailable()) {
                    return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
                }
            }

            // No couplet detail pages — avoid soft 404s from sitemap leftovers
            if ($type === 'couplets' && count($segments) >= 2) {
                return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
            }

            if (in_array($type, ['tag', 'topic'], true) && isset($segments[1]) && count($segments) === 2) {
                $hub = $this->resolveTopicHub($type, $segments[1], $locale);
                if ($hub) {
                    try {
                        $fallback = $this->SEO_TopicHub(
                            $type,
                            $hub['slug'],
                            $hub['name'],
                            $locale,
                            $hub['poets'],
                            $hub['alternateName'] ?? null
                        );
                    } catch (\Throwable) {
                        $fallback = $this->SEO_General($title, $description, null, null, [
                            'og_description' => $ogDescription,
                            'og_image_alt' => $ogImageAlt,
                        ]);
                    }

                    return $this->renderSpa($fallback);
                }

                if ($this->contentTablesAvailable()) {
                    return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
                }
            }
        }

        $isHome = $segments === [] || $segments === [''] || (count($segments) === 1 && ($segments[0] ?? '') === '');
        $listingKey = (count($segments) === 1) ? (string) ($segments[0] ?? '') : '';
        $knownListings = [
            'poets', 'poetry', 'couplets', 'genre', 'period', 'explore', 'prosody',
            'about', 'contact', 'help', 'privacy', 'terms', 'status', 'profile', 'settings',
        ];

        if ($isHome) {
            $fallback = $this->SEO_General($title, $description, null, null, [
                'og_description' => $ogDescription,
                'og_image_alt' => $ogImageAlt,
                'json_ld_type' => 'WebSite',
                'site_nav' => true,
                'fallback_html' => $this->homepageCrawlHtml($locale),
                'faqs' => AeoPlatformFaq::schema('home', $locale),
            ]);

            $feedPreloadUrl = '/api/v1/feed?lang=' . urlencode($locale) . '&page=1';
            $bootstrapFeed = $this->homepageBootstrapFeed($locale);

            return $this->renderSpa($fallback, [
                'feedPreloadUrl' => $feedPreloadUrl,
                'bootstrapFeed' => $bootstrapFeed,
            ]);
        }

        if ($listingKey !== '' && in_array($listingKey, $knownListings, true)) {
            try {
                $fallback = $this->SEO_Listing($listingKey, $locale);
            } catch (\Throwable) {
                $fallback = $this->SEO_General($title, $description, null, null, [
                    'og_description' => $ogDescription,
                    'og_image_alt' => $ogImageAlt,
                    'fallback_html' => $this->listingCrawlHtml($locale, $segments),
                ]);
            }

            return $this->renderSpa($fallback);
        }

        if ($listingKey !== '') {
            $genre = $this->safeFind(
                fn () => \App\Models\Categories::where('slug', $listingKey)->with('details')->first()
            );
            if ($genre) {
                try {
                    $fallback = $this->SEO_GenreCollection($genre, $locale);
                } catch (\Throwable) {
                    $fallback = $this->SEO_General($title, $description, null, null, [
                        'og_description' => $ogDescription,
                        'og_image_alt' => $ogImageAlt,
                        'fallback_html' => $this->listingCrawlHtml($locale, $segments),
                    ]);
                }

                return $this->renderSpa($fallback);
            }
        }

        $prefix = (string) ($segments[0] ?? '');
        if (in_array($prefix, ['auth', 'password-reset'], true)) {
            $fallback = $this->SEO_General($title, $description, null, null, [
                'og_description' => $ogDescription,
                'og_image_alt' => $ogImageAlt,
            ]);

            return $this->renderSpa($fallback);
        }

        // Entity URLs with the database down: keep the SPA shell instead of mass-404.
        if (in_array($prefix, ['poet', 'tag', 'topic'], true) && ! $this->contentTablesAvailable()) {
            $fallback = $this->SEO_General($title, $description, null, null, [
                'og_description' => $ogDescription,
                'og_image_alt' => $ogImageAlt,
            ]);

            return $this->renderSpa($fallback);
        }

        return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
    }

    /**
     * @return array{slug: string, name: string, poets: list<array{name: string, url: string}>}|null
     */
    private function resolveTopicHub(string $type, string $slug, string $locale): ?array
    {
        if ($type === 'tag') {
            $tag = $this->safeFind(
                fn () => Tags::where('slug', $slug)->with('details')->first()
            );
            if (!$tag) {
                return null;
            }
            $detail = $tag->details->firstWhere('lang', $locale) ?? $tag->details->first();
            $otherLang = $locale === 'sd' ? 'en' : 'sd';
            $other = $tag->details->firstWhere('lang', $otherLang)?->name;

            return [
                'slug' => (string) $tag->slug,
                'name' => (string) ($detail?->name ?: $tag->slug),
                'alternateName' => ($other && $other !== ($detail?->name ?? '')) ? (string) $other : null,
                'poets' => $this->hubPoetsForTag($tag, $locale),
            ];
        }

        $category = $this->safeFind(
            fn () => TopicCategory::where('slug', $slug)->with('details')->first()
        );
        if (!$category) {
            return null;
        }
        $detail = $category->details->firstWhere('lang', $locale) ?? $category->details->first();
        $otherLang = $locale === 'sd' ? 'en' : 'sd';
        $other = $category->details->firstWhere('lang', $otherLang)?->name;

        return [
            'slug' => (string) $category->slug,
            'name' => (string) ($detail?->name ?: $category->slug),
            'alternateName' => ($other && $other !== ($detail?->name ?? '')) ? (string) $other : null,
            'poets' => [],
        ];
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    private function hubPoetsForTag(Tags $tag, string $locale): array
    {
        try {
            $poets = Poets::query()
                ->where('visibility', 1)
                ->where('poet_tags', 'like', '%"' . $tag->id . '"%')
                ->with('all_details')
                ->limit(12)
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($poets as $poet) {
            $details = $poet->all_details->firstWhere('lang', $locale)
                ?? $poet->all_details->first();
            $name = (string) ($details?->poet_laqab ?: $details?->poet_name ?: $poet->poet_slug);
            $out[] = [
                'name' => $name,
                'url' => url("{$locale}/poet/{$poet->poet_slug}"),
            ];
        }

        return $out;
    }

    /**
     * First feed page for home HTML so the SPA can paint LCP text without waiting on XHR.
     */
    private function homepageBootstrapFeed(string $locale): ?array
    {
        try {
            $cached = app(StaticCacheService::class)->get("feed_page_1_{$locale}");
            if (!is_array($cached) || $cached === []) {
                return null;
            }

            return [
                'lang' => $locale,
                'payload' => [
                    'data' => $cached,
                    'current_page' => 1,
                    'last_page' => 2,
                    'total' => 100,
                ],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Crawlable HTML for the homepage (outside noscript) so SEO tools see H1, copy, and internal links.
     */
    private function homepageCrawlHtml(string $locale): string
    {
        $isSd = $locale === 'sd';
        $base = '/' . $locale;

        if ($isSd) {
            $html = <<<HTML
<h2>آرڪائيو بابت</h2>
<p>باک سنڌي شاعريءَ جو کليل ڊجيٽل آرڪائيو آھي، جتي شاھ عبداللطيف ڀٽائي، سچل سرمست، شيخ اياز ۽ ٻين کلاسيڪي توڙي جديد شاعرن جي ڪلام کي محفوظ ڪيو وڃي ٿو. ھي سائيٽ غزل، بيت، وايون، نظم ۽ ٻين صنفن کي پڙھڻ، ڳولھڻ ۽ شيئر ڪرڻ لاءِ ٺھيل آھي، ته جيئن ايندڙ نسلن کي سنڌي ادب جي ھن خزانو تائين آسان رسائي ملي.</p>
<p>باک هڪ غير منافع بخش ادبي منصوبو آهي. آرڪائيو جو مقصد سنڌي ٻوليءَ جي شعري ورثي کي آن لائن محفوظ رکڻ، رومن رسم الخط سان گڏ اصل سنڌي متن مهيا ڪرڻ، ۽ دنيا جي پڙهندڙن، شاگردن ۽ محققن تائين پهچائڻ آهي.</p>
<h2>مجموعا</h2>
<h3>شاعر</h3>
<p>هر شاعر جي پروفائل تي سوانح، صنفون، ڪتاب ۽ ڪلام جو فهرص ملي ٿو. پڙهڻ لاءِ <a href="{$base}/poets">شاعرن جو فهرص</a> کوليو.</p>
<h3>شاعري ۽ بند</h3>
<p>مڪمل غزل، بيت، وايون ۽ نظم اصل رسم الخط ۽ رومن ۾ پڙهو. <a href="{$base}/poetry">شاعري</a> ۽ <a href="{$base}/couplets">بند</a> صفحا ڏسو.</p>
<h3>صنفون، دور ۽ موضوع</h3>
<p>غزل کان نظم تائين <a href="{$base}/genre">صنفون</a>، تاريخي <a href="{$base}/period">دور</a>، ۽ <a href="{$base}/explore">موضوع</a> سان دريافت ڪريو.</p>
<h2>ٻوليون</h2>
<p>هر صفحو سنڌي (<code>sd</code>) ۽ انگريزي (<code>en</code>) ۾ موجود آهي. ايجنٽس لاءِ <a href="/llms.txt">llms.txt</a> ۽ <a href="/sitemap.xml">sitemap.xml</a> استعمال ڪريو. رابطو: <a href="{$base}/contact">رابطو</a> يا support@baakh.com.</p>
<nav aria-label="مکيه لنڪس">
    <h2>ڳولا ۽ صفحا</h2>
    <ul>
        <li><a href="{$base}/poets">شاعر</a></li>
        <li><a href="{$base}/poetry">شاعري</a></li>
        <li><a href="{$base}/genre">صنفون</a></li>
        <li><a href="{$base}/couplets">بند</a></li>
        <li><a href="{$base}/explore">ڳولا</a></li>
        <li><a href="{$base}/period">دور</a></li>
        <li><a href="{$base}/about">باک بابت</a></li>
        <li><a href="{$base}/contact">رابطو</a></li>
        <li><a href="{$base}/privacy">رازداري</a></li>
        <li><a href="/en">English</a></li>
    </ul>
</nav>
HTML;

            return $html . AeoPlatformFaq::html('home', $locale);
        }

        $html = <<<HTML
<h2>About the archive</h2>
<p>Baakh is an open digital archive of Sindhi poetry. It preserves classical and modern works — from Shah Abdul Latif Bhittai, Sachal Sarmast, and Shaikh Ayaz to contemporary poets — so readers can discover ghazals, baits, waee, nazms, and other forms in one place, with original Sindhi script and Roman transliteration.</p>
<p>The archive is a non-profit literary project. Its purpose is to keep Sindhi poetic heritage accessible online for students, researchers, and readers around the world, with stable permanent URLs for each poet and poem.</p>
<h2>Collections</h2>
<h3>Poets</h3>
<p>Each poet profile includes biography, genres, books, and an index of works. Start from the <a href="{$base}/poets">poets index</a>.</p>
<h3>Poetry and couplets</h3>
<p>Read full ghazals, baits, waee, and nazms in original script and Roman Sindhi. Browse <a href="{$base}/poetry">poetry</a> and <a href="{$base}/couplets">couplets</a>.</p>
<h3>Genres, periods, and topics</h3>
<p>Explore <a href="{$base}/genre">poetic genres</a>, historical <a href="{$base}/period">periods</a>, and <a href="{$base}/explore">topics</a> from love and homeland to devotion and modern free verse.</p>
<h2>Languages and agents</h2>
<p>Every public page has a Sindhi (<code>sd</code>) and English (<code>en</code>) locale. Agents should use <a href="/llms.txt">llms.txt</a> and <a href="/sitemap.xml">sitemap.xml</a> rather than probing unknown paths. Unknown URLs return HTTP 404. Contact: <a href="{$base}/contact">Contact</a> or support@baakh.com.</p>
<nav aria-label="Primary">
    <h2>Explore Baakh</h2>
    <ul>
        <li><a href="{$base}/poets">Poets</a></li>
        <li><a href="{$base}/poetry">Poetry</a></li>
        <li><a href="{$base}/genre">Genres</a></li>
        <li><a href="{$base}/couplets">Couplets</a></li>
        <li><a href="{$base}/explore">Explore</a></li>
        <li><a href="{$base}/period">Periods</a></li>
        <li><a href="{$base}/about">About</a></li>
        <li><a href="{$base}/contact">Contact</a></li>
        <li><a href="{$base}/privacy">Privacy</a></li>
        <li><a href="/sd">سنڌي</a></li>
    </ul>
</nav>
HTML;

        return $html . AeoPlatformFaq::html('home', $locale);
    }

    private function listingCrawlHtml(string $locale, array $segments): string
    {
        $base = '/' . $locale;
        $isSd = $locale === 'sd';

        $links = $isSd
            ? [
                'poets' => 'شاعر',
                'poetry' => 'شاعري',
                'couplets' => 'بند',
                'explore' => 'ڳولا',
                'about' => 'باک بابت',
                'contact' => 'رابطو',
                'privacy' => 'رازداري',
            ]
            : [
                'poets' => 'Poets',
                'poetry' => 'Poetry',
                'couplets' => 'Couplets',
                'explore' => 'Explore',
                'about' => 'About',
                'contact' => 'Contact',
                'privacy' => 'Privacy',
            ];

        $items = '';
        foreach ($links as $slug => $label) {
            $items .= '<li><a href="' . e($base . '/' . $slug) . '">' . e($label) . '</a></li>';
        }

        $heading = $isSd ? 'باک جا صفحا' : 'Browse Baakh';
        $homeLabel = $isSd ? 'گھر' : 'Home';

        return '<h2>' . e($heading) . '</h2><nav><ul>'
            . '<li><a href="' . e($base) . '">' . e($homeLabel) . '</a></li>'
            . $items
            . '</ul></nav>';
    }

    private function spaNotFound(string $title, string $description, string $ogDescription, string $ogImageAlt)
    {
        $request = request();
        $produces = ['text/html', 'text/markdown'];
        $type = $this->wantsAgentMarkdown($request) ? 'text/markdown' : $this->pickType($request, $produces);
        if ($type === null) {
            return $this->notAcceptableResponse($request, $produces);
        }

        $path = $request->path();
        $markdown = SeoMarkdown::notFound($path);

        if ($type === 'text/markdown') {
            return $this->withAcceptVary(
                response($markdown, 404)
                    ->header('Content-Type', 'text/markdown; charset=utf-8')
                    ->header('Cache-Control', 'no-store')
            );
        }

        return $this->withAcceptVary(
            response()
                ->view('errors.agent-404', ['path' => $path], 404)
                ->header('Cache-Control', 'no-store')
        );
    }

    /**
     * Dispatch the SPA shell with a native Blade SEO payload, or Markdown when negotiated.
     */
    private function renderSpa(array $fallback, array $viewData = [], int $status = 200)
    {
        $request = request();
        $produces = ['text/html', 'text/markdown'];
        $type = $this->wantsAgentMarkdown($request) ? 'text/markdown' : $this->pickType($request, $produces);
        if ($type === null) {
            return $this->notAcceptableResponse($request, $produces);
        }

        $seoData = $this->buildSeoData($fallback);

        if ($type === 'text/markdown') {
            return $this->withAcceptVary(
                response(SeoMarkdown::fromSeoData($seoData), $status)
                    ->header('Content-Type', 'text/markdown; charset=utf-8')
            );
        }

        return $this->withAcceptVary(
            response()->view('app', array_merge(
                compact('fallback', 'seoData'),
                $viewData
            ), $status)
        );
    }

    private function wantsAgentMarkdown(Request $request): bool
    {
        if (strtolower((string) $request->query('mode')) === 'agent') {
            return true;
        }

        return str_ends_with(strtolower($request->getPathInfo()), '.md');
    }

    private function safeFind(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (QueryException) {
            return null;
        }
    }

    private function contentTablesAvailable(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('poets')
                && \Illuminate\Support\Facades\Schema::hasTable('poetry');
        } catch (QueryException) {
            return false;
        }
    }
}
