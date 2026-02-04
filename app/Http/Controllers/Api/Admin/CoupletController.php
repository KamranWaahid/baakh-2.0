<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Couplets;
use Illuminate\Http\Request;

class CoupletController extends Controller
{
    public function index(Request $request)
    {
        // Fetch couplets directly, filtering for Sindhi by default as per request
        $query = Couplets::where('lang', 'sd')->with([
            'poetry' => function ($q) {
                $q->select('id', 'category_id', 'visibility', 'is_featured', 'user_id', 'created_at');
            },
            'poetry.translations' => function ($q) {
                $q->select('id', 'poetry_id', 'lang'); // Optimizing select
            },
            'poetry.category.detail' => function ($q) {
                $q->where('lang', 'sd');
            },
            'poetry.user' => function ($q) {
                $q->select('id', 'name');
            },
            'poet_details' => function ($q) {
                $q->where('lang', 'sd');
            }
        ]);

        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('couplet_text', 'like', "%{$search}%")
                  ->orWhereHas('poet_details', function ($fq) use ($search) {
                      $fq->where('poet_laqab', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->get('per_page', 10);
        $couplets = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($couplets);
    }
}
