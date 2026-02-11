<?php

namespace App\Helpers;

/**
 * A basic Sindhi/Arabic text shaper to handle ligatures for GD rendering.
 * Maps isolated characters to their contextual forms (initial, medial, final, isolated).
 */
class SindhiShaper
{
    private static $mapping = [
        // Format: [isolated, final, medial, initial]
        "\x{0627}" => ["\x{0627}", "\x{FE8E}", "\x{0627}", "\x{0627}"], // Alef
        "\x{0628}" => ["\x{0628}", "\x{FE90}", "\x{FE92}", "\x{FE91}"], // Be
        "\x{067B}" => ["\x{067B}", "\x{FB53}", "\x{FB55}", "\x{FB54}"], // Sindhi Be
        "\x{0680}" => ["\x{0680}", "\x{FB5B}", "\x{FB5D}", "\x{FB5C}"], // Sindhi Bbe
        "\x{067E}" => ["\x{067E}", "\x{FB57}", "\x{FB59}", "\x{FB58}"], // Pe
        "\x{062A}" => ["\x{062A}", "\x{FE96}", "\x{FE98}", "\x{FE97}"], // Te
        "\x{067A}" => ["\x{067A}", "\x{FB5F}", "\x{FB61}", "\x{FB60}"], // Sindhi Te
        "\x{067D}" => ["\x{067D}", "\x{FB63}", "\x{FB65}", "\x{FB64}"], // Sindhi Tte
        "\x{062B}" => ["\x{062B}", "\x{FE9A}", "\x{FE9C}", "\x{FE9B}"], // The
        "\x{062C}" => ["\x{062C}", "\x{FE9E}", "\x{FEA0}", "\x{FE9F}"], // Je
        "\x{0684}" => ["\x{0684}", "\x{FB73}", "\x{FB75}", "\x{FB74}"], // Sindhi Je
        "\x{0683}" => ["\x{0683}", "\x{FB6F}", "\x{FB71}", "\x{FB70}"], // Sindhi Nje
        "\x{0686}" => ["\x{0686}", "\x{FB7B}", "\x{FB7D}", "\x{FB7C}"], // Che
        "\x{0687}" => ["\x{0687}", "\x{FB7F}", "\x{FB81}", "\x{FB80}"], // Sindhi Che
        "\x{062D}" => ["\x{062D}", "\x{FEA2}", "\x{FEA4}", "\x{FEA3}"], // Ha
        "\x{062E}" => ["\x{062E}", "\x{FEA6}", "\x{FEA8}", "\x{FEA7}"], // Kha
        "\x{062F}" => ["\x{062F}", "\x{FEAA}", "\x{062F}", "\x{062F}"], // Dal
        "\x{068A}" => ["\x{068A}", "\x{FB83}", "\x{068A}", "\x{068A}"], // Sindhi Dal
        "\x{0688}" => ["\x{0688}", "\x{FB7F}", "\x{0688}", "\x{0688}"], // Ddal
        "\x{068C}" => ["\x{068C}", "\x{FB87}", "\x{068C}", "\x{068C}"], // Sindhi Ddal
        "\x{0630}" => ["\x{0630}", "\x{FEAC}", "\x{0630}", "\x{0630}"], // Zal
        "\x{0631}" => ["\x{0631}", "\x{FEAE}", "\x{0631}", "\x{0631}"], // Re
        "\x{0691}" => ["\x{0691}", "\x{FB8D}", "\x{0691}", "\x{0691}"], // Sindhi Re (Rre)
        "\x{0632}" => ["\x{0632}", "\x{FEB0}", "\x{0632}", "\x{0632}"], // Ze
        "\x{0633}" => ["\x{0633}", "\x{FEB2}", "\x{FEB4}", "\x{FEB3}"], // Seen
        "\x{0634}" => ["\x{0634}", "\x{FEB6}", "\x{FEB8}", "\x{FEB7}"], // Sheen
        "\x{0635}" => ["\x{0635}", "\x{FEBA}", "\x{FEBC}", "\x{FEBB}"], // Sad
        "\x{0636}" => ["\x{0636}", "\x{FEBE}", "\x{FEC0}", "\x{FEBF}"], // Zad
        "\x{0637}" => ["\x{0637}", "\x{FEC2}", "\x{FEC4}", "\x{FEC3}"], // Toe
        "\x{0638}" => ["\x{0638}", "\x{FEC6}", "\x{FEC8}", "\x{FEC7}"], // Zoe
        "\x{0639}" => ["\x{0639}", "\x{FECA}", "\x{FECC}", "\x{FECB}"], // Ain
        "\x{063A}" => ["\x{063A}", "\x{FECE}", "\x{FED0}", "\x{FECF}"], // Ghain
        "\x{0641}" => ["\x{0641}", "\x{FED2}", "\x{FED4}", "\x{FED3}"], // Fe
        "\x{0642}" => ["\x{0642}", "\x{FED6}", "\x{FED8}", "\x{FED7}"], // Qaf
        "\x{06A9}" => ["\x{06A9}", "\x{FBA9}", "\x{FBAA}", "\x{FBAA}"], // Kaf
        "\x{06AB}" => ["\x{06AB}", "\x{FBAD}", "\x{FBAF}", "\x{FBAE}"], // Sindhi Kaf
        "\x{06AF}" => ["\x{06AF}", "\x{FB93}", "\x{FB95}", "\x{FB94}"], // Gaf
        "\x{06B3}" => ["\x{06B3}", "\x{FB9B}", "\x{FB9D}", "\x{FB9C}"], // Sindhi Gaf
        "\x{06B1}" => ["\x{06B1}", "\x{FB97}", "\x{FB99}", "\x{FB98}"], // Sindhi Ggaf
        "\x{0644}" => ["\x{0644}", "\x{FEDE}", "\x{FEE0}", "\x{FEDF}"], // Lam
        "\x{0645}" => ["\x{0645}", "\x{FEE2}", "\x{FEE4}", "\x{FEE3}"], // Meem
        "\x{0646}" => ["\x{0646}", "\x{FEE6}", "\x{FEE8}", "\x{FEE7}"], // Noon
        "\x{06BB}" => ["\x{06BB}", "\x{FBA1}", "\x{FBA3}", "\x{FBA2}"], // Sindhi Noon
        "\x{0648}" => ["\x{0648}", "\x{FEEE}", "\x{0648}", "\x{0648}"], // Wao
        "\x{0647}" => ["\x{0647}", "\x{FEEA}", "\x{FEEC}", "\x{FEEB}"], // He
        "\x{064A}" => ["\x{064A}", "\x{FEF2}", "\x{FEF4}", "\x{FEF3}"], // Ye
        "لآ" => ["\x{FEFB}", "\x{FEFC}", "\x{FEFC}", "\x{FEFB}"], // Lam-Alef ligature
    ];

    private static $nonJoining = ["\x{0627}", "\x{062F}", "\x{068A}", "\x{0688}", "\x{068C}", "\x{0630}", "\x{0631}", "\x{0691}", "\x{0632}", "\x{0648}"];

    public static function shape($text)
    {
        // Split by spaces to handle words individually
        $words = explode(' ', $text);
        $shapedWords = [];

        foreach ($words as $word) {
            if (self::isRtl($word)) {
                $shapedWords[] = self::shapeWord($word);
            } else {
                $shapedWords[] = $word;
            }
        }

        // For RTL layout in GD, we need to reverse the order of words too
        return implode(' ', array_reverse($shapedWords));
    }

    private static function shapeWord($word)
    {
        // Handle Lam-Alef ligature
        $word = str_replace("لا", "لآ", $word);

        $tokens = self::utf8ToUnicode($word);
        $length = count($tokens);
        $result = [];

        for ($i = 0; $i < $length; $i++) {
            $char = $tokens[$i];

            if (!isset(self::$mapping[$char])) {
                $result[] = $char;
                continue;
            }

            $prev = ($i > 0) ? $tokens[$i - 1] : null;
            $next = ($i < $length - 1) ? $tokens[$i + 1] : null;

            $canJoinPrev = $prev && isset(self::$mapping[$prev]) && !in_array($prev, self::$nonJoining);
            $canJoinNext = $next && isset(self::$mapping[$next]);

            if ($canJoinPrev && $canJoinNext) {
                $result[] = self::$mapping[$char][2]; // Medial
            } elseif ($canJoinPrev) {
                $result[] = self::$mapping[$char][1]; // Final
            } elseif ($canJoinNext) {
                $result[] = self::$mapping[$char][3]; // Initial
            } else {
                $result[] = self::$mapping[$char][0]; // Isolated
            }
        }

        // Reverse word characters for RTL
        return implode('', array_reverse($result));
    }

    private static function isRtl($str)
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $str);
    }

    private static function utf8ToUnicode($str)
    {
        preg_match_all('/./u', $str, $matches);
        return $matches[0];
    }
}
