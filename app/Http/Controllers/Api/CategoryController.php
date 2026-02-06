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
            ->with([
                'details' => function ($q) use ($lang) {
                    $q->where('lang', $lang);
                }
            ])
            ->get()
            ->map(function ($cat) use ($lang) {
                $detail = $cat->details->where('lang', $lang)->first() ?? $cat->details->first();
                return [
                    'id' => $cat->id,
                    'slug' => $cat->slug,
                    'name' => $detail?->cat_name ?? $cat->slug,
                ];
            });

        return response()->json($categories);
    }
}
