<?php
namespace App\Traits;

use App\Enums\CategoryGenderEnum;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\Poets;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;


trait BaakhSeoTrait
{

    public function SEO_General($title, $desc, $seo_image = null, $keywords = null, $additionalData = [])
    {
        // Set default image if SEO image is not provided or it's not a path (might be a URL)
        $image = $seo_image ? $seo_image : asset('assets/og/baakh-og-v2-1200x630.png');

        $currentLang = app()->getLocale();
        $isSd = $currentLang === 'sd';

        // hreflang setup
        $path = request()->path();
        $segments = explode('/', $path);

        // Remove existing locale prefix if any to build base path
        if ($segments[0] === 'en' || $segments[0] === 'sd') {
            array_shift($segments);
        }
        $innerPath = trim(implode('/', $segments), '/');

        $sdUrl = $innerPath === '' ? url('/sd') : url('/sd/' . $innerPath);
        $enUrl = $innerPath === '' ? url('/en') : url('/en/' . $innerPath);
        $canonical = url()->current();

        // Handle keywords
        if (!is_null($keywords)) {
            SEOMeta::addKeyword($keywords);
        } else {
            SEOMeta::addKeyword($this->extractKeywords($desc));
        }

        // Set SEO meta tags
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($desc);
        SEOMeta::setCanonical($canonical);

        // Alternate languages — always include a self-referential hreflang
        SEOMeta::addAlternateLanguage('en', $enUrl);
        SEOMeta::addAlternateLanguage('sd', $sdUrl);
        SEOMeta::addAlternateLanguage('x-default', $sdUrl);
        if ($canonical !== $enUrl && $canonical !== $sdUrl) {
            SEOMeta::addAlternateLanguage($currentLang === 'sd' ? 'sd' : 'en', $canonical);
        }

        // Set OpenGraph data
        OpenGraph::setDescription($additionalData['og_description'] ?? $desc);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl(url()->current());
        OpenGraph::addImage($image, ['height' => 630, 'width' => 1200]);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::setSiteName($isSd ? 'باک' : 'Baakh');

        if (isset($additionalData['og_image_alt'])) {
            OpenGraph::addProperty('image:alt', $additionalData['og_image_alt']);
        } else {
            OpenGraph::addProperty('image:alt', $title);
        }

        TwitterCard::setType('summary_large_image');
        TwitterCard::setTitle($title);
        TwitterCard::setDescription($additionalData['og_description'] ?? $desc);
        TwitterCard::setImage($image);
        TwitterCard::setUrl(url()->current());
        TwitterCard::setSite('@BaakhConnect');

        // You can allow additional OpenGraph properties from controller via $additionalData['opengraph']
        if (isset($additionalData['opengraph']) && is_array($additionalData['opengraph'])) {
            foreach ($additionalData['opengraph'] as $property => $value) {
                OpenGraph::addProperty($property, $value);
            }
        }

        // Set JSON-LD structured data (default for WebPage or FAQPage)
        JsonLd::addValue('@context', 'https://schema.org');
        JsonLd::addValue('@type', $additionalData['json_ld_type'] ?? 'WebPage');
        JsonLd::addValue('name', $isSd ? 'باک' : 'Baakh');
        JsonLd::addValue('inLanguage', $currentLang);
        JsonLd::addValue('description', $desc);
        JsonLd::addValue('url', url()->current());
        JsonLd::addValue('image', $image);

        // Allow additional JSON-LD properties from controller
        if (isset($additionalData['jsonld']) && is_array($additionalData['jsonld'])) {
            foreach ($additionalData['jsonld'] as $property => $value) {
                JsonLd::addValue($property, $value);
            }
        }

        // Set general titles and descriptions for JSON-LD
        JsonLd::setTitle($title);
        JsonLd::setDescription($desc);
        JsonLd::setType($additionalData['json_ld_type'] ?? 'WebPage');

        return [
            'title' => $title,
            'description' => $desc,
            'html' => $additionalData['fallback_html'] ?? ''
        ];
    }


    /**
     * Add Two Keywords Dynamically
     */
    public function appendKeywords($array)
    {
        $keywords = ['Books on Literature', 'Sindhi Books', 'Poetry', 'History Books', 'Fiction Books', 'Sheikh Ayaz', 'Sindh Salamat Kitab Ghar', 'Sindhi Novel'];
        if ($array && is_array($array)) {
            $keywords = array_merge($keywords, $array);
            $keywords = array_slice($keywords, 0, 10);
        }
        return implode(', ', $keywords);
    }
    /**
     * Extract Keywords from description
     */
    public function extractKeywords($content)
    {
        // Convert content to lowercase to ensure case-insensitive matching
        $content = strtolower($content);

        // Remove HTML tags from the content
        $content = strip_tags($content);

        // Remove punctuation and special characters
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', '', $content);

        // Split content into words
        $words = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        // Define common stop words to exclude from keywords
        $stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'is', 'are', 'was', 'were', 'am', 'been', 'be', 'as'];

        // Remove stop words from the list of words
        $filteredWords = array_diff($words, $stopWords);

        // Count word frequencies
        $wordFrequencies = array_count_values($filteredWords);

        // Sort word frequencies in descending order
        arsort($wordFrequencies);

        // Extract top keywords (e.g., top 5)
        $topKeywords = array_slice(array_keys($wordFrequencies), 0, 5);

        return $topKeywords;
    }

    /**
     * SEO Author
     */
    public function SEO_Poet(Poets $poetModel, $category)
    {
        $poetIdWithLang = $poetModel->id . '_lng_' . app()->getLocale();
        $cacheKeyLocations = 'cache_poet_' . $poetIdWithLang . '_locations';

        $poet = $poetModel;

        $poetDetails = $this->poetDetailsForLocale($poet);
        if (!$poetDetails) {
            $currentLang = app()->getLocale();
            $name = $poet->poet_slug;
            $url = url("{$currentLang}/poet/{$poet->poet_slug}");
            $title = trans('labels.seo_title_poet', ['poetLaqab' => $name]);
            $shortBio = $title;
            SEOMeta::setTitle($title);
            SEOMeta::setDescription($shortBio);
            SEOMeta::setCanonical($url);

            return [
                'title' => $title,
                'description' => $shortBio,
                'html' => '<h2>' . e($name) . '</h2>',
            ];
        }

        $locations = $this->cachedPoetLocations($cacheKeyLocations, $poetDetails);

        $poetImage = $poet->poet_pic;
        $poetLaqab = (string) ($poetDetails->poet_laqab ?? $poet->poet_slug);
        $final_name = mb_substr($poetLaqab, -1) == 'و' ? mb_substr($poetLaqab, 0, -1) . 'ي' : $poetLaqab;
        $poet_name = (string) ($poetDetails->poet_name ?? '');
        $tagline = $poetDetails->tagline;
        $currentLang = app()->getLocale();

        if ($category != '') {
            $title = trans('labels.seo_title_poet_category', ['categoryName' => $category, 'poetLaqab' => $final_name]);
        } else {
            $title = trans('labels.seo_title_poet', ['poetLaqab' => $final_name]);
        }


        // $name_en = $author->name_en; // Author's name in English
        $bio = strip_tags((string) ($poetDetails->poet_bio ?? ''));
        $shortBio = Str::limit($bio, 161);
        $currentLang = app()->getLocale();
        $alternateLang = $currentLang === 'en' ? 'sd' : 'en';

        if (request()->query('lang')) {
            $alternateUrl = url("{$alternateLang}/poet/{$poet->poet_slug}");
        } else {
            $alternateUrl = url("{$alternateLang}/poet/{$poet->poet_slug}");
        }

        $url = url("{$currentLang}/poet/{$poet->poet_slug}");


        $birthDate = $poet->date_of_birth;
        $deathDate = $poet->date_of_death;

        // Keywords
        $keywords = $this->appendKeywords([$poet_name, $poetLaqab . '\'s Poetry']);

        // Link previews (WhatsApp, etc.): Baakh logo on white
        $ogImage = asset('assets/og/baakh-og-v2-1200x630.png');

        // SEO metadata
        SEOTools::addImages($ogImage);
        SEOMeta::setTitle($title); // Set title in Sindhi
        SEOMeta::setDescription($shortBio);
        SEOMeta::setCanonical($url);
        SEOMeta::addAlternateLanguage('en', $currentLang === 'en' ? $url : $alternateUrl);
        SEOMeta::addAlternateLanguage('sd', $currentLang === 'sd' ? $url : url("sd/poet/{$poet->poet_slug}"));
        SEOMeta::addAlternateLanguage('x-default', url("sd/poet/{$poet->poet_slug}"));
        SEOMeta::addKeyword($keywords);

        // OpenGraph Metadata
        OpenGraph::setTitle($title);
        OpenGraph::setDescription($shortBio);
        OpenGraph::setType('profile');
        OpenGraph::setUrl($url);
        OpenGraph::addImage($ogImage, ['height' => 630, 'width' => 1200]);
        OpenGraph::setArticle([
            'author' => $poetLaqab,
            'section' => 'Authors',
            'tag' => $keywords,
        ]);

        TwitterCard::setType('summary_large_image');
        TwitterCard::addValue('twitter:domain', 'baakh.com');
        TwitterCard::setTitle($poetLaqab);
        TwitterCard::setImage($ogImage);
        TwitterCard::setDescription($shortBio);
        TwitterCard::setUrl($url);
        TwitterCard::setSite('@BaakhConnect');

        // JSON-LD structured data for authors
        JsonLd::setTitle($poetLaqab);
        JsonLd::setDescription($shortBio);
        JsonLd::setType('Person');
        JsonLd::addImage(asset($poetImage));
        JsonLd::setUrl($url);
        JsonLd::addValue('inLanguage', app()->getLocale());
        JsonLd::addValue('name', $poetLaqab);
        JsonLd::addValue('alternateName', $poet_name); // Adding English name
        JsonLd::addValue('birthDate', $birthDate);
        JsonLd::addValue('knowsAbout', 'poetry,poems');
        if ($deathDate) {
            JsonLd::addValue('deathDate', $deathDate);
        }

        $jsonLdData = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'givenName' => $poetLaqab,
            'familyName' => $poet_name,
            'additionalName' => $tagline,
            'birthDate' => $poetLaqab,
        ];

        // birth / death place (incomplete geo data must not break the page)
        if (!empty($locations['birth']['cityName'])) {
            $jsonLdData['birthDate'] = $birthDate;
            $_add_birth = $locations['birth'];
            $_brth_city = $_add_birth['cityName'];
            $_brth_prov = $_add_birth['provinceName'];
            $_brth_cntry = $_add_birth['countryName'];
            $_complete_addr_brth = $_brth_city . ', ' . $_brth_prov . ', ' . $_brth_cntry;
            $jsonLdData['birthPlace'] = [
                '@context' => 'https://schema.org',
                '@type' => 'Place',
                'address' => $_complete_addr_brth
            ];
        }

        if (!empty($locations['death']['cityName'])) {
            $jsonLdData['deathDate'] = $deathDate;
            $_add_death = $locations['death'];
            $_ddth_city = $_add_death['cityName'];
            $_ddth_prov = $_add_death['provinceName'];
            $_ddth_cntry = $_add_death['countryName'];
            $_complete_addr_ddth = $_ddth_city . ', ' . $_ddth_prov . ', ' . $_ddth_cntry;

            $jsonLdData['deathPlace'] = [
                '@context' => 'https://schema.org',
                '@type' => 'Place',
                'address' => $_complete_addr_ddth
            ];
        }

        JsonLd::addValues($jsonLdData);

        $jsonLdBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url("{$currentLang}/")
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Poets',
                    'item' => url("{$currentLang}/poets")
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $poetLaqab,
                    'item' => $url
                ]
            ]
        ];
        JsonLd::addValues($jsonLdBreadcrumb);

        return [
            'title' => $title,
            'description' => $shortBio,
            'html' => '<h2>' . e($poetLaqab) . '</h2><p>' . nl2br(e($bio)) . '</p>'
        ];
    }

    /**
     * SEO Poetry page
     */
    public function SEO_Poetry(Poetry $poetry, $poetryCategory, Poets $poetModel, $seo_image = null)
    {
        // dd($poetry, $couplets, $poetModel);
        $currentLang = app()->getLocale();
        $poetIdWithLang = $poetModel->id . '_lng_' . $currentLang;
        $cacheKeyLocations = 'cache_poet_' . $poetIdWithLang . '_locations';
        $poetryInfo = $poetry->info;
        $poetDetails = $this->poetDetailsForLocale($poetModel);

        $p_category = $poetry->category;
        if (!$p_category || !$poetDetails) {
            $slugTitle = (string) ($poetry->poetry_title ?: $poetry->poetry_slug);
            $catSeg = $p_category?->slug ?? $poetryCategory;
            $url = url("{$currentLang}/poet/{$poetModel->poet_slug}/{$catSeg}/{$poetry->poetry_slug}");
            SEOMeta::setTitle($slugTitle);
            SEOMeta::setDescription($slugTitle);
            SEOMeta::setCanonical($url);

            return [
                'title' => $slugTitle,
                'description' => $slugTitle,
                'html' => '<h2>' . e($slugTitle) . '</h2>',
            ];
        }

        $couplets = $poetry->all_couplets;

        $poetLaqab = (string) ($poetDetails->poet_laqab ?? $poetModel->poet_slug);

        $final_name = mb_substr($poetLaqab, -1) == 'و' ? mb_substr($poetLaqab, 0, -1) . 'ي' : $poetLaqab;

        $gender = $p_category->gender ? CategoryGenderEnum::tryFrom($p_category->gender) : null;

        if ($currentLang === 'en') {
            $poetName = $poetLaqab . "'s";
        } else {
            $poetName = $final_name . ($gender ? ' ' . $gender->singular() : '');
        }

        $categoryName = (string) ($p_category->category_name ?? $p_category->slug ?? $poetryCategory);
        $categorySlug = (string) ($p_category->slug ?? $poetryCategory);
        $poemTitle = (string) ($poetryInfo->title ?? $poetry->poetry_title ?? $poetry->poetry_slug);

        $title = trans('labels.seo_custom_bio_poetry', ['category' => $categoryName, 'poetName' => $poetName, 'title' => $poemTitle]);
        $stanzas = [];
        $fallbackHtml = '<h2>' . e($title) . '</h2>';
        // if there is no info then make it from couplets
        if ($poetryInfo && ($poetryInfo->info != null && $poetryInfo->info != '')) {
            $shortBio = $poetryInfo->info . ' ' . ($poetryInfo->source ?? '');
            $fallbackHtml .= '<p>' . nl2br(e($shortBio)) . '</p>';
        } else {
            if (count($couplets) > 0) {
                $fallbackHtml .= '<ul>';
                foreach ($couplets as $couplet) {
                    $stanzas[] = [
                        '@type' => 'CreativeWork',
                        'text' => $couplet->couplet_text
                    ];
                    $fallbackHtml .= '<li>' . strip_tags((string) $couplet->couplet_text) . '</li>';
                }
                $fallbackHtml .= '</ul>';
                $shortBio = Str::limit(preg_replace('/\s+/', ' ', strip_tags((string) $couplets[0]->couplet_text)), 160, '...');
            } else {
                $shortBio = $title;
            }
        }

        $poetImage = $poetModel->poet_pic;
        $tags = $poetry->poetry_tags;
        if (is_string($tags)) {
            $tags = json_decode($tags, true);
        }
        $keywords = $this->appendKeywords(is_array($tags) ? $tags : null);

        $url = url("{$currentLang}/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}");

        // WhatsApp / social link previews: Baakh logo on white (not poetry card or poet photo).
        $image = $seo_image ? $seo_image : asset('assets/og/baakh-og-v2-1200x630.png');

        SEOTools::addImages($image);
        SEOMeta::setTitle($title); // Set title in Sindhi
        SEOMeta::setDescription($shortBio);
        SEOMeta::setCanonical($url);
        SEOMeta::addAlternateLanguage(
            'en',
            $currentLang === 'en'
                ? $url
                : url("en/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}")
        );
        SEOMeta::addAlternateLanguage(
            'sd',
            $currentLang === 'sd'
                ? $url
                : url("sd/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}")
        );
        SEOMeta::addAlternateLanguage(
            'x-default',
            url("sd/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}")
        );
        SEOMeta::addKeyword($keywords);

        // OpenGraph Metadata
        OpenGraph::setTitle($title);
        OpenGraph::setDescription($shortBio);
        OpenGraph::setType('webpage');
        OpenGraph::setUrl($url);
        OpenGraph::addImage($image, ['height' => 630, 'width' => 1200]);


        TwitterCard::setType('summary_large_image');
        TwitterCard::addValue('twitter:domain', 'baakh.com');
        TwitterCard::setTitle($title);
        TwitterCard::setImage($image);
        TwitterCard::setDescription($shortBio);
        TwitterCard::setUrl($url);
        TwitterCard::setSite('@BaakhConnect');

        // SEO for Poet in the Poetry
        $locations = $this->cachedPoetLocations($cacheKeyLocations, $poetDetails);

        $poetLaqab = (string) ($poetDetails->poet_laqab ?? $poetModel->poet_slug);
        $poet_name = (string) ($poetDetails->poet_name ?? '');
        $tagline = $poetDetails->tagline;
        $birthDate = $poetModel->date_of_birth;
        $deathDate = $poetModel->date_of_death;

        // main info
        JsonLd::setTitle($poetLaqab);
        JsonLd::setDescription($shortBio);
        JsonLd::setType('CreativeWork');
        JsonLd::addImage(asset($poetImage));
        JsonLd::setUrl($url);
        JsonLd::addValue('inLanguage', app()->getLocale());


        $jsonLdPoetData = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'givenName' => $poetLaqab,
            'familyName' => $poet_name,
            'additionalName' => $tagline,
            'name' => $poetLaqab,
            'url' => url("{$currentLang}/poet/{$poetModel->poet_slug}"),
            'image' => asset($poetImage),
        ];


        // death place
        if (!empty($locations['birth']['cityName'])) {
            $jsonLdPoetData['birthDate'] = $birthDate;
            $_add_birth = $locations['birth'];
            $_brth_city = $_add_birth['cityName'];
            $_brth_prov = $_add_birth['provinceName'];
            $_brth_cntry = $_add_birth['countryName'];
            $_complete_addr_brth = $_brth_city . ', ' . $_brth_prov . ', ' . $_brth_cntry;
            $jsonLdPoetData['birthPlace'] = [
                '@context' => 'https://schema.org',
                '@type' => 'Place',
                'address' => $_complete_addr_brth
            ];
        }

        if (!empty($locations['death']['cityName'])) {
            $jsonLdPoetData['deathDate'] = $deathDate;
            $_add_death = $locations['death'];
            $_ddth_city = $_add_death['cityName'];
            $_ddth_prov = $_add_death['provinceName'];
            $_ddth_cntry = $_add_death['countryName'];
            $_complete_addr_ddth = $_ddth_city . ', ' . $_ddth_prov . ', ' . $_ddth_cntry;

            $jsonLdPoetData['deathPlace'] = [
                '@context' => 'https://schema.org',
                '@type' => 'Place',
                'address' => $_complete_addr_ddth
            ];
        }


        $jsonLdPoetryWork = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $title,
            'author' => $jsonLdPoetData,
            'hasPart' => $stanzas
        ];
        JsonLd::addValues($jsonLdPoetryWork);

        $jsonLdBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url("{$currentLang}/")
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Poets',
                    'item' => url("{$currentLang}/poets")
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $poetLaqab,
                    'item' => url("{$currentLang}/poet/{$poetModel->poet_slug}")
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $categoryName,
                    'item' => url("{$currentLang}/poet/{$poetModel->poet_slug}/{$categorySlug}")
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 5,
                    'name' => $title,
                    'item' => $url
                ]
            ]
        ];
        JsonLd::addValues($jsonLdBreadcrumb);

        return [
            'title' => $title,
            'description' => $shortBio,
            'html' => $fallbackHtml
        ];
    }

    /**
     * Prefer locale-specific poet detail row; fall back to any available translation.
     */
    private function poetDetailsForLocale(Poets $poet): ?\App\Models\PoetsDetail
    {
        $locale = app()->getLocale();

        try {
            return $poet->all_details->firstWhere('lang', $locale)
                ?? $poet->all_details->first()
                ?? $poet->details;
        } catch (\Throwable) {
            try {
                return $poet->details;
            } catch (\Throwable) {
                return null;
            }
        }
    }

    /**
     * Cached birth/death place labels — never throws for broken geo data.
     */
    private function cachedPoetLocations(string $cacheKey, \App\Models\PoetsDetail $poetDetails): array
    {
        $empty = [
            'birth' => ['cityName' => null, 'provinceName' => null, 'countryName' => null],
            'death' => ['cityName' => null, 'provinceName' => null, 'countryName' => null],
        ];

        try {
            return Cache::rememberForever($cacheKey, function () use ($poetDetails, $empty) {
                try {
                    return [
                        'birth' => $poetDetails->birthPlaceComplete(),
                        'death' => $poetDetails->deathPlaceComplete(),
                    ];
                } catch (\Throwable) {
                    return $empty;
                }
            });
        } catch (\Throwable) {
            return $empty;
        }
    }



    public function SEO_lyrics()
    {

    }

    public function shortDesc($content)
    {
        $content = mb_substr($content, 0, 256);
        return preg_replace('/\n+/', ' ', $content);
    }





}


?>