<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProsodyTerm;
use Illuminate\Http\Request;

class ProsodyController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->get('lang', 'sd');

        $terms = ProsodyTerm::orderBy('order', 'asc')->get()->map(function ($term) use ($lang) {
            return [
                'id' => $term->id,
                'title' => $lang === 'sd' ? $term->title_sd : $term->title_en,
                'subtitle' => $lang === 'sd' ? $term->title_en : $term->title_sd,
                'description' => $lang === 'sd' ? $term->desc_sd : $term->desc_en,
                'technical_detail' => $lang === 'sd' ? $term->tech_detail_sd : $term->tech_detail_en,
                'logic_type' => $term->logic_type,
                'icon' => $term->icon,
            ];
        });

        return response()->json($terms);
    }
}
