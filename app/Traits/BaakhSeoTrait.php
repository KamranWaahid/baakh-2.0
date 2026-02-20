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
        $image = $seo_image ? $seo_image : asset('assets/og/baakh-1200x630.png');

        $currentLang = app()->getLocale();
        $isSd = $currentLang === 'sd';

        // hreflang setup
        $path = request()->path();
        $segments = explode('/', $path);

        // Remove existing locale prefix if any to build base path
        if ($segments[0] === 'en' || $segments[0] === 'sd') {
            array_shift($segments);
        }
        $innerPath = implode('/', $segments);

        $sdUrl = url('sd/' . $innerPath);
        $enUrl = url('en/' . $innerPath);

        // Handle keywords
        if (!is_null($keywords)) {
            SEOMeta::addKeyword($keywords);
        } else {
            SEOMeta::addKeyword($this->extractKeywords($desc));
        }

        // Set SEO meta tags
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($desc);
        SEOMeta::setCanonical(url()->current());

        // Add alternate languages for SEO
        SEOMeta::addAlternateLanguage('en', $enUrl);
        SEOMeta::addAlternateLanguage('sd', $sdUrl);
        SEOMeta::addAlternateLanguage('x-default', $enUrl); // x-default usually points to the main/default version

        // Set OpenGraph data
        OpenGraph::setDescription($additionalData['og_description'] ?? $desc);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl(url()->current());
        OpenGraph::addImage($image);
        OpenGraph::addProperty('type', 'website');
        OpenGraph::setSiteName($isSd ? 'باک' : 'Baakh');

        if (isset($additionalData['og_image_alt'])) {
            OpenGraph::addProperty('image:alt', $additionalData['og_image_alt']);
        } else {
            OpenGraph::addProperty('image:alt', $title);
        }

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

        $poetDetails = $poet->details;
        $locations = Cache::rememberForever($cacheKeyLocations, function () use ($poetDetails) {
            return [
                'birth' => $poetDetails->birthPlaceComplete(),
                'death' => $poetDetails->deathPlaceComplete()
            ];
        });

        $poetImage = $poet->poet_pic;
        $poetLaqab = $poetDetails->poet_laqab;
        $final_name = mb_substr($poetLaqab, -1) == 'و' ? mb_substr($poetLaqab, 0, -1) . 'ي' : $poetLaqab;
        $poet_name = $poetDetails->poet_name;
        $tagline = $poetDetails->tagline;
        $currentLang = app()->getLocale();

        if ($category != '') {
            $title = trans('labels.seo_title_poet_category', ['categoryName' => $category, 'poetLaqab' => $final_name]);
        } else {
            $title = trans('labels.seo_title_poet', ['poetLaqab' => $final_name]);
        }


        // $name_en = $author->name_en; // Author's name in English
        $bio = strip_tags($poet->details->poet_bio);
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

        // SEO metadata
        SEOTools::addImages(asset($poetImage));
        SEOMeta::setTitle($title); // Set title in Sindhi
        SEOMeta::setDescription($shortBio);
        SEOMeta::setCanonical($url);
        SEOMeta::addAlternateLanguage($alternateLang, $alternateUrl);
        SEOMeta::addKeyword($keywords);

        // OpenGraph Metadata
        OpenGraph::setTitle($title);
        OpenGraph::setDescription($shortBio);
        OpenGraph::setType('profile');
        OpenGraph::setUrl($url);
        OpenGraph::addImage(asset($poetImage), ['height' => 600, 'width' => 400]);
        OpenGraph::setArticle([
            'author' => $poetLaqab,
            'section' => 'Authors',
            'tag' => $keywords,
        ]);

        TwitterCard::setType('summary_large_image');
        TwitterCard::addValue('twitter:domain', 'baakh.com');
        TwitterCard::setTitle($poetLaqab);
        TwitterCard::setImage($poetImage);
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

        // death place
        if ($locations['birth']['cityName']) {
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

        if ($locations['death']['cityName']) {
            $jsonLdData['deathDate'] = $deathDate;
            $_add_death = $locations['birth'];
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

        // return SEOTools::generate();
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
        $poetDetails = $poetModel->details;

        $p_category = $poetry->category;

        $couplets = $poetry->all_couplets;

        $poetLaqab = $poetDetails->poet_laqab;

        $final_name = mb_substr($poetLaqab, -1) == 'و' ? mb_substr($poetLaqab, 0, -1) . 'ي' : $poetLaqab;

        $gender = $p_category->gender ? CategoryGenderEnum::tryFrom($p_category->gender) : null;

        if ($currentLang === 'en') {
            $poetName = $poetLaqab . "'s";
        } else {
            $poetName = $final_name . ($gender ? ' ' . $gender->singular() : '');
        }


        $title = trans('labels.seo_custom_bio_poetry', ['category' => $p_category->category_name, 'poetName' => $poetName, 'title' => $poetryInfo->title]);
        $stanzas = [];
        // if there is no info then make it from couplets
        if ($poetryInfo->info != null || $poetryInfo->info != '') {
            $shortBio = $poetryInfo->info . ' ' . $poetryInfo->source ?? '';
        } else {
            if (count($couplets) > 0) {
                foreach ($couplets as $couplet) {
                    $stanzas[] = [
                        '@type' => 'CreativeWork',
                        'text' => $couplet->couplet_text
                    ];
                }
                $shortBio = Str::limit(preg_replace('/\s+/', ' ', strip_tags($couplets[0]->couplet_text)), 160, '...');
            } else {
                $shortBio = $title;
            }
        }

        $poetImage = $poetModel->poet_pic;
        $keywords = $this->appendKeywords(json_decode($poetry->poetry_tags)); // ["ishq", "love", "rain"]

        $url = url("{$currentLang}/poet/{$poetModel->poet_slug}/{$p_category->category_slug}/{$poetry->poetry_slug}");

        $alternateLang = $currentLang === 'en' ? 'sd' : 'en';
        $alternateUrl = url("{$alternateLang}/poet/{$poetModel->poet_slug}/{$p_category->category_slug}/{$poetry->poetry_slug}");

        $image = $seo_image ? $seo_image : asset($poetImage);

        SEOTools::addImages($image);
        SEOMeta::setTitle($title); // Set title in Sindhi
        SEOMeta::setDescription($shortBio);
        SEOMeta::setCanonical($url);
        SEOMeta::addAlternateLanguage($alternateLang, $alternateUrl);
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
        $locations = Cache::rememberForever($cacheKeyLocations, function () use ($poetDetails) {
            return [
                'birth' => $poetDetails->birthPlaceComplete(),
                'death' => $poetDetails->deathPlaceComplete()
            ];
        });

        $poetLaqab = $poetDetails->poet_laqab;
        $poet_name = $poetDetails->poet_name;
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
        if ($locations['birth']['cityName']) {
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

        if ($locations['death']['cityName']) {
            $jsonLdPoetData['deathDate'] = $deathDate;
            $_add_death = $locations['birth'];
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