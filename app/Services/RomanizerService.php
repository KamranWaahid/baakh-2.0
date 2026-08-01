<?php

namespace App\Services;

use App\Helpers\SindhiNormalizer;
use App\Models\Romanizer;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\File;

/**
 * Sindhi ↔ Roman dictionary lookups and line transliteration.
 */
class RomanizerService
{
    /** @var array<string, string>|null */
    private static ?array $map = null;

    public function forget(): void
    {
        self::$map = null;
    }

    /**
     * @return array<string, string> word_sd => word_roman
     */
    public function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        self::$map = Romanizer::query()
            ->pluck('word_roman', 'word_sd')
            ->all();

        return self::$map;
    }

    public function upsert(string $wordSd, string $wordRoman, ?int $userId = null): Romanizer
    {
        $wordSd = trim(strip_tags($wordSd));
        $wordRoman = trim(strip_tags($wordRoman));

        $row = Romanizer::updateOrCreate(
            ['word_sd' => $wordSd],
            [
                'word_roman' => $wordRoman,
                'user_id' => $userId ?? auth()->id() ?? 1,
            ]
        );

        $this->forget();

        return $row;
    }

    public function refreshDictionaryFile(): bool
    {
        $filePath = public_path('vendor/roman-converter/all_words.dic');
        $content = '';

        foreach (Romanizer::query()->orderBy('id')->cursor() as $row) {
            $content .= $row->word_sd . ':' . $row->word_roman . PHP_EOL;
        }

        if (!File::exists(public_path('vendor/roman-converter'))) {
            File::makeDirectory(public_path('vendor/roman-converter'), 0755, true);
        }

        File::put($filePath, $content);

        return true;
    }

    /**
     * Punctuation trimmed from token edges during bulk-check / transliterate.
     *
     * @return list<string>
     */
    private function edgePunctuation(): array
    {
        return ['،', '؛', '؟', '’', '‘', '”', '“', '?', '!', '.', ',', '"', "'", '(', ')', '[', ']', '{', '}', '-', '_', ':', ';', "\xD8\x9B"];
    }

    /**
     * Trim edge punctuation only — keep zabar/zer/pesh (َ ِ ُ) on the surface.
     */
    public function cleanSurfaceToken(string $word): string
    {
        $cleanWord = trim($word);
        $punctuation = $this->edgePunctuation();

        while (mb_strlen($cleanWord) > 0 && in_array(mb_substr($cleanWord, 0, 1), $punctuation, true)) {
            $cleanWord = mb_substr($cleanWord, 1);
        }
        while (mb_strlen($cleanWord) > 0 && in_array(mb_substr($cleanWord, -1), $punctuation, true)) {
            $cleanWord = mb_substr($cleanWord, 0, -1);
        }

        return $cleanWord;
    }

    /**
     * True when token is Sindhi/Arabic script (ignore Latin, digits, symbols).
     */
    public function isSindhiToken(string $word): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $word);
    }

    /**
     * Whether transliterate() can resolve this Sindhi surface via the dictionary.
     * Exact form, diacritic-stripped base, or Hesudhar-normalized base all count.
     */
    public function canRomanize(string $surface): bool
    {
        $surface = $this->cleanSurfaceToken($surface);
        if ($surface === '' || !$this->isSindhiToken($surface)) {
            return false;
        }

        $words = $this->map();

        if (isset($words[$surface])) {
            return true;
        }

        // DB exact (BINARY) — airab forms are distinct dictionary keys.
        if (Romanizer::whereRaw('BINARY word_sd = ?', [$surface])->exists()) {
            return true;
        }

        // Bare undiacritized surface may use base fallback; marked airab must match exact.
        if (preg_match('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', $surface)) {
            return false;
        }

        $baseWord = SindhiNormalizer::stripDiacritics($surface);
        if ($baseWord !== '' && isset($words[$baseWord])) {
            return true;
        }

        $normalizedBase = SindhiNormalizer::normalize($baseWord !== '' ? $baseWord : $surface);
        if ($normalizedBase !== '' && isset($words[$normalizedBase])) {
            return true;
        }

        if ($baseWord !== '' && $baseWord !== $surface && Romanizer::whereRaw('BINARY word_sd = ?', [$baseWord])->exists()) {
            return true;
        }
        if ($normalizedBase !== '' && $normalizedBase !== $surface && $normalizedBase !== $baseWord
            && Romanizer::whereRaw('BINARY word_sd = ?', [$normalizedBase])->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Unique Sindhi surfaces from text that the romanizer cannot resolve.
     * Preserves airab on returned forms; skips Latin/digits/punctuation-only tokens.
     *
     * @return list<string>
     */
    public function findMissingWords(string $text): array
    {
        $tokens = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $missing = [];
        $seen = [];

        foreach ($tokens as $token) {
            $surface = $this->cleanSurfaceToken($token);
            if ($surface === '' || !$this->isSindhiToken($surface)) {
                continue;
            }
            if (isset($seen[$surface])) {
                continue;
            }
            $seen[$surface] = true;

            if (!$this->canRomanize($surface)) {
                $missing[] = $surface;
            }
        }

        return array_values($missing);
    }

    /**
     * Transliterate multi-line Sindhi text using the Romanizer dictionary.
     */
    public function transliterate(string $text): string
    {
        $words = $this->map();

        $allPunctuation = [
            '،', '؛', '؟', '۔',
            '’', '‘', '”', '“', '«', '»', '‹', '›',
            '?', '!', '.', ',', '"', "'",
            '(', ')', '[', ']', '{', '}', '-', '_', ':', ';',
        ];

        $diacriticMap = [
            "\u{064E}" => 'a',
            "\u{0650}" => 'i',
            "\u{064F}" => 'u',
        ];

        $lines = explode("\n", $text);
        $resultLines = [];

        foreach ($lines as $line) {
            $wordsInLine = preg_split('/\s+/u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $processedWords = [];

            foreach ($wordsInLine as $word) {
                $cleanWord = $word;
                $foundPunctuationStart = '';
                $foundPunctuationEnd = '';

                $firstChar = mb_substr($cleanWord, 0, 1);
                if (in_array($firstChar, $allPunctuation, true)) {
                    $foundPunctuationStart = DictionaryText::romanizePunctuation($firstChar);
                    $cleanWord = mb_substr($cleanWord, 1);
                }

                if (mb_strlen($cleanWord) > 0) {
                    $lastChar = mb_substr($cleanWord, -1);
                    if (in_array($lastChar, $allPunctuation, true)) {
                        $foundPunctuationEnd = DictionaryText::romanizePunctuation($lastChar);
                        $cleanWord = mb_substr($cleanWord, 0, -1);
                    }
                }

                if ($cleanWord === '') {
                    $combined = $foundPunctuationStart . $foundPunctuationEnd;
                    if ($combined !== '') {
                        $processedWords[] = $combined;
                    }
                    continue;
                }

                if (isset($words[$cleanWord])) {
                    $processedWords[] = $foundPunctuationStart . $words[$cleanWord] . $foundPunctuationEnd;
                    continue;
                }

                // Marked airab forms must match exactly — do not fall back to bare base.
                $hasAirab = (bool) preg_match('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', $cleanWord);
                if ($hasAirab) {
                    $processedWords[] = $foundPunctuationStart . $cleanWord . $foundPunctuationEnd;
                    continue;
                }

                $baseWord = SindhiNormalizer::stripDiacritics($cleanWord);
                $suffix = '';
                $lastChar = mb_substr($cleanWord, -1);
                if (isset($diacriticMap[$lastChar])) {
                    $suffix = $diacriticMap[$lastChar];
                }

                if (isset($words[$baseWord])) {
                    $processedWords[] = $foundPunctuationStart . $words[$baseWord] . $suffix . $foundPunctuationEnd;
                    continue;
                }

                $normalizedBase = SindhiNormalizer::normalize($baseWord);
                if (isset($words[$normalizedBase])) {
                    $processedWords[] = $foundPunctuationStart . $words[$normalizedBase] . $suffix . $foundPunctuationEnd;
                    continue;
                }

                $processedWords[] = $foundPunctuationStart . $cleanWord . $foundPunctuationEnd;
            }

            $resultLines[] = implode(' ', $processedWords);
        }

        return DictionaryText::romanizePunctuation(implode("\n", $resultLines));
    }

    /**
     * Plain-text couplet body for roman lines (HTML stripped, entities decoded).
     */
    public function plainCoupletText(string $htmlOrText): string
    {
        $text = html_entity_decode(strip_tags($htmlOrText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;

        return trim($text);
    }
}
