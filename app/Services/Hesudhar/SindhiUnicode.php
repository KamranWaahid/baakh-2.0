<?php

namespace App\Services\Hesudhar;

/**
 * All Unicode codepoints relevant to Sindhi-Arabic script normalization.
 * Ported from Hesudhar Python Reference Implementation.
 */
class SindhiUnicode
{
    // -- HEH FAMILY ------------------------------------------------------------
    public const HEH_ARABIC = "\u{0647}";   // ه — ملفوظي — Malfoozi (pronounced /h/)
    public const HEH_DOACHASHMEE = "\u{06BE}";   // ھ — وسرڳي — Visargi (aspiration marker)
    public const HEH_GOAL = "\u{06C1}";   // ہ — مختفي — Mukhtafi (word-final weak)
    public const HEH_GOAL_HAMZA = "\u{06C2}";   // ۂ — variant (normalize to HEH_GOAL)
    public const HEH_AE = "\u{06D5}";   // ە — alternative Mukhtafi encoding
    public const HEH_YEH = "\u{06C0}";   // ۀ — normalize to HEH_GOAL

    public const HEH_VARIANTS = [
        self::HEH_ARABIC,
        self::HEH_DOACHASHMEE,
        self::HEH_GOAL,
        self::HEH_GOAL_HAMZA,
        self::HEH_AE,
        self::HEH_YEH
    ];

    // -- KAF FAMILY ------------------------------------------------------------
    public const KAF_ARABIC = "\u{0643}";   // ك — Arabic (NOT Sindhi native)
    public const KAF_SINDHI_SWASH = "\u{06AA}";   // ڪ — unaspirated /k/ (native Sindhi)
    public const KAF_KEHEH = "\u{06A9}";   // ک — aspirated /kh/ (also خ context)
    public const KAF_VARIANTS = [
        self::KAF_ARABIC,
        self::KAF_SINDHI_SWASH,
        self::KAF_KEHEH
    ];

    // -- YEH FAMILY ------------------------------------------------------------
    public const YEH_ARABIC = "\u{064A}";   // ي — Sindhi standard (dotted)
    public const YEH_FARSI = "\u{06CC}";   // ی — Persian/Urdu (dotless)
    public const YEH_ARABIC_MAX = "\u{0649}";   // ى — Alef Maqsura
    public const YEH_VARIANTS = [
        self::YEH_ARABIC,
        self::YEH_FARSI,
        self::YEH_ARABIC_MAX
    ];

    // -- ALEF FAMILY -----------------------------------------------------------
    public const ALEF_MADDA = "\u{0622}";   // آ — atomic preferred
    public const ALEF_HAMZA_ABOVE = "\u{0623}";   // أ
    public const ALEF_HAMZA_BELOW = "\u{0625}";   // إ
    public const ALEF_PLAIN = "\u{0627}";   // ا
    public const ALEF_MADDA_SEQ = "\u{0627}\u{0653}";  // ا + Madda → آ

    // -- IMPLOSIVE CONSONANTS ----------------------------------------------
    public const IMPLOSIVES = [
        "\u{067B}",        // ٻ — implosive B
        "\u{0684}",        // ڄ — implosive J
        "\u{068F}",        // ڏ — implosive D
        "\u{06B3}",        // ڳ — implosive G
    ];

    // -- ASPIRATION-TRIGGERING CONSONANTS --------------------------------------
    public const ASPIRATION_TRIGGERS = [
        "\u{0646}",   // ن — N
        "\u{0645}",   // م — M
        "\u{0644}",   // ل — L
        "\u{06AF}",   // گ — G
        "\u{0DA0}",   // (fallback gaf variant)
        "\u{0DA9}",   // ڙ — RR (retroflex R)
        "\u{062C}",   // ج — J
        "\u{0631}",   // ر — R
        "\u{06BB}",   // ڻ — NN (retroflex N)
        "\u{0686}",   // چ — CH
        "\u{062F}",   // د — D
        "\u{062A}",   // ت — T
        "\u{067D}",   // ٽ — TT (retroflex T)
        "\u{0688}",   // ڈ — DD
        "\u{067A}",   // ٺ — TTH
        "\u{067C}",   // ټ — variant
        "\u{067F}",   // ٿ — TH
        // NOTE: Waw (و) is EXCLUDED here to fix the "علاوہ" bug as discussed.
    ];

    // -- VOWEL DIACRITICS ------------------------------------------------------
    public const VOWEL_DIACRITICS = [
        "\u{064E}",   // ◌َ — Fatha (zabar)
        "\u{064F}",   // ◌ُ — Damma (pesh)
        "\u{0650}",   // ◌ِ — Kasra (zer)
        "\u{064B}",   // ◌ً — Tanwin Fath
        "\u{064C}",   // ◌ٌ — Tanwin Damm
        "\u{064D}",   // ◌ٍ — Tanwin Kasr
        "\u{0652}",   // ◌ْ — Sukun
        "\u{0651}",   // ◌ّ — Shadda
        "\u{0670}",   // ◌ٰ — Superscript Alef
    ];

    public const ARABIC_DEFINITE_ARTICLE = "\u{0627}\u{0644}";   // ال
}
