<?php 
namespace App\Traits;
use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\OpenGraph;
use Artesaos\SEOTools\Facades\TwitterCard;
use Artesaos\SEOTools\Facades\JsonLd;
use Illuminate\Support\Str;
use League\OAuth1\Client\Server\Twitter;

use function PHPUnit\Framework\isNull;


trait BaakhSeoTrait {
    
    public function SEO_General($title, $content, $image = null, $keywords = null)
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