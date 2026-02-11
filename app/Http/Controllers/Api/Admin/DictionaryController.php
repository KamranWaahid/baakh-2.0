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

    // Sense Methods
    public function updateSense(Request $request, $id)
    {
        $sense = Sense::findOrFail($id);
        $validated = $request->validate([
            'definition' => 'string',
            'domain' => 'nullable|string',
            'status' => 'nullable|in:pending,approved',
        ]);

        $sense->update($validated);
        return response()->json($sense);
    }

    public function destroySense($id)
    {
        $sense = Sense::findOrFail($id);
        $sense->delete();
        return response()->json(null, 204);
    }

    // Example Methods
    public function updateExample(Request $request, $id)
    {
        $example = SenseExample::findOrFail($id);
        $validated = $request->validate([
            'sentence' => 'string',
            'source' => 'nullable|string',
            'corpus_sentence_id' => 'nullable|integer',
        ]);

        $example->update($validated);
        return response()->json($example);
    }

    public function destroyExample($id)
    {
        $example = SenseExample::findOrFail($id);
        $example->delete();
        return response()->json(null, 204);
    }

    // Morphology Methods
    public function updateMorphology(Request $request, $lemmaId)
    {
        $lemma = Lemma::findOrFail($lemmaId);
        $validated = $request->validate([
            'root' => 'nullable|string',
            'pattern' => 'nullable|string',
            'gender' => 'nullable|string',
            'number' => 'nullable|string',
            'case' => 'nullable|string',
            'aspect' => 'nullable|string',
            'tense' => 'nullable|string',
        ]);

        $morphology = Morphology::updateOrCreate(
            ['lemma_id' => $lemmaId],
            $validated
        );

        return response()->json($morphology);
    }

    // Variant Methods
    public function storeVariant(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'variant' => 'required|string',
            'type' => 'required|in:dialectal,misspelling,historical',
            'dialect' => 'nullable|string',
        ]);

        $variant = Variant::create([
            'lemma_id' => $lemmaId,
            'variant' => $validated['variant'],
            'type' => $validated['type'],
            'dialect' => $validated['dialect'],
        ]);

        return response()->json($variant, 201);
    }

    public function destroyVariant($id)
    {
        $variant = Variant::findOrFail($id);
        $variant->delete();
        return response()->json(null, 204);
    }

    public function approve($id)
    {
        $lemma = Lemma::findOrFail($id);
        $lemma->update(['status' => 'approved']);
        return response()->json(['message' => 'Lemma approved successfully']);
    }
}
