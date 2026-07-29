<?php

namespace App\Services\Hesudhar;

use App\Models\BaakhHesudhar;
use Illuminate\Support\Facades\Cache;

/**
 * Hesudhar WordNet — wrong→correct dictionary lookup.
 *
 * Correction priority (per HESUDHAR_ALGORITHM_SPEC.md):
 * 1. Exact / variant match on `word`  → return `correct`
 * 2. Token already equals a known `correct` → return token (whitelist, skip algorithm)
 * 3. Miss → null (caller may run phonetic algorithm)
 */
class HesudharDictionary
{
    private const CACHE_KEY = 'hesudhar.dictionary.maps.v1';
    private const CACHE_TTL_SECONDS = 3600;

    /** @var array{wrong: array<string,string>, correct: array<string,true>}|null */
    private static ?array $maps = null;

    /**
     * Lookup callback for HesudharPipeline.
     */
    public static function callback(): \Closure
    {
        return static fn (string $word): ?string => self::lookup($word);
    }

    /**
     * @return string|null Corrected form, or null if dictionary has no mapping
     */
    public static function lookup(string $word): ?string
    {
        $word = trim($word);
        if ($word === '') {
            return null;
        }

        $maps = self::maps();

        // 1) Exact wrong→correct
        if (isset($maps['wrong'][$word])) {
            return $maps['wrong'][$word];
        }

        // 2) Already a known correct form — protect from algorithm rewrites
        if (isset($maps['correct'][$word])) {
            return $word;
        }

        // 3) Encoding variants (Heh / Yeh / Kaf families)
        foreach (self::variants($word) as $variant) {
            if ($variant === $word) {
                continue;
            }
            if (isset($maps['wrong'][$variant])) {
                return $maps['wrong'][$variant];
            }
            if (isset($maps['correct'][$variant])) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Prefetch maps into process memory (optional; lookup loads lazily).
     */
    public static function warm(): void
    {
        self::maps();
    }

    /**
     * Clear in-memory + cache after dictionary CRUD / cleanse / refresh.
     */
    public static function forget(): void
    {
        self::$maps = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Pipeline wired with this dictionary.
     */
    public static function pipeline(): HesudharPipeline
    {
        return new HesudharPipeline(self::callback());
    }

    /**
     * @return array{wrong: array<string,string>, correct: array<string,true>}
     */
    private static function maps(): array
    {
        if (self::$maps !== null) {
            return self::$maps;
        }

        self::$maps = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $wrong = [];
            $correct = [];

            BaakhHesudhar::query()
                ->orderBy('id')
                ->chunkById(2000, function ($rows) use (&$wrong, &$correct) {
                    foreach ($rows as $row) {
                        $w = trim((string) $row->word);
                        $c = trim((string) $row->correct);
                        if ($w === '' || $c === '') {
                            continue;
                        }
                        $wrong[$w] = $c;
                        $correct[$c] = true;
                        // Also index the correct form as identity so whitelist hits are fast
                        if (!isset($wrong[$c])) {
                            $wrong[$c] = $c;
                        }
                    }
                }, 'id');

            return ['wrong' => $wrong, 'correct' => $correct];
        });

        return self::$maps;
    }

    /**
     * Visual / encoding variants for dictionary matching.
     *
     * @return list<string>
     */
    public static function variants(string $word): array
    {
        $hehChars = ['ه', 'ہ', 'ھ', 'ە', 'ة'];
        $out = [$word];

        foreach (['ه', 'ہ', 'ھ'] as $replacement) {
            $out[] = str_replace($hehChars, $replacement, $word);
        }

        $out[] = str_replace('ی', 'ي', $word);
        $out[] = str_replace('ي', 'ی', $word);
        $out[] = str_replace('ك', 'ڪ', $word);
        $out[] = str_replace('ڪ', 'ك', $word);

        // Combined Yeh+Kaf on Heh-normalized bases
        foreach (array_slice($out, 0, 4) as $base) {
            $out[] = str_replace(['ی', 'ك'], ['ي', 'ڪ'], $base);
        }

        return array_values(array_unique(array_filter($out, fn ($v) => $v !== '')));
    }
}
