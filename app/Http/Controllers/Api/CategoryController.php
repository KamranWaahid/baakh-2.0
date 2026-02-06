<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('lang', app()->getLocale());

        $categories = Categories::whereHas('poetry', function ($q) {
            $q->where('visibility', 1);
        })
            ->withCount([
                'poetry' => function ($q) {
                    $q->where('visibility', 1);
                }
            ])
            ->with([
                'details'
            ])
            ->get()
            ->map(function ($cat) use ($lang) {
                $detail = $cat->details->where('lang', $lang)->first() ?? $cat->details->first();
                $enDetail = $cat->details->where('lang', 'en')->first() ?? $cat->details->first();
                $sdDetail = $cat->details->where('lang', 'sd')->first() ?? $cat->details->first();

                return [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'name' => $detail?->cat_name ?? $cat->slug,
                    'sd_name' => $sdDetail?->cat_name ?? $cat->slug,
                    'en_name' => $enDetail?->cat_name ?? $cat->slug,
                    'desc' => $detail?->cat_detail ?? '',
                    'count' => $cat->poetry_count ?? 0,
                ];
            });

        return response()->json($categories);
    }
}
