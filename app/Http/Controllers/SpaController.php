<?php

namespace App\Http\Controllers;

use App\Models\Poets;
use App\Models\Poetry;
use App\Traits\BaakhSeoTrait;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    use BaakhSeoTrait;

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
                        fn () => Poetry::where('poetry_slug', $poemSlug)->first()
                    );
                    $poet = $poetry ? $poetry->poet : null;
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
                        return view('app', compact('fallback'));
                    }

                    // Only emit a hard 404 when the lookup succeeded and found nothing.
                    if ($poetry === null && $this->contentTablesAvailable()) {
                        return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
                    }
                } else {
                    // Case 2: /:lang/poet/:slug
                    $poet = $this->safeFind(
                        fn () => Poets::where('poet_slug', $poetSlug)->first()
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
                        return view('app', compact('fallback'));
                    }

                    if ($this->contentTablesAvailable()) {
                        return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
                    }
                }
            }

            // No couplet detail pages — avoid soft 404s from sitemap leftovers
            if ($type === 'couplets' && count($segments) >= 2) {
                return $this->spaNotFound($title, $description, $ogDescription, $ogImageAlt);
            }
        }

        $isHome = $segments === [] || $segments === [''] || (count($segments) === 1 && ($segments[0] ?? '') === '');
        $fallback = $this->SEO_General($title, $description, null, null, [
            'og_description' => $ogDescription,
            'og_image_alt' => $ogImageAlt,
            'fallback_html' => $isHome
                ? $this->homepageCrawlHtml($locale)
                : $this->listingCrawlHtml($locale, $segments),
        ]);

        return view('app', compact('fallback'));
    }

    /**
     * Crawlable HTML for the homepage (outside noscript) so SEO tools see H1, copy, and internal links.
     */
    private function homepageCrawlHtml(string $locale): string
    {
        $isSd = $locale === 'sd';
        $base = '/' . $locale;

        if ($isSd) {
            $intro = <<<'HTML'
<p>باک سنڌي شاعريءَ جو کليل ڊجيٽل آرڪائيو آھي، جتي شاھ عبداللطيف ڀٽائي، سچل سرمست، شيخ اياز ۽ ٻين کلاسيڪي توڙي جديد شاعرن جي ڪلام کي محفوظ ڪيو وڃي ٿو. ھي سائيٽ غزل، بيت، وايون، نظم ۽ ٻين صنفن کي پڙھڻ، ڳولھڻ ۽ شيئر ڪرڻ لاءِ ٺھيل آھي، ته جيئن ايندڙ نسلن کي سنڌي ادب جي ھن خزانو تائين آسان رسائي ملي.</p>
<p>باک تي توھان شاعرن جي سوانح، دور ۽ صنف مطابق شاعري پڙھي سگھو ٿا. آرڪائيو جو مقصد سنڌي ٻوليءَ جي ادبي ورثي کي آن لائن محفوظ رکڻ ۽ دنيا جي پڙھندڙن تائين پھچائڻ آھي.</p>
HTML;
            $nav = <<<HTML
<nav aria-label=" fortني لنڪس">
    <h2>ڳولا ۽ صفحا</h2>
    <ul>
        <li><a href="{$base}/poets">شاعر</a></li>
        <li><a href="{$base}/poetry">شاعري</a></li>
        <li><a href="{$base}/couplets">بند</a></li>
        <li><a href="{$base}/explore">ڳولا</a></li>
        <li><a href="{$base}/period">دور</a></li>
        <li><a href="{$base}/about">باک بابت</a></li>
        <li><a href="/en">English</a></li>
    </ul>
</nav>
HTML;
        } else {
            $intro = <<<'HTML'
<p>Baakh is an open digital archive of Sindhi poetry. It preserves classical and modern works — from Shah Abdul Latif Bhittai, Sachal Sarmast, and Shaikh Ayaz to contemporary poets — so readers can discover ghazals, baits, waee, nazms, and other forms in one place.</p>
<p>Browse poets, genres, and historical periods, read full poems and couplets, and explore Sindhi literary heritage online. Baakh exists to keep this tradition accessible for future generations and for readers around the world.</p>
HTML;
            $nav = <<<HTML
<nav aria-label="Primary">
    <h2>Explore Baakh</h2>
    <ul>
        <li><a href="{$base}/poets">Poets</a></li>
        <li><a href="{$base}/poetry">Poetry</a></li>
        <li><a href="{$base}/couplets">Couplets</a></li>
        <li><a href="{$base}/explore">Explore</a></li>
        <li><a href="{$base}/period">Periods</a></li>
        <li><a href="{$base}/about">About</a></li>
        <li><a href="/sd">سنڌي</a></li>
    </ul>
</nav>
HTML;
        }

        return $intro . $nav;
    }

    /**
     * Minimal crawlable nav for non-entity listing routes inside the SPA.
     */
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
            ]
            : [
                'poets' => 'Poets',
                'poetry' => 'Poetry',
                'couplets' => 'Couplets',
                'explore' => 'Explore',
                'about' => 'About',
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
        $fallback = $this->SEO_General(
            '404 — ' . $title,
            $description,
            null,
            null,
            [
                'og_description' => $ogDescription,
                'og_image_alt' => $ogImageAlt,
            ]
        );

        SEOMeta::setRobots('noindex, follow');

        return response()->view('app', compact('fallback'), 404);
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
