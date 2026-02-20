<?php

namespace App\Http\Controllers;

use App\Models\Poets;
use App\Models\Poetry;
use App\Models\Categories;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpaController extends Controller
{
    use BaakhSeoTrait;

    public function index(Request $request, $any = null)
    {
        $locale = app()->getLocale();
        $path = $request->path();

        // Handle Language Prefix (if present)
        $segments = explode('/', $path);
        if ($segments[0] === 'en' || $segments[0] === 'sd') {
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
            $type = $segments[0]; // 'poet', 'poets', 'poetry', etc.

            if ($type === 'poet' && isset($segments[1])) {
                $poetSlug = $segments[1];

                // Case 1: /:lang/poet/:slug/:category/:poemSlug (Single Poem Page)
                if (count($segments) >= 4) {
                    $categorySlug = $segments[2];
                    $poemSlug = $segments[3];
                    $poetry = Poetry::where('poetry_slug', $poemSlug)->first();
                    $poet = $poetry ? $poetry->poet : null;
                    if ($poetry && $poet) {
                        $ogImageUrl = route('og.poetry', ['slug' => $poemSlug]);
                        $this->SEO_Poetry($poetry, $categorySlug, $poet, $ogImageUrl);
                        return view('app');
                    }
                }

                // Case 2: /:lang/poet/:slug (Poet Profile Page)
                $poet = Poets::where('poet_slug', $poetSlug)->first();
                if ($poet) {
                    $this->SEO_Poet($poet, '');
                    return view('app');
                }
            }
        }

        // Default SEO for other pages
        $this->SEO_General($title, $description, null, null, [
            'og_description' => $ogDescription,
            'og_image_alt' => $ogImageAlt
        ]);

        return view('app');
    }
}
