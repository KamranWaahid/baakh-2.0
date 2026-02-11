<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PoetDetail;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Models\Period;
use App\Models\Lemma;
use App\Models\CorpusSentence;
use App\Models\Categories;
use App\Models\Tags;
use App\Models\Poets;
use Illuminate\Support\Facades\DB;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('query');

        if (!$query || strlen($query) < 2) {
            return response()->json([
                'poets' => [],
                'poetry' => [],
                'periods' => [],
                'dictionary' => [],
                'corpus' => [],
                'categories' => [],
                'tags' => []
            ]);
        }

        $lang = $request->header('Accept-Language', 'en');

        // 1. Search Poets using Scout
        $poets = Poets::search($query)->take(5)->get()->map(function ($poet) use ($lang) {
            $detail = $poet->all_details->where('lang', $lang)->first() ?? $poet->all_details->first();
            return [
                'id' => $poet->id,
                'name' => $detail->poet_name ?? 'N/A',
                'slug' => $poet->poet_slug ?? '',
                'image' => $poet->poet_pic ?? null,
                'type' => 'poet'
            ];
        });

        // 2. Search Poetry using Scout
        $poetry = Poetry::search($query)->take(5)->get()->map(function ($poem) use ($lang) {
            $translation = $poem->translations->where('lang', $lang)->first() ?? $poem->translations->first();
            $poetName = $poem->poet_details->poet_name ?? 'Unknown';
            return [
                'id' => $poem->id,
                'title' => $translation->poetry_title ?? ($translation->title ?? 'Untitled'),
                'slug' => $poem->poetry_slug ?? '',
                'poet_name' => $poetName,
                'cat_slug' => $poem->category->slug ?? 'ghazal',
                'poet_slug' => $poem->poet->poet_slug ?? '',
                'type' => 'poetry'
            ];
        });

        // 3. Search Dictionary (Lemmas) using Scout
        $dictionary = Lemma::search($query)->take(5)->get()->map(function ($lemma) {
            return [
                'id' => $lemma->id,
                'lemma' => $lemma->lemma,
                'transliteration' => $lemma->transliteration,
                'type' => 'dictionary'
            ];
        });

        // 4. Search Corpus using Scout
        $corpus = CorpusSentence::search($query)->take(5)->get()->map(function ($sentence) {
            return [
                'id' => $sentence->id,
                'sentence' => $sentence->sentence,
                'source' => $sentence->source,
                'type' => 'corpus'
            ];
        });

        // 5. Search Categories using Scout
        $categories = Categories::search($query)->take(5)->get()->map(function ($category) use ($lang) {
            $detail = $category->details->where('lang', $lang)->first() ?? $category->details->first();
            return [
                'id' => $category->id,
                'name' => $detail->cat_name ?? 'N/A',
                'slug' => $category->slug,
                'type' => 'category'
            ];
        });

        // 6. Search Tags using Scout
        $tags = Tags::search($query)->take(5)->get()->map(function ($tag) use ($lang) {
            $detail = $tag->details->where('lang', $lang)->first() ?? $tag->details->first();
            return [
                'id' => $tag->id,
                'name' => $detail->tag_name ?? 'N/A',
                'tag_type' => $tag->type,
                'slug' => $tag->slug,
                'type' => 'tag'
            ];
        });

        // 7. Search Periods (Manual for now or add Searchable if needed)
        $periods = Period::where('title_en', 'LIKE', "%{$query}%")
            ->orWhere('title_sd', 'LIKE', "%{$query}%")
            ->take(3)
            ->get()
            ->map(function ($period) use ($lang) {
                return [
                    'id' => $period->id,
                    'title' => $lang === 'sd' ? $period->title_sd : $period->title_en,
                    'date_range' => $period->date_range,
                    'type' => 'period'
                ];
            });

        return response()->json([
            'poets' => $poets,
            'poetry' => $poetry,
            'periods' => $periods,
            'dictionary' => $dictionary,
            'corpus' => $corpus,
            'categories' => $categories,
            'tags' => $tags
        ]);
    }
}
