<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorpusStat;
use App\Models\Lemma;
use App\Models\Sense;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function frequency(Request $request)
    {
        $limit = min(100, max(1, (int) $request->get('limit', 50)));
        $page = $request->get('page', 1);

        if (CorpusStat::query()->exists()) {
            $stats = CorpusStat::query()
                ->when($request->filled('search'), fn ($query) => $query->where('word', 'like', '%' . trim($request->search) . '%'))
                ->orderBy('frequency', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json($stats);
        }

        $stats = Sense::query()
            ->join('lemmas', 'senses.lemma_id', '=', 'lemmas.id')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->search);
                $query->where(function ($query) use ($search) {
                    $query->where('lemmas.lemma', 'like', '%' . $search . '%')
                        ->orWhere('senses.definition', 'like', '%' . $search . '%');
                });
            })
            ->select([
                'lemmas.lemma as word',
                DB::raw('COUNT(senses.id) as frequency'),
                DB::raw('MAX(senses.source_dictionary) as source_dictionary'),
                DB::raw('MAX(senses.part_of_speech) as part_of_speech'),
            ])
            ->groupBy('lemmas.id', 'lemmas.lemma')
            ->orderByDesc('frequency')
            ->paginate($limit, ['*'], 'page', $page);

        return response()->json($stats);
    }

    public function dialect()
    {
        $variantDialects = Variant::query()
            ->selectRaw("COALESCE(NULLIF(dialect, ''), type, 'Unspecified') as name, COUNT(*) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $lexiconDirections = Sense::query()
            ->whereNotNull('language_direction')
            ->where('language_direction', '<>', '')
            ->selectRaw('language_direction as name, COUNT(*) as total')
            ->groupBy('language_direction')
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        $topVariants = Sense::query()
            ->with(['lemma:id,lemma'])
            ->whereNotNull('word_variant')
            ->where('word_variant', '<>', '')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'lemma_id', 'word_variant', 'language_direction', 'source_dictionary']);

        return response()->json([
            'variant_dialects' => $variantDialects,
            'lexicon_directions' => $lexiconDirections,
            'top_variants' => $topVariants,
            'totals' => [
                'curated_variants' => Variant::count(),
                'lexicon_variants' => Sense::whereNotNull('word_variant')->where('word_variant', '<>', '')->count(),
            ],
        ]);
    }

    public function trends()
    {
        $sources = Sense::query()
            ->select('source_dictionary', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_dictionary')
            ->groupBy('source_dictionary')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $partsOfSpeech = Sense::query()
            ->selectRaw("COALESCE(NULLIF(part_of_speech, ''), 'Unspecified') as name, COUNT(*) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recent = Lemma::query()
            ->withCount('senses')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'lemma', 'status', 'updated_at']);

        return response()->json([
            'sources' => $sources,
            'parts_of_speech' => $partsOfSpeech,
            'recent_lemmas' => $recent,
            'totals' => [
                'lemmas' => Lemma::count(),
                'senses' => Sense::count(),
                'approved_lemmas' => Lemma::where('status', 'approved')->count(),
            ],
        ]);
    }
}
