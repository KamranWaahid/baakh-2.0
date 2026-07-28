<?php

namespace App\Helpers;

/**
 * Sindhi/Arabic text shaper for GD (no OpenType).
 * Converts letters to Arabic Presentation Forms and reverses for LTR canvas drawing.
 *
 * IMPORTANT: use PHP \u{XXXX} escapes (not \x{XXXX}, which is a literal string).
 */
class SindhiShaper
{
    /** @var array<string, list<string>> [isolated, final, medial, initial] */
    private static array $mapping = [
        "\u{0627}" => ["\u{0627}", "\u{FE8E}", "\u{0627}", "\u{0627}"], // Alef
        "\u{0622}" => ["\u{0622}", "\u{FE82}", "\u{0622}", "\u{0622}"], // Alef Madda
        "\u{0623}" => ["\u{0623}", "\u{FE84}", "\u{0623}", "\u{0623}"], // Alef Hamza above
        "\u{0625}" => ["\u{0625}", "\u{FE88}", "\u{0625}", "\u{0625}"], // Alef Hamza below
        "\u{0621}" => ["\u{0621}", "\u{0621}", "\u{0621}", "\u{0621}"], // Hamza
        "\u{0628}" => ["\u{0628}", "\u{FE90}", "\u{FE92}", "\u{FE91}"], // Be
        "\u{067B}" => ["\u{067B}", "\u{FB53}", "\u{FB55}", "\u{FB54}"], // ٻ
        "\u{0680}" => ["\u{0680}", "\u{FB5B}", "\u{FB5D}", "\u{FB5C}"], // ڀ
        "\u{067E}" => ["\u{067E}", "\u{FB57}", "\u{FB59}", "\u{FB58}"], // پ
        "\u{062A}" => ["\u{062A}", "\u{FE96}", "\u{FE98}", "\u{FE97}"], // ت
        "\u{067A}" => ["\u{067A}", "\u{FB5F}", "\u{FB61}", "\u{FB60}"], // ٺ
        "\u{067D}" => ["\u{067D}", "\u{FB63}", "\u{FB65}", "\u{FB64}"], // ٽ
        "\u{067F}" => ["\u{067F}", "\u{067F}", "\u{067F}", "\u{067F}"], // ٿ
        "\u{062B}" => ["\u{062B}", "\u{FE9A}", "\u{FE9C}", "\u{FE9B}"], // ث
        "\u{062C}" => ["\u{062C}", "\u{FE9E}", "\u{FEA0}", "\u{FE9F}"], // ج
        "\u{0684}" => ["\u{0684}", "\u{FB73}", "\u{FB75}", "\u{FB74}"], // ڄ
        "\u{0683}" => ["\u{0683}", "\u{FB6F}", "\u{FB71}", "\u{FB70}"], // ڃ
        "\u{0686}" => ["\u{0686}", "\u{FB7B}", "\u{FB7D}", "\u{FB7C}"], // چ
        "\u{0687}" => ["\u{0687}", "\u{FB7F}", "\u{FB81}", "\u{FB80}"], // ڇ
        "\u{062D}" => ["\u{062D}", "\u{FEA2}", "\u{FEA4}", "\u{FEA3}"], // ح
        "\u{062E}" => ["\u{062E}", "\u{FEA6}", "\u{FEA8}", "\u{FEA7}"], // خ
        "\u{062F}" => ["\u{062F}", "\u{FEAA}", "\u{062F}", "\u{062F}"], // د
        "\u{068A}" => ["\u{068A}", "\u{FB83}", "\u{068A}", "\u{068A}"], // ڊ
        "\u{0688}" => ["\u{0688}", "\u{FB8B}", "\u{0688}", "\u{0688}"], // ڈ
        "\u{068C}" => ["\u{068C}", "\u{FB87}", "\u{068C}", "\u{068C}"], // ڌ
        "\u{068D}" => ["\u{068D}", "\u{FB89}", "\u{068D}", "\u{068D}"], // ڏ
        "\u{0630}" => ["\u{0630}", "\u{FEAC}", "\u{0630}", "\u{0630}"], // ذ
        "\u{0631}" => ["\u{0631}", "\u{FEAE}", "\u{0631}", "\u{0631}"], // ر
        "\u{0691}" => ["\u{0691}", "\u{FB8D}", "\u{0691}", "\u{0691}"], // ڑ
        "\u{0632}" => ["\u{0632}", "\u{FEB0}", "\u{0632}", "\u{0632}"], // ز
        "\u{0633}" => ["\u{0633}", "\u{FEB2}", "\u{FEB4}", "\u{FEB3}"], // س
        "\u{0634}" => ["\u{0634}", "\u{FEB6}", "\u{FEB8}", "\u{FEB7}"], // ش
        "\u{0635}" => ["\u{0635}", "\u{FEBA}", "\u{FEBC}", "\u{FEBB}"], // ص
        "\u{0636}" => ["\u{0636}", "\u{FEBE}", "\u{FEC0}", "\u{FEBF}"], // ض
        "\u{0637}" => ["\u{0637}", "\u{FEC2}", "\u{FEC4}", "\u{FEC3}"], // ط
        "\u{0638}" => ["\u{0638}", "\u{FEC6}", "\u{FEC8}", "\u{FEC7}"], // ظ
        "\u{0639}" => ["\u{0639}", "\u{FECA}", "\u{FECC}", "\u{FECB}"], // ع
        "\u{063A}" => ["\u{063A}", "\u{FECE}", "\u{FED0}", "\u{FECF}"], // غ
        "\u{0641}" => ["\u{0641}", "\u{FED2}", "\u{FED4}", "\u{FED3}"], // ف
        "\u{0642}" => ["\u{0642}", "\u{FED6}", "\u{FED8}", "\u{FED7}"], // ق
        "\u{06A9}" => ["\u{06A9}", "\u{FB8F}", "\u{FB91}", "\u{FB90}"], // ک
        "\u{06AA}" => ["\u{06A9}", "\u{FB8F}", "\u{FB91}", "\u{FB90}"], // ڪ → use ک forms for joining
        "\u{06AF}" => ["\u{06AF}", "\u{FB93}", "\u{FB95}", "\u{FB94}"], // گ
        "\u{06B1}" => ["\u{06B1}", "\u{FB97}", "\u{FB99}", "\u{FB98}"], // ڱ
        "\u{06B3}" => ["\u{06B3}", "\u{FB9B}", "\u{FB9D}", "\u{FB9C}"], // ڳ
        "\u{0644}" => ["\u{0644}", "\u{FEDE}", "\u{FEE0}", "\u{FEDF}"], // ل
        "\u{0645}" => ["\u{0645}", "\u{FEE2}", "\u{FEE4}", "\u{FEE3}"], // م
        "\u{0646}" => ["\u{0646}", "\u{FEE6}", "\u{FEE8}", "\u{FEE7}"], // ن
        "\u{06BB}" => ["\u{06BB}", "\u{FBA1}", "\u{FBA3}", "\u{FBA2}"], // ڻ
        "\u{0648}" => ["\u{0648}", "\u{FEEE}", "\u{0648}", "\u{0648}"], // و
        "\u{0647}" => ["\u{0647}", "\u{FEEA}", "\u{FEEC}", "\u{FEEB}"], // ه
        "\u{06BE}" => ["\u{06BE}", "\u{FBAB}", "\u{FBAD}", "\u{FBAC}"], // ھ
        "\u{06C1}" => ["\u{06C1}", "\u{FBA7}", "\u{FBA9}", "\u{FBA8}"], // ہ
        "\u{064A}" => ["\u{064A}", "\u{FEF2}", "\u{FEF4}", "\u{FEF3}"], // ي
        "\u{06CC}" => ["\u{06CC}", "\u{FBFD}", "\u{FBFF}", "\u{FBFE}"], // ی
        "\u{06D2}" => ["\u{06D2}", "\u{FBAF}", "\u{06D2}", "\u{06D2}"], // ے
    ];

    /** Letters that do not connect to the following letter */
    private static array $nonJoining = [
        "\u{0627}", "\u{0622}", "\u{0623}", "\u{0625}", "\u{0621}",
        "\u{062F}", "\u{068A}", "\u{0688}", "\u{068C}", "\u{068D}",
        "\u{0630}", "\u{0631}", "\u{0691}", "\u{0632}", "\u{0648}",
        "\u{06D2}",
    ];

    public static function shape(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $words = explode(' ', $text);
        $shapedWords = [];

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            if (self::isRtl($word)) {
                $shapedWords[] = self::shapeWord($word);
            } else {
                $shapedWords[] = $word;
            }
        }

        // Reverse word order for LTR GD canvas
        return implode(' ', array_reverse($shapedWords));
    }

    /**
     * Remove Arabic combining marks that GD cannot attach to presentation forms.
     */
    public static function stripHarakat(string $text): string
    {
        return preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $text) ?? $text;
    }

    private static function shapeWord(string $word): string
    {
        // Lam + Alef ligatures
        $word = str_replace(
            ["\u{0644}\u{0627}", "\u{0644}\u{0622}", "\u{0644}\u{0623}", "\u{0644}\u{0625}"],
            ["\u{FEFB}", "\u{FEF5}", "\u{FEF7}", "\u{FEF9}"],
            $word
        );

        $chars = self::utf8ToUnicode($word);
        $length = count($chars);
        $result = [];

        for ($i = 0; $i < $length; $i++) {
            $char = $chars[$i];

            // Combining marks stay with the preceding base after reverse
            if (self::isDiacritic($char)) {
                $result[] = $char;
                continue;
            }

            if (!isset(self::$mapping[$char])) {
                $result[] = $char;
                continue;
            }

            $prev = self::previousLetter($chars, $i);
            $next = self::nextLetter($chars, $i);

            $canJoinPrev = $prev !== null
                && isset(self::$mapping[$prev])
                && !in_array($prev, self::$nonJoining, true);
            $canJoinNext = $next !== null && isset(self::$mapping[$next]);

            if ($canJoinPrev && $canJoinNext) {
                $form = self::$mapping[$char][2];
            } elseif ($canJoinPrev) {
                $form = self::$mapping[$char][1];
            } elseif ($canJoinNext) {
                $form = self::$mapping[$char][3];
            } else {
                $form = self::$mapping[$char][0];
            }

            $result[] = $form;
        }

        // Reverse as grapheme-like units: base (+ following diacritics)
        $units = [];
        $unit = '';
        foreach ($result as $ch) {
            if ($unit !== '' && self::isDiacritic($ch)) {
                $unit .= $ch;
                continue;
            }
            if ($unit !== '') {
                $units[] = $unit;
            }
            $unit = $ch;
        }
        if ($unit !== '') {
            $units[] = $unit;
        }

        return implode('', array_reverse($units));
    }

    private static function previousLetter(array $chars, int $index): ?string
    {
        for ($i = $index - 1; $i >= 0; $i--) {
            if (self::isDiacritic($chars[$i])) {
                continue;
            }
            return $chars[$i];
        }
        return null;
    }

    private static function nextLetter(array $chars, int $index): ?string
    {
        $len = count($chars);
        for ($i = $index + 1; $i < $len; $i++) {
            if (self::isDiacritic($chars[$i])) {
                continue;
            }
            return $chars[$i];
        }
        return null;
    }

    private static function isDiacritic(string $char): bool
    {
        $ord = mb_ord($char, 'UTF-8');
        if ($ord === false) {
            return false;
        }
        // Harakat + Quranic marks commonly used in Sindhi/Arabic orthography
        return ($ord >= 0x064B && $ord <= 0x065F)
            || $ord === 0x0670
            || ($ord >= 0x06D6 && $ord <= 0x06ED);
    }

    private static function isRtl(string $str): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $str);
    }

    /**
     * @return list<string>
     */
    private static function utf8ToUnicode(string $str): array
    {
        preg_match_all('/./u', $str, $matches);
        return $matches[0] ?? [];
    }
}
