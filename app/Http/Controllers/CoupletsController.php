<?php

namespace App\Http\Controllers;

use App\Models\Bundles;
use App\Models\Couplets;
use App\Models\LikeDislike;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\UserComments;
use App\Models\UserLikes;
use App\Traits\BaakhLikedTrait;
use App\Traits\BaakhSeoTrait;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isNull;

class CoupletsController extends UserController
{

    use BaakhSeoTrait;
    use BaakhLikedTrait;
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $locale = app()->getLocale();
        $tags = Tags::where('lang', $locale)->limit(18)->get(); // get all tags
        // $bundles = Bundles::where('is_featured', true)->get();
        
        $topCouplets = $this->getFavoritedCouplets();
         
  
        // SEO 
        $title = trans('labels.couplets');
        $desc = ($locale == 'sd') ? 'Sindhi Description' : 'English Description';
        $this->SEO_General($title, 'Here is couplets page description of Baakh');

    //    dd($topCouplets);
 

        return view('web.couplets.index', compact('topCouplets', 'tags'));
    }

    /**
     * Most Liked couplets
     */
    public function mostLikedCouplets()
    {
        $couplets = Couplets::with(['poet:id,poet_slug'])
                ->whereRaw('LENGTH(poetry_couplets.couplet_text) - LENGTH(REPLACE(poetry_couplets.couplet_text, "\n", "")) = 1')
                ->withCount('likes')
                ->where(['lang' => app()->getLocale(), 'poetry_id' => 0])
                ->orderByDesc('likes_count')
                ->limit(400)
                ->paginate(14);
        if(!$couplets) {
            abort(404);
        }
        
        $title = trans('labels.most_liked_couplets') . ' - ' . trans('labels.title');
        $this->SEO_General($title, trans('labels.most_liked_couplets_desc'));
        return view('web.couplets.most_liked_couplets', compact('couplets'));
    }

    /**
     * Show Couplet with Slug
     */
    public function show($slug) {

        $poetryUrl = URL::localized(route('web.couplets.single', ['slug'=> $slug]));
        $locale = (request()->input('lang')) ?  request()->input('lang') :  app()->getLocale();
         
        $couplet = Couplets::with([
            'info' => function ($query) use ($locale) {
                $query->where('lang', $locale)->take(1);
            }
            
        ])
        ->where(['couplet_slug' => $slug, 'lang' => $locale])
        ->first();

        $couplet->load('poet');
        
        if(!$couplet) {
            abort(404);
        }
        
        
        // Poetry Tags
        if(!is_null($couplet->couplet_tags) && $couplet->couplet_tags !='null'){
            $decodeTags = json_decode($couplet->couplet_tags);
            $used_tags = Tags::whereIn('slug', $decodeTags)->where('lang' , $locale)->pluck('tag', 'slug');
        }else{
            $used_tags = null;
        }
 

        if(!isNull($couplet->poetry_id) || $couplet->poetry_id > 0) {
            $haveMainPoetry = $this->getMainPoetryUrl($couplet->poetry_id);
        }else{
            $haveMainPoetry = null;
        }
        
        return view('web.couplets.show', compact('couplet','used_tags','poetryUrl'));
    }

    

    private function getFavoritedCouplets()
    {
        $couplets = Couplets::with(['poet:id,poet_slug'])
                ->whereRaw('LENGTH(poetry_couplets.couplet_text) - LENGTH(REPLACE(poetry_couplets.couplet_text, "\n", "")) = 1')
                ->withCount('likes')
                ->where(['lang' => app()->getLocale(), 'poetry_id' => 0])
                ->orderByDesc('likes_count')
                ->limit(8)
                ->get();
        
                
        $result = [];
        foreach ($couplets as $k => $items) {
            if($k <= 3) {
                $result['left'][] = $items;
            }

            if($k > 3) {
                $result['right'][] = $items;
            }
        }
        return $result;
    }

    /**
     * Get Main Poetry URL from couplet ID
     */
    public function getMainPoetryUrl($id) {
        $poetryIs = Poetry::with([
            'category' => function ($query) use ($id) {
                $query->where('id', $id)->take(1); // Load category with the specified language
            }
        ])->first();
        return dd($poetryIs);
    }
}
