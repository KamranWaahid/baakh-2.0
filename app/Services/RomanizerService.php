<?php

namespace App\Services;

use App\Helpers\SindhiNormalizer;
use App\Models\Romanizer;
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
     * Transliterate multi-line Sindhi text using the Romanizer dictionary.
     */
    public function transliterate(string $text): string
    {
        $words = $this->map();

        $sindhiPunctuation = ['،', '؛', '؟', "\xD8\x9B", ':', ';', '{', '}', '[', ']', '(', ')'];
        $romanPunctuation = ['.', '!', '?', ',', '"', "'", '"', '"'];
        $allPunctuation = array_merge($sindhiPunctuation, $romanPunctuation);

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
                    $foundPunctuationStart = in_array($firstChar, $sindhiPunctuation, true) ? '' : $firstChar;
                    $cleanWord = mb_substr($cleanWord, 1);
                }

                if (mb_strlen($cleanWord) > 0) {
                    $lastChar = mb_substr($cleanWord, -1);
                    if (in_array($lastChar, $allPunctuation, true)) {
                        $foundPunctuationEnd = in_array($lastChar, $sindhiPunctuation, true) ? '' : $lastChar;
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

        return implode("\n", $resultLines);
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
