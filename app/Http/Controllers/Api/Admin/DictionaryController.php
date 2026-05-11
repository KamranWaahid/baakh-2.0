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
        $query = Lemma::withCount(['senses', 'lemmaRelations'])
            ->with([
                'morphology',
                'senses' => function ($query) {
                    $query->select([
                        'id',
                        'lemma_id',
                        'lexical_id',
                        'definition',
                        'part_of_speech',
                        'word_variant',
                        'domain',
                        'language_direction',
                        'source_dictionary',
                        'status',
                    ])->orderBy('id');
                },
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($query) use ($search) {
                $query->where('lemma', 'like', '%' . $search . '%')
                    ->orWhere('normalized_lemma', 'like', '%' . $search . '%')
                    ->orWhere('transliteration', 'like', '%' . $search . '%')
                    ->orWhereHas('senses', function ($query) use ($search) {
                        $query->where('definition', 'like', '%' . $search . '%')
                            ->orWhere('normalized_definition', 'like', '%' . $search . '%')
                            ->orWhere('source_dictionary', 'like', '%' . $search . '%')
                            ->orWhere('domain', 'like', '%' . $search . '%')
                            ->orWhere('lexical_id', $search);
                    });
            });
        }

        if ($request->filled('pos')) {
            $query->where('pos', $request->pos);
        }

        if ($request->filled('source')) {
            $query->whereHas('senses', function ($query) use ($request) {
                $query->where('source_dictionary', $request->source);
            });
        }

        if ($request->has('status')) {
            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }
        } else {
            // Default to only showing approved words on the main dictionary browse page
            $query->where('status', 'approved');
        }

        $limit = min(100, max(1, (int) $request->get('limit', 20)));

        return response()->json($query->orderBy('lemma')->paginate($limit));
    }

    public function stats()
    {
        $sources = Sense::query()
            ->select('source_dictionary', DB::raw('COUNT(*) as total'))
            ->whereNotNull('source_dictionary')
            ->groupBy('source_dictionary')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'total_lemmas' => Lemma::count(),
            'approved_lemmas' => Lemma::where('status', 'approved')->count(),
            'total_senses' => Sense::count(),
            'open_lexicon_entries' => Sense::whereNotNull('lexical_id')->count(),
            'sources' => $sources,
        ]);
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

        // Sync with Romanizer
        if (!empty($validated['transliteration'])) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $validated['transliteration'],
                    'user_id' => auth()->id() ?? 1
                ]
            );
        }

        return response()->json($lemma, 201);
    }

    public function show($id)
    {
        $lemma = Lemma::with(['senses.examples', 'morphology', 'variants', 'lemmaRelations'])->findOrFail($id);

        // Auto-fetch transliteration from Romanizer if it's empty
        if (empty($lemma->transliteration)) {
            $roman = \App\Models\Romanizer::where('word_sd', $lemma->lemma)->first();
            if ($roman) {
                // Attach the transliteration just for the response so the frontend receives it
                $lemma->transliteration = $roman->word_roman;
            }
        }

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

        // Sync with Romanizer
        if (!empty($validated['transliteration'])) {
            \App\Models\Romanizer::updateOrCreate(
                ['word_sd' => $lemma->lemma],
                [
                    'word_roman' => $validated['transliteration'],
                    'user_id' => auth()->id() ?? 1
                ]
            );
        }

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
    public function storeSense(Request $request)
    {
        $validated = $request->validate([
            'lemma_id' => 'required|exists:lemmas,id',
            'definition' => 'required|string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'domain' => 'nullable|string',
            'lang' => 'nullable|string',
        ]);

        $sense = Sense::create($validated);
        return response()->json($sense, 201);
    }

    public function updateSense(Request $request, $id)
    {
        $sense = Sense::findOrFail($id);
        $validated = $request->validate([
            'definition' => 'string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
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

    // Relation Methods
    public function storeRelation(Request $request, $lemmaId)
    {
        $validated = $request->validate([
            'relation_type' => 'required|in:synonym,antonym,hypernym',
            'related_word' => 'required|string',
        ]);

        $relation = \App\Models\LemmaRelation::create([
            'lemma_id' => $lemmaId,
            'relation_type' => $validated['relation_type'],
            'related_word' => $validated['related_word'],
        ]);

        return response()->json($relation, 201);
    }

    public function destroyRelation($id)
    {
        $relation = \App\Models\LemmaRelation::findOrFail($id);
        $relation->delete();
        return response()->json(null, 204);
    }

    // Scraping Method



}
