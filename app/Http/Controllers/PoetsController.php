<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Couplets;
use App\Models\LikeDislike;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Traits\BaakhSeoTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class PoetsController extends UserController
{
    use BaakhSeoTrait;
    public function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $locale = app()->getLocale();
        if ($request->exists('startsWith')) {
            $word = $request->input('startsWith');
            $poets = Poets::with(['details' => function ($query) use ($locale) {
                        $query->where('lang', $locale);
                }])
                ->whereHas('details', function ($query) use ($locale, $word) {
                    $query->where('poet_laqab', 'like',   $word.'%')
                    ->orWhere('poet_name', 'like',  $word.'%')
                    ->orWhere('pen_name', 'like',   $word.'%');
                })
                ->where('visibility', 1)
                ->orderBy('date_of_birth', 'asc')
                ->paginate(24);
       
            $title ='باک | شاعر | '.$word.' سان شروع ٿيندڙ';
            $desc = '';

        } elseif ($request->exists('query')) {
            $get_query = $request->input('query');

            $poets = Poets::with(['details' => function ($query) use ($locale) {
                        $query->where('lang', $locale);
                }])
                ->whereHas('details', function ($query) use ($get_query) {
                    $query->where('poet_laqab', 'like', '%' . $get_query . '%')
                        ->orWhere('poet_name', 'like', '%' . $get_query . '%');
                })
                ->where('visibility', 1)
                ->orderBy('date_of_birth', 'asc')
                ->paginate(24);
 
            $title = 'باک | شاعر | ڳولا: '.$get_query ;
            $desc = '';

        } else {
            $locale = app()->getLocale();

            $poets = Poets::with(['details' => function ($query) use ($locale) {
                        $query->where('lang', $locale);
                    }])
                    ->where('visibility', 1)
                    ->orderBy('date_of_birth', 'asc')
                    ->paginate(24);

            $title = 'باک | شاعر';
            $desc = '';
        }
        

        // SEO 
        $this->SEO_General($title, $desc);
        $total_poets = Poets::where('visibility', 1)->count();

        $poet_tags = Tags::where(['type' => 'poets', 'lang' => $locale])->get();

        $alphabets = $this->listOfAlphabet($locale);

       
        return view('web.poets.index', compact('poets', 'alphabets', 'poet_tags', 'total_poets'));
    }

    /**
     * All Poets With Their Tags
     * Special Tags
    */
    public function with_tags($tag)
    {
        $locale = app()->getLocale();
        $poets = Poets::with(['details' => function ($query) use ($locale) {
            $query->where('lang', $locale);
        }])
        ->whereHas('details', function ($query) use ($locale) {
            $query->where('lang', $locale);
        })
        ->where('poet_tags', 'like', '%"'.$tag.'"%')
        ->where('visibility', 1)
        ->paginate(24);
 
 
        $active_tag = Tags::where('slug', $tag)->first();
        
        $this->SEO_General($active_tag->tag, 'This is description');
        return view('web.poets.poets-with-tags', compact('poets', 'active_tag'));
    }

    /**
     * with_slug() function is for Poet's profile page
     * @name = poet name's slug
     * @category_get = category from URL like Ghazal, Nazam, Couplets
     */
    public function with_slug($name, $category_get = 'all')
    {
        // language 
        $locale = app()->getLocale();
        $profile = Poets::where('poet_slug', $name)
                    ->with(['details' => function($q) use ($locale) {
                        $q->where('lang', $locale);
                    }])
                    ->where('visibility', 1)
                    ->first();
        
        if(!$profile){
            return redirect(URL::localized(route('poets.all')));
        }
        
        // fetch category
        $category = Categories::where(['slug' => $category_get])
                    ->with(['detail' => function($q) use ($locale) {
                        $q->where('lang', $locale);
                    }])
                    ->first();

        $poetId = $profile->id;

        // count poetry
        $categoriesWithCounts = Categories::select(['id', 'slug'])->withCount(['poetry' => function ($query) use ($poetId, $locale) {
            $query->where(['poet_id' => $poetId]);
        }])
        ->whereHas('poetry', function ($query) use ($poetId, $locale) {
            $query->where(['poet_id' => $poetId]);
        })
        ->with(['detail' => function($q) use ($locale) {
            $q->where('lang', $locale)->select('id', 'cat_id', 'cat_name');
        }])
        ->get(['slug']);
        
        $total_couplets = Couplets::where(['poet_id' => $poetId, 'lang' => $locale, 'poetry_id' => 0])->count();
        
        if($category !==null){
            $category_name = ' '.$category->detail->cat_name;
            // active or selected category, pass this to profile and use in the tabls selection
            $active_category = $category->slug;
            $active_category_id = $category->id;
            $poetry_limited = null;
        }elseif($category_get === 'couplets')
        {
            $category_name = ($locale == 'sd') ? ' شعر ' : ' Couplets ';
            $active_category = 'couplets';
            $poetry_limited = null;
            $active_category_id = 0;
        }
        else{
            $category_name = ($locale == 'sd') ? ' شعر، غزل، گيت ۽ وايون ' : ' Couplets, Ghazals and other poetry ';
            $active_category = 'all';
            $poetry_limited = $this->getAllPoetryLimited($name, $poetId, $categoriesWithCounts);
            $active_category_id = 0;
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

        $poet_url = url('/poets').'/'.$name;
        
        
        $profile_details = $profile->details;
        $bio = $profile_details->poet_bio;
        $pen_name = $profile_details->pen_name;
        $laqab = $profile_details->poet_laqab;
        $tagline = $profile_details->tagline;

        $full_desc = $tagline .' - '. $laqab .' - '. strip_tags(Str::limit($bio, 180, '...'));

        // SEO
        $jo_ja = ($locale == 'sd') ? ' جا ' : '';
        
        $title = ($locale == 'sd') ? trans('menus.poets').' | '. $laqab . $jo_ja.$category_name : ucfirst($category_name) . $jo_ja . $laqab . ' | ' . trans('menus.poets');

        $this->SEO_Poet($profile, $category?->detail->cat_name_plural);
        
        
        $totalLikes = LikeDislike::where(['likable_type' => 'Poets', 'likable_id' =>  $profile->id])->count();// count total likes
        return view('web.poets.profile', compact('profile', 'famous_poets', 'total_couplets', 'poetry_limited', 'active_category', 'active_category_id', 'poet_url', 'categoriesWithCounts', 'totalLikes'));
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

        $locale = app()->getLocale();
        // validate request fetch all parameters
        $request->validate([
            'poet_id' => 'required',
            'category' => 'required',
            'category_id' => 'required',
            'start' => 'required',
            'limit' => 'required'
        ]);

        $start = $request->start;
        $limit = $request->limit;
 
        
        $poetryQuery = ($request->category === 'couplets')
        ? Couplets::where(['poet_id' => $request->poet_id, 'lang' => $locale])->where('poetry_id', 0)
        : Poetry::where('category_id', $request->category_id)->with(['info' => function ($query) use ($locale) {
                    $query->where('lang', $locale); // Load only one translation
                }
            ], 'category')
            ->where('poet_id', $request->poet_id);

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
                    $html .= view('web.poets.couplets-list', ['index' => $index, 'item'=> $item, 'liked' => $liked])->render();
                }
            }else{
                // requested category is not couplet
                // use foreach loop to load couplets HTML elements of non-couplets
                foreach ($poetry as $index =>  $item) {
                    $liked = $this->isLiked('Poetry', $item->poetry_slug);
                    $html .= view('web.poets.poetry-list', ['index' => $index, 'item'=> $item, 'liked' => $liked])->render();
                    
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

    /**
     * This function will load poetry from all available categories of the poet
     * for example, 5 Ghazal, 5 Couplets, 5 Nazam
     * it will check each poetry by its category, but it will load only titles
     * getAllPoetryLimited 
     * @poetId = visible poets' ID
     * @categories = available poetry categories of the poet
    */
    private function getAllPoetryLimited($name, $poetId, $categories)
    {
        $locale = app()->getLocale();
        // make a new variable to store data $html
        $couplets = Couplets::where(['poet_id' => $poetId, 'lang' => $locale, 'poetry_id' => 0])
                    ->take(5)
                    ->get();

        $html = '';

        // check if couplets are available
        if(count($couplets) > 0)
        { 
            // new div to seprate Couplets
            $html .= '<div class="all-poetry-heading"><h3 class="text-baakh">'.trans('menus.couplets').'</h3></div>';
            // render couplets
            foreach ($couplets as $index =>  $item) {
                // check if poetry is liked
                $html .= view('web.poets.couplets-list', ['index' => $index, 'item'=> $item])->render();
            }

            // View All Button Before
            $html .='<div class="text-center mt-4 mb-4">';
            $html .='<a href="'.URL::localized(route('poets.slug', ['name' => $name, 'category' => 'couplets'])).'" class="btn btn-block btn-secondary btn-gol">'.trans('buttons.see_all', ['category' => trans('menus.couplets')]).'</a>';
            $html .='</div>';
        }
        
        // get poetry from other categories
        foreach ($categories as $key => $cat) {
            
            $items =  Poetry::with([
                        'info' => function ($query) use ($locale) {
                            $query->where('lang', $locale); // Load only one translation
                        },
                        'category:id,slug',
                        'media' => function ($query) use ($locale) {
                            $query->where('lang', $locale) // Filter by language
                                  ->orderBy('id') // Order by ID or any other relevant column
                                  ->take(1); // Take only the first media item
                        }
                    ])
                    ->where('category_id', $cat->id)
                    ->where('poet_id', $poetId)
                    ->take(5)->get();

            // new div to seprate each category
           
            $html .= '<div class="all-poetry-heading"><h3 class="text-baakh">'.ucfirst($cat->detail->cat_name).'</h3></div>';

            // load poetry of each category
            foreach ($items as $index =>  $item) {
                $html .= view('web.poets.poetry-list', ['index' => $index, 'item'=> $item])->render();
            }
            
            // View All Button Before
            $html .='<div class="text-center mt-4 mb-4">';
            $html .='<a href="'.URL::localized(route('poets.slug', ['name' => $name, 'category' => $cat->slug])).'" class="btn btn-block btn-secondary btn-gol">'.trans('buttons.see_all', ['category' => ucfirst($cat->detail->cat_name)]).'</a>';
            $html .='</div>';
        }
        return $html;

    }


    /**
     * List of Alphabets in Poets Page
     */
    public function listOfAlphabet($lang = 'sd')
    {
        $html = '';
        if (isset($_GET['startsWith'])) {
            $alphabetId = $_GET['startsWith'];
        } else {
            $alphabetId = 'x';
        }

        
        if($lang == 'sd')
        {
            $alphabets = ['x','ا','ب','ٻ','ڀ','ت','ٿ','ٽ','ٺ','ث','ج','ڄ','جهہ','ڃ','چ','ڇ','ح','خ','د','ڌ','ڏ','ڊ','ڍ','ذ','ر','ڙ','ز','س','ش','ص','ض','ط','ظ','ع','غ','ف','ڦ','ق','ڪ','ک','گ','ڳ','گهہ','ڱ','ل','م','ن','ڻ','و','ھہ','ء','ي'];
            $index = array_search($alphabetId, $alphabets);
            if ($index !== false) {
                $startIndex = max(0, $index - 8);
                $endIndex = min(count($alphabets) - 1, $index + 8);
            }
        }else{
            $alphabets = ['x','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'];
            $index = array_search($alphabetId, $alphabets);
            if ($index !== false) {
                $startIndex = max(0, $index - 8);
                $endIndex = min(count($alphabets) - 1, $index + 8);
            }
        }

        // logic starts
        if($startIndex > 0) {
            $html .='<li class="list-inline-item"><a href="#" class="btn btn-secondary btn-alphabet">..</a></li>';
        }

        // for loop to display alphabets
        for ($i = $startIndex; $i <= $endIndex; $i++) { 
            $active = ($alphabets[$i] == $alphabetId) ? 'active' : '';
            if($alphabets[$i] != 'x')
            {
                $alphabetsss[] = $alphabets[$i];
                $html .= '<li class="list-inline-item">
                            <a href="'.URL::localized(route('poets.all', ['startsWith' => $alphabets[$i]])).'" class="btn btn-secondary '.$active.' btn-alphabet">'.$alphabets[$i].'</a>
                        </li>';
            }
        }

        if($endIndex <= count($alphabets) - 1) {
            $html .= '<li class="list-inline-item"><a href="#" class="btn btn-secondary btn-alphabet">..</a></li>';
        }

        return $html;
        
    }

    
}
