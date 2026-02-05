<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PoetsDetail;
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
        // Search mostly by Title for now
        $poetry = PoetryTranslations::where('title', 'LIKE', "%{$query}%")
            ->with(['poetry', 'poetry.poet_details']) // Eager load relations
            ->take(5)
            ->get()
            ->map(function ($trans) {
                $poetName = $trans->poetry->poet_details->poet_name ?? 'Unknown';
                return [
                    'id' => $trans->poetry_id,
                    'title' => $trans->title,
                    'slug' => $trans->poetry->poetry_slug ?? '',
                    'poet_name' => $poetName,
                    'cat_slug' => $trans->poetry->category->slug ?? 'ghazal', // default or fetch
                    'poet_slug' => $trans->poetry->poet->poet_slug ?? '',
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
