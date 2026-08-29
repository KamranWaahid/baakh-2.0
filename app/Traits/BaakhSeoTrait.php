<?php
namespace App\Traits;

use App\Models\Categories;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\PoetBook;
use App\Models\Tags;
use App\Support\AeoIntentMatrix;
use App\Support\AeoJsonLd;
use App\Support\AeoPlatformFaq;
use App\Support\PoetSameAs;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Cache;
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

        $speakable = [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => [
                '#baakh-seo-fallback h1',
                '#baakh-seo-fallback h2',
                '#baakh-seo-fallback h3',
                '#baakh-seo-fallback p',
            ],
        ];

        // Identity graph first so homepage parsers see Organization (not only WebSite).
        JsonLdMulti::setType('Organization');
        JsonLdMulti::addValue('@type', ['Organization', 'ArchiveOrganization']);
        foreach ($this->organizationSchemaFields() as $property => $value) {
            JsonLdMulti::addValue($property, $value);
        }
        JsonLdMulti::addValue('speakable', $speakable);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType($additionalData['json_ld_type'] ?? 'WebPage');
        JsonLdMulti::setTitle($title);
        JsonLdMulti::setDescription($desc);
        JsonLdMulti::setUrl(url()->current());
        JsonLdMulti::addValue('@id', $canonical . '#page');
        JsonLdMulti::addValue('inLanguage', $currentLang);
        JsonLdMulti::addValue('speakable', $speakable);
        JsonLdMulti::addValue('isPartOf', [
            '@type' => 'WebSite',
            'name' => $isSd ? 'باک' : 'Baakh',
            'url' => url('/'),
            'publisher' => ['@id' => url('/') . '#organization'],
        ]);

        if (isset($additionalData['jsonld']) && is_array($additionalData['jsonld'])) {
            foreach ($additionalData['jsonld'] as $property => $value) {
                JsonLdMulti::addValue($property, $value);
            }
        }

        if (!empty($additionalData['site_nav'])) {
            JsonLdMulti::newJsonLd();
            JsonLdMulti::setType('ItemList');
            JsonLdMulti::setTitle($isSd ? 'باک جا مکيه صفحا' : 'Baakh site navigation');
            JsonLdMulti::addValue('itemListElement', $this->siteNavListItems($currentLang));
        }

        if (! empty($additionalData['faqs']) && is_array($additionalData['faqs']) && count($additionalData['faqs']) >= 2) {
            JsonLdMulti::newJsonLd();
            JsonLdMulti::setType('FAQPage');
            JsonLdMulti::addValue('inLanguage', $currentLang);
            JsonLdMulti::addValue('mainEntity', $additionalData['faqs']);
        }

        if (!empty($additionalData['site_nav'])) {
            $this->emitArchiveServiceSchema($isSd, $currentLang);
        }

        return [
            'title' => $title,
            'description' => $desc,
            'html' => $additionalData['fallback_html'] ?? '',
            'h1' => $additionalData['h1'] ?? $title,
            'image' => $image,
            'og_description' => $additionalData['og_description'] ?? $desc,
            'og_image_alt' => $additionalData['og_image_alt'] ?? $title,
        ];
    }

    /**
     * Blade-ready SEO payload after the SEO_* methods have populated SEOTools.
     */
    public function buildSeoData(array $fallback): array
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'sd';
        $urls = $this->localeAlternateUrls();
        $inner = $urls['inner'];

        $ogType = 'website';
        if (preg_match('#^poet/[^/]+/[^/]+/#', $inner)) {
            $ogType = 'article';
        } elseif (preg_match('#^poet/[^/]+$#', $inner)) {
            $ogType = 'profile';
        }

        $title = (string) ($fallback['title'] ?: SEOMeta::getTitle() ?: ($locale === 'sd'
            ? 'باک - سنڌي شاعريءَ جو آرڪائيو'
            : 'Baakh - Archive of Sindhi Poetry'));
        $description = (string) ($fallback['description'] ?? '');

        return [
            'lang' => $locale,
            'title' => $title,
            'description' => $description,
            'h1' => (string) ($fallback['h1'] ?? $title),
            'raw_text' => (string) ($fallback['html'] ?? ''),
            'canonical' => SEOMeta::getCanonical() ?: $urls['canonical'],
            'en_url' => $urls['en'],
            'sd_url' => $urls['sd'],
            'og_type' => $ogType,
            'image' => (string) ($fallback['image'] ?? asset('assets/og/baakh-og-v2-1200x630.png')),
            'og_description' => (string) ($fallback['og_description'] ?? $description),
            'og_image_alt' => (string) ($fallback['og_image_alt'] ?? $title),
            'site_name' => $locale === 'sd' ? 'باک' : 'Baakh',
            'og_locale' => $locale === 'sd' ? 'sd_PK' : 'en_US',
            'og_locale_alternate' => $locale === 'sd' ? 'en_US' : 'sd_PK',
            'schema' => $this->jsonLdGraphs(),
            'robots' => SEOMeta::getRobots() ?: 'index, follow',
            'twitter_site' => '@BaakhConnect',
            'markdown_url' => $inner === '' ? url('/index.md') : url('/' . $locale . '/' . $inner . '.md'),
        ];
    }

    /**
     * @return array{inner: string, en: string, sd: string, canonical: string}
     */
    private function localeAlternateUrls(): array
    {
        $segments = explode('/', request()->path());
        if (($segments[0] ?? '') === 'en' || ($segments[0] ?? '') === 'sd') {
            array_shift($segments);
        }
        $inner = trim(implode('/', $segments), '/');

        return [
            'inner' => $inner,
            'en' => $inner === '' ? url('/en') : url('/en/' . $inner),
            'sd' => $inner === '' ? url('/sd') : url('/sd/' . $inner),
            'canonical' => url()->current(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jsonLdGraphs(): array
    {
        try {
            $multi = app('seotools.json-ld-multi');
            $list = (new \ReflectionClass($multi))->getProperty('list');
            $list->setAccessible(true);
            $graphs = [];
            foreach ($list->getValue($multi) as $jsonLd) {
                if (!is_object($jsonLd) || (method_exists($jsonLd, 'isEmpty') && $jsonLd->isEmpty())) {
                    continue;
                }
                if (!method_exists($jsonLd, 'convertToArray')) {
                    continue;
                }
                $graphs[] = array_merge(
                    ['@context' => 'https://schema.org'],
                    $jsonLd->convertToArray()
                );
            }

            return $graphs;
        } catch (\Throwable) {
            return [];
        }
    }


    /**
     * Add Two Keywords Dynamically
     */
    public function appendKeywords($array)
    {
        $generic = ['Sindhi Poetry', 'Baakh', 'Sheikh Ayaz'];
        $specific = [];
        if ($array && is_array($array)) {
            foreach ($array as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    $specific[] = $item;
                }
            }
        }
        $keywords = array_values(array_unique(array_merge($specific, $generic)));

        return implode(', ', array_slice($keywords, 0, 16));
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
        $poet = $poetModel;
        $currentLang = app()->getLocale();
        $poetDetails = $this->poetDetailsForLocale($poet);
        $poetSlug = $poet->poet_slug;
        $url = url("{$currentLang}/poet/{$poetSlug}");
        $enUrl = url("en/poet/{$poetSlug}");
        $sdUrl = url("sd/poet/{$poetSlug}");

        $poetLaqab = (string) ($poetDetails?->poet_laqab ?? $poetSlug);
        $poetName = (string) ($poetDetails?->poet_name ?? $poetLaqab);
        $displayName = $this->sindhiPossessiveName($poetLaqab);
        $book = $this->featuredBook($poet);
        $bookName = $this->bookDisplayName($book);
        $bio = strip_tags((string) ($poetDetails?->poet_bio ?? ''));
        $works = $this->poetIndexedWorks($poet);
        $genres = $this->poetGenreNames($poet, $works);
        $topicThings = $this->tagThingsFromJson($poet->poet_tags ?? null, $currentLang);
        $intentTokens = AeoIntentMatrix::hydrate($currentLang, [
            'poet' => ['slug' => $poetSlug, 'label' => $poetLaqab, 'url' => $url],
            'genre' => $this->poetPrimaryGenreToken($works),
            'topic' => $this->firstTopicToken($topicThings),
            'book' => ['label' => (string) ($bookName ?? '')],
        ]);
        $genreLabel = $this->formatGenreList($genres);
        $h1 = trans('labels.seo_h1_poet', ['poetName' => $displayName]);

        if ($category !== '' && $category !== null) {
            $title = trans('labels.seo_title_poet_category', [
                'categoryName' => $category,
                'poetName' => $displayName,
            ]);
        } else {
            $title = trans('labels.seo_title_poet', ['poetName' => $displayName]);
        }

        $templateDesc = trans('labels.seo_desc_poet', [
            'poetName' => $displayName,
            'genres' => $genreLabel,
        ]);
        $shortBio = $bio !== '' ? Str::limit($bio, 158) : $templateDesc;

        $ogImage = asset('assets/og/baakh-og-v2-1200x630.png');
        $keywords = $this->appendKeywords(array_filter(array_merge(
            AeoIntentMatrix::keywords($intentTokens),
            [
                $poetName,
                $poetLaqab . ' poetry',
                $bookName,
            ],
            $genres
        )));

        $this->applyShareMeta($title, $shortBio, $url, $ogImage, $keywords, 'profile');
        SEOMeta::addAlternateLanguage('en', $enUrl);
        SEOMeta::addAlternateLanguage('sd', $sdUrl);
        SEOMeta::addAlternateLanguage('x-default', $sdUrl);

        $person = $this->poetPersonSchema($poet, $poetDetails, $url, $shortBio, $works, $genres);
        $faqs = $this->poetFaqEntities($displayName, $bio, $poet, $url, $genres, $person);
        $faqs = AeoIntentMatrix::mergeFaqs($faqs, array_merge(
            AeoIntentMatrix::schema(AeoIntentMatrix::SCENARIO_INTERSECT, $intentTokens, $currentLang),
            AeoIntentMatrix::schema(AeoIntentMatrix::SCENARIO_BIBLIO, $intentTokens, $currentLang)
        ));

        JsonLdMulti::setType('ProfilePage');
        JsonLdMulti::setTitle($h1);
        JsonLdMulti::setDescription($shortBio);
        JsonLdMulti::setUrl($url);
        JsonLdMulti::addValue('@id', $url . '#profile');
        JsonLdMulti::addValue('inLanguage', $currentLang);
        JsonLdMulti::addValue('mainEntity', $person);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('BreadcrumbList');
        JsonLdMulti::addValue('itemListElement', [
            $this->breadcrumbItem(1, $currentLang === 'sd' ? 'گھر' : 'Home', url("{$currentLang}/")),
            $this->breadcrumbItem(2, $currentLang === 'sd' ? 'شاعر' : 'Poets', url("{$currentLang}/poets")),
            $this->breadcrumbItem(3, $poetLaqab, $url),
        ]);

        if (count($faqs) >= 2) {
            JsonLdMulti::newJsonLd();
            JsonLdMulti::setType('FAQPage');
            JsonLdMulti::addValue('inLanguage', $currentLang);
            JsonLdMulti::addValue('mainEntity', $faqs);
        }

        return [
            'title' => $title,
            'description' => $shortBio,
            'h1' => $h1,
            'image' => $ogImage,
            'html' => $this->breadcrumbHtml([
                [$currentLang === 'sd' ? 'گھر' : 'Home', url("{$currentLang}/")],
                [$currentLang === 'sd' ? 'شاعر' : 'Poets', url("{$currentLang}/poets")],
                [$poetLaqab, $url],
            ]) . $this->poetFallbackHtml($displayName, $bio, $bookName, $poet, $works, $currentLang, $faqs, $genres),
        ];
    }

    /**
     * SEO Poetry page
     */
    public function SEO_Poetry(Poetry $poetry, $poetryCategory, Poets $poetModel, $seo_image = null)
    {
        $currentLang = app()->getLocale();
        $poetDetails = $this->poetDetailsForLocale($poetModel);
        $p_category = $poetry->category;

        $poetLaqab = (string) ($poetDetails?->poet_laqab ?? $poetModel->poet_slug);
        $displayName = $this->sindhiPossessiveName($poetLaqab);
        $categorySlug = (string) ($p_category?->slug ?? $poetryCategory);
        $categoryName = $this->categoryDisplayName($p_category, $categorySlug);
        $poemTitle = $this->poemDisplayTitle($poetry, $currentLang);
        $book = $this->poetryBook($poetry);
        $bookName = $this->bookDisplayName($book);
        $url = url("{$currentLang}/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}");
        $enUrl = url("en/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}");
        $sdUrl = url("sd/poet/{$poetModel->poet_slug}/{$categorySlug}/{$poetry->poetry_slug}");

        $title = trans('labels.seo_custom_bio_poetry', [
            'poetName' => $displayName,
            'category' => $categoryName,
            'title' => $poemTitle,
        ]);

        $h1 = trans('labels.seo_h1_poetry', [
            'poetName' => $displayName,
            'category' => $categoryName,
            'title' => $poemTitle,
        ]);

        $poetryInfo = $this->poetryTranslationForLocale($poetry, $currentLang);
        $coupletsByLang = $this->poetryCoupletsByLang($poetry);
        $couplets = $coupletsByLang['sd'] !== [] ? $coupletsByLang['sd'] : $coupletsByLang['en'];
        $infoText = trim(strip_tags((string) ($poetryInfo->info ?? '')));
        $templateDesc = trans('labels.seo_desc_poetry', [
            'poetName' => $displayName,
            'category' => $categoryName,
            'title' => $poemTitle,
        ]);

        if ($infoText !== '') {
            $shortBio = Str::limit($infoText, 158);
        } elseif ($couplets !== []) {
            $shortBio = Str::limit(preg_replace('/\s+/', ' ', $couplets[0]), 158);
        } else {
            $shortBio = $templateDesc;
        }

        $ogImage = $seo_image ?: asset('assets/og/baakh-og-v2-1200x630.png');
        $topicThings = $this->poetryTopicThings($poetry, $currentLang);
        $topicNames = array_values(array_filter(array_map(
            fn (array $thing) => (string) ($thing['name'] ?? ''),
            $topicThings
        )));
        $intentTokens = AeoIntentMatrix::hydrate($currentLang, [
            'poet' => ['slug' => $poetModel->poet_slug, 'label' => $poetLaqab, 'url' => url("{$currentLang}/poet/{$poetModel->poet_slug}")],
            'genre' => ['slug' => $categorySlug, 'label' => $categoryName],
            'work' => ['slug' => (string) $poetry->poetry_slug, 'label' => $poemTitle],
            'topic' => $this->firstTopicToken($topicThings),
            'book' => ['label' => (string) ($bookName ?? '')],
        ]);
        $keywords = $this->appendKeywords(array_values(array_filter(array_merge(
            $topicNames,
            AeoIntentMatrix::keywords($intentTokens),
            AeoJsonLd::discoveryKeywords($poetLaqab, $categoryName, $topicNames),
            [$poetLaqab, $categoryName, $poemTitle, $bookName]
        ))));

        $this->applyShareMeta($title, $shortBio, $url, $ogImage, $keywords, 'article');
        SEOMeta::addAlternateLanguage('en', $enUrl);
        SEOMeta::addAlternateLanguage('sd', $sdUrl);
        SEOMeta::addAlternateLanguage('x-default', $sdUrl);

        $poetUrl = url("{$currentLang}/poet/{$poetModel->poet_slug}");
        $altTitle = $this->poemDisplayTitle($poetry, $currentLang === 'sd' ? 'en' : 'sd');
        $work = [
            'name' => $poemTitle,
            'headline' => $h1,
            'url' => $url,
            'inLanguage' => array_values(array_filter([
                ($coupletsByLang['sd'] ?? []) !== [] ? 'sd' : null,
                ($coupletsByLang['en'] ?? []) !== [] ? 'en' : null,
                'sd',
            ])),
            'genre' => $categoryName,
            'keywords' => implode(', ', array_values(array_unique(array_filter(array_merge(
                AeoJsonLd::discoveryKeywords($poetLaqab, $categoryName, $topicNames),
                AeoIntentMatrix::keywords($intentTokens)
            ))))),
            'abstract' => $shortBio,
            'author' => [
                '@type' => 'Person',
                '@id' => $poetUrl . '#person',
                'name' => $poetLaqab,
                'alternateName' => (string) ($poetDetails?->poet_name ?? ''),
                'url' => $poetUrl,
            ],
            'publisher' => $this->publisherOrganization(),
        ];
        $work['inLanguage'] = array_values(array_unique($work['inLanguage']));
        if ($altTitle !== '' && $altTitle !== $poemTitle) {
            $work['alternativeHeadline'] = $altTitle;
        }
        if ($topicThings !== []) {
            $work['about'] = $topicThings;
        }
        if ($bookName) {
            $work['isPartOf'] = [
                '@type' => 'Book',
                'name' => $bookName,
            ];
        }
        if ($couplets !== []) {
            $work['text'] = implode("\n", array_slice($couplets, 0, 12));
            $work['hasPart'] = array_map(fn ($text) => [
                '@type' => 'CreativeWork',
                'text' => $text,
            ], array_slice($couplets, 0, 12));
        }

        JsonLdMulti::setType('CreativeWork');
        JsonLdMulti::setTitle($poemTitle);
        JsonLdMulti::setDescription($shortBio);
        JsonLdMulti::setUrl($url);
        JsonLdMulti::addValue('@id', $url . '#work');
        foreach ($work as $key => $value) {
            JsonLdMulti::addValue($key, $value);
        }

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('BreadcrumbList');
        JsonLdMulti::addValue('itemListElement', [
            $this->breadcrumbItem(1, $currentLang === 'sd' ? 'گھر' : 'Home', url("{$currentLang}/")),
            $this->breadcrumbItem(2, $currentLang === 'sd' ? 'شاعر' : 'Poets', url("{$currentLang}/poets")),
            $this->breadcrumbItem(3, $poetLaqab, $poetUrl),
            $this->breadcrumbItem(4, $categoryName, url("{$currentLang}/poet/{$poetModel->poet_slug}/{$categorySlug}")),
            $this->breadcrumbItem(5, $poemTitle, $url),
        ]);

        $faqs = $this->poemFaqEntities($poemTitle, $infoText, $bookName, $displayName, $categoryName, $url, $topicNames, $poetUrl);
        $faqs = AeoIntentMatrix::mergeFaqs($faqs, array_merge(
            AeoIntentMatrix::schema(AeoIntentMatrix::SCENARIO_WORK, $intentTokens, $currentLang),
            AeoIntentMatrix::schema(AeoIntentMatrix::SCENARIO_INTERSECT, $intentTokens, $currentLang)
        ));
        if (count($faqs) >= 2) {
            JsonLdMulti::newJsonLd();
            JsonLdMulti::setType('FAQPage');
            JsonLdMulti::addValue('inLanguage', $currentLang);
            JsonLdMulti::addValue('mainEntity', $faqs);
        }

        return [
            'title' => $title,
            'description' => $shortBio,
            'h1' => $h1,
            'image' => $ogImage,
            'html' => $this->poemFallbackHtml(
                $displayName,
                $categoryName,
                $poemTitle,
                $infoText,
                $bookName,
                $coupletsByLang,
                $poetUrl,
                $url,
                $faqs
            ),
        ];
    }

    /**
     * Collection pages (poets, poetry, genre index) for sitelinks and crawlable IA.
     */
    public function SEO_Listing(string $key, string $locale): array
    {
        $copy = $this->listingCopy($key, $locale === 'sd');
        $collectionKeys = ['poets', 'poetry', 'couplets', 'genre', 'period', 'explore', 'prosody'];
        $isCollection = in_array($key, $collectionKeys, true);
        $pageTypes = [
            'about' => 'AboutPage',
            'contact' => 'ContactPage',
        ];
        $html = $this->listingCollectionHtml($key, $locale, $copy['h1']);
        $faqs = AeoPlatformFaq::schema($key, $locale);
        if ($faqs !== []) {
            $html .= AeoPlatformFaq::html($key, $locale);
        }

        $jsonld = ['name' => $copy['h1']];
        if ($isCollection) {
            $jsonld['mainEntity'] = [
                '@type' => 'ItemList',
                'itemListElement' => $this->listingItemList($key, $locale),
            ];
        } elseif ($key === 'contact') {
            $jsonld['mainEntity'] = $this->publisherOrganization();
        }

        $fallback = $this->SEO_General($copy['title'], $copy['desc'], null, null, [
            'h1' => $copy['h1'],
            'json_ld_type' => $pageTypes[$key] ?? ($isCollection ? 'CollectionPage' : 'WebPage'),
            'fallback_html' => $html,
            'jsonld' => $jsonld,
            'faqs' => $faqs,
        ]);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('BreadcrumbList');
        JsonLdMulti::addValue('itemListElement', [
            $this->breadcrumbItem(1, $locale === 'sd' ? 'گھر' : 'Home', url("{$locale}/")),
            $this->breadcrumbItem(2, $copy['h1'], url("{$locale}/{$key}")),
        ]);

        return $fallback;
    }

    /**
     * Genre feed at /{lang}/{genre_slug} (SPA catch-all Home category filter).
     */
    public function SEO_GenreCollection(Categories $genre, string $locale): array
    {
        $isSd = $locale === 'sd';
        $name = $this->categoryDisplayName($genre, (string) $genre->slug);
        $detail = $genre->details->firstWhere('lang', $locale) ?? $genre->details->first();
        $desc = trim((string) ($detail?->cat_detail ?? ''));
        if ($desc === '') {
            $desc = trans('labels.seo_genre_archive_desc', ['genre' => $name]);
        }

        $h1 = trans('labels.seo_genre_archive_h1', ['genre' => $name]);
        $title = $name . ($isSd ? ' | باک' : ' | Baakh');
        $url = url("{$locale}/{$genre->slug}");
        $poets = $this->prominentPoetsForGenre($genre, $locale);

        $listItems = [];
        foreach ($poets as $i => $poet) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $poet['name'],
                'url' => $poet['url'],
            ];
        }

        $html = $this->genreCollectionHtml($name, $desc, $poets, $locale, $genre->slug);
        $intentTokens = AeoIntentMatrix::hydrate($locale, [
            'genre' => ['slug' => (string) $genre->slug, 'label' => $name, 'url' => $url],
        ]);
        $faqs = [
            $this->faqItem(
                trans('labels.seo_faq_genre_what', ['genre' => $name]),
                Str::limit($desc, 240)
            ),
            $this->faqItem(
                trans('labels.seo_faq_genre_where', ['genre' => $name]),
                trans('labels.seo_faq_genre_where_answer', ['genre' => $name, 'url' => $url])
            ),
        ];
        $faqs = AeoIntentMatrix::mergeFaqs(
            $faqs,
            AeoIntentMatrix::schema(AeoIntentMatrix::SCENARIO_SNIPPET, $intentTokens, $locale)
        );
        $html .= $this->faqFallbackHtml($faqs);

        $fallback = $this->SEO_General($title, $this->shortDesc($desc), null, null, [
            'h1' => $h1,
            'json_ld_type' => 'CollectionPage',
            'fallback_html' => $html,
            'jsonld' => [
                '@id' => $url . '#collection',
                'name' => $h1,
                'inLanguage' => $locale,
                'about' => [
                    '@type' => 'Thing',
                    'name' => $name,
                    'url' => $url,
                ],
                'keywords' => implode(', ', array_values(array_unique(array_filter(array_merge(
                    AeoJsonLd::discoveryKeywords('', $name),
                    AeoIntentMatrix::keywords($intentTokens)
                ))))),
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => count($listItems),
                    'itemListElement' => $listItems,
                ],
            ],
        ]);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('BreadcrumbList');
        JsonLdMulti::addValue('itemListElement', [
            $this->breadcrumbItem(1, $isSd ? 'گھر' : 'Home', url("{$locale}/")),
            $this->breadcrumbItem(2, $isSd ? 'صنفون' : 'Genres', url("{$locale}/genre")),
            $this->breadcrumbItem(3, $name, $url),
        ]);

        if (count($faqs) >= 2) {
            JsonLdMulti::newJsonLd();
            JsonLdMulti::setType('FAQPage');
            JsonLdMulti::addValue('inLanguage', $locale);
            JsonLdMulti::addValue('mainEntity', $faqs);
        }

        return $fallback;
    }

    /**
     * Topic/tag hubs at /{lang}/tag/{slug} and /{lang}/topic/{slug}.
     *
     * @param list<array{name: string, url: string}> $poets
     */
    public function SEO_TopicHub(string $kind, string $slug, string $name, string $locale, array $poets = [], ?string $alternateName = null): array
    {
        $isSd = $locale === 'sd';
        $pathKind = $kind === 'topic' ? 'topic' : 'tag';
        $url = url("{$locale}/{$pathKind}/{$slug}");
        $h1 = trans('labels.seo_topic_hub_h1', ['topic' => $name]);
        $desc = trans('labels.seo_topic_hub_desc', ['topic' => $name]);
        $title = $name . ($isSd ? ' | باک' : ' | Baakh');

        $listItems = [];
        foreach ($poets as $i => $poet) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $poet['name'],
                'url' => $poet['url'],
            ];
        }

        $explore = $isSd ? 'ڳولا' : 'Explore';
        $html = $this->breadcrumbHtml([
            [$isSd ? 'گھر' : 'Home', url("{$locale}/")],
            [$explore, url("{$locale}/explore")],
            [$name, $url],
        ]);
        $html .= '<p class="lead-text">' . e($desc) . '</p>';
        if ($poets !== []) {
            $html .= '<h2>' . e($name) . '</h2><ul>';
            foreach ($poets as $poet) {
                $html .= '<li><a href="' . e($poet['url']) . '">' . e($poet['name']) . '</a></li>';
            }
            $html .= '</ul>';
        }

        $about = [
            '@type' => 'Thing',
            'name' => $name,
            'url' => $url,
        ];
        if ($alternateName && $alternateName !== $name) {
            $about['alternateName'] = $alternateName;
        }

        $faqs = [
            $this->faqItem(
                trans('labels.seo_faq_topic_what', ['topic' => $name]),
                $desc
            ),
            $this->faqItem(
                trans('labels.seo_faq_topic_where', ['topic' => $name]),
                trans('labels.seo_faq_topic_where_answer', ['topic' => $name, 'url' => $url])
            ),
        ];
        if ($poets !== []) {
            $poetNames = implode(', ', array_slice(array_column($poets, 'name'), 0, 8));
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_topic_poets', ['topic' => $name]),
                $poetNames
            );
        }
        $intentTokens = AeoIntentMatrix::hydrate($locale, [
            'topic' => ['slug' => $slug, 'label' => $name, 'url' => $url],
        ]);
        $faqs = AeoIntentMatrix::mergeFaqs(
            $faqs,
            AeoIntentMatrix::schema(AeoIntentMatrix::SCENARIO_SNIPPET, $intentTokens, $locale)
        );
        $html .= $this->faqFallbackHtml($faqs);

        $jsonld = [
            '@id' => $url . '#collection',
            'name' => $h1,
            'inLanguage' => $locale,
            'about' => $about,
            'keywords' => implode(', ', array_values(array_unique(array_filter(array_merge(
                AeoJsonLd::discoveryKeywords('', '', [$name]),
                AeoIntentMatrix::keywords($intentTokens)
            ))))),
        ];
        if ($listItems !== []) {
            $jsonld['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($listItems),
                'itemListElement' => $listItems,
            ];
        }

        $fallback = $this->SEO_General($title, $desc, null, null, [
            'h1' => $h1,
            'json_ld_type' => 'CollectionPage',
            'fallback_html' => $html,
            'jsonld' => $jsonld,
        ]);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('BreadcrumbList');
        JsonLdMulti::addValue('itemListElement', [
            $this->breadcrumbItem(1, $isSd ? 'گھر' : 'Home', url("{$locale}/")),
            $this->breadcrumbItem(2, $explore, url("{$locale}/explore")),
            $this->breadcrumbItem(3, $name, $url),
        ]);

        if (count($faqs) >= 2) {
            JsonLdMulti::newJsonLd();
            JsonLdMulti::setType('FAQPage');
            JsonLdMulti::addValue('inLanguage', $locale);
            JsonLdMulti::addValue('mainEntity', $faqs);
        }

        return $fallback;
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

    private function applyShareMeta(string $title, string $description, string $url, string $ogImage, $keywords, string $ogType): void
    {
        SEOTools::addImages($ogImage);
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);
        SEOMeta::addKeyword($keywords);

        OpenGraph::setTitle($title);
        OpenGraph::setDescription($description);
        OpenGraph::setType($ogType);
        OpenGraph::setUrl($url);
        OpenGraph::addImage($ogImage, ['height' => 630, 'width' => 1200]);
        OpenGraph::setSiteName(app()->getLocale() === 'sd' ? 'باک' : 'Baakh');

        TwitterCard::setType('summary_large_image');
        TwitterCard::addValue('twitter:domain', 'baakh.com');
        TwitterCard::setTitle($title);
        TwitterCard::setImage($ogImage);
        TwitterCard::setDescription($description);
        TwitterCard::setUrl($url);
        TwitterCard::setSite('@BaakhConnect');
    }

    private function sindhiPossessiveName(string $name): string
    {
        if ($name === '') {
            return $name;
        }

        return mb_substr($name, -1) === 'و' ? mb_substr($name, 0, -1) . 'ي' : $name;
    }

    private function bookDisplayName(?PoetBook $book): ?string
    {
        if (!$book) {
            return null;
        }

        $isSd = app()->getLocale() === 'sd';
        $name = $isSd
            ? (string) ($book->title_sd ?: $book->title)
            : (string) ($book->title ?: $book->title_sd);

        $name = trim($name);

        return $name !== '' ? $name : null;
    }

    private function featuredBook(Poets $poet): ?PoetBook
    {
        try {
            if ($poet->relationLoaded('books') && $poet->books->isNotEmpty()) {
                return $poet->books->first();
            }

            return $poet->books()
                ->where('visibility', 1)
                ->orderByDesc('is_featured')
                ->orderByDesc('published_year')
                ->first();
        } catch (\Throwable) {
            return null;
        }
    }

    private function poetryBook(Poetry $poetry): ?PoetBook
    {
        try {
            if ($poetry->relationLoaded('book')) {
                return $poetry->book;
            }

            return $poetry->book;
        } catch (\Throwable) {
            return null;
        }
    }

    private function categoryDisplayName($category, string $fallback): string
    {
        if (!$category) {
            return $fallback;
        }

        $name = (string) ($category->category_name ?? '');
        if ($name === '') {
            try {
                $detail = $category->details->firstWhere('lang', app()->getLocale())
                    ?? $category->details->first()
                    ?? $category->detail;
                $name = (string) ($detail->cat_name ?? '');
            } catch (\Throwable) {
                $name = '';
            }
        }

        return $name !== '' ? $name : (string) ($category->slug ?? $fallback);
    }

    private function poemDisplayTitle(Poetry $poetry, string $locale): string
    {
        $translation = $this->poetryTranslationForLocale($poetry, $locale);
        $title = trim((string) ($translation->title ?? $poetry->poetry_title ?? $poetry->poetry_slug));

        return $title !== '' ? $title : (string) $poetry->poetry_slug;
    }

    private function poetryTranslationForLocale(Poetry $poetry, string $locale): ?\App\Models\PoetryTranslations
    {
        try {
            $translations = $poetry->relationLoaded('translations')
                ? $poetry->translations
                : $poetry->translations()->get();

            return $translations->firstWhere('lang', $locale)
                ?? $translations->first()
                ?? $poetry->info;
        } catch (\Throwable) {
            try {
                return $poetry->info;
            } catch (\Throwable) {
                return null;
            }
        }
    }

    /**
     * @return array{sd: list<string>, en: list<string>}
     */
    private function poetryCoupletsByLang(Poetry $poetry): array
    {
        try {
            $couplets = $poetry->relationLoaded('all_couplets')
                ? $poetry->all_couplets
                : $poetry->all_couplets()->limit(40)->get();
        } catch (\Throwable) {
            return ['sd' => [], 'en' => []];
        }

        $byLang = ['sd' => [], 'en' => []];
        foreach ($couplets as $couplet) {
            $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $couplet->couplet_text)));
            if ($text === '') {
                continue;
            }
            $lang = strtolower((string) ($couplet->lang ?? 'sd'));
            $bucket = $lang === 'en' ? 'en' : 'sd';
            $byLang[$bucket][] = $text;
        }

        return $byLang;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function poetryTopicThings(Poetry $poetry, string $locale): array
    {
        $things = $this->tagThingsFromJson($poetry->poetry_tags ?? null, $locale);

        try {
            $topic = $poetry->relationLoaded('topicCategory')
                ? $poetry->topicCategory
                : $poetry->topicCategory()->with('details')->first();
            if ($topic) {
                $details = $topic->relationLoaded('details') ? $topic->details : $topic->details()->get();
                $here = (string) ($details->firstWhere('lang', $locale)?->name
                    ?? $details->first()?->name
                    ?? $topic->slug);
                $otherLang = $locale === 'sd' ? 'en' : 'sd';
                $other = (string) ($details->firstWhere('lang', $otherLang)?->name ?? '');
                $already = array_column($things, 'name');
                if ($here !== '' && ! in_array($here, $already, true)) {
                    $thing = [
                        '@type' => 'Thing',
                        'name' => $here,
                        'url' => url($locale . '/topic/' . $topic->slug),
                    ];
                    if ($other !== '' && $other !== $here) {
                        $thing['alternateName'] = $other;
                    }
                    $things[] = $thing;
                }
            }
        } catch (\Throwable) {
            // topic_category_id may be empty or the table unavailable
        }

        return $things;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tagThingsFromJson(mixed $raw, string $locale): array
    {
        $ids = AeoJsonLd::jsonIdList($raw);
        if ($ids === []) {
            return [];
        }

        try {
            $tags = Tags::query()->whereIn('id', $ids)->with('details')->get();
        } catch (\Throwable) {
            return [];
        }

        $topics = [];
        foreach ($tags as $tag) {
            $here = (string) ($tag->details->firstWhere('lang', $locale)?->name
                ?? $tag->details->first()?->name
                ?? $tag->slug);
            $otherLang = $locale === 'sd' ? 'en' : 'sd';
            $other = (string) ($tag->details->firstWhere('lang', $otherLang)?->name ?? '');
            $topics[] = [
                'name' => $here,
                'alternateName' => ($other !== '' && $other !== $here) ? $other : '',
                'url' => url($locale . '/tag/' . $tag->slug),
            ];
        }

        return AeoJsonLd::aboutThings($topics);
    }

    /**
     * @return list<string>
     */
    private function poetGenreNames(Poets $poet, $works = null): array
    {
        $names = [];
        if ($works && $works->isNotEmpty()) {
            foreach ($works as $work) {
                $slug = (string) ($work->category?->slug ?? '');
                $name = $this->categoryDisplayName($work->category ?? null, $slug ?: 'poetry');
                if ($name !== '') {
                    $names[$name] = $name;
                }
            }
        }

        if ($names !== []) {
            return array_values($names);
        }

        try {
            $categories = Categories::query()
                ->whereIn('id', Poetry::query()
                    ->where('poet_id', $poet->id)
                    ->where('visibility', 1)
                    ->select('category_id'))
                ->with('details')
                ->get(['id', 'slug']);
            foreach ($categories as $category) {
                $name = $this->categoryDisplayName($category, (string) $category->slug);
                if ($name !== '') {
                    $names[$name] = $name;
                }
            }
        } catch (\Throwable) {
            return [];
        }

        return array_values($names);
    }

    /**
     * @param  iterable<mixed>|null  $works
     * @return array{slug: string, label: string}|null
     */
    private function poetPrimaryGenreToken($works): ?array
    {
        if (! $works) {
            return null;
        }
        foreach ($works as $work) {
            $slug = (string) ($work->category?->slug ?? '');
            if ($slug === '') {
                continue;
            }

            return [
                'slug' => $slug,
                'label' => $this->categoryDisplayName($work->category ?? null, $slug),
            ];
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $things
     * @return array{slug: string, label: string, url: string}|null
     */
    private function firstTopicToken(array $things): ?array
    {
        foreach ($things as $thing) {
            $node = AeoIntentMatrix::node($thing);
            if ($node && ($node['label'] ?? '') !== '') {
                return $node;
            }
        }

        return null;
    }

    private function formatGenreList(array $names): string
    {
        $names = array_values(array_filter($names));
        if ($names === []) {
            return app()->getLocale() === 'sd' ? 'غزل ۽ بيت' : 'Ghazals and couplets';
        }
        if (count($names) === 1) {
            return $names[0];
        }

        $last = array_pop($names);
        $glue = app()->getLocale() === 'sd' ? ' ۽ ' : ', and ';

        return implode(', ', $names) . $glue . $last;
    }

    private function poetIndexedWorks(Poets $poet)
    {
        try {
            return Poetry::query()
                ->where('poet_id', $poet->id)
                ->where('visibility', 1)
                ->with([
                    'category.details',
                    'translations:id,poetry_id,title,lang',
                ])
                ->latest()
                ->limit(40)
                ->get(['id', 'poet_id', 'category_id', 'poetry_slug', 'poetry_title']);
        } catch (\Throwable) {
            return collect();
        }
    }

    private function poetPersonSchema(Poets $poet, $poetDetails, string $url, string $description, $works, array $genres = []): array
    {
        $poetLaqab = (string) ($poetDetails?->poet_laqab ?? $poet->poet_slug);
        $poetName = (string) ($poetDetails?->poet_name ?? '');
        $penName = trim((string) ($poetDetails?->pen_name ?? ''));
        $locale = app()->getLocale();
        $topicThings = $this->tagThingsFromJson($poet->poet_tags ?? null, $locale);
        $topicNames = array_map(fn (array $thing) => (string) $thing['name'], $topicThings);
        $knowsAbout = array_values(array_unique(array_filter(array_merge(
            ['Sindhi Poetry'],
            $genres,
            $topicNames
        ))));
        $person = [
            '@type' => 'Person',
            '@id' => $url . '#person',
            'name' => $poetLaqab,
            'url' => $url,
            'description' => $description,
            'jobTitle' => $this->poetJobTitle($poetDetails, $locale),
            'knowsAbout' => $knowsAbout !== [] ? $knowsAbout : ['Sindhi Poetry'],
        ];
        $alts = array_values(array_unique(array_filter([$poetName, $penName], fn ($n) => $n !== '' && $n !== $poetLaqab)));
        if ($alts !== []) {
            $person['alternateName'] = count($alts) === 1 ? $alts[0] : $alts;
        }
        if ($topicThings !== []) {
            $person['knowsAbout'] = array_values(array_unique(array_merge(
                $person['knowsAbout'],
                array_column($topicThings, 'name')
            )));
        }

        if (!empty($poet->poet_pic)) {
            $person['image'] = str_starts_with((string) $poet->poet_pic, 'http')
                ? $poet->poet_pic
                : asset($poet->poet_pic);
        }
        $birthDate = AeoJsonLd::isoDate($poet->date_of_birth ?? null);
        if ($birthDate) {
            $person['birthDate'] = $birthDate;
        }
        if (!empty($poet->date_of_death)) {
            $deathDate = AeoJsonLd::isoDate($poet->date_of_death);
            if ($deathDate) {
                $person['deathDate'] = $deathDate;
            }
        }

        $cacheKey = 'cache_poet_' . $poet->id . '_lng_' . $locale . '_locations';
        if ($poetDetails) {
            $locations = $this->cachedPoetLocations($cacheKey, $poetDetails);
            $birthPlace = AeoJsonLd::place(
                $locations['birth']['cityName'] ?? null,
                $locations['birth']['provinceName'] ?? null,
                $locations['birth']['countryName'] ?? null
            );
            if ($birthPlace) {
                $person['birthPlace'] = $birthPlace;
            }
            $deathPlace = AeoJsonLd::place(
                $locations['death']['cityName'] ?? null,
                $locations['death']['provinceName'] ?? null,
                $locations['death']['countryName'] ?? null
            );
            if ($deathPlace) {
                $person['deathPlace'] = $deathPlace;
            }
            $nationality = trim((string) ($locations['birth']['countryName'] ?? ''));
            if ($nationality !== '') {
                $person['nationality'] = $nationality;
            }
        }

        $examples = [];
        foreach ($works->take(10) as $work) {
            $catSlug = (string) ($work->category?->slug ?? 'poetry');
            $workTitle = $this->poemDisplayTitle($work, $locale);
            $examples[] = [
                '@type' => 'CreativeWork',
                'name' => $workTitle,
                'url' => url($locale . '/poet/' . $poet->poet_slug . '/' . $catSlug . '/' . $work->poetry_slug),
                'genre' => $this->categoryDisplayName($work->category, $catSlug),
            ];
        }
        if ($examples !== []) {
            $person['workExample'] = $examples;
        }

        $sameAs = PoetSameAs::urls($poet);
        if ($sameAs !== []) {
            $person['sameAs'] = $sameAs;
        }

        return $person;
    }

    private function poetJobTitle($poetDetails, string $locale): string
    {
        $tagline = trim((string) ($poetDetails?->tagline ?? ''));
        if ($tagline !== '' && strcasecmp($tagline, 'Poet') !== 0) {
            return $tagline;
        }

        return $locale === 'sd' ? 'شاعر' : 'Poet';
    }

    private function poetFaqEntities(string $poetName, string $bio, Poets $poet, string $url, array $genres = [], array $person = []): array
    {
        $faqs = [];
        $isSd = app()->getLocale() === 'sd';
        $birthDate = $person['birthDate'] ?? AeoJsonLd::isoDate($poet->date_of_birth ?? null);
        $placeLabel = null;
        if (isset($person['birthPlace']['name'])) {
            $placeLabel = (string) $person['birthPlace']['name'];
        }
        $birthFaq = AeoJsonLd::birthQuestion($poetName, $birthDate, $placeLabel, $isSd);
        if ($birthFaq) {
            $faqs[] = $birthFaq;
        }

        if (trim($bio) !== '') {
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poet_who', ['poetName' => $poetName]),
                Str::limit($bio, 240)
            );
        }

        if ($genres !== []) {
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poet_genres', ['poetName' => $poetName]),
                trans('labels.seo_faq_poet_genres_answer', [
                    'poetName' => $poetName,
                    'genres' => $this->formatGenreList($genres),
                ])
            );
        }

        $faqs[] = $this->faqItem(
            trans('labels.seo_faq_poet_where', ['poetName' => $poetName]),
            trans('labels.seo_faq_poet_where_answer', ['poetName' => $poetName, 'url' => $url])
        );

        $bookNames = [];
        try {
            $books = $poet->relationLoaded('books')
                ? $poet->books
                : $poet->books()->where('visibility', 1)->limit(6)->get();
            foreach ($books as $book) {
                $name = $this->bookDisplayName($book);
                if ($name) {
                    $bookNames[] = $name;
                }
            }
        } catch (\Throwable) {
            $bookNames = [];
        }

        if ($bookNames !== []) {
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poet_books', ['poetName' => $poetName]),
                implode(', ', $bookNames)
            );
        }

        return $faqs;
    }

    private function poemFaqEntities(string $poemTitle, string $infoText, ?string $bookName, string $poetName, string $categoryName, string $url = '', array $topicNames = [], string $poetUrl = ''): array
    {
        $faqs = [
            $this->faqItem(
                trans('labels.seo_faq_poem_format', ['title' => $poemTitle]),
                trans('labels.seo_faq_poem_format_answer', [
                    'poetName' => $poetName,
                    'category' => $categoryName,
                ])
            ),
        ];
        if ($topicNames !== []) {
            $topicLabel = $this->formatGenreList($topicNames);
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poem_topics', ['title' => $poemTitle]),
                trans('labels.seo_faq_poem_topics_answer', [
                    'poetName' => $poetName,
                    'category' => $categoryName,
                    'topics' => $topicLabel,
                ])
            );
        }
        $faqs[] = $this->faqItem(
            trans('labels.seo_faq_poem_more', ['poetName' => $poetName]),
            trans('labels.seo_faq_poem_more_answer', [
                'poetName' => $poetName,
                'url' => $poetUrl !== '' ? $poetUrl : $url,
            ])
        );
        if ($url !== '') {
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poem_cite', ['title' => $poemTitle]),
                trans('labels.seo_faq_poem_cite_answer', ['url' => $url])
            );
        }
        if ($infoText !== '') {
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poem_meaning', ['title' => $poemTitle]),
                Str::limit($infoText, 240)
            );
        }
        if ($bookName) {
            $faqs[] = $this->faqItem(
                trans('labels.seo_faq_poem_book'),
                $bookName
            );
        }

        return $faqs;
    }

    private function faqItem(string $question, string $answer): array
    {
        return [
            '@type' => 'Question',
            'name' => $question,
            'inLanguage' => app()->getLocale(),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
                'inLanguage' => app()->getLocale(),
            ],
        ];
    }

    private function breadcrumbItem(int $position, string $name, string $item): array
    {
        return [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $name,
            'item' => $item,
        ];
    }

    private function publisherOrganization(): array
    {
        return array_merge(
            ['@type' => 'Organization'],
            $this->organizationSchemaFields()
        );
    }

    /**
     * Top-level Organization fields for identity / contact (no @type).
     *
     * @return array<string, mixed>
     */
    private function organizationSchemaFields(): array
    {
        $contact = config('baakh.contact', []);
        $email = (string) ($contact['email'] ?? 'support@baakh.com');
        $phone = trim((string) ($contact['telephone'] ?? ''));
        $contactPoint = [
            '@type' => 'ContactPoint',
            'contactType' => (string) ($contact['contact_type'] ?? 'customer support'),
            'email' => $email,
            'url' => url('/en/contact'),
            'availableLanguage' => ['en', 'sd'],
        ];
        if ($phone !== '') {
            $contactPoint['telephone'] = $phone;
        }

        $address = [
            '@type' => 'PostalAddress',
            'streetAddress' => (string) ($contact['street'] ?? 'Office 428, 4th Floor, Mashreq Center, Expo Center'),
            'addressLocality' => (string) ($contact['locality'] ?? 'Karachi'),
            'addressRegion' => (string) ($contact['region'] ?? 'Sindh'),
            'addressCountry' => (string) ($contact['country'] ?? 'PK'),
        ];
        $postal = trim((string) ($contact['postal_code'] ?? ''));
        if ($postal !== '') {
            $address['postalCode'] = $postal;
        }

        return [
            '@id' => url('/') . '#organization',
            'name' => 'Baakh',
            'alternateName' => 'باک',
            'url' => url('/'),
            'additionalType' => 'https://schema.org/ArchiveOrganization',
            'email' => $email,
            'description' => 'A bilingual digital archive of Sindhi poetry.',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('assets/og/baakh-og-v2-1200x630.png'),
            ],
            'contactPoint' => $contactPoint,
            'address' => $address,
            'sameAs' => array_values(array_filter(config('baakh.same_as', []))),
        ];
    }

    /**
     * Extra homepage types so AI/SEO crawlers see more than ItemList + FAQPage.
     * Service is free public access. Dataset is the archive catalog. No invented star ratings.
     */
    private function emitArchiveServiceSchema(bool $isSd, string $lang): void
    {
        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('Service');
        JsonLdMulti::setTitle($isSd ? 'اوپن سورس سنڌي شاعري ڊجيٽل آرڪائيو' : 'Open-source digital archive of Sindhi poetry');
        JsonLdMulti::setDescription($isSd
            ? 'مفت ۽ اوپن سورس آن لائن رسائي: شاعر پروفائلون، غزل، بيت، وايون ۽ نظم اصل سنڌي ۽ رومن ۾.'
            : 'Free, open-source online access to Sindhi poet profiles, ghazals, baits, waee, and nazms in original script and Roman Sindhi.');
        JsonLdMulti::addValue('@id', url('/') . '#archive-service');
        JsonLdMulti::addValue('url', url('/' . $lang));
        JsonLdMulti::addValue('serviceType', 'Open-source digital literary archive');
        JsonLdMulti::addValue('category', 'Open Source');
        JsonLdMulti::addValue('provider', ['@id' => url('/') . '#organization']);
        JsonLdMulti::addValue('areaServed', [
            '@type' => 'Country',
            'name' => 'Pakistan',
        ]);
        JsonLdMulti::addValue('audience', [
            '@type' => 'Audience',
            'audienceType' => $isSd ? 'پڙهندڙ، شاگرد ۽ محقق' : 'Readers, students, and researchers',
        ]);
        JsonLdMulti::addValue('inLanguage', ['sd', 'en']);
        JsonLdMulti::addValue('isAccessibleForFree', true);
        JsonLdMulti::addValue('offers', [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'PKR',
            'availability' => 'https://schema.org/InStock',
            'url' => url('/' . $lang),
        ]);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('SoftwareSourceCode');
        JsonLdMulti::setTitle($isSd ? 'باک اوپن سورس ڪوڊ' : 'Baakh open-source code');
        JsonLdMulti::setDescription($isSd
            ? 'سنڌي شاعري آرڪائيو جو کليل سورس ايپليڪيشن.'
            : 'Open-source application that powers the Baakh Sindhi poetry archive.');
        JsonLdMulti::addValue('@id', url('/') . '#source-code');
        JsonLdMulti::addValue('codeRepository', 'https://github.com/KamranWaahid/baakh-2.0');
        JsonLdMulti::addValue('url', 'https://github.com/KamranWaahid/baakh-2.0');
        JsonLdMulti::addValue('programmingLanguage', ['PHP', 'JavaScript']);
        JsonLdMulti::addValue('isAccessibleForFree', true);
        JsonLdMulti::addValue('creator', ['@id' => url('/') . '#organization']);

        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType('Dataset');
        JsonLdMulti::setTitle($isSd ? 'باک سنڌي شاعري ڊيٽاسيٽ' : 'Baakh Sindhi poetry dataset');
        JsonLdMulti::setDescription($isSd
            ? 'کليل ڪيٽلاگ: شاعر، صنفون، موضوع ۽ مستقل URLs شاعريءَ لاءِ.'
            : 'Open catalog of Sindhi poets, genres, topics, and permanent URLs for poems.');
        JsonLdMulti::addValue('@id', url('/') . '#archive-dataset');
        JsonLdMulti::addValue('url', url('/sitemap.xml'));
        JsonLdMulti::addValue('license', url('/' . $lang . '/terms'));
        JsonLdMulti::addValue('creator', ['@id' => url('/') . '#organization']);
        JsonLdMulti::addValue('isAccessibleForFree', true);
        JsonLdMulti::addValue('inLanguage', ['sd', 'en']);
        JsonLdMulti::addValue('distribution', [
            [
                '@type' => 'DataDownload',
                'encodingFormat' => 'application/xml',
                'contentUrl' => url('/sitemap.xml'),
            ],
            [
                '@type' => 'DataDownload',
                'encodingFormat' => 'text/markdown',
                'contentUrl' => url('/llms.txt'),
            ],
            [
                '@type' => 'DataDownload',
                'encodingFormat' => 'text/markdown',
                'contentUrl' => url('/docs/llms.txt'),
            ],
            [
                '@type' => 'DataDownload',
                'encodingFormat' => 'text/markdown',
                'contentUrl' => url('/api/llms.txt'),
            ],
        ]);
    }

    private function siteNavListItems(string $locale): array
    {
        $items = [];
        foreach ($this->siteNavMap($locale === 'sd') as $i => $nav) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $nav['name'],
                'url' => url($locale . '/' . $nav['path']),
            ];
        }

        return $items;
    }

    private function siteNavMap(bool $isSd): array
    {
        return $isSd
            ? [
                ['path' => 'poets', 'name' => 'شاعر'],
                ['path' => 'poetry', 'name' => 'شاعري'],
                ['path' => 'genre', 'name' => 'صنفون'],
                ['path' => 'couplets', 'name' => 'بند'],
                ['path' => 'period', 'name' => 'دور'],
                ['path' => 'explore', 'name' => 'ڳولا'],
            ]
            : [
                ['path' => 'poets', 'name' => 'Poets'],
                ['path' => 'poetry', 'name' => 'Poetry'],
                ['path' => 'genre', 'name' => 'Genres'],
                ['path' => 'couplets', 'name' => 'Couplets'],
                ['path' => 'period', 'name' => 'Periods'],
                ['path' => 'explore', 'name' => 'Explore'],
            ];
    }

    private function breadcrumbHtml(array $crumbs): string
    {
        $parts = [];
        $last = count($crumbs) - 1;
        foreach ($crumbs as $i => [$label, $href]) {
            if ($i === $last) {
                $parts[] = '<span>' . e($label) . '</span>';
            } else {
                $parts[] = '<a href="' . e($href) . '">' . e($label) . '</a>';
            }
        }

        return '<nav class="breadcrumb-nav">' . implode(' &gt; ', $parts) . '</nav>';
    }

    private function listingCopy(string $key, bool $isSd): array
    {
        $en = [
            'poets' => ['title' => 'Sindhi Poets | Baakh', 'h1' => 'Poets', 'desc' => 'Browse Sindhi poets in the Baakh archive, from classical to contemporary voices.'],
            'poetry' => ['title' => 'Sindhi Poetry | Baakh', 'h1' => 'Poetry', 'desc' => 'Read ghazals, baits, waee, nazms, and other Sindhi poetry in the Baakh archive.'],
            'couplets' => ['title' => 'Sindhi Couplets | Baakh', 'h1' => 'Couplets', 'desc' => 'Read Sindhi couplets with original script and Roman transliteration on Baakh.'],
            'genre' => ['title' => 'Poetic Genres | Baakh', 'h1' => 'Poetic Genres', 'desc' => 'Explore Sindhi poetic forms — ghazals, baits, waee, nazms — in the Baakh archive.'],
            'period' => ['title' => 'Literary Periods | Baakh', 'h1' => 'Literary Periods', 'desc' => 'Browse Sindhi poetry by historical period in the Baakh archive.'],
            'explore' => ['title' => 'Explore Topics | Baakh', 'h1' => 'Explore', 'desc' => 'Explore Sindhi poetry by theme and topic in the Baakh archive.'],
            'prosody' => ['title' => 'Sindhi Prosody | Baakh', 'h1' => 'Prosody', 'desc' => 'Learn the meters and rhythmic forms used in Sindhi poetry.'],
            'about' => ['title' => 'About Baakh', 'h1' => 'About Baakh', 'desc' => 'Baakh is a non-profit digital archive of Sindhi poetry, preserving classical and modern works for readers and researchers.'],
            'contact' => ['title' => 'Contact Baakh', 'h1' => 'Contact Baakh', 'desc' => 'Contact Baakh Foundation about the Sindhi poetry archive: support@baakh.com, Karachi.'],
            'help' => ['title' => 'Help | Baakh', 'h1' => 'Help', 'desc' => 'Get help using the Baakh archive of Sindhi poetry.'],
            'privacy' => ['title' => 'Privacy | Baakh', 'h1' => 'Privacy', 'desc' => 'Privacy policy for the Baakh archive of Sindhi poetry: what we collect, why, and how to reach us.'],
            'terms' => ['title' => 'Terms | Baakh', 'h1' => 'Terms', 'desc' => 'Terms of use for the Baakh archive.'],
        ];
        $sd = [
            'poets' => ['title' => 'شاعر | باک', 'h1' => 'شاعر', 'desc' => 'باک تي کلاسيڪي کان جديد سنڌي شاعرن کي ڳوليو.'],
            'poetry' => ['title' => 'شاعري | باک', 'h1' => 'شاعري', 'desc' => 'باک تي غزل، بيت، وايون، نظم ۽ ٻي سنڌي شاعري پڙھو.'],
            'couplets' => ['title' => 'بند | باک', 'h1' => 'بند', 'desc' => 'باک تي سنڌي بند اصل رسم الخط ۽ رومن ۾ پڙھو.'],
            'genre' => ['title' => 'ادبي صنفون | باک', 'h1' => 'ادبي صنفون', 'desc' => 'باک تي سنڌي شاعريءَ جون صنفون — غزل، بيت، وايون، نظم — دريافت ڪريو.'],
            'period' => ['title' => 'ادبي دور | باک', 'h1' => 'ادبي دور', 'desc' => 'باک تي سنڌي شاعريءَ کي دور مطابق پڙھو.'],
            'explore' => ['title' => 'ڳولا | باک', 'h1' => 'ڳولا', 'desc' => 'باک تي موضوع مطابق سنڌي شاعري دريافت ڪريو.'],
            'prosody' => ['title' => 'عروض | باک', 'h1' => 'عروض', 'desc' => 'سنڌي شاعريءَ جا وزن ۽ روايتي ڍانچا.'],
            'about' => ['title' => 'باک بابت', 'h1' => 'باک بابت', 'desc' => 'باک سنڌي شاعريءَ جو غير منافع بخش ڊجيٽل آرڪائيو آهي، جتي کلاسيڪي ۽ جديد ڪلام محفوظ آهي.'],
            'contact' => ['title' => 'باک سان رابطو', 'h1' => 'باک سان رابطو', 'desc' => 'باک فائونڊيشن سان رابطو: support@baakh.com، ڪراچي.'],
            'help' => ['title' => 'مدد | باک', 'h1' => 'مدد', 'desc' => 'باک استعمال ڪرڻ ۾ مدد.'],
            'privacy' => ['title' => 'رازداري | باک', 'h1' => 'رازداري', 'desc' => 'باک جي رازداري پاليسي: اسان ڪهڙي معلومات گڏ ڪريون ٿا ۽ ڪيئن رابطو ڪجي.'],
            'terms' => ['title' => 'شرط | باک', 'h1' => 'شرط', 'desc' => 'باک جي استعمال جا شرط.'],
        ];

        $map = $isSd ? $sd : $en;

        return $map[$key] ?? ($isSd
            ? ['title' => 'باک', 'h1' => 'باک', 'desc' => 'باک سنڌي شاعريءَ جو ڊجيٽل آرڪائيو آهي.']
            : ['title' => 'Baakh', 'h1' => 'Baakh', 'desc' => 'Baakh is a digital archive of Sindhi poetry.']);
    }

    private function listingItemList(string $key, string $locale): array
    {
        if ($key === 'genre') {
            return $this->genreIndexListItems($locale);
        }

        return $this->siteNavListItems($locale);
    }

    private function genreIndexListItems(string $locale): array
    {
        try {
            $categories = Categories::query()
                ->whereHas('poetry', fn ($q) => $q->where('visibility', 1))
                ->with('details')
                ->orderBy('id')
                ->limit(24)
                ->get();
        } catch (\Throwable) {
            return $this->siteNavListItems($locale);
        }

        $items = [];
        foreach ($categories as $i => $cat) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $this->categoryDisplayName($cat, (string) $cat->slug),
                'url' => url("{$locale}/{$cat->slug}"),
            ];
        }

        return $items !== [] ? $items : $this->siteNavListItems($locale);
    }

    private function listingCollectionHtml(string $key, string $locale, string $h1): string
    {
        $home = $locale === 'sd' ? 'گھر' : 'Home';
        $html = $this->breadcrumbHtml([
            [$home, url("{$locale}/")],
            [$h1, url("{$locale}/{$key}")],
        ]);

        $trust = $this->trustAnchorHtml($key, $locale);
        if ($trust !== null) {
            return $html . $trust;
        }

        if ($key === 'genre') {
            $html .= '<p>' . e($this->listingCopy($key, $locale === 'sd')['desc']) . '</p><ul>';
            foreach ($this->genreIndexListItems($locale) as $item) {
                $html .= '<li><a href="' . e($item['url']) . '">' . e($item['name']) . '</a></li>';
            }
            $html .= '</ul>';
            return $html;
        }

        $html .= '<nav><ul>';
        foreach ($this->siteNavMap($locale === 'sd') as $nav) {
            $html .= '<li><a href="' . e(url($locale . '/' . $nav['path'])) . '">' . e($nav['name']) . '</a></li>';
        }
        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Crawlable copy for About / Contact / Privacy (≥500 characters, no JS).
     */
    private function trustAnchorHtml(string $key, string $locale): ?string
    {
        $base = '/' . $locale;
        $email = e((string) (config('baakh.contact.email') ?: 'support@baakh.com'));
        $street = e((string) (config('baakh.contact.street') ?: 'Office 428, 4th Floor, Mashreq Center, Expo Center'));
        $locality = e((string) (config('baakh.contact.locality') ?: 'Karachi'));
        $region = e((string) (config('baakh.contact.region') ?: 'Sindh'));
        $country = e((string) (config('baakh.contact.country') ?: 'PK'));

        if ($key === 'about' && $locale === 'sd') {
            return <<<HTML
<h2>مشن</h2>
<p>باک سنڌي شاعريءَ جو غير منافع بخش ڊجيٽل آرڪائيو آهي، جيڪو 21 مارچ 2024ع، عالمي شاعريءَ جي ڏينهن تي عوامي طور شروع ٿيو. آرڪائيو کلاسيڪي ۽ جديد غزل، بيت، وايون، نظم ۽ ٻيون صنفون اصل سنڌي رسم الخط ۽ رومن ۾ محفوظ ڪري ٿو، ته شاگرد، محقق ۽ پڙهندڙ مستحڪم URL سان حوالو ڏئي سگهن.</p>
<h2>تاريخ</h2>
<p>منصوبو 2020ع ۾ شروع ٿيو ۽ 2023ع ۾ Laravel ڏانهن ويو. باني ڪامران واحد ۽ عبيد ٿهيم ان کي ان ڪري ٺاهيو ته ڪتابن، رسالن ۽ نجي مجموعن ۾ پکڙيل شاعري هڪ هنڌ ملي. پليٽفارم جا حق باک فائونڊيشن وٽ آهن. باک تجارتي پبلشر ناهي ۽ ذاتي ڊيٽا نه وڪڻندو آهي.</p>
<h2>لاڳاپيل صفحا</h2>
<ul>
    <li><a href="{$base}/contact">رابطو</a></li>
    <li><a href="{$base}/privacy">رازداري</a></li>
    <li><a href="{$base}/help">مدد</a></li>
</ul>
HTML;
        }

        if ($key === 'about') {
            return <<<HTML
<h2>Mission</h2>
<p>Baakh is a non-profit digital archive of Sindhi poetry, launched publicly on World Poetry Day, 21 March 2024. It preserves classical and contemporary ghazals, baits, waee, nazms, and other forms in original Sindhi script and Roman transliteration so students, researchers, and readers can cite stable URLs.</p>
<h2>History</h2>
<p>The project began in 2020 and moved to Laravel in 2023. Founders Kamran Wahid and Ubaid Thaheem built it to gather poetry that had lived in books, journals, and private collections. Platform rights sit with Baakh Foundation. The archive is not a commercial publisher and does not sell personal data.</p>
<h2>Related pages</h2>
<ul>
    <li><a href="{$base}/contact">Contact</a></li>
    <li><a href="{$base}/privacy">Privacy</a></li>
    <li><a href="{$base}/help">Help</a></li>
</ul>
HTML;
        }

        if ($key === 'contact' && $locale === 'sd') {
            return <<<HTML
<h2>اي ميل</h2>
<p>آرڪائيو، فهرست جي درستي، حق يا هٽائڻ، اڪائونٽ ۽ رازداري، يا پريس لاءِ <a href="mailto:{$email}">{$email}</a> تي لکو. جنهن شاعر يا شعر بابت لکو، ان جو پبلڪ URL ڏيو. اسان انگريزي يا سنڌيءَ ۾ جواب ڏيون ٿا. هي baakh.com جو پبلڪ رابطي جو صفحو آهي.</p>
<h2>پوسٽل پتو</h2>
<p>{$street}، {$locality}، {$region}، {$country}. ڊاک تي باک فائونڊيشن ۽ baakh.com لکيو. آرڪائيو لاءِ پبلڪ فون نمبر ناهي؛ اي ميل قابل اعتماد ذريعو آهي.</p>
<h2>ڪهڙن ڪمن لاءِ</h2>
<p>شاعر جي سوانح يا شعر جي متن ۾ درستي، حق جون درخواستون، رازداري ۽ اڪائونٽ ختم ڪرڻ، تحقيق يا ڪلاس ۾ استعمال، ۽ ٽٽل URL جي رپورٽ. گيت lyrics.baakh.com تي آهن. عام خبرون ۽ ڪتابن جو واپار هن پتي لاءِ ناهي.</p>
HTML;
        }

        if ($key === 'contact') {
            return <<<HTML
<h2>Email</h2>
<p>Write to <a href="mailto:{$email}">{$email}</a> for archive questions, listing corrections, rights or takedown requests, account and privacy requests, and press notes. Include the public URL of any poet or poem you mean. We reply in English or Sindhi. This is the public contact page for baakh.com, not a commercial sales desk.</p>
<h2>Postal address</h2>
<p>{$street}, {$locality}, {$region}, {$country}. Address mail to Baakh Foundation and baakh.com. There is no public telephone number for the archive; email is the reliable channel.</p>
<h2>What to use this page for</h2>
<p>Corrections to a poet biography or poem text, rights requests, privacy and account deletion, research or classroom use, and reporting a broken public URL. Song lyrics live on lyrics.baakh.com. General news and commercial book orders are out of scope.</p>
HTML;
        }

        if ($key === 'privacy' && $locale === 'sd') {
            return <<<HTML
<h2>اسان ڪهڙي معلومات گڏ ڪريون ٿا</h2>
<p>باک هڪ آزاد، غير سرڪاري، غير منافع بخش ويب آرڪائيو آهي. شاعري پڙهڻ لاءِ اڪائونٽ گهربل ناهي. جيڪڏهن اڪائونٽ ٺاهيو ته اي ميل ۽ پاسورڊ صرف پسند ڪرڻ ۽ شاعري محفوظ ڪرڻ لاءِ استعمال ٿين ٿا. اسان ذاتي ڊيٽا مارڪيٽنگ يا منافع لاءِ استعمال نه ٿا ڪريون، ۽ ان کي ٽئين ڌر کي اشتهارن لاءِ نه ٿا ڏيون.</p>
<h2>ڪوڪيز، حفاظت ۽ ختم ڪرڻ</h2>
<p>صرف سائيٽ هلائڻ لاءِ محدود ڪوڪيز استعمال ٿين ٿيون. اڪائونٽ ڊيٽا انڪرپٽ ٿيل رکجي ٿي. اڪائونٽ ۽ لاڳاپيل ڊيٽا ختم ڪرائڻ جو حق آهي: <a href="mailto:{$email}">{$email}</a> تي لکو يا <a href="{$base}/contact">رابطو</a> صفحو استعمال ڪريو. پاليسي ۾ تبديليون هن صفحي تي ظاهر ٿينديون.</p>
HTML;
        }

        if ($key === 'privacy') {
            return <<<HTML
<h2>What we collect</h2>
<p>Baakh is an independent, non-governmental, non-profit web archive. You do not need an account to read poetry. If you create an account, email and password are used only so you can like and save poems. We do not use personal data for marketing or profit, and we do not share it with third parties for advertising.</p>
<h2>Cookies, security, and deletion</h2>
<p>Only limited cookies needed to run the site are used. Account data is stored encrypted. You may ask us to delete your account and related data: write to <a href="mailto:{$email}">{$email}</a> or use the <a href="{$base}/contact">Contact</a> page. Policy updates appear on this page. Baakh Foundation operates baakh.com from Karachi, Sindh, Pakistan.</p>
HTML;
        }

        return null;
    }

    private function prominentPoetsForGenre(Categories $genre, string $locale): array
    {
        try {
            $poets = Poets::query()
                ->where('visibility', 1)
                ->whereHas('poetry', function ($q) use ($genre) {
                    $q->where('visibility', 1)->where('category_id', $genre->id);
                })
                ->with('all_details')
                ->withCount([
                    'poetry' => function ($q) use ($genre) {
                        $q->where('visibility', 1)->where('category_id', $genre->id);
                    },
                ])
                ->orderByDesc('poetry_count')
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

    private function genreCollectionHtml(string $genreName, string $desc, array $poets, string $locale, string $slug): string
    {
        $isSd = $locale === 'sd';
        $html = $this->breadcrumbHtml([
            [$isSd ? 'گھر' : 'Home', url("{$locale}/")],
            [$isSd ? 'صنفون' : 'Genres', url("{$locale}/genre")],
            [$genreName, url("{$locale}/{$slug}")],
        ]);
        $html .= '<p class="lead-text">' . e($this->shortDesc($desc)) . '</p>';
        if ($poets === []) {
            return $html;
        }

        $html .= '<h2>' . e(trans('labels.seo_genre_poets_heading', ['genre' => $genreName])) . '</h2><ul>';
        foreach ($poets as $poet) {
            $html .= '<li><a href="' . e($poet['url']) . '">' . e($poet['name']) . '</a></li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function poetFallbackHtml(string $poetName, string $bio, ?string $bookName, Poets $poet, $works, string $lang, array $faqs = [], array $genres = []): string
    {
        $html = '<p>' . e(trans('labels.seo_intro_poet', ['poetName' => $poetName])) . '</p>';
        if ($bio !== '') {
            $html .= '<p>' . nl2br(e($bio)) . '</p>';
        }

        if ($genres !== []) {
            $html .= '<h2>' . e(trans('labels.seo_genres_heading', ['poetName' => $poetName])) . '</h2><ul>';
            foreach ($genres as $genre) {
                $html .= '<li>' . e($genre) . '</li>';
            }
            $html .= '</ul>';
        }

        $bookNames = [];
        try {
            $books = $poet->relationLoaded('books')
                ? $poet->books
                : $poet->books()->where('visibility', 1)->limit(8)->get();
            foreach ($books as $book) {
                $name = $this->bookDisplayName($book);
                if ($name) {
                    $bookNames[] = $name;
                }
            }
        } catch (\Throwable) {
            if ($bookName) {
                $bookNames[] = $bookName;
            }
        }

        if ($bookNames !== []) {
            $html .= '<h2>' . e(trans('labels.seo_books_heading')) . '</h2><ul>';
            foreach ($bookNames as $name) {
                $html .= '<li>' . e($name) . '</li>';
            }
            $html .= '</ul>';
        }

        if ($works->isNotEmpty()) {
            $html .= '<h2>' . e(trans('labels.seo_index_heading')) . '</h2><ul>';
            foreach ($works as $work) {
                $catSlug = (string) ($work->category?->slug ?? 'poetry');
                $genreName = $this->categoryDisplayName($work->category ?? null, $catSlug);
                $workTitle = $this->poemDisplayTitle($work, $lang);
                $href = '/' . $lang . '/poet/' . e($poet->poet_slug) . '/' . e($catSlug) . '/' . e($work->poetry_slug);
                $label = trim($poetName . ' ' . $genreName . ' - ' . $workTitle . ' | Baakh');
                $html .= '<li><a href="' . $href . '">' . e($label) . '</a></li>';
            }
            $html .= '</ul>';
        }

        $html .= $this->faqFallbackHtml($faqs);

        return $html;
    }

    private function poemFallbackHtml(
        string $poetName,
        string $categoryName,
        string $poemTitle,
        string $infoText,
        ?string $bookName,
        array $coupletsByLang,
        string $poetUrl,
        string $poemUrl,
        array $faqs = []
    ): string {
        $html = $this->breadcrumbHtml([
            [app()->getLocale() === 'sd' ? 'گھر' : 'Home', url(app()->getLocale() . '/')],
            [app()->getLocale() === 'sd' ? 'شاعر' : 'Poets', url(app()->getLocale() . '/poets')],
            [$poetName, $poetUrl],
            [$poemTitle, $poemUrl],
        ]);

        $html .= '<div class="ai-quick-summary-box">';
        $html .= '<p><strong>' . e(trans('labels.seo_summary_poem', [
            'title' => $poemTitle,
            'category' => $categoryName,
            'poetName' => $poetName,
        ])) . '</strong></p>';
        $html .= '</div>';

        $html .= '<p>' . e(trans('labels.seo_intro_poet', ['poetName' => $poetName]));
        $html .= ' <a href="' . e($poetUrl) . '">' . e($poetName) . '</a>';
        if ($bookName) {
            $html .= ' — ' . e($bookName);
        }
        $html .= '</p>';

        $original = $coupletsByLang['sd'] ?? [];
        $roman = $coupletsByLang['en'] ?? [];

        if ($original !== []) {
            $html .= '<article class="script-column-block" lang="sd" dir="rtl">';
            $html .= '<h2>' . e(trans('labels.seo_original_heading')) . '</h2>';
            $html .= '<div class="poetry-lines-render-arabic">';
            foreach (array_slice($original, 0, 20) as $line) {
                $html .= '<p>' . e($line) . '</p>';
            }
            $html .= '</div></article>';
        }

        if ($roman !== []) {
            $html .= '<article class="script-column-block" lang="sd-Latn">';
            $html .= '<h2>' . e(trans('labels.seo_roman_heading')) . '</h2>';
            $html .= '<div class="poetry-lines-render-roman">';
            foreach (array_slice($roman, 0, 20) as $line) {
                $html .= '<p>' . e($line) . '</p>';
            }
            $html .= '</div></article>';
        }

        if ($infoText !== '') {
            $html .= '<article class="script-column-block" lang="en">';
            $html .= '<h2>' . e(trans('labels.seo_english_heading')) . '</h2>';
            $html .= '<div class="poetry-lines-render-english"><p>' . nl2br(e($infoText)) . '</p></div>';
            $html .= '</article>';
        }

        $html .= $this->faqFallbackHtml($faqs);

        return $html;
    }

    private function faqFallbackHtml(array $faqs): string
    {
        if ($faqs === []) {
            return '';
        }

        $html = '<h2>' . e(app()->getLocale() === 'sd' ? 'سوال' : 'FAQ') . '</h2>';
        foreach ($faqs as $faq) {
            $html .= '<h3>' . e($faq['name'] ?? '') . '</h3>';
            $html .= '<p>' . e($faq['acceptedAnswer']['text'] ?? '') . '</p>';
        }

        return $html;
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
