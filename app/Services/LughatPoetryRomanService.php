<?php

namespace App\Services;

use App\Models\LughatInflection;
use App\Models\LughatLemma;
use App\Models\LughatWordForm;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\Schema;

/**
 * Validate poetry Sindhi tokens against Baakh Lughat and build Roman text
 * from lemma transliterations (single source of truth for new poetry).
 *
 * Airab (zer/zabar/pesh) is part of lemma identity: نَھن ≠ نُھن.
 */
class LughatPoetryRomanService
{
    public const STATUS_OK = 'ok';
    public const STATUS_MISSING_ROMAN = 'missing_roman';
    public const STATUS_MISSING_WORD = 'missing_word';
    public const STATUS_AMBIGUOUS = 'ambiguous';

    /**
     * @return array{words: list<array<string, mixed>>, ready: bool, unresolved_count: int}
     */
    public function check(string $title = '', string $text = ''): array
    {
        $combined = trim($title . "\n\n" . $text);
        $tokens = $this->extractTokens($combined);
        $byIdentity = [];

        foreach ($tokens as $token) {
            $key = $token['identity'];
            if (!isset($byIdentity[$key])) {
                $byIdentity[$key] = $token;
            }
        }

        $words = [];
        $unresolved = 0;

        foreach ($byIdentity as $token) {
            $resolved = $this->resolveLemma($token['surface']);
            $lemma = $resolved['lemma'];
            $roman = $lemma ? trim((string) ($lemma->transliteration ?? '')) : '';

            if (($resolved['matched_via'] ?? null) === 'ambiguous') {
                $status = self::STATUS_AMBIGUOUS;
                $unresolved++;
            } elseif (!$lemma) {
                $status = self::STATUS_MISSING_WORD;
                $unresolved++;
            } elseif ($roman === '') {
                $status = self::STATUS_MISSING_ROMAN;
                $unresolved++;
            } else {
                $status = self::STATUS_OK;
            }

            $words[] = [
                'surface' => $token['surface'],
                'normalized' => $token['identity'],
                'lookup_base' => $token['lookup_base'],
                'status' => $status,
                'lemma_id' => $lemma?->id,
                'lemma' => $lemma?->lemma,
                'transliteration' => $roman !== '' ? $roman : null,
                'candidates' => $resolved['candidates'] ?? [],
            ];
        }

        usort($words, function (array $a, array $b) {
            $rank = [
                self::STATUS_MISSING_WORD => 0,
                self::STATUS_AMBIGUOUS => 1,
                self::STATUS_MISSING_ROMAN => 2,
                self::STATUS_OK => 3,
            ];
            $cmp = ($rank[$a['status']] ?? 9) <=> ($rank[$b['status']] ?? 9);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['surface'], $b['surface']);
        });

        return [
            'words' => $words,
            'ready' => $unresolved === 0 && $words !== [],
            'unresolved_count' => $unresolved,
            'empty' => $words === [],
        ];
    }

    /**
     * @return array{roman_title: string, roman_content: string, ready: bool, check: array}
     */
    public function transliteratePair(string $title, string $text): array
    {
        $check = $this->check($title, $text);

        return [
            'roman_title' => $this->transliterate($title),
            'roman_content' => $this->transliterate($text),
            'ready' => ($check['ready'] ?? false) === true,
            'check' => $check,
        ];
    }

    public function transliterate(string $text): string
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace("/\r\n?/", "\n", $plain) ?? $plain;
        if ($plain === '') {
            return '';
        }

        $parts = preg_split('/(\s+)/u', $plain, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $out = [];

        foreach ($parts as $part) {
            if ($part === '' || preg_match('/^\s+$/u', $part)) {
                $out[] = $part;
                continue;
            }

            $surface = trim(DictionaryText::stripPunctuation($part));
            if ($surface === '' || !$this->isSindhiToken($surface)) {
                $out[] = $part;
                continue;
            }

            $resolved = $this->resolveLemma($surface);
            $roman = $resolved['lemma'] ? trim((string) ($resolved['lemma']->transliteration ?? '')) : '';

            if ($roman === '' || ($resolved['matched_via'] ?? null) === 'ambiguous') {
                $out[] = $part;
                continue;
            }

            $prefix = '';
            $suffix = '';
            $trimmed = $part;
            while (mb_strlen($trimmed) > 0 && $this->isEdgePunctuation(mb_substr($trimmed, 0, 1))) {
                $prefix .= mb_substr($trimmed, 0, 1);
                $trimmed = mb_substr($trimmed, 1);
            }
            while (mb_strlen($trimmed) > 0 && $this->isEdgePunctuation(mb_substr($trimmed, -1))) {
                $suffix = mb_substr($trimmed, -1) . $suffix;
                $trimmed = mb_substr($trimmed, 0, -1);
            }

            $out[] = $prefix . $roman . $suffix;
        }

        return implode('', $out);
    }

    /**
     * @return array{lemma_id: int, lemma: string, created: bool, transliteration: ?string}
     */
    public function resolveOrCreateStub(string $surface, array $metadata = []): array
    {
        $surface = trim(DictionaryText::stripPunctuation($surface));
        $identity = DictionaryText::normalizeForIdentity($surface);
        if ($identity === '') {
            throw new \InvalidArgumentException('Empty surface after normalize.');
        }

        $resolved = $this->resolveLemma($surface);
        // Ambiguous bare forms must not silently attach to one airab variant.
        if ($resolved['lemma'] && ($resolved['matched_via'] ?? null) !== 'ambiguous') {
            $lemma = $resolved['lemma'];

            return [
                'lemma_id' => (int) $lemma->id,
                'lemma' => (string) $lemma->lemma,
                'created' => false,
                'transliteration' => filled($lemma->transliteration) ? trim((string) $lemma->transliteration) : null,
            ];
        }

        // Keep airab on the headword — نَھن and نُھن are different lemmas.
        $attrs = [
            'lemma' => $surface,
            'normalized_lemma' => $identity,
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => null,
            'romanization_status' => 'proposed',
            'status' => 'pending',
            'completion_status' => LughatLemma::COMPLETION_PENDING,
            'metadata_json' => array_merge([
                'dictionary' => 'Baakh Lughat',
                'version' => '2',
                'source' => 'poetry_roman_check',
            ], $metadata),
        ];
        if (Schema::hasColumn('lughat_lemmas', 'lookup_base')) {
            $attrs['lookup_base'] = DictionaryText::lookupBase($surface);
        }
        $lemma = LughatLemma::create($attrs);

        return [
            'lemma_id' => (int) $lemma->id,
            'lemma' => (string) $lemma->lemma,
            'created' => true,
            'transliteration' => null,
        ];
    }

    /**
     * Resolve order:
     * 1) exact lemma / identity key (airab preserved)
     * 2) exact word-form / inflection
     * 3) if query has NO airab: unique lookup_base match only; multiple → ambiguous
     *
     * @return array{lemma: ?LughatLemma, matched_via: ?string, candidates?: list<array{id:int,lemma:string}>}
     */
    public function resolveLemma(string $surface, ?string $normalized = null): array
    {
        if (!Schema::hasTable('lughat_lemmas')) {
            return ['lemma' => null, 'matched_via' => null];
        }

        $surface = trim(DictionaryText::stripPunctuation($surface));
        $identity = DictionaryText::normalizeForIdentity($surface);
        if ($identity === '') {
            return ['lemma' => null, 'matched_via' => null];
        }

        $lemma = LughatLemma::query()
            ->whereRaw(DictionaryText::binaryEquals('lemma'), [$surface])
            ->orderBy('homograph_number')
            ->first();
        if ($lemma) {
            return ['lemma' => $lemma, 'matched_via' => 'lemma_exact'];
        }

        $lemma = LughatLemma::query()
            ->whereRaw(DictionaryText::binaryEquals('normalized_lemma'), [$identity])
            ->orderBy('homograph_number')
            ->first();
        if ($lemma) {
            return ['lemma' => $lemma, 'matched_via' => 'normalized_lemma'];
        }

        if (Schema::hasTable('lughat_word_forms')) {
            $form = LughatWordForm::query()
                ->with('lemma')
                ->where(function ($q) use ($surface, $identity) {
                    $q->whereRaw(DictionaryText::binaryEquals('form'), [$surface])
                        ->orWhereRaw(DictionaryText::binaryEquals('normalized_form'), [$identity]);
                })
                ->first();
            if ($form?->lemma) {
                return ['lemma' => $form->lemma, 'matched_via' => 'word_form'];
            }
        }

        if (Schema::hasTable('lughat_inflections')) {
            $inf = LughatInflection::query()
                ->with('lemma')
                ->where(function ($q) use ($surface, $identity) {
                    $q->whereRaw(DictionaryText::binaryEquals('form'), [$surface])
                        ->orWhereRaw(DictionaryText::binaryEquals('normalized_form'), [$identity]);
                })
                ->first();
            if ($inf?->lemma) {
                return ['lemma' => $inf->lemma, 'matched_via' => 'inflection'];
            }
        }

        // Bare form without airab: allow unique base match; never force one of many.
        if (!DictionaryText::hasDiacritics($surface)) {
            $base = DictionaryText::lookupBase($surface);
            if ($base !== '') {
                $hasLookupBase = Schema::hasColumn('lughat_lemmas', 'lookup_base');
                $matches = LughatLemma::query()
                    ->where(function ($q) use ($base, $hasLookupBase) {
                        if ($hasLookupBase) {
                            $q->where('lookup_base', $base)
                                ->orWhere('normalized_lemma', $base);
                        } else {
                            // Pre-migration fallback: old rows stored stripped key in normalized_lemma.
                            $q->where('normalized_lemma', $base);
                        }
                    })
                    ->orderBy('homograph_number')
                    ->limit(8)
                    ->get(['id', 'lemma', 'transliteration']);

                if ($matches->count() === 1) {
                    return ['lemma' => $matches->first(), 'matched_via' => 'lookup_base_unique'];
                }
                if ($matches->count() > 1) {
                    return [
                        'lemma' => null,
                        'matched_via' => 'ambiguous',
                        'candidates' => $matches->map(fn ($m) => [
                            'id' => (int) $m->id,
                            'lemma' => (string) $m->lemma,
                        ])->all(),
                    ];
                }
            }
        }

        return ['lemma' => null, 'matched_via' => null];
    }

    /**
     * @return list<array{surface: string, identity: string, lookup_base: string}>
     */
    public function extractTokens(string $text): array
    {
        $plain = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];

        foreach ($parts as $raw) {
            $surface = trim(DictionaryText::stripPunctuation($raw));
            if ($surface === '' || !$this->isSindhiToken($surface)) {
                continue;
            }
            $identity = DictionaryText::normalizeForIdentity($surface);
            if ($identity === '') {
                continue;
            }
            $out[] = [
                'surface' => $surface,
                'identity' => $identity,
                'lookup_base' => DictionaryText::lookupBase($surface),
                // Back-compat for callers still reading "normalized".
                'normalized' => $identity,
            ];
        }

        return $out;
    }

    private function isSindhiToken(string $word): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $word);
    }

    private function isEdgePunctuation(string $char): bool
    {
        static $set = null;
        if ($set === null) {
            $set = array_flip(['،', '؛', '؟', '’', '‘', '”', '“', '«', '»', '‹', '›', '?', '!', '.', ',', '"', "'", '(', ')', '[', ']', '{', '}', '-', '_', ':', ';']);
        }

        return isset($set[$char]);
    }
}
