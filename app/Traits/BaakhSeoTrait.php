<?php 
namespace App\Traits;

use App\Models\Poets;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Artesaos\SEOTools\Facades\SEOTools;
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
        $keywords = array_merge($keywords, $array);
        $keywords = array_slice($keywords, 0, 10);
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
    public function SEO_Author(Poets $author) {
        $author_image = $author->thumbnail; // Use the thumbnail method for the image URL
        $name_sd = $author->name_sd; // Author's name in Sindhi
        $name_en = $author->name_en; // Author's name in English
        $bio = strip_tags($author->auth_info); // Get the author's information
        $short_bio = Str::limit($bio, 161); // Shorten bio for SEO description
        $url = route('web.author.single', $author->id); // Assuming this route exists and uses the slug
        $birth_date = $author->birth_date;
        $death_date = $author->death_date;


         // Collecting books authored for SEO
        $books = $author->books; // Assuming the relationship is defined
        $bookTitles = $books->pluck('name_sd')->toArray(); // Collecting titles
        $bookTitlesString = implode(', ', $bookTitles);

        // Keywords
        $keywords = $this->appendKeywords([$name_sd, $name_en . '\'s Books']);

        // SEO metadata
        SEOTools::addImages(asset($author_image));
        SEOMeta::setTitle($name_sd); // Set title in Sindhi
        SEOMeta::setDescription($short_bio);
        SEOMeta::setCanonical($url);
        SEOMeta::addKeyword($keywords);

        // OpenGraph SEO
        SEOMeta::setTitle($name_sd); // Set title in Sindhi
        OpenGraph::setDescription($short_bio);
        OpenGraph::setType('profile');
        OpenGraph::setUrl($url);
        OpenGraph::addImage(asset($author_image), ['height' => 600, 'width' => 400]);
        OpenGraph::setArticle([
            'author' => $name_sd,
            'section' => 'Authors',
            'tag' => $keywords,
            'books' => $bookTitlesString,
        ]);

        // JSON-LD structured data for authors
        JsonLd::setTitle($name_sd);
        JsonLd::setDescription($short_bio);
        JsonLd::setType('Person');
        JsonLd::addImage(asset($author_image));
        JsonLd::setUrl($url);

        JsonLd::addValue('name', $name_sd);
        JsonLd::addValue('alternateName', $name_en); // Adding English name
        JsonLd::addValue('birthDate', $birth_date);
        JsonLd::addValue('deathDate', $death_date);
        JsonLd::addValue('sameAs', [
            // Assuming you might have social links
            $author->facebook_profile,
            $author->twitter_profile,
            $author->linkedin_profile,
        ]);
        
        $jsonLdData = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author->name_sd, // Your person's name
            'url' => route('web.author.single', $author->id), // Optional: person's URL
            'sameAs' => $author->wikipedia, // Optional: links to social profiles
             
            'mainEntityOfPage' => array_map(function ($book) use ($author) {
                return [
                    '@type' => 'Book',
                    'name' => $book['name_sd'],
                    'url' => route('web.book.view', $book['id']),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $author->name_sd, // Author info here
                    ],
                ];
            }, $books->toArray()),
        ];
        
        // Add this JSON-LD structure to your response
        JsonLd::addValue('person', $jsonLdData);

        return SEOTools::generate();
    }


    public function SEO_General__s($title, $content, $image = null, $keywords = null)
    {
    $desc = $this->shortDesc($content);
    $image = (!is_null($image) && file_exists($image)) ? asset($image) : asset('assets/img/Baakh-beta.svg');
    SEOMeta::setTitle($title);
    SEOMeta::setDescription($desc);
    if(!is_null($keywords)){
        SEOMeta::addKeyword($keywords);
    }

    OpenGraph::setDescription($desc);
    OpenGraph::setTitle($title);
    OpenGraph::setUrl(url()->current());
    OpenGraph::addImage($image);

    TwitterCard::setType('summary_large_image');
    TwitterCard::addValue('twitter:domain', 'baakh.com');
    TwitterCard::setTitle($title);
    TwitterCard::setImage($image);
    TwitterCard::setDescription($desc);
    TwitterCard::setUrl(url()->current());
    TwitterCard::setSite('@BaakhConnect');

    JsonLd::setTitle($title);
    JsonLd::setDescription($desc);
    JsonLd::setType('Corporate');
    }

    public function SEO_Poet(){

    }

    public function SEO_Poetry($title, $content, $image = null, $keywords = null)
    {
        $desc = $this->shortDesc($content);
        $image = (!is_null($image) && file_exists($image)) ? asset($image) : asset('assets/img/Baakh-beta.svg');
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($desc);
        if(!is_null($keywords)){
            SEOMeta::addKeyword($keywords);
        }

        OpenGraph::setDescription($desc);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl(url()->current());
        OpenGraph::addImage($image);

        
        TwitterCard::setType('summary_large_image');
        TwitterCard::addValue('twitter:domain', 'baakh.com');
        TwitterCard::setTitle($title);
        TwitterCard::setImage($image);
        TwitterCard::setDescription($desc);
        TwitterCard::setUrl(url()->current());
        TwitterCard::setSite('@BaakhConnect');

        JsonLd::setTitle($title);
        JsonLd::setDescription($desc);
        JsonLd::setType('article');
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