<?php

namespace App\Support;

/**
 * Sindhi alphabet order used for dictionary / catalog letter browsing (ا ب ت …).
 */
class SindhiAlphabet
{
    /**
     * Head letters for filter chips. Digraphs (جهہ, گهہ) are listed after their base letter.
     *
     * @return list<string>
     */
    public static function letters(): array
    {
        return [
            'ا', 'ب', 'ٻ', 'ڀ', 'ت', 'ٿ', 'ٽ', 'ٺ', 'ث', 'پ',
            'ج', 'ڄ', 'جهہ', 'ڃ', 'چ', 'ڇ', 'ح', 'خ',
            'د', 'ڌ', 'ڏ', 'ڊ', 'ڍ', 'ذ', 'ر', 'ڙ', 'ز',
            'س', 'ش', 'ص', 'ض', 'ط', 'ظ', 'ع', 'غ',
            'ف', 'ڦ', 'ق', 'ڪ', 'ک', 'گ', 'ڳ', 'گهہ', 'ڱ',
            'ل', 'م', 'ن', 'ڻ', 'و', 'ھ', 'ء', 'ي',
        ];
    }

    public static function isValidLetter(?string $letter): bool
    {
        if ($letter === null || $letter === '') {
            return false;
        }

        return in_array($letter, self::letters(), true);
    }

    /**
     * Prefixes that should match for a selected alphabet chip.
     * ا also covers آ / أ / إ; digraph chips accept both جهہ and جه forms.
     *
     * @return list<string>
     */
    public static function matchPrefixes(string $letter): array
    {
        return match ($letter) {
            'ا' => ['ا', 'آ', 'أ', 'إ'],
            'جهہ' => ['جهہ', 'جه'],
            'گهہ' => ['گهہ', 'گه'],
            'ھ' => ['ھ', 'ه', 'ھہ'],
            default => [$letter],
        };
    }

    /**
     * Digraph prefixes that belong to a more specific letter chip (exclude when filtering the base letter).
     *
     * @return list<string>
     */
    public static function excludedDigraphPrefixes(string $letter): array
    {
        return match ($letter) {
            'ج' => ['جهہ', 'جه'],
            'گ' => ['گهہ', 'گه'],
            default => [],
        };
    }

    /**
     * Apply Sindhi letter starts-with filter on a lemma column.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>|\Illuminate\Database\Query\Builder  $query
     */
    public static function applyStartsWith($query, string $letter, string $column = 'lemma'): void
    {
        if (!self::isValidLetter($letter)) {
            return;
        }

        $prefixes = self::matchPrefixes($letter);
        $excluded = self::excludedDigraphPrefixes($letter);

        $query->where(function ($q) use ($column, $prefixes) {
            foreach ($prefixes as $i => $prefix) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $q->{$method}($column, 'like', $prefix . '%');
            }
        });

        foreach ($excluded as $digraph) {
            $query->where($column, 'not like', $digraph . '%');
        }
    }
}
