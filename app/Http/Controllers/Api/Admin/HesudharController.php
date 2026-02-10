<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaakhHesudhar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class HesudharController extends Controller
{
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

        $word = BaakhHesudhar::create($validated);

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

        $word->update($validated);

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
        // Punctuation to strip from beginning and end
        $punctuation = ['،', '’', '‘', '”', '“', '?', '!', '؛', '.', '؟', ',', '"', "'", '(', ')', '[', ']', '{', '}', '-', '_'];

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

            // Strip diacritics from the entire word
            $cleanWord = str_replace($diacritics, '', $cleanWord);

            // Strip punctuation from start
            while (mb_strlen($cleanWord) > 0 && in_array(mb_substr($cleanWord, 0, 1), $punctuation)) {
                $cleanWord = mb_substr($cleanWord, 1);
            }

            // Strip punctuation from end
            while (mb_strlen($cleanWord) > 0 && in_array(mb_substr($cleanWord, -1), $punctuation)) {
                $cleanWord = mb_substr($cleanWord, 0, -1);
            }

            if (!empty($cleanWord)) {
                $mistake = BaakhHesudhar::where('word', $cleanWord)->first();
                if ($mistake) {
                    $mistakes[] = [
                        'word' => $cleanWord,
                        'correct' => $mistake->correct
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
            return response()->json(['message' => 'Failed to update dictionary file: ' . $e->getMessage()], 500);
        }
    }
}
