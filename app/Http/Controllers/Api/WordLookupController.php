<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lemma;
use Illuminate\Http\Request;

class WordLookupController extends Controller
{
    /**
     * Look up a single word in the dictionary.
     * Tries exact match first, then strips Arabic diacritics to find a match.
     */
    public function lookup(string $word)
    {
        // 1. Exact match
        $lemma = Lemma::where('lemma', $word)
            ->with(['morphology', 'senses', 'lemmaRelations'])
            ->first();

        // 2. Stripped-diacritics fallback
        if (!$lemma) {
            $stripped = $this->stripDiacritics($word);
            $lemma = Lemma::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(lemma, 'َ',''), 'ُ',''), 'ِ',''), 'ّ',''), 'ً',''), 'ٌ',''), 'ٍ',''), 'ْ','') = ?", [$stripped])
                ->with(['morphology', 'senses', 'lemmaRelations'])
                ->first();
        }

        if (!$lemma) {
            return response()->json(['found' => false], 200);
        }

        // Build response
        $synonyms = $lemma->lemmaRelations
            ->where('relation_type', 'synonym')
            ->pluck('related_word')
            ->values();

        $antonyms = $lemma->lemmaRelations
            ->where('relation_type', 'antonym')
            ->pluck('related_word')
            ->values();

        $hypernyms = $lemma->lemmaRelations
            ->where('relation_type', 'hypernym')
            ->pluck('related_word')
            ->values();

        $meanings = $lemma->senses->pluck('definition')->filter()->values();
        $meanings_en = $lemma->senses->pluck('definition_en')->filter()->values();
        $meanings_sd = $lemma->senses->pluck('definition_sd')->filter()->values();

        return response()->json([
            'found' => true,
            'word' => $lemma->lemma,
            'romanized' => $lemma->transliteration ?? \App\Models\Romanizer::where('word_sd', $lemma->lemma)->value('word_roman'),
            'pos' => $lemma->pos,
            'gender' => $lemma->morphology?->gender,
            'number' => $lemma->morphology?->number,
            'tense' => $lemma->morphology?->tense,
            'meanings' => $meanings,
            'meanings_en' => $meanings_en,
            'meanings_sd' => $meanings_sd,
            'synonyms' => $synonyms,
            'antonyms' => $antonyms,
            'hypernyms' => $hypernyms,
        ]);
    }

    private function stripDiacritics(string $text): string
    {
        // Remove common Arabic/Sindhi diacritical marks
        return preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
    }
}
