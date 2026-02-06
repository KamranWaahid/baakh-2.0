<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poets;
use App\Models\Tags;
use Illuminate\Http\Request;

class PoetController extends Controller
{
    public function index(Request $request)
    {
        $query = Poets::query()->with('all_details')
            ->withCount('poetry')
            ->where('visibility', 1); // Only visible poets

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('all_details', function ($q) use ($search) {
                $q->where('poet_name', 'like', "%{$search}%")
                    ->orWhere('poet_laqab', 'like', "%{$search}%");
            });
        }

        if ($request->has('tag')) {
            $tag = $request->tag;
            // JSON Search in poet_tags column
            $query->where('poet_tags', 'like', '%"' . $tag . '"%');
        }

        // Filter by category if needed (Assuming 'category' logic exists, but for now simple list)
        // If categories are tags or separate table, implementation varies.
        // User asked for "Real data", so listing all active poets is primary.

        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 20);
        $poets = $query->paginate($perPage);

        $poets->through(function ($poet) {
            // Helper to get detail by lang
            $getDetail = function ($lang) use ($poet) {
                return $poet->all_details->where('lang', $lang)->first();
            };

            $detailSd = $getDetail('sd');
            $detailEn = $getDetail('en');
            // Fallbacks
            $defaultDetail = $poet->all_details->first() ?? (object) [];

            return [
                'id' => $poet->id,
                'slug' => $poet->poet_slug,
                'avatar' => $poet->poet_pic, // Full URL expected if accessor exists or handled in frontend
                // English Data
                'name_en' => $detailEn->poet_laqab ?? $detailEn->poet_name ?? $detailSd->poet_laqab ?? $detailSd->poet_name ?? 'N/A',
                'bio_en' => strip_tags($detailEn->poet_bio ?? $detailSd->poet_bio ?? ''),
                // Sindhi Data
                'name_sd' => $detailSd->poet_laqab ?? $detailSd->poet_name ?? $detailEn->poet_laqab ?? $detailEn->poet_name ?? 'N/A',
                'bio_sd' => strip_tags($detailSd->poet_bio ?? $detailEn->poet_bio ?? ''),

                'entries_count' => $poet->poetry_count ?? 0,

                // Extra metadata
                'followers' => '0', // Placeholder or real relation count
                'category' => 'all', // Dynamic categorization if available
            ];
        });

        return response()->json($poets);
    }

    public function tags(Request $request)
    {
        $lang = $request->get('lang', 'sd');

        $tags = Tags::where('type', 'poets')
            ->where('lang', $lang)
            ->select('tag', 'slug')
            ->get();

        return response()->json($tags);
    }
}
