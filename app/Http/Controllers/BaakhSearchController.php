<?php

namespace App\Http\Controllers;

use App\Enums\CategoryGenderEnum;
use App\Models\Categories;
use App\Models\Poets;
use App\Models\Search\UnifiedCategories;
use App\Models\Search\UnifiedCouplets;
use App\Models\Search\UnifiedPoetry;
use App\Models\Search\UnifiedPoets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BaakhSearchController extends Controller
{
    public function index()
    {
        $query = request()->query('q');
        $lang = request()->query('lang');
      
        $resp = $this->getResults($query, $lang);
        
        return response()->json($resp, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return view('web.home.search_box');
    }


    /**
     * Search from DB
     */
    public function getResults($query, $lang)
    {
        $conjunctions = ['جو','جا','جي','جون','جيون']; // get Sindhi omit words
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $conjunctions)) . ')\b/u';
        $query = preg_replace($pattern, '', $query);
        $query = preg_replace('/\s+/', ' ', trim($query));
        $results = [];
        $term =  '%'.$query.'%';
        $explodeQuery = explode(' ', $query);
        $lastTerm = count($explodeQuery) > 0 ? trim(end($explodeQuery)) : '';

        // Split query to identify possible poet name and category term
        $queryWithoutLast = trim(str_replace($lastTerm, '', $query));

        $poetry = UnifiedPoetry::where('lang', $lang)
            ->where('title', 'like', "%$term%")
            ->with([
                'category' => function ($query) use ($lang) {
                    $query->where('lang', $lang);
                }, 
                'poet' => function ($query) use ($lang) {
                    $query->where('lang', $lang);
                }
            ])
            ->limit(5)->orderBy('title', 'asc')->get();

        // Poets 
        $poets = UnifiedPoets::where('lang', $lang)
        ->where(function($query) use ($term, $queryWithoutLast) {
            $query->where('poet_laqab', 'like', $term)
                ->orWhere('poet_name', 'like', $term);
        })
        ->limit(5)->orderBy('poet_laqab', 'asc')->get();

        // couplets
        $couplets = UnifiedCouplets::where('lang', $lang)
                ->with(['poet'=> function ($query) use ($lang) {
                    $query->where('lang', $lang);
                }, 'poetry' => function ($poetry_q) use ($lang) {
                    $poetry_q->where('lang', $lang);
                }, 'poetry.category' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }])
                ->where('poetry_id' , '!=', 0)
                ->where('couplet_text', 'like', $term)
                ->groupBy('poetry_id')
                ->limit(5)->get();

        if ($poetry->isNotEmpty()) {
            $results['poetry'] = $poetry;
        }

        if ($poets->isNotEmpty()) {
            $results['poets'] = $poets->map(function ($poet) use ($lang, $lastTerm) {
                $categoryQuery = UnifiedCategories::whereIn('category_id', function ($query) use ($poet) {
                    $query->select('category_id')->from('unified_poetry')->where('poet_id', $poet->poet_id);
                })
                ->where('lang', $lang);
        
                // Apply the cat_name condition only if $lastTerm is non-empty
                if (!empty($lastTerm)) {
                    $categoryQuery->where('cat_name', 'like', "%$lastTerm%");
                }
        
                // Get the categories
                $categories = $categoryQuery->get();
        
                // If no categories are fetched and $lastTerm was used, fetch all categories as a fallback
                if ($categories->isEmpty() && !empty($lastTerm)) {
                    $categories = UnifiedCategories::whereIn('category_id', function ($query) use ($poet) {
                        $query->select('category_id')->from('unified_poetry')->where('poet_id', $poet->poet_id);
                    })
                    ->where('lang', $lang)
                    ->get();
                }
        
                return [
                    'poet' => $poet,
                    'categories' => $categories,
                ];
            });
        }
        
        
        if($couplets) {
            $results['couplets'] = $couplets;
        }

        return $results;
    }


    /**
     * Suggestions
     */
    public function getSuggestions($q, $lang)
    {
        app()->setLocale($lang);
        $query = urldecode($q);

        $result = $this->getResults($query, $lang);
        $html = '';
        $error = true;

        // Process poets if available
        if (!empty($result['poets'])) {
            $error = false;
            $currentLocale = app()->getLocale();
            foreach ($result['poets'] as $item) {

                $poet = $item['poet'];
                $link = route('poets.slug', ['category' => null, 'name' => $poet->poet_slug]);
                
                $path = asset('assets/images/poets/');
                $userImage = glob($path.$poet->poet_slug . "_smasll.*", GLOB_ERR); 
               
                if(!empty($userImage)) {
                   $image = $userImage[0];
                }else{
                   $image = asset('assets/img/baakh-logo-small.jpg');
                }

                
                $html .= view('web.home.search_suggestion_list_poet', [
                    'link' => $link,
                    'poet_name' => $poet->poet_name,
                    'image' => $image,
                    'text' => $poet->poet_laqab
                ])->render();


                if(!empty($item['categories'])) {
                    foreach ($item['categories'] as $cat) {
                        $link = URL::localized(route('poets.slug', ['category' => $cat->slug, 'name' => $poet->poet_slug]));

                        $gender = CategoryGenderEnum::from($cat['gender']);

                        if ($currentLocale === 'en') {
                            $text = $poet->poet_laqab . "'s " . $cat->cat_name;
                        } else {
                            $text = $poet->poet_laqab . ' ' . $gender->plural() . ' ' . $cat->cat_name_plural;
                        }

                        $html .= view('web.home.search_suggestion_list', [
                            'link' => $link,
                            'text' => $text
                        ])->render();
                    }
                }
               
            }
        }

        // Process poetry if available
        if (!empty($result['poetry'])) {
            $error = false;
            foreach ($result['poetry'] as $item) {
                $category = $item->category;
                $categorySlug = $category->slug ?? 'uncategorized'; // Fallback for missing category
                $link = route('poetry.with-slug', ['category' => $categorySlug, 'slug' => $item->poetry_slug]);

                $title = $item->title . ' ('.$category->cat_name.')';

                $link = $link . '#:~:text=' . Str::words($item->title_original, 5, '');
                $html .= view('web.home.search_suggestion_list', [
                    'link' => $link,
                    'text' => $title
                ])->render();
            }
        }

        if(!empty($result['couplets'])) {
            $error = false;
            foreach ($result['couplets'] as $item) {
                $categorySlug = $item->poetry->category->slug ?? 'uncategorized'; // Fallback for missing category
                $link = route('poetry.with-slug', ['category' => $categorySlug, 'slug' => $item->poetry->poetry_slug]);
                
                
                $lines = explode("\n", $item->couplet_text);
                $couplet_text_original = explode("\n", $item->couplet_text_original);
                $result = '';
                $highlight_line = '';

                foreach ($lines as $k => $line) {
                    if (strpos($line, $query) !== false) {
                        $result = $line;
                        $highlight_line = $couplet_text_original[$k];
                        break; // Stop after finding the first match
                    }
                }

                $link = $link . '#:~:text=' . $highlight_line;
                $html .= view('web.home.search_suggestion_list', [
                    'link' => $link,
                    'text' => $result
                ])->render();
            }
        }

        if($html === "") {
            $gotoLink = URL::localized(route('web.search.index', ['what' => urlencode($query)]));
            $gotoTitle = $query . ' - ڳوڙھي ڳولا ڪريو';
            $html .= view('web.home.search_suggestion_list', [
                'link' => $gotoLink,
                'text' => $gotoTitle
            ])->render();
        }

        return response()->json([
            'error' => $error,
            'data' => $html
        ], 200, ['Content-Type' => 'application/json;charset=UTF-8']);
    }



    
    /**
     * Generate JSON files
     */
    public function generateJson()
    {
        $poets = Poets::select([
            'id', 'poet_slug', 'poet_pic'
        ])->with('shortDetail:id,poet_id,poet_laqab')->get();

        $categories = Categories::select([
            'id', 'slug'
        ])->with('shortDetail:id,cat_id,cat_name,cat_name_plural')->get();

        $content = [];

        return response()->json($content, 200, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
