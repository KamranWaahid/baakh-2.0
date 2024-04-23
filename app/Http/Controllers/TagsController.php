<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Couplets;
use App\Models\Media;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\UserComments;
use App\Traits\BaakhLikedTrait;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class TagsController extends UserController
{
    use BaakhSeoTrait, BaakhLikedTrait;
    public function index()
    {
        $locale = app()->getLocale();
        $tags_db = Tags::where('lang', $locale)->orderBy('tag', 'asc')->get(); // get all tags
        $totalTags = count($tags_db);

        $tags = [];
        foreach ($tags_db as $tag) {
            $firstLetter = strtoupper(mb_substr($tag->tag, 0, 1));
            $tags[$firstLetter][] = $tag;
        }

        if($locale == 'sd')
        {
            // Define the custom sorting order for Sindhi characters
            $customSortingOrder = [
                'ا' ,'ب' ,'ٻ' ,'ڀ' ,'ت' ,'ٿ' ,'ٽ' ,'ٺ' ,'ث' ,'پ' ,'ج' ,'ڄ' ,'جه' ,'ڃ' ,'چ' ,'ڇ' ,'ح' ,'خ' ,'د' ,'ڌ' ,'ڏ' ,'ڊ' ,'ڍ' ,'ذ' ,'ر' ,'ز' ,'ڙ' ,'س' ,'ش' ,'ص' ,'ض' ,'ط' ,'ظ' ,'ع' ,'غ' ,'ف' ,'ڦ' ,'ق' ,'ڪ' ,'ک' ,'گ' ,'ڳ' ,'گه' ,'ڱ' ,'ل' ,'م' ,'ن' ,'ڻ' ,'و' ,'ه', 'ھ' ,'ء' ,'ي'
            ];

            // Sort the groups by the first letter using the custom order
            uksort($tags, function ($a, $b) use ($customSortingOrder) {
                $aIndex = array_search($a, $customSortingOrder);
                $bIndex = array_search($b, $customSortingOrder);

                return $aIndex - $bIndex;
            });
        }else{
            // Sort the groups by the first letter
            ksort($tags);
        }

        // SEO 
        $title = trans('labels.tags');
        $desc = ($locale == 'sd') ? 'Sindhi Description' : 'English Description';
        $this->SEO_General($title, 'Here is home page description of Baakh');

        return view('web.couplets.all_tags', compact('tags', 'totalTags'));
    }
 
    
    /**
     * Poetry With Tag
     * 
    */
    public function show($tag, $category_get = 'couplets')
    {
        $poetryUrl = URL::localized(route('poetry.with-tag', ['tag'=>$tag, 'category'=> $category_get]));

        // site language
        $locale = app()->getLocale();

        // get tags information
        $profile = Tags::where('slug', $tag)->where('lang', $locale)->firstOrFail();
        // language 
        $locale = app()->getLocale();
         
        
        if(!$profile){
            return redirect(route('poets.all'));
        }
        
        // fetch category
        $category = Categories::where(['slug' => $category_get])
        ->with(['detail' => function($q) use ($locale) {
            $q->where('lang', $locale);
        }])
        ->first();

        $poetId = $profile->id;

        // count poetry
        $categoriesWithCounts = Categories::withCount(['poetry' => function ($query) use ($tag, $locale) {
            $query->whereJsonContains('poetry_tags', $tag)
            ->where('lang', $locale);
        }])
        ->whereHas('poetry', function ($query) use ($tag, $locale) {
            $query->whereJsonContains('poetry_tags', $tag)
            ->where('lang', $locale);
        })
        ->with(['detail' => function($q) {
            $q->where('lang', app()->getLocale());
        }])
        ->get(['slug', 'cat_name', 'poetry_count']);
        
        // check all couplets with tag
        $total_couplets = Couplets::whereJsonContains('couplet_tags', $tag)
        ->where(['poet_id' => $poetId, 'lang' => $locale, 'poetry_id' => 0])->count();
        
        if($category !==null){
            $category_name = ' | '.$category->detail->cat_name;
            // active or selected category, pass this to profile and use in the tabls selection
            $active_category = $category->slug;
            $title = trans_choice('labels.best_poetry_on_title_category', 1, ['title' => $profile->tag, 'category' => $category->detail->cat_name]);
        }
        else{
            $category_name = ' | شعر';
            $active_category = 'couplets';
            $title = trans_choice('labels.best_poetry_on_title_category', 1, ['title' => $profile->tag, 'category' => 'شعر']);
        }
        
        $famous_poets = Poets::with(['details' => function ($query) use ($locale) {
            $query->where('lang', $locale);
        }])
        ->where(['visibility' => 1, 'is_featured' => 1])
        ->where('id', '!=', $poetId)
        ->whereHas('details', function ($query) use ($locale) {
            $query->where('lang', $locale);
        })
        ->inRandomOrder()
        ->limit(10)
        ->get();    

        $poet_url = url('/tags').'/'.$tag;
        
        
        // SEO
        //$title = trans_choice('labels.tag', 1, ['count' => 1]).' | '. $profile->tag.$category_name;
        $this->SEO_General($title, 'This is description');

        
        $liked = $this->isLiked('Tags', $profile->slug);

        return view('web.poetry.with-tags', compact('profile', 'title', 'famous_poets', 'total_couplets',  'active_category', 'poet_url', 'categoriesWithCounts', 'liked'));
 
        
    }


    /**
     * Load More Poetry of Author
     * This method accepts few parameters to display the result in Ajax
     * This method is called on Poet's profile page [with_slug($name)]
     * @lang = site_lang
     * @poet_id
     * @category
     * @start [start_from]
     * @limit [display_items]
    */
    public function load_more_poetry(Request $request)
    {
        $user = Auth::user();
        // validate request fetch all parameters
        $request->validate([
            'tag' => 'required',
            'category' => 'required',
            'start' => 'required',
            'limit' => 'required'
        ]);

        $start = $request->start;
        $limit = $request->limit;

        $category = Categories::where('slug', $request->category)->first();

        
        $poetryQuery = ($request->category === 'couplets')
        ? Couplets::where(['poetry_id' => 0, 'lang' => app()->getLocale()])->whereJsonContains('couplet_tags', $request->tag)
        : Poetry::with('category')->where([
            'category_id' => $category->id,
            'lang' => app()->getLocale()
        ])->whereJsonContains('poetry_tags', $request->tag);
        $poetry = $poetryQuery->skip($start)->take($limit)->get();

        if (count($poetry) < 1 || $poetry == null) {
            # send response to the json
            $message = [
                'message' => 'Data not available',
                'type' => 'error',
                'code' => 204
            ];
        }else{
            // use foreach loop to display results
            
            $html = ''; // empty variable to store HTML data

            #if request is for couplets, change content settins of html foreach
            if($request->category === 'couplets'){
                // use foreach loop to load couplets HTML elements
                foreach ($poetry as $index =>  $item) {
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
                    
                    ///$html .= view('web.poets.couplets-list', ['index' => $index, 'item'=> $item, 'liked' => $liked])->render();
                }
            }else{
                // requested category is not couplet
                // use foreach loop to load couplets HTML elements of non-couplets
                foreach ($poetry as $index =>  $item) {
                    $liked = $this->isLiked('Poetry', $item->poetry_slug);
                    //$html .= view('web.poets.poetry-list', ['index' => $index, 'item'=> $item, 'liked' => $liked])->render();
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

            // Update the $start value for the next batch of couplets
            //$start +=  $limit;
            $data_start = ($start + count($poetry));
            $html .= '<div class="loaded-poetry" data-limit="'.$limit.'" data-start="'.$data_start.'"></div>';
            $message = [
                'message' => 'Data not available',
                'type' => 'success',
                'html' => $html,
                'start' => $start
            ];
        }

        // response to the page
        return response()->json($message);
    }

 
}
