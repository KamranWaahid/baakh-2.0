<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use App\Models\Sense;
use App\Models\SenseExample;
use App\Models\Morphology;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DictionaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Lemma::withCount('senses');

        if ($request->has('search')) {
            $query->where('lemma', 'like', '%' . $request->search . '%');
        }

        if ($request->has('pos')) {
            $query->where('pos', $request->pos);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate($request->get('limit', 20)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lemma' => 'required|string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $lemma = Lemma::create($validated);

        return response()->json($lemma, 201);
    }

    public function show($id)
    {
        $lemma = Lemma::with(['senses.examples', 'morphology', 'variants'])->findOrFail($id);
        return response()->json($lemma);
    }

    public function update(Request $request, $id)
    {
        $lemma = Lemma::findOrFail($id);

        $validated = $request->validate([
            'lemma' => 'string',
            'pos' => 'nullable|string',
            'transliteration' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $lemma->update($validated);

        // Nested updates for senses, morphology, variants could be added here if needed
        // For a full CRUD, usually we have separate endpoints or a complex sync logic.
        // Given the UI shows separate sections, we'll keep it simple for now and expand as needed.

        return response()->json($lemma);
    }

    public function destroy($id)
    {
        $lemma = Lemma::findOrFail($id);
        $lemma->delete();
        return response()->json(null, 204);
    }

    // Additional methods for Senses, Morphology, etc.
    public function storeSense(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'definition' => 'required|string',
            'domain' => 'nullable|string',
        ]);

        $sense = Sense::create([
            'lemma_id' => $lemmaId,
            'definition' => $validated['definition'],
            'domain' => $validated['domain'],
        ]);

        return response()->json($sense, 201);
    }

    public function storeExample(Request $request, $senseId)
    {
        $validated = $request->validate([
            'sentence' => 'required|string',
            'source' => 'nullable|string',
            'corpus_sentence_id' => 'nullable|integer',
        ]);

        $example = SenseExample::create([
            'sense_id' => $senseId,
            'sentence' => $validated['sentence'],
            'source' => $validated['source'],
            'corpus_sentence_id' => $validated['corpus_sentence_id'],
        ]);

        return response()->json($example, 201);
    }
}
