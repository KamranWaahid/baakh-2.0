# Baakh Lughat — Full System & Data Model Spec

**Audience:** Give this document to an AI (or reviewer) to propose improvements.  
**Product:** Baakh (Laravel + React admin) — Sindhi poetry platform.  
**Feature:** **Baakh Lughat** = poetic dictionary, separate from the general **Open Lexicon** dictionary.  
**Date:** 2026-07-29 (updated with **v2 linguistic layers**)  
**Stack:** Laravel (PHP), MySQL/MariaDB, React admin (`/admin/baakh-lughat`), Sanctum auth.

---

## v2 status (implemented after linguistic review)

The review about `تنھنجو / تنھنجي / تنھنجا / تنھنجون` drove these changes:

| Recommendation | Status |
|----------------|--------|
| `lughat_occurrences` (every poetry token) | **Done** |
| `lughat_word_forms` (surface → form → lemma) | **Done** |
| Richer `lughat_inflections` (gender, number, case, person, stem, suffix, …) | **Done** |
| Homograph-safe uniqueness `(language, normalized_lemma, homograph_number)` | **Done** |
| Preserve surface form + normalized form on occurrence | **Done** |
| Derived frequencies from occurrences | **Done** (cached on lemma) |
| Poetry import: token → occurrence → word_form → lemma | **Done** |
| Non-destructive AI import (no delete-by-omission; `_replace_missing`) | **Done** |
| Decouple poetry EN publish (`publish_romanization` / `romanization_status`) | **Done** (default: Romanizer upsert only; EN rebuild on publish) |
| Form → lemma search `GET /api/admin/lughat/search?q=` | **Done** |
| Editor JSON `baakh.lughat.editor_json.v2` with structured forms | **Done** |
| Multiword expressions (`lughat_expressions` + components + span occurrences) | **Done** |
| Izafat rule detection on poetry import (`جامِ محبت`) | **Done** |
| Sense-level poetic fields / occurrence↔sense candidates | Later |
| Public `/api/v1/lughat` | Later |
| Remove `usage` relation / full Open Lexicon UI cleanup | Later |

**Canonical flow now:**

```text
Poetry line
  → individual tokens (surface keeps izafat kasra; normalized strips diacritics)
  → lughat_occurrences → lughat_word_forms → lughat_lemmas → lughat_senses
  → detect consecutive spans (e.g. جامِ + محبت)
  → lughat_expressions + lughat_expression_components + lughat_expression_occurrences
```

Example: `جامِ محبت` is **not** a lemma. Lemmas stay `جام` and `محبت`; the phrase is an `izafat` expression with a couplet span occurrence.

---

## 0. Ask for the reviewing AI

Please read this entire document and propose concrete improvements for:

1. **Data model** (schema, uniqueness, FKs, provenance, frequency, cross-links to poetry)
2. **Editorial / linguistic model** (senses, examples, relations, variants, morphology for Sindhi poetry)
3. **Poetry → Lughat pipeline** (tokenization, diacritics, duplicates, multi-poetry provenance)
4. **AI enrich contract** (JSON schema, prompt quality, validation, batch enrich)
5. **Roman / Romanizer / poetry EN sync** side effects
6. **Admin UX & workflow** (inbox, completion, QA)
7. **Public API / search / site integration** (currently missing)
8. **Separation from Open Lexicon** (branding leftovers, shared vs forked code)
9. **Performance, ops, testing**

Prioritize by impact. Call out risks, missing invariants, and “do this next” vs “later”. Prefer Sindhi-poetry-aware suggestions over generic dictionary advice.

---

## 1. Purpose

### What Baakh Lughat is
A **poetic Sindhi dictionary**: headwords taken from Sindhi poetry couplets, then enriched with meanings, examples, morphology, relations, variants — ideally via AI using poetry context.

### What it is not
- Not the general **Open Lexicon** dictionary (`lemmas` / `senses` tables, `/admin/dictionary`).
- Not Hesudhar (orthography corrector for poetry text) — that is a separate pipeline.
- Not yet a public reader-facing API.

### Design goals (current)
1. Pull unique words from poetry (oldest first), create **word-only** stubs.
2. Strip zabar / pesh / zer (and other diacritics) on import.
3. Skip duplicates (`normalized_lemma` unique).
4. Export **editor JSON** with poetry context for AI.
5. AI returns enriched JSON (including **roman transliteration** + **new senses**).
6. Import JSON → update lemma; sync roman → **Romanizer** + rebuild **poetry EN** couplets/title.
7. Source branding: Publisher `baakh.com`, Prepared by `Kamran Wahid`, URL `https://baakh.com/`.

---

## 2. High-level architecture

```
Poetry (poetry_main + poetry_couplets lang=sd)
        │
        ▼  POST /api/admin/lughat/import-from-poetry
LughatPoetryWordImporter
        │  word-only stubs (no roman, no senses)
        ▼
lughat_lemmas (+ poetry_id, couplet_id)
        │
        ▼  GET …/editor-json  →  Copy for AI (ChatGPT clipboard)
AI returns enriched JSON
        │
        ▼  POST …/import-json
LughatLemmaJsonImportService
        ├── senses / morphology / relations / variants / forms
        ├── general.transliteration
        ├── RomanizerService.upsert + refresh .dic
        └── PoetryRomanSyncService → poetry EN couplets + EN title
```

Admin UI: React under `/admin/baakh-lughat/*`.  
API: `/api/admin/lughat/*` (auth:sanctum).

---

## 3. Separation from Open Lexicon

| Concern | Open Lexicon | Baakh Lughat |
|--------|--------------|--------------|
| Tables | `lemmas`, `senses`, … | `lughat_lemmas`, `lughat_senses`, … |
| Models | `Lemma`, `Sense`, … | `LughatLemma`, `LughatSense`, … |
| Admin | `/admin/dictionary` | `/admin/baakh-lughat` |
| API | `/api/admin/dictionary` | `/api/admin/lughat` |
| Ingest | scrapes / imports / lexical_id | poetry couplet tokens |
| Schema | largely mirrored | same shape + `poetry_id` / `couplet_id` |

Code was largely adapted from Dictionary UI/controller/services. Some Open Lexicon naming remains in Lughat UI (“Open Lexicon Source” card, Word Lookup hitting `/api/v1/word`).

---

## 4. Data model

### 4.1 Tables

#### `lughat_lemmas` (headword)
| Column | Notes |
|--------|--------|
| `id`, `public_id` (`blug_…`) | Public ULID-style id |
| `lemma` | Display headword (Sindhi Arabic script) |
| `normalized_lemma` | **Unique**; lookup key via `DictionaryText::normalizeForLookup` (strip punct + diacritics + lower) |
| `transliteration` | Roman; **null** after poetry import until AI |
| `ipa`, `phonetic`, `pronunciation_simple`, `audio_url`, `syllabification` | Pronunciation |
| `pos`, `etymology`, `notes` | Lexical metadata |
| `source_confidence` | 0–100 |
| `search_keywords_json` | `{ sindhi[], english[], romanized[] }` |
| `metadata_json` | e.g. `{ dictionary, version, source: poetry_import, poetry_id }` |
| `frequency` | Default 0 (not auto-updated from poetry frequency yet) |
| `status` | `pending \| approved \| rejected` |
| `completion_status` | `pending \| complete` (+ score, notes, checklist, completed_at/by) |
| `*_reviewed` flags | variants / examples / morphology / pronunciation |
| `poetry_id`, `couplet_id` | Provenance (**indexed, no FK**) — first poem/couplet that introduced the word |

#### `lughat_senses`
Definitions (sd/en), glosses, POS, domain, register, dialect, confidence, language_direction, source fields (`source_dictionary`, `source`, `publisher`, `license`, …), `english_equivalents` JSON, `extra` JSON (stores `prepared_by`, `publisher_url`, etc.), review/status.

#### `lughat_sense_examples`
`sentence`, `romanization`, `translation`, `source`, `citation`, quality/review flags, optional `poetry_id` / `couplet_id`.

#### `lughat_morphologies` (1:1 with lemma)
`root`, `pattern`, `gender`, `number`, `case`, `aspect`, `tense`, `review_status`.  
Note: editor JSON currently exports tense but **not aspect**.

#### `lughat_variants`
`variant`, `normalized_variant`, `type`, romanization, dialect, note, source.

**Allowed `type` values:**
`dialectal`, `misspelling`, `historical`, `diacritic`, `spelling`, `normalized`, `short_vowel_variant`, `fully_voweled_variant`, `fatha_variant`

#### `lughat_relations`
`relation_type`, `related_word`, optional `related_lemma_id`, romanization, note, **gloss** (text — for usage example sentences), POS, source.

**Allowed `relation_type` values:**
`synonym`, `antonym`, `hypernym`, `related`, `singular`, `plural`, `dialect`, `derived`, `usage`

- `derived` — e.g. محبت → محبتي، خون → خوني  
- `usage` — people-say / first·second form; label in `note`, example sentence in `gloss`

#### `lughat_inflections`
Inflected `form` + romanization + description. Compared with `BINARY` so diacritic-only differences stay distinct.

#### `lughat_idiomatic_expressions`
`phrase`, romanization, `english_gloss`, example_sindhi / example_english.  
Narrow idiom store; prefer `lughat_expressions` for poetic MWEs.

#### `lughat_expressions` (broader multiword layer)
`expression` (surface, may keep `ِ`), `normalized_expression` (`جام محبت`), `compact_search_key` (`جاممحبت`, secondary only), `expression_type`, glosses, register, status/review.

**Types:** `izafat`, `compound`, `collocation`, `idiom`, `metaphor`, `fixed_phrase`, `formulaic_phrase`, `reduplicative`, `name_or_title`, `other`.

#### `lughat_expression_components`
Ordered parts: `position`, optional `lemma_id` / `word_form_id`, `surface_form` (e.g. `جامِ`), `connector` (`izafat`), `role` (`head` / `complement`).

#### `lughat_expression_occurrences`
Span annotation on couplets: `start_token_index`–`end_token_index`, surface/normalized text, `detection_method` (`manual|dictionary_match|rule_based|ai`), review status.  
Unique on `(expression_id, couplet_id, start_token_index, end_token_index)`.

### 4.2 Models (Eloquent)
`app/Models/Lughat{Lemma,Sense,SenseExample,Morphology,Variant,Relation,Inflection,IdiomaticExpression,Expression,ExpressionComponent,ExpressionOccurrence}.php`  
`LughatLemma` relations: senses, morphology, variants, lemmaRelations, inflections, idiomaticExpressions, expressionComponents, expressions, poetry, couplet.

### 4.3 Source defaults (Baakh)
Applied on AI import / sense create / editor JSON fill:

| Field | Value |
|-------|--------|
| `source_dictionary` / `source` | `Baakh Lughat` |
| `publisher` | `baakh.com` |
| `extra.prepared_by` | `Kamran Wahid` |
| `extra.publisher_url` | `https://baakh.com/` |

---

## 5. Services

| Service | Responsibility |
|---------|----------------|
| `LughatPoetryWordImporter` | Walk poetry by id; token occurrences + lemma stubs; rule-based izafat spans |
| `LughatExpressionService` | Upsert expressions/components; search keys; izafat detection; lemma-linked lists |
| `LughatLemmaEditorJsonService` | Build/normalize `baakh.lughat.editor_json.v2` + poetry/token hints |
| `LughatLemmaJsonImportService` | Upsert lemma graph from JSON; expression_candidates → pending review |
| `LughatCompletionService` | Checklist score (reuses dictionary completion config) |
| `LughatStructuredEntryService` | Display/export structure on lemma show |
| `RomanizerService` | `word_sd`↔`word_roman` map, transliterate lines, refresh `.dic` |
| `PoetryRomanSyncService` | Rebuild EN couplets (`lang=en`, slug `{poetry_slug}-roman-{n}`) + EN title |

Shared text: `App\Support\DictionaryText` (strip diacritics / punctuation / normalizeForLookup / normalizeExpression; **surface keeps kasra**).

---

## 6. Admin UI

| Route | Page | Actions |
|-------|------|---------|
| `/admin/baakh-lughat` | Lughat Home | Stats, browse, Add Word, **Get words from poetry**, delete, JSON modal |
| `/admin/baakh-lughat/lemma-inbox` | Inbox | Pending completion queue |
| `/admin/baakh-lughat/lemmas/:id` | Sense Editor | Full edit + Copy for AI / Import JSON |
| `…/sense-editor` | Sense search | |
| `…/morphology-lab` | Morphology | |
| `…/variants` | Variants | |
| `…/qa-search` | QA buckets | |

---

## 7. Pipelines (detail)

### 7.1 Poetry → word stubs
1. Cursor: Cache key `lughat.poetry_import.cursor` = last processed `poetry_id` (not DB-backed).
2. Next poetry: `id > cursor` ordered by `id` ascending (oldest first).
3. Couplets: `lang` empty / `sd` / `snd` only; HTML stripped.
4. Tokenize on whitespace; strip punctuation + **all diacritics**; require Arabic/Sindhi letter.
5. Dedup within poem by `normalized_lemma`; skip if already in DB.
6. Create lemma: word only, `transliteration=null`, `status=pending`, `completion_status=pending`, link `poetry_id` + first `couplet_id`, metadata `source: poetry_import`.
7. Advance cursor. UI supports reset to oldest.

**Limitations today**
- One provenance pair only (first poem); later poems that reuse the word are skipped entirely.
- `frequency` not incremented.
- No batch “import N poems”.
- No stemming / clitics beyond whitespace tokens.
- Multiword **izafat** spans detected when token ends with kasra + next token; other MWE types via AI `expression_candidates`.
- Lemma lookup keys still strip diacritics; expression **surface** preserves `جامِ محبت`.

### 7.2 AI enrich → import
1. `GET /api/admin/lughat/lemmas/{id}/editor-json`
2. UI prepends `AI_ENRICH_PROMPT` + JSON → clipboard → ChatGPT
3. Paste reply → `POST …/import-json`
4. Sync all sections; new senses allowed without `id`
5. If `general.transliteration` newly set/changed:
   - Upsert Romanizer (`baakh_roman_words`)
   - Refresh `public/vendor/roman-converter/all_words.dic`
   - Re-transliterate all SD couplets of linked poetry → upsert EN rows + EN title

**AI is required to**
- Fill `general.transliteration` (roman of headword)
- Add/update senses (may invent additional poetic senses beyond the snippet)
- Set Baakh publisher fields on senses
- Keep schema keys and existing numeric ids

**AI must not**
- Invent poetry EN couplet lines in JSON (system rebuilds EN from Romanizer)
- Strip/replace poetry context block (read-only)

### 7.3 Diacritics vs Hesudhar
- Lughat import/normalize: strip diacritics for headword keys.
- Hesudhar refine on poetry/couplets: also strips diacritics when refining stored verse text (separate feature).
- No Hesudhar call inside Lughat services.

---

## 8. Editor JSON contract (`baakh.lughat.editor_json.v1`)

### Top-level keys
```
_schema, _name, _instructions,
id, public_id,
poetry, general, completion, morphology,
senses, relations, variants, forms
```

### `poetry` (Sindhi context only — no EN)
```json
{
  "poetry_id": 1,
  "couplet_id": 10,
  "title": "…",
  "poetry_slug": "…",
  "poet_id": 2,
  "source_couplet": { "id": 10, "text": "…", "lang": "sd" },
  "couplets": [{ "id": 10, "text": "…" }, …]
}
```

### `general` (important)
`lemma`, `normalized_lemma`, `transliteration` (AI fills), pronunciation fields, `pos`, confidence, status, etymology, notes, keyword arrays, metadata fields, review flags, `primary_meanings`.

### `senses[]`
`id?`, definitions, `english_equivalents[]`, usage/domain, `source_dictionary`, `publisher`, `publisher_url`, `prepared_by`, `examples[]`.

### `forms`
`inflections[]`, `idiomatic_expressions[]`.

Import accepts this nested editor shape **or** a flattened dump. Read-only blobs (`source_summary`, `structured_entry`, …) are stripped.

---

## 9. API surface (admin)

Base: `/api/admin/lughat/`

- `GET stats`
- `GET|POST import-from-poetry` (peek / import next)
- `apiResource lemmas` + `approve`, `editor-json`, `import-json`, `completion`
- CRUD: senses, examples, morphology, variants, relations, inflections, idioms
- `GET senses | morphology | variants | qa | lemma-search`

No public `/api/v1/lughat/…` yet.

---

## 10. Current known gaps / smells

1. **Open Lexicon leftovers** — UI labels, forced `is_open_lexicon`, Home Word Lookup hits Open Lexicon API, Sindhila scrape stub/toast.
2. **No public Lughat lookup** for site/tooltips.
3. **Provenance** — only first poetry/couplet; no multi-occurrence table; frequency unused.
4. **Poetry import cursor** — cache-only; lost on cache flush.
5. **No FKs** on `poetry_id` / `couplet_id`.
6. **AI is clipboard-only** — no server-side LLM, no batch enrich, no validation schema (JSON Schema / Zod).
7. **Roman sync** — regenerates whole poem EN from dictionary; orphan EN rows possible if slug scheme differed historically; incomplete roman leaves Sindhi tokens in EN lines.
8. **Completion** — Dictionary config reused; may not fit poetry-first checklist.
9. **Morphology `aspect`** in DB but missing from editor JSON export.
10. **Tokenization** naive (whitespace); no handling of clitics, compounds, or poetic sandhi.
11. **Duplication** with Dictionary codebase (large forked controller/UI) — maintenance cost.
12. **Tests** — little/no dedicated Lughat feature coverage.
13. **Search** — admin only; no Scout/Meilisearch index for Lughat.
14. **Cross-dictionary** — no link between same headword in Open Lexicon vs Lughat.

---

## 11. Example word lifecycle

1. Admin clicks **Get words from poetry** → poetry `#1` yields unique tokens → 47 pending lemmas.
2. Open lemma `دل` → Word JSON includes poem couplets where it appeared.
3. Copy for AI → model returns senses + `transliteration: "dil"` + relations.
4. Import JSON → lemma complete-able; Romanizer has `دل:dil`; poetry EN lines re-rendered with `dil` where dictionary matches.
5. Inbox / Mark Complete / Approve.

---

## 12. File index

```
database/migrations/2026_07_29_200000_create_baakh_lughat_tables.php
database/migrations/2026_07_29_210000_widen_relation_gloss_for_usage_sentences.php
database/migrations/2026_07_29_220000_lughat_v2_word_forms_occurrences.php
database/migrations/2026_07_29_230000_create_lughat_expressions_tables.php

app/Models/Lughat*.php
app/Http/Controllers/Api/Admin/LughatDictionaryController.php
app/Services/LughatLemmaEditorJsonService.php
app/Services/LughatLemmaJsonImportService.php
app/Services/LughatPoetryWordImporter.php
app/Services/LughatExpressionService.php
app/Services/LughatCompletionService.php
app/Services/LughatStructuredEntryService.php
app/Services/RomanizerService.php
app/Services/PoetryRomanSyncService.php
app/Support/DictionaryText.php

routes/api.php                          # lughat routes
resources/js/admin/pages/Lughat/*
resources/js/admin/main.jsx
resources/js/admin/components/Sidebar.jsx
```

Related (not Lughat, but linked side effects):
```
app/Models/Poetry.php, Couplets.php, Romanizer.php
app/Services/Hesudhar/*                 # verse orthography, separate
```

---

## 13. Sample editor JSON (word-only stub shape)

```json
{
  "_schema": "baakh.lughat.editor_json.v1",
  "_name": "Baakh Lughat",
  "_instructions": "…",
  "id": 42,
  "public_id": "blug_…",
  "poetry": {
    "poetry_id": 1,
    "couplet_id": 5,
    "title": "اي ڏاھر",
    "couplets": [{ "id": 5, "text": "…" }]
  },
  "general": {
    "lemma": "ڏاھر",
    "normalized_lemma": "ڏاھر",
    "transliteration": null,
    "pos": null,
    "primary_meanings": { "definition": null, "definition_sd": null, "definition_en": null }
  },
  "completion": { "completion_status": "pending", "completion_notes": null },
  "morphology": { "root": null, "pattern": null, "gender": null, "number": null, "case": null, "tense": null },
  "senses": [],
  "relations": [],
  "variants": [],
  "forms": { "inflections": [], "idiomatic_expressions": [] }
}
```

After AI, `general.transliteration` must be set; `senses` should contain one or more meanings with Baakh publisher fields; new senses omit `id`.

---

## 14. Constraints / invariants to preserve when improving

1. Lughat stays **separate tables** from Open Lexicon (unless a deliberate merge strategy is proposed).
2. Poetry import creates **word-only** stubs — no roman until AI (or explicit editorial roman).
3. `normalized_lemma` uniqueness drives dedup.
4. Diacritics are stripped for headword identity on poetry import.
5. AI roman must update Romanizer and (when linked) poetry EN.
6. Publisher identity for Baakh Lughat senses: baakh.com / Kamran Wahid / https://baakh.com/.
7. Sindhi script remains Arabic-Sindhi orthography (not Latin) for `lemma` and example sentences.

---

*End of Baakh Lughat system spec.*
