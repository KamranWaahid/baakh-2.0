<?php

namespace App\Http\Controllers;

use App\Models\Categories;
use App\Models\Poets;
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
        $results = [];
        $term =  '%'.$query.'%';
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

        $poets = UnifiedPoets::where('lang', $lang)
        ->where(function($query) use ($term) {
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
            $results['poets'] = $poets;
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
        $query = urldecode($q);
        // dd($query);
        $result = $this->getResults($query, $lang);
        $html = '';
        $error = true;

        // Process poets if available
        if (!empty($result['poets'])) {
            $error = false;
            foreach ($result['poets'] as $item) {
                $link = route('poets.slug', ['category' => null, 'name' => $item->poet_slug]);
                $html .= view('web.home.search_suggestion_list', [
                    'link' => $link,
                    'text' => $item->poet_laqab . ' (' . $item->poet_name . ')'
                ])->render();
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
