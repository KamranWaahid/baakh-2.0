<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaakhHesudhar;
use App\Helpers\SindhiNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HesudharController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    public function index(Request $request)
    {
        $query = BaakhHesudhar::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('word', 'like', "%{$search}%")
                ->orWhere('correct', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 20);
        $words = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($words);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:255|unique:baakh_hesudhars,word',
            'correct' => 'required|string|max:255',
        ]);

        $word = BaakhHesudhar::create([
            'word' => strip_tags($validated['word']),
            'correct' => strip_tags($validated['correct']),
        ]);

        return response()->json([
            'message' => 'Word added to dictionary',
            'data' => $word
        ], 201);
    }

    public function show($id)
    {
        $word = BaakhHesudhar::findOrFail($id);
        return response()->json($word);
    }

    public function update(Request $request, $id)
    {
        $word = BaakhHesudhar::findOrFail($id);

        $validated = $request->validate([
            'word' => 'required|string|max:255|unique:baakh_hesudhars,word,' . $id,
            'correct' => 'required|string|max:255',
        ]);

        $word->update([
            'word' => strip_tags($validated['word']),
            'correct' => strip_tags($validated['correct']),
        ]);

        return response()->json([
            'message' => 'Word updated successfully',
            'data' => $word
        ]);
    }

    public function destroy($id)
    {
        $word = BaakhHesudhar::findOrFail($id);
        $word->delete();

        return response()->json([
            'message' => 'Word deleted successfully'
        ]);
    }

    public function checkWords(Request $request)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        // Split by whitespace (space, tab, newline, etc.)
        $get_text = preg_split('/\s+/u', $request->text, -1, PREG_SPLIT_NO_EMPTY);
        $text = array_unique($get_text);

        $mistakes = array();
        // Punctuation to strip from beginning and end (Added U+06D4 Arabic Full Stop)
        $punctuation = ['۔', '،', '’', '‘', '”', '“', '?', '!', '؛', '.', '؟', ',', '"', "'", '(', ')', '[', ']', '{', '}', '-', '_'];

        // Diacritics to strip globally from words
        $diacritics = [
            "\u{064B}", // Fathatayn
            "\u{064C}", // Dammatayn
            "\u{064D}", // Kasratayn
            "\u{064E}", // Fatha
            "\u{064F}", // Damma
            "\u{0650}", // Kasra
            "\u{0651}", // Shadda
            "\u{0652}", // Sukun
            "\u{0653}", // Maddah
            "\u{0670}", // Superscript Alef
        ];

        foreach ($text as $word) {
            $cleanWord = $word;

            // Strip diacritics for dictionary/normalization lookup
            $matchWord = str_replace($diacritics, '', $cleanWord);

            // Strip punctuation from start/end
            while (mb_strlen($matchWord) > 0 && in_array(mb_substr($matchWord, 0, 1), $punctuation)) {
                $matchWord = mb_substr($matchWord, 1);
            }
            while (mb_strlen($matchWord) > 0 && in_array(mb_substr($matchWord, -1), $punctuation)) {
                $matchWord = mb_substr($matchWord, 0, -1);
            }

            if (!empty($matchWord)) {
                $matchWord = trim($matchWord);

                // Phase 2: Establish Functional Phonetic Truth FIRST
                $phoneticCorrect = SindhiNormalizer::normalize($matchWord);

                // Phase 3: WordNet Validation & Feedback Loop
                $dictionaryEntry = BaakhHesudhar::where('word', $matchWord)->first();
                if (!$dictionaryEntry && preg_match('/[هہةھە]/u', $matchWord)) {
                    $variants = [
                        str_replace(['ه', 'ہ', 'ھ', 'ە', 'ة'], 'ه', $matchWord),
                        str_replace(['ه', 'ہ', 'ھ', 'ە', 'ة'], 'ہ', $matchWord),
                        str_replace(['ه', 'ہ', 'ھ', 'ە', 'ة'], 'ھ', $matchWord),
                    ];
                    $dictionaryEntry = BaakhHesudhar::whereIn('word', $variants)->first();
                }

                $finalCorrection = $phoneticCorrect;

                if ($dictionaryEntry) {
                    // Auto-Flagging Feedback Loop (Phase 3)
                    // If the dictionary's explicit correction fundamentally disagrees with the established final
                    // functional normalization, flag it for manual editorial review to resolve legacy visual hacks.
                    if ($dictionaryEntry->correct !== $finalCorrection) {
                        \Illuminate\Support\Facades\DB::table('baakh_hesudhars')
                            ->where('id', $dictionaryEntry->id)
                            ->update(['is_flagged' => true]);
                    }
                }

                // If the final established correction is different from the raw input, flag it as a mistake for the UI
                if ($matchWord !== $finalCorrection) {
                    $mistakes[] = [
                        'word' => $matchWord,
                        'correct' => $finalCorrection,
                        'type' => $dictionaryEntry ? 'dictionary' : 'normalization'
                    ];
                }
            }
        }

        return response()->json([
            'mistakes' => $mistakes,
            'total_mistakes' => count($mistakes)
        ]);
    }

    public function refresh()
    {
        $words = BaakhHesudhar::all();
        $filePath = public_path('vendor/hesudhar/words.dic');

        $content = "";
        foreach ($words as $v) {
            $content .= $v->word . ":" . $v->correct . PHP_EOL;
        }

        try {
            if (!File::exists(public_path('vendor/hesudhar'))) {
                File::makeDirectory(public_path('vendor/hesudhar'), 0755, true);
            }
            File::put($filePath, $content);

            return response()->json(['message' => 'Dictionary file updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update file: ' . $e->getMessage()], 500);
        }
    }

    public function cleanse()
    {
        $fixedCount = 0;
        $offset = 0;
        $chunkSize = 5000;

        do {
            $rows = \Illuminate\Support\Facades\DB::table('baakh_hesudhars')
                ->orderBy('id')
                ->offset($offset)
                ->limit($chunkSize)
                ->get(['id', 'word', 'correct']);

            foreach ($rows as $row) {
                $newWord = $this->cleanseString($row->word);
                $newCorrect = $this->cleanseString($row->correct);

                if ($row->word !== $newWord || $row->correct !== $newCorrect) {
                    \Illuminate\Support\Facades\DB::table('baakh_hesudhars')
                        ->where('id', $row->id)
                        ->update(['word' => $newWord, 'correct' => $newCorrect]);
                    $fixedCount++;
                }
            }

            $offset += $chunkSize;
        } while (count($rows) === $chunkSize);

        return response()->json([
            'message' => "Phonetic cleanse complete. Fixed {$fixedCount} records.",
            'fixed_count' => $fixedCount,
        ]);
    }

    /**
     * Apply standardised Sindhi orthographic cleansing rules to a string.
     *
     * Rules (per Mansour 2023 & SIL 2021):
     *  1. Kaf Standardisation      – Arabic ك  → Sindhi ڪ
     *  2. Yeh Standardisation      – Farsi  ی  → Arabic ي
     *  3. Atomic Recomposition     – Alef + Madda → آ
     *  4. Double weak-heh fix      – any two WEAK hehs at word end → ہ (U+06C1)
     *  5. Word-final aspirated-heh – ھ (U+06BE) at word end → ہ (U+06C1)
     *  6. Final weak heh           – any weak heh at string end → ہ (U+06C1)
     */
    private function cleanseString(?string $text): ?string
    {
        if (empty($text)) {
            return $text;
        }

        // 1. Kaf Standardisation (Arabic ك → Sindhi ڪ)
        $text = str_replace('ك', 'ڪ', $text);

        // 2. Yeh Standardisation (Farsi ی → Arabic ي)
        $text = str_replace('ی', 'ي', $text);

        // 3. Atomic Recomposition (Alef + Madda → آ)
        $text = str_replace('ا' . 'ٓ', 'آ', $text);

        // 4. Collapse double WEAK hehs (ه ہ ة ە) -> single ہ U+06C1
        //    Crucially: EXCLUDES ھ (U+06BE), so valid sequences like ھہ (Aspiration + Weak Heh) are preserved!
        $text = preg_replace('/[هہةە]{2}$/u', 'ہ', $text);

        // 5. Word-final aspirated-heh fix: ھ (U+06BE) strictly at word end is a legacy "glyph hack".
        $text = preg_replace('/ھ$/u', 'ہ', $text);

        // 6. Any other single weak heh at the end becomes the standard ہ (Heh Goal)
        $text = preg_replace('/[هةە]$/u', 'ہ', $text);

        return $text;
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
}
