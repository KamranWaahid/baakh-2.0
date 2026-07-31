<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\SindhiNormalizer;
use App\Models\LughatLemma;
use App\Models\Romanizer;
use App\Services\RomanizerService;
use App\Support\DictionaryText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RomanizerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage_romanizer')->only(['store', 'update', 'destroy']);
        $this->middleware('role:super_admin')->only(['refresh']);
    }

    public function index(Request $request)
    {
        $query = Romanizer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('word_sd', 'like', "%{$search}%")
                    ->orWhere('word_roman', 'like', "%{$search}%");
            });
        }

        $lughatStatus = $request->get('lughat_status', 'all');
        if ($lughatStatus === 'added') {
            $addedIds = $this->romanizerLughatOverlap()['added_ids'];
            if ($addedIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('id', $addedIds);
            }
        } elseif ($lughatStatus === 'remaining') {
            $addedIds = $this->romanizerLughatOverlap()['added_ids'];
            if ($addedIds !== []) {
                $query->whereNotIn('id', $addedIds);
            }
        }

        $perPage = $request->get('per_page', 20);
        $words = $query->orderBy('id', 'desc')->paginate($perPage);

        if ($lughatStatus === 'all' && $words->count() > 0) {
            $addedLookup = array_fill_keys($this->romanizerLughatOverlap()['added_ids'], true);
            $words->getCollection()->transform(function ($row) use ($addedLookup) {
                $row->in_lughat = isset($addedLookup[$row->id]);

                return $row;
            });
        } else {
            $words->getCollection()->transform(function ($row) use ($lughatStatus) {
                $row->in_lughat = $lughatStatus === 'added';

                return $row;
            });
        }

        return response()->json($words);
    }

    /**
     * Stats: Romanizer words already present in Baakh Lughat vs still remaining.
     */
    public function lughatStats()
    {
        $sets = $this->romanizerLughatOverlap();

        return response()->json([
            'total' => $sets['total'],
            'added' => $sets['added'],
            'remaining' => $sets['remaining'],
            'lughat_lemmas' => $sets['lughat_lemmas'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word_sd' => 'required|string|max:255|unique:baakh_roman_words,word_sd',
            'word_roman' => 'required|string|max:255',
        ]);

        $validated['user_id'] = Auth::id() ?? 1; // Fallback to 1 if not authenticated for some reason

        // Manual creation to ensure sanitization matches validated order/keys or just use dedicated array
        $word = Romanizer::create([
            'word_sd' => strip_tags($validated['word_sd']),
            'word_roman' => strip_tags($validated['word_roman']),
            'user_id' => $validated['user_id']
        ]);

        $this->forgetLughatOverlapCache();

        return response()->json([
            'message' => 'Word added to Romanizer dictionary',
            'data' => $word
        ], 201);
    }

    public function show($id)
    {
        $word = Romanizer::findOrFail($id);
        return response()->json($word);
    }

    public function update(Request $request, $id)
    {
        $word = Romanizer::findOrFail($id);

        $validated = $request->validate([
            'word_sd' => 'required|string|max:255|unique:baakh_roman_words,word_sd,' . $id,
            'word_roman' => 'required|string|max:255',
        ]);

        $word->update([
            'word_sd' => strip_tags($validated['word_sd']),
            'word_roman' => strip_tags($validated['word_roman'])
        ]);

        $this->forgetLughatOverlapCache();

        return response()->json([
            'message' => 'Word updated successfully',
            'data' => $word
        ]);
    }

    public function destroy($id)
    {
        $word = Romanizer::findOrFail($id);
        $word->delete();

        $this->forgetLughatOverlapCache();

        return response()->json([
            'message' => 'Word deleted successfully'
        ]);
    }

    public function refresh(RomanizerService $romanizer)
    {
        try {
            $romanizer->forget();
            $romanizer->refreshDictionaryFile();

            return response()->json(['message' => 'Romanizer dictionary file updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update Romanizer file: ' . $e->getMessage()], 500);
        }
    }

    public function checkWords(Request $request, RomanizerService $romanizer)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        // Only Sindhi surfaces the romanizer cannot resolve (exact / airab-base / normalized).
        // Keeps zer/zabar/pesh on surfaces; skips Latin, digits, and punctuation-only tokens.
        $missing = $romanizer->findMissingWords($request->text);

        return response()->json([
            'missing_words' => $missing,
            'total_missing' => count($missing)
        ]);
    }
    public function standardize(Request $request)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        $standardized = SindhiNormalizer::normalize($request->text);

        return response()->json([
            'standardized_text' => $standardized
        ]);
    }

    public function transliterate(Request $request, RomanizerService $romanizer)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        return response()->json([
            'transliterated_text' => $romanizer->transliterate($request->text)
        ]);
    }

    /**
     * @return array{added_ids: list<int>, remaining_ids: list<int>, added: int, remaining: int, total: int, lughat_lemmas: int}
     */
    private function romanizerLughatOverlap(): array
    {
        return Cache::remember('romanizer.lughat_overlap.v2', 60, function () {
            $lughatKeys = [];
            foreach (LughatLemma::query()->select(['lemma', 'normalized_lemma'])->cursor() as $lemma) {
                foreach ([$lemma->lemma, $lemma->normalized_lemma] as $value) {
                    if (!$value) {
                        continue;
                    }
                    $identity = DictionaryText::normalizeForIdentity($value);
                    if ($identity !== '') {
                        $lughatKeys[$identity] = true;
                    }
                }
            }

            $addedIds = [];
            $remainingIds = [];
            foreach (Romanizer::query()->select(['id', 'word_sd'])->cursor() as $row) {
                $identity = DictionaryText::normalizeForIdentity((string) $row->word_sd);
                if ($identity !== '' && isset($lughatKeys[$identity])) {
                    $addedIds[] = (int) $row->id;
                } else {
                    $remainingIds[] = (int) $row->id;
                }
            }

            return [
                'added_ids' => $addedIds,
                'remaining_ids' => $remainingIds,
                'added' => count($addedIds),
                'remaining' => count($remainingIds),
                'total' => count($addedIds) + count($remainingIds),
                'lughat_lemmas' => LughatLemma::query()->count(),
            ];
        });
    }

    private function forgetLughatOverlapCache(): void
    {
        Cache::forget('romanizer.lughat_overlap');
        Cache::forget('romanizer.lughat_overlap.v2');
    }
}
