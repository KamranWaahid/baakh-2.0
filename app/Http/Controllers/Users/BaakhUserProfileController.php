<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Couplets;
use App\Models\Languages;
use App\Models\LikeDislike;
use App\Models\Poetry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BaakhUserProfileController extends Controller
{
    protected $lang_dir;
    protected $langs;
    public $lang;
    public function __construct()
    {
        $this->lang = (request('lang')) ? request('lang') : 'sd';
        $this->setLocale($this->lang);
        $this->langs = Languages::all();
    }


    public function index()
    {
        $siteLanguages = $this->langs;
        $langDir = $this->lang_dir;
        $profile = Auth::user();
        $active_category = 'couplets';
        $item_type = 'couplets';

        $curr_lang = $this->lang;

        $categories = $this->getCategories($curr_lang);

        $total_likes = $profile->likesDislikes->count();
        $total_comments = $profile->comments->count();

        if($this->lang == 'sd')
        {
            $user_name = $profile->name_sd;
        }else{
            $user_name = $profile->name;
        }

        $companct_data = [
            'profile',
            'user_name',
            'total_likes',
            'item_type',
            'categories',
            'total_comments',
            'active_category',
            'siteLanguages',
            'langDir'
        ];

        return view('web.users.user-profile', compact($companct_data));
    }

    // favorite poetry
    public function userFavoritePoetry($slug)
    {
        $siteLanguages = $this->langs;
        $langDir = $this->lang_dir;
        $profile = Auth::user();
        $active_category = $slug;
        $item_type = 'poetry';

        $curr_lang = $this->lang;

        $categories = $this->getCategories($curr_lang);

        $total_likes = $profile->likesDislikes->count();
        $total_comments = $profile->comments->count();

        if($this->lang == 'sd')
        {
            $user_name = $profile->name_sd;
        }else{
            $user_name = $profile->name;
        }

        $companct_data = [
            'profile',
            'user_name',
            'item_type',
            'total_likes',
            'categories',
            'total_comments',
            'active_category',
            'siteLanguages',
            'langDir'
        ];

        return view('web.users.user-profile', compact($companct_data));
    }

    // user other favorites
    public function userFavorites($slug)
    {
        $siteLanguages = $this->langs;
        $langDir = $this->lang_dir;
        $profile = Auth::user();
        $active_category = $slug;
        

        $curr_lang = $this->lang;

        $categories = $this->getCategories($curr_lang);

        $total_likes = $profile->likesDislikes->count();
        $total_comments = $profile->comments->count();

        if($this->lang == 'sd')
        {
            $user_name = $profile->name_sd;
        }else{
            $user_name = $profile->name;
        }

        if($slug !='couplets')
        {
            $item_type = 'others';
        }else{
            $item_type = 'couplets';
        }


        $companct_data = [
            'profile',
            'user_name',
            'total_likes',
            'item_type',
            'categories',
            'total_comments',
            'active_category',
            'siteLanguages',
            'langDir'
        ];

        return view('web.users.user-profile', compact($companct_data));
    }

    public function editProfile()
    {

    }



    protected function setLocale($lang)
    {
        // Check if the provided language is supported
        $supportedLanguages = Languages::pluck('lang_code')->toArray();

        if (empty($lang) || !in_array($lang, $supportedLanguages)) {
            // Set a default language if the provided language is not supported or not provided
            $lang = 'sd';
            
            $this->lang_dir = 'rtl';
        }
        App::setLocale($lang);
        $this->lang = $lang;
        // get language direction of selected language
        $this->lang_dir = Languages::where('lang_code', $lang)->value('lang_dir');
    }

    /**
     * getLikedItems accpets $profile & $slug 
     */
    public function getLikedItems(Request $request)
    {
        $user = Auth::user();
        $lang = app()->getLocale();
        $itemType = $request->itemType;
        $start = $request->start;
        $limit = $request->limit;
        $content = '';
        switch ($itemType) {
            case 'poets':
                $content = $this->getLikedPoets($user, $lang, $start, $limit);
                break;
            case 'bundles':
                $content = $this->getLikedBundles($user, $lang, $start, $limit);
                break;
            
            case 'tags':
                $content = $this->getLikedTags($user, $lang, $start,$limit);
                break;
            
        }
        if(!blank($content))
        {
            $message = [
                'message' => 'Data is available',
                'type' => 'success',
                'html' => $content,
                'start' => $start,
                'code' => 200
            ];
        }else{
            $message = [
                'message' => 'Data not available',
                'type' => 'error',
                'code' => 404
            ];
        }
        
        return response()->json($message);

    }

    private function getLikedPoets($user, $lang, $start = 0, $limit = 10)
    {
        $likedItems = LikeDislike::select('likes_dislikes.*', 'poets.id as poet_id', 'poets.poet_slug', 'poets.poet_pic', 'poets.date_of_birth', 'poets.date_of_death', 'poets_detail.poet_name', 'poets_detail.poet_laqab', 'poets_detail.tagline')
        ->join('poets', 'poets.id', '=', 'likes_dislikes.likable_id')
        ->join('poets_detail', 'poets_detail.poet_id', '=', 'poets.id')
        ->where(['likes_dislikes.user_id' => $user->id,'likes_dislikes.likable_type' => 'Poets', 'poets_detail.lang' => $lang])
        ->skip($start)
        ->take($limit)
        ->get();

        $html = '';
        foreach ($likedItems as $poet) {
            $html .= view('web.users.liked-poets', ['item' => $poet])->render();
        }
        if(count($likedItems) > 0)
        {
            $data_start = ($start + count($likedItems));
            $html .= '<div class="loaded-poetry" data-limit="'.$limit.'" data-start="'.$data_start.'"></div>';
        }
        return $html;
    }

    private function getLikedBundles($user, $lang, $start = 0, $limit = 10)
    {

    }

    private function getLikedTags($user, $lang, $start = 0, $limit = 10)
    {

    }

    /**
     * Liked Items as per their category
     */
    private function getLimitedPoetry($type = 'Couplets', $category = 'ghazal',  $start = 0, $limit = 10)
    {
        $user = Auth::user();
        $html = '';
        $lang = app()->getLocale();
        if($type == 'Couplets')
        {
            // all favorited couplets
            $likedPoetry = LikeDislike::select('likes_dislikes.*', 'poetry_couplets.couplet_slug')
            ->join('poetry_couplets', 'poetry_couplets.id', '=', 'likes_dislikes.id')
            ->where(['user_id' => $user->id, 'likable_type' => $type])
            ->skip($start)
            ->take($limit)
            ->get();
              

            if(!is_null($likedPoetry))
            {
                foreach ($likedPoetry as $poetry) {
                    $getCouplet = Couplets::with(['poet.details' => function ($query) use ($lang) {
                        $query->where('poets_detail.lang', $lang);
                    }])
                    ->where(['couplet_slug' => $poetry->couplet_slug, 'lang' => $lang])
                    ->get();
                    foreach ($getCouplet as $index =>  $item) {
                        $poetName = $item->poet->details->poet_laqab; // get this as per $lang
                        $liked = '-fill text-baakh';
                        $html .= view('web.poets.couplets-list', ['index' => $index, 'item'=> $item, 'liked'=>$liked, 'poetName'=>$poetName])->render();
                    }
                }
                if(count($likedPoetry) > 0)
                {
                    $data_start = ($start + count($likedPoetry));
                    $html .= '<div class="loaded-items" data-limit="'.$limit.'" data-start="'.$data_start.'"></div>';
                }
                
            }
        }else 
        {
            $likedPoetry = LikeDislike::select('likes_dislikes.*', 'categories.slug', 'poetry_main.poetry_slug')
            ->join('poetry_main', 'poetry_main.id', '=', 'likes_dislikes.id')
            ->join('categories', 'categories.id', '=', 'poetry_main.category_id')
            ->where(['likes_dislikes.user_id' => $user->id, 'likes_dislikes.likable_type' => $type, 'categories.slug' => $category])
            ->skip($start)
            ->take($limit)
            ->get();

            if(!is_null($likedPoetry))
            {
                foreach ($likedPoetry as $poetry) {
                    $getPoetry = Poetry::with(['poet.details' => function ($query) use ($lang) {
                        $query->where('poets_detail.lang', $lang);
                    }])
                    ->where(['poetry_slug' => $poetry->poetry_slug, 'lang' => $lang])
                    ->get();
                    foreach ($getPoetry as $index =>  $item) {
                        $poetName = $item->poet->details->poet_laqab; // get this as per $lang
                        $liked = '-fill text-baakh';
                        $html .= view('web.poets.poetry-list', ['index' => $index, 'item'=> $item, 'liked'=>$liked, 'poetName'=>$poetName])->render();
                    }
                }
                if(count($likedPoetry) > 0)
                {
                    $data_start = ($start + count($likedPoetry));
                    $html .= '<div class="loaded-items" data-limit="'.$limit.'" data-start="'.$data_start.'"></div>';
                }
            }
        }
         
        return $html;
    }

    public function getLimitedPoetryAjax(Request $request)
    {
        $type = ucfirst($request->type);
        $category = $request->category;
        $start = $request->start;
        $limit = $request->limit;
        $content =  $this->getLimitedPoetry($type, $category,  $start, $limit);
        if(!blank($content))
        {
            $message = [
                'message' => 'Data is available',
                'type' => 'success',
                'html' => $content,
                'start' => $start,
                'code' => 200
            ];
        }else{
            $message = [
                'message' => 'Data not available',
                'type' => 'error',
                'code' => 404
            ];
        }
        
        return response()->json($message);
        
    }

    /**
     * Liked Poetry's Categories
    */
    private function getCategories($lang)
    {
        $categories = LikeDislike::select([
            'categories.id as categoryId',
            'categories.slug',
            'category_details.cat_name',
            DB::raw('COUNT(*) as item_count')
        ])
        ->join('poetry_main', 'poetry_main.id', '=', 'likes_dislikes.id')
        ->join('categories', 'categories.id', '=', 'poetry_main.category_id')
        ->join('category_details', 'category_details.cat_id', '=', 'categories.id')
        ->where('likes_dislikes.likable_type', 'Poetry')
        ->where('category_details.lang', $lang)
        ->groupBy('categories.id', 'categories.slug', 'category_details.cat_name')
        ->get();
        return $categories;
    }

}
