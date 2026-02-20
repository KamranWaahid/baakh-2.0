<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Romanizer;
use App\Helpers\SindhiNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;

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

        // Manual creation to ensure sanitization matches validated order/keys or just use dedicated array
        $word = Romanizer::create([
            'word_sd' => strip_tags($validated['word_sd']),
            'word_roman' => strip_tags($validated['word_roman']),
            'user_id' => $validated['user_id']
        ]);

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
        $missing = array();
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

            // Normalize ALL Heh variants to standard Sindhi 'ھ' (U+06BE) for dictionary consistency check
            // UPDATE: Use SindhiNormalizer for phonetic-contextual rules
            $normalizedCleanWord = SindhiNormalizer::normalize($cleanWord);

            $exists = Romanizer::where('word_sd', $cleanWord)->exists();
            if (!$exists && $cleanWord !== $normalizedCleanWord) {
                $exists = Romanizer::where('word_sd', $normalizedCleanWord)->exists();
            }

            if (!empty($cleanWord) && !$exists) {
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

    public function transliterate(Request $request)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        $text = $request->text;

        // Load the dictionary
        $words = Romanizer::all()->pluck('word_roman', 'word_sd')->toArray();

        // Punctuation to strip and NOT carry into Roman output
        $sindhiPunctuation = ['،', '؛', '؟', "\xD8\x9B", ':', ';', '{', '}', '[', ']', '(', ')'];
        // Punctuation to preserve in Roman output
        $romanPunctuation = ['.', '!', '?', ',', '"', "'", '"', '"'];
        $allPunctuation = array_merge($sindhiPunctuation, $romanPunctuation);

        // Diacritic mappings for fallback (UTF-8 bytes)
        $diacriticMap = [
            "\xD9\x8E" => 'a', // Zabar (U+064E)
            "\xD9\x90" => 'i', // Zer (U+0650)
            "\xD9\x8F" => 'u', // Pesh (U+064F)
        ];

        $lines = explode("\n", $text);
        $resultLines = [];

        foreach ($lines as $line) {
            $wordsInLine = explode(' ', $line);
            $processedWords = [];

            foreach ($wordsInLine as $word) {
                if (empty(trim($word)))
                    continue;

                $cleanWord = $word;
                $foundPunctuationStart = '';
                $foundPunctuationEnd = '';

                // Extract leading punctuation
                $firstChar = mb_substr($cleanWord, 0, 1);
                if (in_array($firstChar, $allPunctuation)) {
                    $foundPunctuationStart = in_array($firstChar, $sindhiPunctuation) ? '' : $firstChar;
                    $cleanWord = mb_substr($cleanWord, 1);
                }

                // Extract trailing punctuation
                if (mb_strlen($cleanWord) > 0) {
                    $lastChar = mb_substr($cleanWord, -1);
                    if (in_array($lastChar, $allPunctuation)) {
                        $foundPunctuationEnd = in_array($lastChar, $sindhiPunctuation) ? '' : $lastChar;
                        $cleanWord = mb_substr($cleanWord, 0, -1);
                    }
                }

                if (empty($cleanWord)) {
                    $combined = $foundPunctuationStart . $foundPunctuationEnd;
                    if (!empty($combined))
                        $processedWords[] = $combined;
                    continue;
                }

                // Logic:
                // 1. Try Exact Match
                if (isset($words[$cleanWord])) {
                    $processedWords[] = $foundPunctuationStart . $words[$cleanWord] . $foundPunctuationEnd;
                    continue;
                }

                // 2. Try Stripping Diacritics (Base Lookup)
                $baseWord = SindhiNormalizer::stripDiacritics($cleanWord);
                $suffix = '';

                // Check for ending diacritic to append vowel
                $lastChar = mb_substr($cleanWord, -1);
                if (isset($diacriticMap[$lastChar])) {
                    $suffix = $diacriticMap[$lastChar];
                }

                if (isset($words[$baseWord])) {
                    $processedWords[] = $foundPunctuationStart . $words[$baseWord] . $suffix . $foundPunctuationEnd;
                } else {
                    // 3. Try Phonetic Normalization on Base
                    $normalizedBase = SindhiNormalizer::normalize($baseWord);
                    if (isset($words[$normalizedBase])) {
                        $processedWords[] = $foundPunctuationStart . $words[$normalizedBase] . $suffix . $foundPunctuationEnd;
                    } else {
                        // Keep original (or stripped version?) - User prefers original if no match?
                        // Actually, if we couldn't match, return original.
                        $processedWords[] = $foundPunctuationStart . $cleanWord . $foundPunctuationEnd;
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
