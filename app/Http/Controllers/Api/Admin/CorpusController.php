<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorpusSentence;
use Illuminate\Http\Request;

class CorpusController extends Controller
{
    public function index(Request $request)
    {
        $query = CorpusSentence::query();

        if ($request->has('search')) {
            $query->where('sentence', 'like', '%' . $request->search . '%');
        }

        if ($request->has('source')) {
            $query->where('source', $request->source);
        }

        $sentences = $query->latest()->paginate($request->get('limit', 20));

        return response()->json($sentences);
    }

    public function stats()
    {
        // For now, return some mock stats since full aggregation is pending
        // In a real scenario, we'd query the corpus_stats table
        return response()->json([
            'total_sentences' => CorpusSentence::count(),
            'total_tokens' => CorpusSentence::sum('token_count'),
            'sources' => CorpusSentence::select('source', \DB::raw('count(*) as count'))
                ->groupBy('source')
                ->get(),
        ]);
    }
}
