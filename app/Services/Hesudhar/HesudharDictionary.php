<?php

namespace App\Services\Hesudhar;

use App\Models\BaakhHesudhar;
use App\Support\DictionaryText;
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
     * Search Hesudhar with or without airab. Never returns a stripped form as a
     * "correction" unless it is a real wrong→correct dictionary mapping.
     *
     * @return string|null Corrected form, original word (whitelist), or null if no mapping
     */
    public static function lookup(string $word): ?string
    {
        $word = trim($word);
        if ($word === '') {
            return null;
        }

        $maps = self::maps();

        $hit = self::lookupInMaps($word, $maps, preferOriginal: $word);
        if ($hit !== null) {
            return $hit;
        }

        // 4) Airab-insensitive search — match only; keep vocalized surface when identity/whitelist.
        $stripped = DictionaryText::stripDiacritics($word);
        if ($stripped !== '' && $stripped !== $word) {
            $hit = self::lookupInMaps($stripped, $maps, preferOriginal: $word);
            if ($hit !== null) {
                return $hit;
            }

            foreach (self::variants($stripped) as $variant) {
                if ($variant === $stripped || $variant === $word) {
                    continue;
                }
                $hit = self::lookupInMaps($variant, $maps, preferOriginal: $word);
                if ($hit !== null) {
                    return $hit;
                }
            }
        }

        return null;
    }

    /**
     * @param  array{wrong: array<string,string>, correct: array<string,true>}  $maps
     */
    private static function lookupInMaps(string $key, array $maps, string $preferOriginal): ?string
    {
        if (isset($maps['wrong'][$key])) {
            $correct = $maps['wrong'][$key];
            // Identity mapping (correct indexed as wrong→correct) — keep original airab surface.
            if ($correct === $key) {
                return $preferOriginal;
            }

            return $correct;
        }

        if (isset($maps['correct'][$key])) {
            return $preferOriginal;
        }

        foreach (self::variants($key) as $variant) {
            if ($variant === $key) {
                continue;
            }
            if (isset($maps['wrong'][$variant])) {
                $correct = $maps['wrong'][$variant];
                if ($correct === $variant || $correct === $key) {
                    return $preferOriginal;
                }

                return $correct;
            }
            if (isset($maps['correct'][$variant])) {
                return $preferOriginal;
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
