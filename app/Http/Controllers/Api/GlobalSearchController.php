<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PoetsDetail;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Models\Period;
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
                'periods' => []
            ]);
        }

        $lang = $request->header('Accept-Language', 'en'); // 'en' or 'sd'

        // 1. Search Poets
        // Search in all languages for the name, but return relevant details
        $poets = PoetsDetail::where('poet_name', 'LIKE', "%{$query}%")
            ->orWhere('pen_name', 'LIKE', "%{$query}%")
            ->with('poet')
            ->take(5)
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->poet_id,
                    'name' => $detail->poet_name,
                    'slug' => $detail->poet->poet_slug ?? '',
                    'image' => $detail->poet->poet_pic ?? null,
                    'type' => 'poet'
                ];
            });

        // 2. Search Poetry
        // Search by title in translations
        $poetry = Poetry::whereHas('translations', function ($q) use ($query) {
            $q->where('title', 'LIKE', "%{$query}%");
        })
            ->with(['translations', 'poet_details', 'poet', 'category'])
            ->take(5)
            ->get()
            ->map(function ($poem) use ($lang) {
                $translation = $poem->translations->where('lang', $lang)->first()
                    ?? $poem->translations->first();
                $poetName = $poem->poet_details->poet_name ?? 'Unknown';
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

        // 3. Search Periods
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
            'periods' => $periods
        ]);
    }
}
