<?php 
namespace App\Traits;

use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\Poets;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\OAuth1\Client\Server\Twitter;

use function PHPUnit\Framework\isNull;


trait BaakhSeoTrait {
    
    public function SEO_General($title, $desc, $seo_image = null, $keywords = null, $additionalData = [])
    {
        // Set default image if SEO image is not provided or doesn't exist
        $image = ($seo_image && file_exists($seo_image)) ? asset($seo_image) : asset('assets/placeholder.png');
        
        // Set SEO meta tags
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($desc);
        SEOMeta::setCanonical(url()->current());
        
        // Handle keywords
        if (!is_null($keywords)) {
            SEOMeta::addKeyword($keywords);
        } else {
            SEOMeta::addKeyword($this->extractKeywords($desc));
        }

        // Set OpenGraph data
        OpenGraph::setDescription($desc);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl(url()->current());
        OpenGraph::addImage($image);
        
        // You can allow additional OpenGraph properties from controller via $additionalData['opengraph']
        if (isset($additionalData['opengraph']) && is_array($additionalData['opengraph'])) {
            foreach ($additionalData['opengraph'] as $property => $value) {
                OpenGraph::addProperty($property, $value);
            }
        }

        // Set JSON-LD structured data (default for WebPage or FAQPage)
        JsonLd::addValue('@context', 'https://schema.org');
        JsonLd::addValue('@type', $additionalData['json_ld_type'] ?? 'WebPage');
        JsonLd::addValue('name', env('APP_NAME'));
        JsonLd::addValue('description', $desc);
        JsonLd::addValue('url', env('APP_URL')); // Website URL
        JsonLd::addValue('image', $image);

        // Allow additional JSON-LD properties from controller
        if (isset($additionalData['jsonld']) && is_array($additionalData['jsonld'])) {
            foreach ($additionalData['jsonld'] as $property => $value) {
                JsonLd::addValue($property, $value);
            }
        }

        // FAQ Schema: Dynamically handle FAQ data if provided
        if (isset($additionalData['faq']) && is_array($additionalData['faq'])) {
            $faqData = [];
            foreach ($additionalData['faq'] as $faq) {
                $faqData[] = [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer']
                    ]
                ];
            }
            JsonLd::addValue('mainEntity', $faqData);
        }

        // Set general titles and descriptions for JSON-LD
        JsonLd::setTitle($title);
        JsonLd::setDescription($desc);
        JsonLd::setType($additionalData['json_ld_type'] ?? 'WebPage');
    }


    /**
     * Add Two Keywords Dynamically
     */
    public function appendKeywords($array) {
        $keywords = ['Books on Literature', 'Sindhi Books', 'Poetry', 'History Books', 'Fiction Books', 'Sheikh Ayaz',  'Sindh Salamat Kitab Ghar', 'Sindhi Novel'];
        if($array && is_array($array)) {
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
    public function SEO_Poet(Poets $poetModel, $category) {
        $poetIdWithLang = $poetModel->id . '_lng_'.app()->getLocale();
        $cacheKeyLocations = 'cache_poet_'.$poetIdWithLang.'_locations';

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
        $poet_name = $poetDetails->poet_name;
        $tagline = $poetDetails->tagline;
        $currentLang = app()->getLocale();

        if($category !='') {
            $title = trans('labels.seo_title_poet_category', [ 'categoryName' => $category , 'poetLaqab' => $poetLaqab]);
        }else{
            $title = trans('labels.seo_title_poet', ['poetLaqab' => $poetLaqab]);
        }
        

        // $name_en = $author->name_en; // Author's name in English
        $bio = strip_tags($poet->details->poet_bio);
        $shortBio = Str::limit($bio, 161); // Shorten bio for SEO description
        $url = URL::localized(route('poets.slug', ['category' => $category, 'name' => $poet->poet_slug])); // Assuming this route exists and uses the slug
        
        $alternateLang = $currentLang === 'en' ? 'sd' : 'en';
       
        if(request()->query('lang')) {
            $alternateUrl = route('poets.slug', [
                'category' => $category, 
                'name' => $poet->poet_slug,
            ]);
        }else{
            $alternateUrl = route('poets.slug', [
                'category' => $category, 
                'name' => $poet->poet_slug,
                'lang' => $alternateLang,
            ]);
        }
        

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
        if($deathDate) {
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
        if($locations['birth']['cityName']) {
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

        if($locations['death']['cityName']) {
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
    public function SEO_Poetry(Poetry $poetry, $poetryCategory, Poets $poetModel)
    {
        // dd($poetry, $couplets, $poetModel);
        $poetIdWithLang = $poetModel->id . '_lng_'.app()->getLocale();
        $cacheKeyLocations = 'cache_poet_'.$poetIdWithLang.'_locations';
        $poetryInfo = $poetry->info;
        $poetDetails = $poetModel->details;

        $couplets = $poetry->all_couplets;

        $title =  trans('labels.seo_custom_bio_poetry', ['category' => $poetry->category->category_name, 'poetName' => $poetDetails->poet_laqab, 'title' => $poetryInfo->title]);
        $stanzas = [];
        // if there is no info then make it from couplets
        if($poetryInfo->info != null || $poetryInfo->info != '') {
            $shortBio = $poetryInfo->info . ' '. $poetryInfo->source ?? ''; 
        }else{
            if(count($couplets) > 0) {
                foreach ($couplets as $couplet) {
                    $stanzas[] = [
                        '@type' => 'CreativeWork',
                        'text' => $couplet->couplet_text
                    ];
                }
                $shortBio = Str::limit(preg_replace('/\s+/', ' ', strip_tags($couplets[0]->couplet_text)),  160, '...');
            }else{
                $shortBio = $title;
            }
        }

        $poetImage = $poetModel->poet_pic;
        $keywords = $this->appendKeywords(json_decode($poetry->poetry_tags)); // ["ishq", "love", "rain"]
        $currentLang = app()->getLocale();
        $url = URL::localized(route('poetry.with-slug', ['category' => $poetryCategory, 'slug' => $poetry->poetry_slug ]));

        $alternateLang = $currentLang === 'en' ? 'sd' : 'en';
        // Check if the current URL already has a ?lang parameter
        if (request()->query('lang')) {
            $alternateUrl = route('poetry.with-slug', [
                'category' => $poetryCategory,
                'slug' => $poetry->poetry_slug,
            ]);
        } else {
            $alternateUrl = route('poetry.with-slug', [
                'category' => $poetryCategory,
                'slug' => $poetry->poetry_slug,
                'lang' => $alternateLang,
            ]);
        }

        SEOTools::addImages(asset($poetImage));
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
        OpenGraph::addImage(asset($poetImage), ['height' => 600, 'width' => 400]);
        

        TwitterCard::setType('summary_large_image');
        TwitterCard::addValue('twitter:domain', 'baakh.com');
        TwitterCard::setTitle($title);
        TwitterCard::setImage($poetImage);
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
            'url' => URL::localized(route('poets.slug', ['category' => '', 'name' => $poetModel->poet_slug])),
            'image' => asset($poetImage),
        ];

       
        // death place
        if($locations['birth']['cityName']) {
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

        if($locations['death']['cityName']) {
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