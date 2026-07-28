<?php

namespace App\Services;

use App\Support\DictionaryText;
use PDO;
use Throwable;

/**
 * Read-only lookup against the bundled Sindhi Open Lexicon SQLite file.
 * Used when the app lemmas/senses tables are empty or missing a headword.
 */
class BundledOpenLexiconLookup
{
    private const SQLITE_RELATIVE = 'database/sindhi_open_lexicon_master_223k_final/sqlite/sindhi_open_lexicon_master_223342.sqlite';

    public function lookup(string $word): ?array
    {
        $word = trim($word);
        if ($word === '') {
            return null;
        }

        $path = base_path(self::SQLITE_RELATIVE);
        if (! is_file($path)) {
            return null;
        }

        try {
            $pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $normalized = DictionaryText::normalizeForLookup($word);
            $rows = $this->fetchRows($pdo, $word, $normalized);
            if ($rows === []) {
                return null;
            }

            // Prefer authentic Sindhi headword entries with Arabic-script definitions.
            usort($rows, fn (array $a, array $b) => $this->entryRank($a) <=> $this->entryRank($b));

            $headword = $rows[0]['word'] ?? $word;
            $pos = $this->firstFilled(array_column($rows, 'part_of_speech'));

            $senses = [];
            $meanings = [];
            $meaningsEn = [];
            $meaningsSd = [];

            foreach ($rows as $index => $row) {
                $definition = trim((string) ($row['definition'] ?? ''));
                if ($definition === '') {
                    continue;
                }

                $direction = strtolower((string) ($row['language_direction'] ?? ''));
                $isLatinDef = $this->isMostlyLatin($definition);
                $isEnglishDef = $isLatinDef
                    || (str_contains($direction, 'english') && ! str_starts_with($direction, 'sindhi'));

                $senses[] = [
                    'id' => null,
                    'public_id' => $row['lexical_id'] ?? null,
                    'lexical_id' => $row['lexical_id'] ?? null,
                    'sense_order' => $index + 1,
                    'part_of_speech' => $row['part_of_speech'] ?: null,
                    'short_gloss' => null,
                    'definition' => $definition,
                    'definition_en' => $isEnglishDef ? $definition : null,
                    'definition_sd' => $isEnglishDef ? null : $definition,
                    'full_definition' => $definition,
                    'usage_notes' => null,
                    'register' => null,
                    'dialect' => null,
                    'domain' => $row['domain'] ?: null,
                    'language_direction' => $row['language_direction'] ?: null,
                    'source' => $row['source_dictionary'] ?: null,
                    'source_dictionary' => $row['source_dictionary'] ?: null,
                    'source_entry_id' => isset($row['entry_id']) ? (string) $row['entry_id'] : null,
                    'publisher' => $row['publisher'] ?: null,
                    'license' => null,
                    'examples' => [],
                ];

                $meanings[] = $definition;
                if ($isEnglishDef) {
                    $meaningsEn[] = $definition;
                } else {
                    $meaningsSd[] = $definition;
                }
            }

            if ($senses === []) {
                return null;
            }

            return [
                'found' => true,
                'id' => null,
                'public_id' => $rows[0]['lexical_id'] ?? null,
                'word' => $headword,
                'normalized' => $normalized,
                'romanized' => null,
                'pronunciation' => [
                    'ipa' => null,
                    'phonetic' => null,
                    'simple' => null,
                    'audio_url' => null,
                    'syllabification' => null,
                ],
                'pos' => $pos,
                'completion_status' => null,
                'gender' => null,
                'number' => null,
                'tense' => null,
                'morphology' => null,
                'variants' => array_values(array_filter(array_map(function (array $row) use ($headword) {
                    $variant = trim((string) ($row['word_with_airab_or_variant'] ?? ''));
                    if ($variant === '' || $variant === $headword) {
                        return null;
                    }

                    return [
                        'id' => null,
                        'public_id' => null,
                        'variant' => $variant,
                        'form' => $variant,
                        'type' => 'airab',
                        'romanization' => null,
                        'note' => null,
                        'dialect' => null,
                    ];
                }, $rows))),
                'senses' => $senses,
                'meanings' => array_values(array_unique($meanings)),
                'meanings_en' => array_values(array_unique($meaningsEn)),
                'meanings_sd' => array_values(array_unique($meaningsSd)),
                'synonyms' => [],
                'antonyms' => [],
                'hypernyms' => [],
                'source' => 'bundled_open_lexicon',
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function fetchRows(PDO $pdo, string $word, string $normalized): array
    {
        $stmt = $pdo->prepare(
            'SELECT lexical_id, entry_id, word, word_with_airab_or_variant, part_of_speech, domain,
                    definition, language_direction, source_dictionary, normalized_word, publisher
             FROM lexicon_entries
             WHERE word = :word
                OR normalized_word = :word
                OR word = :normalized
                OR normalized_word = :normalized
             LIMIT 40'
        );
        $stmt->execute([
            ':word' => $word,
            ':normalized' => $normalized,
        ]);

        $rows = $stmt->fetchAll() ?: [];
        if ($rows !== []) {
            return $rows;
        }

        // Diacritic / airab forms stored only in the variant column (exact-ish).
        $stmt = $pdo->prepare(
            'SELECT lexical_id, entry_id, word, word_with_airab_or_variant, part_of_speech, domain,
                    definition, language_direction, source_dictionary, normalized_word, publisher
             FROM lexicon_entries
             WHERE word_with_airab_or_variant = :word
                OR word_with_airab_or_variant = :normalized
                OR normalized_word = :normalized
             LIMIT 20'
        );
        $stmt->execute([
            ':word' => $word,
            ':normalized' => $normalized,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Lower rank = better. Prefer Sindhi Arabic-script definitions over Latin glosses.
     */
    private function entryRank(array $row): int
    {
        $score = $this->directionRank($row['language_direction'] ?? null) * 100;
        $definition = (string) ($row['definition'] ?? '');
        $source = (string) ($row['source_dictionary'] ?? '');

        if ($this->isMostlyLatin($definition)) {
            $score += 40;
        }
        if (str_contains($source, 'جامع')) {
            $score -= 15;
        }
        if (str_contains($source, 'Mewaram') || str_contains($source, 'Devanagari')) {
            $score += 10;
        }

        return $score;
    }

    private function directionRank(?string $direction): int
    {
        $direction = strtolower(trim((string) $direction));

        return match (true) {
            $direction === 'sindhi' => 0,
            str_starts_with($direction, 'sindhi') => 1,
            $direction === 'english' => 3,
            default => 2,
        };
    }

    private function isMostlyLatin(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        // Treat as Latin/English if it has Latin letters and no Arabic-script letters.
        $hasLatin = (bool) preg_match('/\p{Latin}/u', $text);
        $hasArabic = (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}]/u', $text);

        return $hasLatin && ! $hasArabic;
    }

    private function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
