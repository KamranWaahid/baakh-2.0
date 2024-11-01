<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\UserComments;
use App\Traits\BaakhLikedTrait;
use Illuminate\Support\Str;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
 

class PoetryController extends UserController
{
    use BaakhSeoTrait;
    public function __construct()
    {
        parent::__construct();
        
    }

    public function index()
    {
        
    }

    public function with_genre($slug)
    {

    }

    /**
     * Poetry With Slug
     * 
    */
    public function with_slug($category, $slug)
    {
        $poetryUrl = URL::localized(route('poetry.with-slug', ['category'=>$category, 'slug'=> $slug]));

        // site language
        $locale = app()->getLocale();

        // get poetry by URL 
        $poetry = Poetry::with([
                    'info' => function ($query) use ($locale) {
                        $query->where('lang', $locale)->take(1); // Load only one translation
                    },
                    'all_couplets' => function ($query) use ($locale) {
                        $query->where('lang', $locale); // Load all couplets with the specified language
                    },
                    'category' => function ($query) use ($category) {
                        $query->where('slug', $category)->take(1); // Load category with the specified language
                    }
                ])
                ->where(['poetry_slug' => $slug, 'visibility' => 1])
                ->first();

       
        // media
        $media_videos = Media::where(['lang' => $locale, 'media_type'=>'video', 'poetry_id' => $poetry->id])->get();
        $media_audios = Media::where(['lang' => $locale, 'media_type'=>'audio', 'poetry_id' => $poetry->id])->get();
 
        $poet_id = $poetry->poet_id;
        $poet_info = Poets::with(['details' => function ($query) use ($locale) {
                                $query->where('lang', $locale);
                            }])->where('id', $poet_id)->first();
        
        // Poetry Tags
        if(!is_null($poetry->poetry_tags) && $poetry->poetry_tags !='null'){
            $decodeTags = json_decode($poetry->poetry_tags);
            $used_tags = Tags::whereIn('slug', $decodeTags)->where('lang' , app()->getLocale())->pluck('tag', 'slug');
        }else{
            $used_tags = null;
        }
 
         
        $next_poetry = Poetry::with([
                            'info' => function ($query) use ($locale) {
                                $query->where('lang', $locale)->take(1); // Load only one translation
                            },
                            'category'
                        ])
                        ->where('poet_id', $poet_id)->where('id', '>', $poetry->id)
                        ->orderBy('id', 'asc')->first(); 

        $previous_poetry = Poetry::with([
                            'info' => function ($query) use ($locale) {
                                $query->where('lang', $locale)->take(1); // Load only one translation
                            },
                            'category'
                        ])
                        ->where('poet_id', $poet_id)->where('id', '<', $poetry->id)
                        ->orderBy('id', 'desc')->first();
        /**
         * Poetry Meta
         */
        $poet_detail = $poet_info->details;
        $title = $poet_detail->poet_laqab. ' | '.$poetry->poetry_title;
        
        if(count($poetry->all_couplets) > 0) {
            // Generate SEO description without HTML tags and newline characters
            $seo_desc = Str::limit(preg_replace('/\s+/', ' ', strip_tags($poetry->all_couplets[0]->couplet_text)), 160, '...');
            $this->SEO_Poetry($title, $seo_desc, $poet_info->poet_pic);
        }
         

        if(Auth::user())
        {
            $currentUserId = Auth::user()->id;
            $already_commented = UserComments::where(['poetry_id' => $poetry->id, 'user_id' => $currentUserId])->first();
        }else{
            $currentUserId = 0;
            $already_commented = NULL;
        }

        $user_comments = $this->getUserComments($poetry->id, $locale, $currentUserId);
        $total_comments = UserComments::where('poetry_id', $poetry->id)->count();

        $liked = $this->isLiked('Poetry', $poetry->poetry_slug);
        

        $compact_views = [
            'poetry',
            'poet_info',
            'poet_detail',
            'media_videos',
            'media_audios',
            'used_tags',
            'next_poetry',
            'previous_poetry',
            'poetryUrl',
            'user_comments',
            'total_comments',
            'already_commented',
            'liked'
        ];

        return view('web.poetry.with-category', compact($compact_views));
    }

    /**
     * getRelatedPoetry contains related poetry to the opened poetry
     * it accepts php array of tags and current opened poetry id
     * it will check those tags into Poetry's JSON column poetry_tags
     * in the return it will load those poetry with dynamic blade page
     */
    private function getRelatedPoetry($tags = array(), $poetry_id)
    {
        $html = '';
        $poetry = Poetry::with('category', 'poet')->where('id', '!=', $poetry_id);
        foreach ($tags as $key => $value) {
            $poetry->orWhere('poetry_tags', 'like', '%' .$key . '%');
        }
        $relatedPoetry = $poetry->limit(5)->get();
        // count poetry
        $totalPoetry = count($relatedPoetry);
        // check if there is poetry available
        if($totalPoetry > 0)
        {
            // loop the poetry
            foreach ($relatedPoetry as $key => $poetry) {
                $html .= view('web.poetry.related-poetry', ['total' => $totalPoetry, 'key' => $key, 'poetry' => $poetry]);
            }
        }
        return $html;
    }


    /**
     * User Comments On Poetry
     */
    private function getUserComments($poetryId, $locale, $currentUserId)
    {
        if($currentUserId !=0)
        {
            $comments = UserComments::with('user')
                            ->where('poetry_id', $poetryId)
                            ->orderByRaw("CASE WHEN user_id = $currentUserId THEN 0 ELSE id END ASC")
                            ->limit(2)
                            ->get(); 
        }else{
            $comments = UserComments::with('user')
                            ->where('poetry_id', $poetryId)
                            ->orderByRaw("id ASC")
                            ->limit(2)
                            ->get(); 
        }
        
        

        // check if comments are available
        if(!is_null($comments) && count($comments) > 0)
        {
            $html = '';
            
            // send $name_sd, $name_en, $avatar, $time, $comment 

            foreach ($comments as $comnt) {
                $avatar = (file_exists($comnt->user->avatar)) ? asset($comnt->user->avatar) : $comnt->user->avatar;
                $name = ($locale == 'sd') ? $comnt->user->name_sd : $comnt->user->name;
                $time = $comnt->created_at;
                $comment = $comnt->comment;
                $editable = ($comnt->user_id == $currentUserId);
                $html .= view('web.poetry.users-comment', ['id'=>$comnt->id, 'name' => $name, 'avatar' => $avatar, 'time' => $time, 'comment' => $comment, 'editable' => $editable]);
            }

            return $html;
        }

        return null;
    }


    public function loadMoreComments(Request $request)
    {

        $poetryId = $request->poetry_id;
        $lastId = $request->last_comment_id;
        $locale = app()->getLocale();
        
        $comments = UserComments::with('user')
            ->where('poetry_id', $poetryId)
            ->where('id', '>', $lastId) // Assuming you want to load comments after the last ID
            ->limit(1)
            ->orderBy('id', 'asc')
            ->get();

        // check if comments are available
        if($comments->isNotEmpty())
        {
            $html = '';

            // send $name_sd, $name_en, $avatar, $time, $comment 

            foreach ($comments as $comnt) {
                $avatar = (file_exists($comnt->user->avatar)) ? asset($comnt->user->avatar) : $comnt->user->avatar;
                $name = ($locale == 'sd') ? $comnt->user->name_sd : $comnt->user->name;
                $time = $comnt->created_at;
                $comment = $comnt->comment;
                $html .= view('web.poetry.users-comment', ['id'=> $comnt->id,'name' => $name, 'avatar' => $avatar, 'time' => $time, 'comment' => $comment, 'editable' =>false])->render();
            }

            $notify = ['type'=> 'success', 'message' => 'Data available', 'status'=> 200,  'html_comments' => $html];

        }else{
            $notify = ['type'=> 'success', 'message' => 'NO data available', 'status' => 403];
        }

        return response()->json($notify);
    }
}
