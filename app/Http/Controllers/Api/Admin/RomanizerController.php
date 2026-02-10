<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Romanizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

class RomanizerController extends Controller
{
    public function index(Request $request)
    {
        $query = Romanizer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('word_sd', 'like', "%{$search}%")
                ->orWhere('word_roman', 'like', "%{$search}%");
        }

        $perPage = $request->get('per_page', 20);
        $words = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($words);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word_sd' => 'required|string|max:255|unique:baakh_roman_words,word_sd',
            'word_roman' => 'required|string|max:255',
        ]);

        $validated['user_id'] = Auth::id() ?? 1; // Fallback to 1 if not authenticated for some reason
        $validated['approved'] = 1;

        $word = Romanizer::create($validated);

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

        $word->update($validated);

        return response()->json([
            'message' => 'Word updated successfully',
            'data' => $word
        ]);
    }

    public function destroy($id)
    {
        $word = Romanizer::findOrFail($id);
        $word->delete();

        return response()->json([
            'message' => 'Word deleted successfully'
        ]);
    }

    public function refresh()
    {
        $words = Romanizer::all();
        $filePath = public_path('vendor/roman-converter/all_words.dic');

        $content = "";
        foreach ($words as $v) {
            $content .= $v->word_sd . ":" . $v->word_roman . PHP_EOL;
        }

        try {
            if (!File::exists(public_path('vendor/roman-converter'))) {
                File::makeDirectory(public_path('vendor/roman-converter'), 0755, true);
            }
            File::put($filePath, $content);

            return response()->json(['message' => 'Romanizer dictionary file updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update Romanizer file: ' . $e->getMessage()], 500);
        }
    }

    public function checkWords(Request $request)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        // Split by whitespace (space, tab, newline, etc.)
        $get_text = preg_split('/\s+/u', $request->text, -1, PREG_SPLIT_NO_EMPTY);
        $text = array_unique($get_text);
        $missing_words = array();
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

            // Normalize terminal 'ه' (U+0647 / Arabic Heh) to 'ہ' (U+06C1 / Urdu/Sindhi Heh Goal)
            // UPDATE: We should normalize ALL Heh variants to standard Sindhi 'ھ' (U+06BE) for dictionary consistency
            $cleanWord = str_replace(["ه", "ہ"], "ھ", $cleanWord);

            if (!empty($cleanWord) && !Romanizer::where('word_sd', $cleanWord)->exists()) {
                $missing[] = $cleanWord;
            }
        }

        // Re-index array to ensure JSON array (not object with numeric keys)
        $missing = array_values(array_unique($missing));

        return response()->json([
            'missing_words' => $missing,
            'total_missing' => count($missing)
        ]);
    }
    public function transliterate(Request $request)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        $text = $request->text;

        // Load the dictionary
        // We can use the DB directly for better consistency
        $words = Romanizer::all()->pluck('word_roman', 'word_sd')->toArray();

        // Helper function to process text
        // This is a simplified version of the JS logic, adapted for PHP

        $lines = explode("\n", $text);
        $resultLines = [];

        foreach ($lines as $line) {
            $wordsInLine = explode(' ', $line);
            $processedWords = [];

            foreach ($wordsInLine as $word) {
                // Remove punctuation to find the root word
                // Sindhi punctuation: ، ؛ . ” “ ؟ !
                // And common ones: ? , .
                $punctuation = '';
                $cleanWord = $word;

                // Simple punctuation extraction (start and end)
                if (preg_match('/^([^\w\s]*)(.*?)([^\w\s]*)$/u', $word, $matches)) {
                    // matches[1] is start punct, [2] is word, [3] is end punct
                    // But "word" characters in unicode... regex is tricky for Sindhi.
                    // Let's use the JS approach: strip known punctuation
                }

                $mapped = $words[$word] ?? null;

                if ($mapped) {
                    $processedWords[] = $mapped;
                } else {
                    // Try stripping punctuation
                    $sindhiPunctuation = ['،', '؛', '.', '”', '“', '!', '?', '؟', ',', '"', "'"];
                    $foundPunctuationStart = '';
                    $foundPunctuationEnd = '';

                    // Verify if word starts with punctuation
                    $firstChar = mb_substr($word, 0, 1);
                    if (in_array($firstChar, $sindhiPunctuation)) {
                        $foundPunctuationStart = $firstChar;
                        $cleanWord = mb_substr($word, 1);
                    }

                    // Verify if word ends with punctuation
                    $lastChar = mb_substr($cleanWord, -1);
                    if (in_array($lastChar, $sindhiPunctuation)) {
                        $foundPunctuationEnd = $lastChar;
                        $cleanWord = mb_substr($cleanWord, 0, -1);
                    }

                    if (isset($words[$cleanWord])) {
                        $processedWords[] = $foundPunctuationStart . $words[$cleanWord] . $foundPunctuationEnd;
                    } else {
                        $processedWords[] = $word; // Keep original if not found
                    }
                }
            }
            $resultLines[] = implode(' ', $processedWords);
        }

        return response()->json([
            'transliterated_text' => implode("\n", $resultLines)
        ]);
    }
}
