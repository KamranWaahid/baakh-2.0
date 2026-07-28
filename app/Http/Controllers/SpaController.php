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
        $title = $isSd ? 'باک - سنڌي شاعريءَ جو آرڪائيو' : 'Baakh - Sindhi Poetry Archive';
        $description = $isSd
            ? 'باک سنڌي شاعريءَ جو ھڪ ڊجيٽل آرڪائيو آھي، جيڪو ڪلاسيڪي ۽ جديد شاعريءَ کي محفوظ ڪري ٿو.'
            : 'Baakh is a digital archive of Sindhi poetry, preserving classical and modern literary works for future generations.';

        $ogDescription = $isSd
            ? 'سنڌي شاعريءَ جو ڊجيٽل آرڪائيو — ڪلاسيڪي کان جديد دور تائين.'
            : 'A digital archive dedicated to preserving Sindhi poetry from classical to modern eras.';

        $ogImageAlt = $isSd ? 'باک - سنڌي شاعريءَ جو آرڪائيو' : 'Baakh - Sindhi Poetry Archive';

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
                        $ogImageUrl = asset('assets/og/baakh-1200x630.png');
                        $fallback = $this->SEO_Poetry($poetry, $categorySlug, $poet, $ogImageUrl);
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
                        $fallback = $this->SEO_Poet($poet, '');
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

        $fallback = $this->SEO_General($title, $description, null, null, [
            'og_description' => $ogDescription,
            'og_image_alt' => $ogImageAlt,
        ]);

        return view('app', compact('fallback'));
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
