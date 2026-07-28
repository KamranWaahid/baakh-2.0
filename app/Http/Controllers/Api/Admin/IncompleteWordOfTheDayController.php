<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\IncompleteWordOfTheDayService;
use Illuminate\Http\Request;

class IncompleteWordOfTheDayController extends Controller
{
    public function show(IncompleteWordOfTheDayService $service)
    {
        return response()->json(
            $service->getTodayPayload(auth()->id())
        );
    }

    public function skip(IncompleteWordOfTheDayService $service)
    {
        return response()->json(
            $service->skipToday(auth()->id())
        );
    }

    public function save(Request $request, IncompleteWordOfTheDayService $service)
    {
        $validated = $request->validate([
            'lemma_id' => 'nullable|integer|exists:lemmas,id',
            'lemma' => 'nullable|string|max:191',
            'normalized_lemma' => 'nullable|string|max:191',
            'pos' => 'nullable|string|max:100',
            'transliteration' => 'nullable|string|max:191',
            'ipa' => 'nullable|string|max:191',
            'phonetic' => 'nullable|string|max:191',
            'pronunciation_simple' => 'nullable|string|max:191',
            'audio_url' => 'nullable|url|max:500',
            'syllabification' => 'nullable|string|max:191',
            'etymology' => 'nullable|string',
            'notes' => 'nullable|string',
            'completion_notes' => 'nullable|string',
            'variants_reviewed' => 'nullable|boolean',
            'examples_reviewed' => 'nullable|boolean',
            'morphology_reviewed' => 'nullable|boolean',
            'pronunciation_reviewed' => 'nullable|boolean',

            'sense_id' => 'nullable|integer',
            'definition' => 'nullable|string',
            'definition_en' => 'nullable|string',
            'definition_sd' => 'nullable|string',
            'short_gloss' => 'nullable|string|max:255',
            'full_definition' => 'nullable|string',
            'language_direction' => 'nullable|string|max:100',
            'source' => 'nullable|string|max:150',
            'source_dictionary' => 'nullable|string|max:150',
            'sense_pos' => 'nullable|string|max:100',
            'review_status' => 'nullable|in:unreviewed,reviewed,curated,needs_work',
            'sense_status' => 'nullable|in:pending,approved',

            'example_sentence' => 'nullable|string',
            'example_translation' => 'nullable|string',

            'synonyms' => 'nullable',
            'antonyms' => 'nullable',
        ]);

        $payload = $service->save($validated, auth()->id());

        return response()->json($payload);
    }
}
