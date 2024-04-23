<?php

namespace App\Http\Controllers;

use App\Models\Bundles;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\Tags;
use App\Traits\BaakhSeoTrait;
use Illuminate\Support\Facades\URL;


class BundlesController extends UserController
{
    use BaakhSeoTrait;
    protected $l; // locale
    public function __construct()
    {
        parent::__construct();
        $this->l = app()->getLocale();
    }

    public function index()
    {

    }

    public function with_slug($slug)
    {
        $bundle = Bundles::where(['slug' => $slug])->first();
        if(is_null($bundle))
        {
            return to_route('poetry.bundle');
        }
        $other_bundles = Bundles::inRandomOrder()->limit(5)->get(); //where(['lang' => $this->l])-> for translation
        $bundle_poetry = $this->getPoetryOfBundle($bundle->bundle_type, $bundle->items);
        $profileUrl = URL::localized(route('poetry.bundle.slug', $slug));
        $liked = $this->isLiked('Bundles', $slug);

        // SEO 
        $title = trans('labels.bundles').' - '.$bundle->title;
        $this->SEO_General($title, $bundle->bundle_info, $bundle->bundle_thumbnail);

        return view('web.bundles.bundle_with_slug', compact('bundle', 'liked', 'bundle_poetry', 'other_bundles', 'profileUrl'));
    }


    // get poetry of bundle
    private function getPoetryOfBundle($type, $BundleItems)
    {
        $html = '';
        if($type === 'couplet') // fetch all couplets
        {
            foreach ($BundleItems as $bitems) {
                $couplets = Couplets::where('id', $bitems->couplet_id)->get();
                if ($couplets) {
                    $couplets->load(['poet.details' => function ($query) {
                        $query->where('poets_detail.lang', app()->getLocale());
                    }]);
                }
                
                foreach ($couplets as $index =>  $item) {
                    $liked = $this->isLiked('Couplets', $item->couplet_slug);
                    $poetName = $item->poet->details->poet_laqab;
                    if($item->couplet_tags != NULL)
                    {
                        $decodeTags = json_decode($item->couplet_tags);
                        $usedTags = Tags::where('lang', $item->lang)->whereIn('slug', $decodeTags)->pluck('tag', 'slug')->toArray();
                        $html .= view('web.poets.couplets-list', ['index' => $index, 'item'=> $item, 'usedTags' => $usedTags, 'liked'=> $liked, 'poetName'=>$poetName])->render();
                    }else{
                        $html .= view('web.poets.couplets-list', ['index' => $index, 'item'=> $item, 'liked' => $liked, 'poetName'=>$poetName])->render();
                    }

                }
            }
            
        }else{
            foreach ($BundleItems as $bitems) {
                $couplets = Poetry::where('id', $bitems->couplet_id)->get();
                if ($couplets) {
                    $couplets->load(['poet.details' => function ($query) {
                        $query->where('poets_detail.lang', app()->getLocale());
                    }]);
                }
                $liked = $this->isLiked('Poetry', $bitems->poetry_slug);
                foreach ($couplets as $index =>  $item) {
                    $poetName = $item->poet->details->poet_laqab;
                    if($item->poetry_tags != NULL)
                    {
                        $decodeTags = json_decode($item->poetry_tags);
                        $usedTags = Tags::where('lang', $item->lang)->whereIn('slug', $decodeTags)->pluck('tag', 'slug')->toArray();
                        $html .= view('web.poets.poetry-list', ['index' => $index, 'item'=> $item, 'usedTags' => $usedTags, 'liked' => $liked, 'poetName' => $poetName])->render();
                    }else{
                        $html .= view('web.poets.poetry-list', ['index' => $index, 'item'=> $item, 'liked' => $liked, 'poetName' => $poetName])->render();
                    }
                    
                }
            }
        }
    
        
        return $html;
    }


    /**
     * Get Bundle's Poetry with Reference ID and Reference Type
     */
    private function getBundlePoetry()
    {
        
    }
}
