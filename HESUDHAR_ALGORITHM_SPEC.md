# Hesudhar (هيسڌار) Engine - Algorithm Specification (Modern Phonetic)

This document details the phonetic and dictionary-based rules engine for the "Hesudhar" Sindhi text correction system, updated to comply with modern orthographic standards for Sindhi-Arabic digital text (prioritizing semantic meaning over legacy visual glyph hacks).

## 1. Phase 1: Global Script Normalization (Pre-processing)
Before word-level Heh analysis, the incoming text stream is cleaned to remove common cross-language encoding errors and legacy hacks.

1. **Atomic Recomposition**: Characters use atomic Unicode forms (`آ` U+0622 instead of Alef+Madda) for consistent matching.
2. **Yeh Standardization**: All instances of Farsi Yeh `ی` (U+06CC) are mapped to Arabic Letter Yeh `ي` (U+064A) as Sindhi orthography strictly requires the dotted form in all positions.
3. **Kaf Standardization**: 
   - Unaspirated `/k/`: Strictly rendered using the Swash Kaf `ڪ` (U+06AA). Standard Arabic `ك` (U+0643) is forcefully converted to `ڪ`.
   - Aspirated `/kh/`: Validly preserved using the standard Sindhi character `ک` (U+06A9) or the `کھ` digraph.

## 2. Priority 1: WordNet Lookup (Dictionary-First)
- **Variant-Agnostic Matching**: The system checks multiple "Heh" permutations simultaneously (e.g., `الله` finds `اللہ`). 
- **Bypass**: If an exact match is found, algorithmic normalization (Phase 2 & 3) is **bypassed completely**.

## 3. Phase 2 & 3: Advanced Phonetic & Semantic Inference
For words absent from WordNet, the algorithm applies phonetic-contextual inference to correctly map the 3 distinct "Heh" sounds: Aspiration (وسرڳي), Weak/Silent (مختفي), and Pronounced (ملفوظي).

### Step 1: Semantic Cleanup (Legacy Trigraphs)
- **Problem**: Legacy fonts lacked tapered final forms for aspirated consonants, prompting users to append an extra Heh Goal "tail" (`ھہ` or `هه`).
- **Action**: The sequence of any two terminal Heh variants `[هہةەھ]{2}$` at word ends is collapsed into pure aspiration `ھ`. 
- **Example**: `گروھہ` $\rightarrow$ `گروھ` | `ڳالهه` $\rightarrow$ `ڳالھ`.

### Step 2: Arabic Citation Detection
- **Action**: If a word starts with a high-frequency Arabic marker (like the *Alif-Lam* `ال` prefix), the aspiration rules (Step 3) are bypassed to preserve the stylistic shape of Arabic citations.
- **Example**: `الجزيره` bypasses aspiration, flowing directly to Step 4.

### Step 3: Identify Aspiration (وسرڳي)
- **Problem**: Sindhi aspiration must exclusively use Heh Doachashmee **`ھ` (U+06BE)**. However, many aspiration-supporting consonants (like `ن`, `م`, `ل`, `ر`, `ڏ`) frequently precede a standard pronounced `/h/` across a syllable boundary (e.g., `مهم`, `انهن`, `رهيا`, `جيڪڏهن`). 
- **Rule**: To prevent corrupting syllable onsets, the algorithmic fallback ONLY triggers automatic aspiration if a Heh immediately follows the *unambiguous* phonetic aspirates: `ڻ گ ل ج ڙ و`.
- **Target**: Force to **`ھ` (U+06BE)**.
- **Example**: `سگهن` $\rightarrow$ `سگھن` | `ڳالھائيندي` $\rightarrow$ `ڳالھائيندي`. Genuine aspirations for ambiguous consonants must be explicitly defined by the WordNet dictionary.

### Step 4: Identify Word-Final Weak Heh (مختفي)
- **Rule**: If a Heh variant is at the absolute end of a word (followed by space/punctuation) and was NOT aspirated in Step 3. This represents a waning release of air.
- **Target**: Force to Heh Goal **`ہ` (U+06C1)**.
- **Example**: `ڪتابه` $\rightarrow$ `ڪتابہ` | `الجزيره` $\rightarrow$ `الجزيرہ`.

### Step 5: Default to Pronounced Heh (ملفوظي)
- **Rule**: Any remaining Heh variant at the start or middle of a syllable that was not processed by Step 3 or 4.
- **Target**: Force to Arabic Heh **`ه` (U+0647)**.
- **Example**: `ھڪ` $\rightarrow$ `هڪ` | `اھم` $\rightarrow$ `اهم` | `آھي` $\rightarrow$ `آهي`.

## 4. UI Behavior: "Identified Mistakes" Logic
- **Ping-Pong Prevention**: A word is only flagged if the algorithm or dictionary suggests a *different* phonetic spelling. `تهران` won't flag itself in an infinite loop.
- **Regex Boundary Replacement**: The bulk "Fix All" tool uses strict Regular Expressions mapped to precise Sindhi/Arabic punctuation boundaries (`،`, `۔`, etc.) to accurately replace words without accidentally replacing substrings inside completely different words.
