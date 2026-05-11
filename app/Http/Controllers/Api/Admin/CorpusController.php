<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorpusSentence;
use App\Models\CorpusStat;
use App\Models\Sense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorpusController extends Controller
{
    public function index(Request $request)
    {
        $query = CorpusSentence::query();

        if ($request->filled('search')) {
            $query->where('sentence', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $sentences = $query->latest()->paginate($limit);

        return response()->json($sentences);
    }

    public function stats()
    {
        return response()->json([
            'total_sentences' => CorpusSentence::count(),
            'total_tokens' => CorpusSentence::sum('token_count'),
            'sources' => CorpusSentence::select('source', DB::raw('count(*) as count'))
                ->groupBy('source')
                ->get(),
        ]);
    }

    public function clusters()
    {
        $corpusTotal = CorpusSentence::count();
        if ($corpusTotal > 0) {
            $clusters = CorpusSentence::query()
                ->selectRaw("COALESCE(NULLIF(category, ''), NULLIF(source, ''), 'General') as name, COUNT(*) as count")
                ->groupBy('name')
                ->orderByDesc('count')
                ->limit(12)
                ->get()
                ->map(function ($cluster) use ($corpusTotal) {
                    return [
                        'name' => $cluster->name,
                        'weight' => round(($cluster->count / max(1, $corpusTotal)) * 100, 1),
                        'count' => (int) $cluster->count,
                        'keywords' => CorpusStat::query()
                            ->orderByDesc('frequency')
                            ->limit(8)
                            ->pluck('word')
                            ->all(),
                        'source' => 'corpus_sentences',
                    ];
                });

            return response()->json($clusters);
        }

        $senseTotal = Sense::count();
        $clusters = Sense::query()
            ->selectRaw("COALESCE(NULLIF(domain, ''), NULLIF(source_dictionary, ''), 'General') as name, COUNT(*) as count")
            ->groupBy('name')
            ->orderByDesc('count')
            ->limit(12)
            ->get()
            ->map(function ($cluster) use ($senseTotal) {
                $keywords = Sense::query()
                    ->join('lemmas', 'senses.lemma_id', '=', 'lemmas.id')
                    ->where(function ($query) use ($cluster) {
                        $query->where('senses.domain', $cluster->name)
                            ->orWhere('senses.source_dictionary', $cluster->name);
                    })
                    ->orderBy('lemmas.lemma')
                    ->limit(8)
                    ->pluck('lemmas.lemma')
                    ->all();

                return [
                    'name' => $cluster->name,
                    'weight' => round(($cluster->count / max(1, $senseTotal)) * 100, 1),
                    'count' => (int) $cluster->count,
                    'keywords' => $keywords,
                    'source' => 'lexicon_senses',
                ];
            });

        return response()->json($clusters);
    }

    public function trends()
    {
        $top = CorpusStat::query()
            ->orderByDesc('frequency')
            ->limit(10)
            ->get(['word', 'frequency'])
            ->map(fn ($row) => [
                'word' => $row->word,
                'change' => number_format((int) $row->frequency) . ' uses',
                'frequency' => (int) $row->frequency,
                'trend' => 'top_frequency',
            ]);

        $rare = CorpusStat::query()
            ->where('frequency', '>', 0)
            ->orderBy('frequency')
            ->limit(10)
            ->get(['word', 'frequency'])
            ->map(fn ($row) => [
                'word' => $row->word,
                'change' => number_format((int) $row->frequency) . ' uses',
                'frequency' => (int) $row->frequency,
                'trend' => 'low_frequency',
            ]);

        return response()->json([
            'trending_up' => $top,
            'trending_down' => $rare,
        ]);
    }
}
