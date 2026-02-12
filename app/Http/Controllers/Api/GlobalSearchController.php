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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GlobalSearchController extends Controller
{
    /**
     * Perform a global search across multiple models.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
        $driver = config('scout.driver');
        Log::info("GlobalSearch: query='{$query}', lang='{$lang}', driver='{$driver}'");

        DB::enableQueryLog();

        try {
            $results = [
                'poets' => $this->searchPoets($query, $lang),
                'poetry' => $this->searchPoetry($query, $lang),
                'periods' => $this->searchPeriods($query, $lang),
                'dictionary' => $this->searchDictionary($query),
                'corpus' => $this->searchCorpus($query),
                'categories' => $this->searchCategories($query, $lang),
                'tags' => $this->searchTags($query, $lang)
            ];
        } catch (\Exception $e) {
            Log::error("GlobalSearch Error: " . $e->getMessage());
            Log::error("Queries: " . print_r(DB::getQueryLog(), true));
            throw $e;
        }

        return response()->json($results);
    }

    /**
     * Search for poets.
     *
     * @param string $query
     * @param string $lang
     * @return Collection
     */
    private function searchPoets(string $query, string $lang): Collection
    {
        if (config('scout.driver') === 'database') {
            return Poets::where(function ($q) use ($query) {
                $q->whereHas('all_details', function ($sq) use ($query) {
                    $sq->where('poet_name', 'LIKE', "%{$query}%")
                        ->orWhere('poet_laqab', 'LIKE', "%{$query}%");
                })->orWhere('poet_slug', 'LIKE', "%{$query}%");
            })->take(5)->get()->load('all_details')->map(function ($poet) use ($lang) {
                $detail = $poet->all_details->where('lang', $lang)->first() ?? $poet->all_details->first();
                return [
                    'id' => $poet->id,
                    'name' => $detail->poet_name ?? 'N/A',
                    'slug' => $poet->poet_slug ?? '',
                    'image' => ($poet->poet_pic) ? (str_starts_with($poet->poet_pic, 'http') ? $poet->poet_pic : '/' . $poet->poet_pic) : null,
                    'type' => 'poet'
                ];
            });
        }

        return Poets::search($query)->take(5)->get()->load('all_details')->map(function ($poet) use ($lang) {
            $detail = $poet->all_details->where('lang', $lang)->first() ?? $poet->all_details->first();
            return [
                'id' => $poet->id,
                'name' => $detail->poet_name ?? 'N/A',
                'slug' => $poet->poet_slug ?? '',
                'image' => ($poet->poet_pic) ? (str_starts_with($poet->poet_pic, 'http') ? $poet->poet_pic : '/' . $poet->poet_pic) : null,
                'type' => 'poet'
            ];
        });
    }

    /**
     * Search for poetry.
     *
     * @param string $query
     * @param string $lang
     * @return Collection
     */
    private function searchPoetry(string $query, string $lang): Collection
    {
        if (config('scout.driver') === 'database') {
            return Poetry::where(function ($q) use ($query) {
                $q->whereHas('translations', function ($sq) use ($query) {
                    $sq->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('info', 'LIKE', "%{$query}%");
                })->orWhere('poetry_slug', 'LIKE', "%{$query}%");
            })->take(5)->get()->load(['translations', 'category', 'poet', 'poet.all_details'])->map(function ($poem) use ($lang) {
                $translation = $poem->translations->where('lang', $lang)->first() ?? $poem->translations->first();
                $poetDetail = $poem->poet->all_details->where('lang', $lang)->first() ?? $poem->poet->all_details->first();
                $poetName = $poetDetail->poet_name ?? 'Unknown';
                return [
                    'id' => $poem->id,
                    'title' => $translation->title ?? 'Untitled',
                    'slug' => $poem->poetry_slug ?? '',
                    'poet_name' => $poetName,
                    'cat_slug' => $poem->category->slug ?? 'ghazal',
                    'poet_slug' => $poem->poet->poet_slug ?? '',
                    'type' => 'poetry'
                ];
            });
        }

        return Poetry::search($query)->take(5)->get()->load(['translations', 'category', 'poet', 'poet.all_details'])->map(function ($poem) use ($lang) {
            $translation = $poem->translations->where('lang', $lang)->first() ?? $poem->translations->first();
            $poetDetail = $poem->poet->all_details->where('lang', $lang)->first() ?? $poem->poet->all_details->first();
            $poetName = $poetDetail->poet_name ?? 'Unknown';
            return [
                'id' => $poem->id,
                'title' => $translation->title ?? 'Untitled',
                'slug' => $poem->poetry_slug ?? '',
                'poet_name' => $poetName,
                'cat_slug' => $poem->category->slug ?? 'ghazal',
                'poet_slug' => $poem->poet->poet_slug ?? '',
                'type' => 'poetry'
            ];
        });
    }

    /**
     * Search for dictionary lemmas.
     *
     * @param string $query
     * @return Collection
     */
    private function searchDictionary(string $query): Collection
    {
        if (config('scout.driver') === 'database') {
            return Lemma::where(function ($q) use ($query) {
                $q->where('lemma', 'LIKE', "%{$query}%")
                    ->orWhere('transliteration', 'LIKE', "%{$query}%");
            })->take(5)->get()->map(function ($lemma) {
                return [
                    'id' => $lemma->id,
                    'lemma' => $lemma->lemma,
                    'transliteration' => $lemma->transliteration,
                    'type' => 'dictionary'
                ];
            });
        }

        return Lemma::search($query)->take(5)->get()->map(function ($lemma) {
            return [
                'id' => $lemma->id,
                'lemma' => $lemma->lemma,
                'transliteration' => $lemma->transliteration,
                'type' => 'dictionary'
            ];
        });
    }

    /**
     * Search for corpus sentences.
     *
     * @param string $query
     * @return Collection
     */
    private function searchCorpus(string $query): Collection
    {
        if (config('scout.driver') === 'database') {
            return CorpusSentence::where(function ($q) use ($query) {
                $q->where('sentence', 'LIKE', "%{$query}%")
                    ->orWhere('source', 'LIKE', "%{$query}%");
            })->take(5)->get()->map(function ($sentence) {
                return [
                    'id' => $sentence->id,
                    'sentence' => $sentence->sentence,
                    'source' => $sentence->source,
                    'type' => 'corpus'
                ];
            });
        }

        return CorpusSentence::search($query)->take(5)->get()->map(function ($sentence) {
            return [
                'id' => $sentence->id,
                'sentence' => $sentence->sentence,
                'source' => $sentence->source,
                'type' => 'corpus'
            ];
        });
    }

    /**
     * Search for categories.
     *
     * @param string $query
     * @param string $lang
     * @return Collection
     */
    private function searchCategories(string $query, string $lang): Collection
    {
        if (config('scout.driver') === 'database') {
            return Categories::where(function ($q) use ($query) {
                $q->whereHas('details', function ($sq) use ($query) {
                    $sq->where('cat_name', 'LIKE', "%{$query}%");
                })->orWhere('slug', 'LIKE', "%{$query}%");
            })->take(5)->get()->load('details')->map(function ($category) use ($lang) {
                $detail = $category->details->where('lang', $lang)->first() ?? $category->details->first();
                return [
                    'id' => $category->id,
                    'name' => $detail->cat_name ?? 'N/A',
                    'slug' => $category->slug,
                    'type' => 'category'
                ];
            });
        }

        return Categories::search($query)->take(5)->get()->load('details')->map(function ($category) use ($lang) {
            $detail = $category->details->where('lang', $lang)->first() ?? $category->details->first();
            return [
                'id' => $category->id,
                'name' => $detail->cat_name ?? 'N/A',
                'slug' => $category->slug,
                'type' => 'category'
            ];
        });
    }

    /**
     * Search for tags.
     *
     * @param string $query
     * @param string $lang
     * @return Collection
     */
    private function searchTags(string $query, string $lang): Collection
    {
        if (config('scout.driver') === 'database') {
            return Tags::where(function ($q) use ($query) {
                $q->whereHas('details', function ($sq) use ($query) {
                    $sq->where('name', 'LIKE', "%{$query}%");
                })->orWhere('slug', 'LIKE', "%{$query}%");
            })->take(5)->get()->load('details')->map(function ($tag) use ($lang) {
                $detail = $tag->details->where('lang', $lang)->first() ?? $tag->details->first();
                return [
                    'id' => $tag->id,
                    'name' => $detail->name ?? 'N/A',
                    'tag_type' => $tag->type,
                    'slug' => $tag->slug,
                    'type' => 'tag'
                ];
            });
        }

        return Tags::search($query)->take(5)->get()->load('details')->map(function ($tag) use ($lang) {
            $detail = $tag->details->where('lang', $lang)->first() ?? $tag->details->first();
            return [
                'id' => $tag->id,
                'name' => $detail->name ?? 'N/A',
                'tag_type' => $tag->type,
                'slug' => $tag->slug,
                'type' => 'tag'
            ];
        });
    }

    /**
     * Search for periods.
     *
     * @param string $query
     * @param string $lang
     * @return Collection
     */
    private function searchPeriods(string $query, string $lang): Collection
    {
        return Period::where(function ($q) use ($query) {
            $q->where('title_en', 'LIKE', "%{$query}%")
                ->orWhere('title_sd', 'LIKE', "%{$query}%");
        })->take(3)->get()->map(function ($period) use ($lang) {
            return [
                'id' => $period->id,
                'title' => $lang === 'sd' ? $period->title_sd : $period->title_en,
                'date_range' => $period->date_range,
                'type' => 'period'
            ];
        });
    }
}
