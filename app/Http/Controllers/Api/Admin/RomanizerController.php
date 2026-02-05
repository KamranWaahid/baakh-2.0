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

        $get_text = explode(' ', $request->text);
        $text = array_unique($get_text);
        $missing = array();

        foreach ($text as $word) {
            $cleanWord = str_replace(['،', '’', '‘', '”', '“', '?', '!', '؛', '.', '؟', ' '], '', $word);
            if (!empty($cleanWord) && !Romanizer::where('word_sd', $cleanWord)->exists()) {
                $missing[] = $cleanWord;
            }
        }

        return response()->json([
            'missing_words' => $missing,
            'total_missing' => count($missing)
        ]);
    }
}
