# Sindhi Open Lexicon Master Dataset

**Publisher:** SindhiLanguage.org  
**Website:** https://sindhilanguage.org/  
**Prepared and curated by:** Amar Fayaz Buriro (امر فياض ٻرڙو)

## Overview

This is a developer-ready and AI-ready master lexical dataset for the Sindhi language.  
It contains lexical entries from multiple Sindhi dictionary and terminology sources, normalized into CSV, JSONL, and SQLite formats.

## Dataset Statistics

- Total entries: **223,342**
- ٺيٺ سنڌي ٻول / جامع سنڌي لغات entries: **80,588**
- Mewaram لغت entries: **29,514**

## Included Files

```text
data/sindhi_open_lexicon_master_223342.csv
data/sindhi_open_lexicon_master_223342.jsonl
sqlite/sindhi_open_lexicon_master_223342.sqlite
metadata/stats.json
metadata/sources.json
LICENSE.txt
README.md
```

## Main Fields

- `lexical_id` — stable generated ID
- `entry_id` — original database ID
- `word` — headword / lexical item
- `word_with_airab_or_variant` — variant or diacritic form where available
- `part_of_speech` — grammatical category where available
- `domain` — subject/domain where available
- `definition` — meaning or definition
- `language_direction` — language/source direction
- `source_dictionary` — original dictionary/source
- `normalized_word` — normalized searchable word
- `normalized_definition` — normalized searchable definition
- `extra` — additional original metadata where available

## Source Counts

- جامع سنڌي لغات: 80,588
- Official Terms: 37,599
- Mewaram لغت: 29,514
- English → Sindhi: 21,726
- Devanagari/Sindhi → English: 16,519
- Hindi → Sindhi: 15,300
- Trade & Commerce: 7,998
- Haematology: 5,158
- Arabic → Sindhi: 4,566
- Print Technology: 4,374

## Usage

This dataset may be used for:

- Sindhi language applications
- AI and NLP systems
- Large Language Model training and evaluation
- Search, dictionary, and lexicon portals
- Educational and linguistic research

## Required Attribution

Any public use, redistribution, model training note, research paper, or application using this dataset should acknowledge:

**SindhiLanguage.org**  
https://sindhilanguage.org/  

**Prepared and curated by Amar Fayaz Buriro (امر فياض ٻرڙو)**

## Suggested Citation

Sindhi Open Lexicon Master Dataset, published at SindhiLanguage.org, prepared and curated by Amar Fayaz Buriro (امر فياض ٻرڙو).

## Notes

The dataset preserves source-level attribution for transparency. Some entries may contain incomplete grammar or domain fields because the original dictionaries differ in structure.
