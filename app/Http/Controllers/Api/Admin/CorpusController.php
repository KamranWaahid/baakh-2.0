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
        return response()->json([
            'total_sentences' => CorpusSentence::count(),
            'total_tokens' => CorpusSentence::sum('token_count'),
            'sources' => CorpusSentence::select('source', \DB::raw('count(*) as count'))
                ->groupBy('source')
                ->get(),
        ]);
    }

    public function clusters()
    {
        // Mocking clusters for UI functionality
        // In reality, this would query a clusters table or run an LDA model
        return response()->json([
            [
                'name' => 'Education',
                'weight' => 42,
                'keywords' => ['school', 'teacher', 'student', 'library', 'exam', 'class'],
                'color' => 'blue'
            ],
            [
                'name' => 'Literature',
                'weight' => 28,
                'keywords' => ['poetry', 'writer', 'pages', 'ink', 'rhyme', 'verse'],
                'color' => 'green'
            ],
            [
                'name' => 'Politics',
                'weight' => 15,
                'keywords' => ['election', 'government', 'policy', 'vote', 'assembly'],
                'color' => 'amber'
            ],
        ]);
    }

    public function trends()
    {
        // Mocking trends for UI
        return response()->json([
            'trending_up' => [
                ['word' => 'ڊجيٽل', 'change' => '+45%', 'trend' => 'up'],
                ['word' => 'آرٽيفيشل', 'change' => '+32%', 'trend' => 'up'],
                ['word' => 'نيٽورڪ', 'change' => '+28%', 'trend' => 'up'],
            ],
            'trending_down' => [
                ['word' => 'ٽيليگراف', 'change' => '-12%', 'trend' => 'down'],
                ['word' => 'فلاپي', 'change' => '-8%', 'trend' => 'down'],
            ]
        ]);
    }
}
