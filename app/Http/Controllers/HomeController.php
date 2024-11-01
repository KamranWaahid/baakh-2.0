<?php

namespace App\Http\Controllers;

use App\Models\Bundles;
use App\Models\Couplets;
use App\Models\Languages;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Sliders;
use App\Models\Tags;
use App\Models\TodaysModule;
use App\Traits\BaakhSeoTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class HomeController extends UserController
{
    use BaakhSeoTrait;
    public function __construct()
    {
        parent::__construct();
        $this->updateGhazalOfTheDay();
        
    }

    /**
     * Show the application home page.
     *
     */
    public function index()
    {
        $locale = app()->getLocale();
        
        $sliders = Sliders::where(['lang' => $locale, 'visibility' => 1])->get();
        $famous_poet = $this->getFamousPoets($locale);
        $ghazal_of_day = $this->getGhazalOfDay($locale);
        $bundles = Bundles::where('is_featured', true)->get();
        $quiz_couplet = $this->getQuizCouplet($locale);
        $quiz_poets = $this->getQuizPoets($quiz_couplet, $locale);
        $random_poetry = $this->showRandomPoetry(10, $locale);
        $poet_tags = Tags::where(['type'=> 'poets', 'lang' => $locale])->get();
        $tags = Tags::where('lang', $locale)->limit(18)->get(); // get all tags

        // SEO 
        $title = ($locale == 'sd') ? 'باک - سنڌي شاعريءَ جو خزانو' : 'Baakh - Treasure of Sindhi Poetry';
        $desc = ($locale == 'sd') ? 'باک، شاعريءَ جي ھڪ قديم دور کان جديد ۽ ٽيڪنالاجيءَ واري دور ڏانھن ھڪ سفر آھي. ھِن پورٽل ۾ جديد توڙي قديم شاعريءَ کي سھيڙي ھڪ ئي ھنڌ سھڻي نموني رکيو ويو آھي. باک ۾ مشھور شاعرن جي شاعري، سنڌي ۽ رومن رسم الخط ۾ پڙھي سگهو ٿا.' : 'Baakh: A comprehensive web portal dedicated to preserving and promoting Sindhi poetry. Features include multi-lingual support, auto transliteration, and a rich history section. Bakkh celebrating Sindhi poetry heritage and fostering a global community of poetry enthusiasts.';

        $this->SEO_General($title, $desc);

        $ghazal_of_day_poet = $ghazal_of_day?->poet;
        
        $compact = compact(
            'sliders', 'poet_tags', 'bundles', 
            'ghazal_of_day', 'ghazal_of_day_poet', 'famous_poet', 'random_poetry', 
            'quiz_couplet', 'quiz_poets', 'tags');
        
        return view('web.home.home',  $compact);
    }
     
    
    private function getFamousPoets($locale)
    {
        return Poets::with(['details' => function ($q) use ($locale) {
            $q->where('lang', $locale);
        }])
        ->where('visibility', '1')
        ->whereHas('details', function ($query) use ($locale) {
            $query->where('lang', $locale);
        })
        ->inRandomOrder()
        ->limit(5)
        ->get();
    }
    
    private function getGhazalOfDay($locale)
    {
        $todayModule = new TodaysModule();
        return $todayModule->ghazal($locale);
    }
    
    private function getQuizCouplet($locale)
    {
        return Couplets::with(['poet.details' => function ($query) use ($locale) {
            $query->where('poets_detail.lang', $locale);
        }])
        ->whereRaw('LENGTH(couplet_text) - LENGTH(REPLACE(couplet_text, "\n", "")) = 1')
        ->whereHas('poet', function ($query) {
            $query->whereNull('deleted_at');
        })
        ->where('lang', $locale)
        ->inRandomOrder()
        ->limit(1)
        ->first();
    }
    
    private function getQuizPoets($quiz_couplet, $locale)
    {
        $random_poets = Poets::with(['details' => function ($q) use ($locale) {
            $q->where('lang', $locale);
        }])
        ->where('visibility', '1')
        ->whereHas('details', function ($query) use ($locale) {
            $query->where('lang', $locale);
        })
        ->where('id', '!=', $quiz_couplet->poet_id)
        ->limit(2)
        ->inRandomOrder()
        ->get();
        $quiz_poets = $random_poets->push($quiz_couplet->poet);
        $quiz_poets = $quiz_poets->shuffle();
        return $quiz_poets;
    }

    /**
     * About Page
     */
    public function about()
    {
        $locale = app()->getLocale();
        $title = ($locale == 'sd') ? ' باک جي باري ۾' : 'About Baakh';
        $desc = ($locale == 'sd') ? 'باک، شاعريءَ جي ھڪ قديم دور کان جديد ۽ ٽيڪنالاجيءَ واري دور ڏانھن ھڪ سفر آھي. ھِن پورٽل ۾ جديد توڙي قديم شاعريءَ کي سھيڙي ھڪ ئي ھنڌ سھڻي نموني رکيو ويو آھي. باک ۾ مشھور شاعرن جي شاعري، سنڌي ۽ رومن رسم الخط ۾ پڙھي سگهو ٿا.' : 'Baakh: A comprehensive web portal dedicated to preserving and promoting Sindhi poetry. Features include multi-lingual support, auto transliteration, and a rich history section. Bakkh celebrating Sindhi poetry heritage and fostering a global community of poetry enthusiasts.';
        $this->SEO_General($title, $desc);
        return view('web.about');
    }

    /**
     * Contact Page
     */
    public function contact()
    {
        $locale = app()->getLocale();
        $title = ($locale == 'sd') ? 'رابطو' : 'Contact Us';
        $desc = ($locale == 'sd') ? 'باک، شاعريءَ جي ھڪ قديم دور کان جديد ۽ ٽيڪنالاجيءَ واري دور ڏانھن ھڪ سفر آھي. ھِن پورٽل ۾ جديد توڙي قديم شاعريءَ کي سھيڙي ھڪ ئي ھنڌ سھڻي نموني رکيو ويو آھي. باک ۾ مشھور شاعرن جي شاعري، سنڌي ۽ رومن رسم الخط ۾ پڙھي سگهو ٿا.' : 'Baakh: A comprehensive web portal dedicated to preserving and promoting Sindhi poetry. Features include multi-lingual support, auto transliteration, and a rich history section. Bakkh celebrating Sindhi poetry heritage and fostering a global community of poetry enthusiasts.';
        $this->SEO_General($title, $desc);
        return view('web.contact');
    }

    private function showRandomPoetry($limit, $locale)
    {
        // get all peotry couplets
        $random_poetry = Couplets::where('lang', $locale)
                        ->whereRaw('LENGTH(poetry_couplets.couplet_text) - LENGTH(REPLACE(poetry_couplets.couplet_text, "\n", "")) = 1')
                        ->whereHas('poet', function ($query) {
                            $query->whereNull('deleted_at');
                        })
                        ->inRandomOrder()
                        ->limit($limit)
                        ->get();
       
        $html = '';
        foreach ($random_poetry as $item) {
            $liked = $this->isLiked('Couplets', $item->id);
            if($item->couplet_tags != NULL){
                $decodeTags = json_decode($item->couplet_tags);
                $usedTags = Tags::whereIn('slug', $decodeTags)->where('lang', $locale)->pluck('tag', 'slug')->toArray();
                $html .= view('web.home.random-poetry', ['item' => $item, 'liked'=> $liked, 'usedTags' => $usedTags]);
            }else{
                $html .= view('web.home.random-poetry', ['item' => $item, 'liked'=> $liked]);
            }
        }
        return $html;
    }

    /**
     * Check Quiz Answer
     */
    public function quizCheck(Request $request)
    {
        $post_data = $request->validate([
            'couplet' => 'required',
            'main_id' => 'required',
            'poet' => 'required'
        ]);

        // get poet by main ID
        $correct_poet = Couplets::with('poet')->findOrFail($post_data['couplet']);
        if($post_data['poet'] == $correct_poet->poet_id){
            // answer yes
            $message = [
                'message' => trans('labels.quiz_msg_correct_answer'),
                'correct_poet' => $correct_poet->poet_id,
                'type' => 'success'
            ];
        }else{
            // answer no
            $poet_name = $correct_poet->poet->details->poet_laqab;
            $message = [
                'message' => trans_choice('labels.quiz_msg_wrong_answer', 1, ['poetName' => $poet_name], app()->getLocale()),
                'correct_poet' => $correct_poet->poet_id,
                'type' => 'error'
            ];
        }

        return response()->json($message);
    }


    // method for updating today ghazal date
    public function updateGhazalOfTheDay()
    {
        $thisday = Carbon::now()->format('Y-m-d');
        $result = TodaysModule::where('table_name', 'poetry_main')->first();
 
        if ($result->date_today != $thisday) {
            $poetry = DB::select("SELECT p.* FROM poetry_main p
            WHERE p.category_id = 1 AND p.poet_id != (SELECT b.poet_id FROM poetry_main b WHERE b.poetry_slug = ?)
            AND p.visibility = 1 ORDER BY RAND() LIMIT 1", [$result->table_id]);
 
            $id = $poetry[0]->poetry_slug;

            $data = [
                'table_id' => $id,
                'date_today' => $thisday,
            ];

            $result->update($data);
        }
    }

    /**
     * Testing Function
     */
 
    public function _test_fun(Request $request) {
        
        $lang = $request->input('lang');
        $poet = $request->input('poet_id');

        
        $columns = ['id'];

        $query = Poetry::with([
            'info' => function($query) use ($lang) {
                $query->select('poetry_id', 'title')->where('lang', $lang);
            },
            'poet_details' => function ($query) use ($lang) {
                $query->select('poet_id', 'poet_laqab')->where('lang', $lang);
            },
            'user' => function ($q) { // belongsTo relation with User model
                $q->select('id', 'name', 'name_sd', 'role'); // showing NULL 
            },
            'category.detail' => function ($cat_query) use ($lang) {
                $cat_query->select('cat_id', 'cat_name')->where('lang', $lang);
            },
            'translations' => function($query){
                $query->with(['language' => function ($lang_query) {
                    $lang_query->select('lang_code', 'lang_title');
                }])->select('poetry_id', 'lang');
            },
        ])
        ->limit(20)
        ->get();


        if ($request->has('search')) {
            $searchValue = '%' . $request->search . '%';

            $query->where(function ($q) use ($searchValue) {
                $q->orWhere('info.title', 'like', $searchValue)
                  ->orWhereHas('poet_details', function ($q) use ($searchValue) {
                      $q->where('poet_laqab', 'like', $searchValue);
                  });
            });
        }
        
        
        
         
        return response()->Json($query);
        // Implement search
        /* if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = '%' . $request->search['value'] . '%';
 
            $query->where('info.title', 'like', $searchValue)
            ->orWhere('poet_details.laqab', 'like', $searchValue);
        } */

        
        // Implement filtering by language and poet_id
        if ($poet != 0 || $poet != '0') {
            $query->where('poet_id' , $poet);
        }

        // Implement ordering based on request
        if ($request->has('order')) {
            $column = $columns[$request->order[0]['column']];
            $direction = $request->order[0]['dir'];
            $query->orderBy($column, $direction);
        }

        /* $data = DataTables::eloquent($query)
        ->addColumn('actions', function ($row) {
            $mediaCreateUrl = route('admin.media.create', $row->id);
            $editUrl = route('admin.poetry.edit', $row->id);
            $duplicateUrl = route('admin.poetry.duplicate', $row->id);
            $toggleVisibilityUrl = route('admin.poetry.toggle-visibility', ['id' => $row->id]);
            $deleteUrl = route('admin.poetry.destroy', ['id' => $row->id]);
    
            return '<a href="' . $mediaCreateUrl . '" class="btn btn-xs btn-success mr-1" data-toggle="tooltip" data-placement="top" title="Poetry Media"><i class="fa fa-video"></i></a>' .
                   '<a href="' . $editUrl . '" class="btn btn-xs btn-warning mr-1" data-toggle="tooltip" data-placement="top" title="Update Poetry"><i class="fa fa-edit"></i></a>' .
                   '<a href="' . $duplicateUrl . '" class="btn btn-xs btn-default mr-1" data-toggle="tooltip" data-placement="top" title="Duplicate Poetry"><i class="fa fa-copy"></i></a>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $toggleVisibilityUrl . '" data-toggle="tooltip" data-placement="top" title="' . ($row->visibility == 1 ? 'Hide' : 'Show') . ' Poetry" class="btn btn-xs btn-info mr-1 btn-visible-poetry"><i class="fa fa-' . ($row->visibility == 1 ? 'eye' : 'eye-slash') . '"></i></button>' .
                   '<button type="button" data-id="' . $row->id . '" data-url="' . $deleteUrl . '" data-toggle="tooltip" data-placement="top" title="Delete Poetry" class="btn btn-xs btn-danger mr-1 btn-delete-poetry"><i class="fa fa-trash"></i></button>';
        })
        ->rawColumns(['actions', 'information'])
        ->toJson(); */
         
    }
}
