# Baakh `/prosody` — mobile brief

Use this to rebuild the web **Prosody** screen and **Poet's Workbench** (`شاعر جي پٽي`) in the Android app.

There are two parts:

1. **Glossary** — cards that explain Arooz, Chhand, Patti, Matra, Beher, etc.
2. **Workbench / Patti** — the poet types a line and taps **وزن چيڪ ڪريو** to scan meter.
3. The scan engine runs **on the device**. There is no “check meter” API. Port the rules below so the app matches [baakh.com/sd/prosody](https://baakh.com/sd/prosody).

---

## Screens and routes


| Web                           | App should do                                          |
| ----------------------------- | ------------------------------------------------------ |
| `/{lang}/prosody`             | Prosody home: intro + workbench banner + concept cards |
| Open “Patti” / “Try Scansion” | Workbench screen (the screenshot)                      |


`lang` is `sd` (RTL, Sindhi copy) or `en`.

---



## 1. Glossary API

`GET /api/v1/prosody?lang=sd`

or `lang=en`

No auth.

### Response (array)

```json
[
  {
    "id": 1,
    "title": "ڇند وديا",
    "subtitle": "Chhand Widya",
    "description": "ڪلاسيڪل سنڌي شاعريءَ جو مقامي نظام...",
    "technical_detail": "ماترائن (وقت جي يونٽن) ۾ ماپيو ويندو آهي...",
    "logic_type": "chhand",
    "icon": "Ruler"
  }
]
```


| Field              | Use                                                                                                                                                      |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `title`            | Card title in the requested language                                                                                                                     |
| `subtitle`         | The other language (small label)                                                                                                                         |
| `description`      | Short card body                                                                                                                                          |
| `technical_detail` | Full text in the detail modal                                                                                                                            |
| `logic_type`       | `chhand` · `arooz` · `both` · `generic`                                                                                                                  |
| `icon`             | Lucide-style name: `Ruler`, `Scale`, `Scissors`, `Music`, `Columns`, `Wrench`, `Scroll`, `Footprints`, `Infinity`, `Anchor`, `Sunrise`, `Sunset`, `Info` |




### How the web app uses this

- If `title` contains `پٽي` or `patti` (case-insensitive), **do not show a card**. That row opens the Workbench instead.
- Other rows open a detail sheet: title, subtitle, description, `technical_detail`.
- Home also has a black banner: “شاعري جي پرک” / “Poet's Workbench” → opens Workbench.

---



## 2. Workbench UI (match the web)

State:


| State       | Values              | Default                                        |
| ----------- | ------------------- | ---------------------------------------------- |
| `method`    | `arooz` or `chhand` | `arooz`                                        |
| `script`    | `perso` or `roman`  | `perso` when `lang=sd`, `roman` when `lang=en` |
| `inputText` | multi-line string   | empty                                          |
| `result`    | scan object or null | null                                           |




### Header

- Back
- Title: `شاعر جي پٽي (Workbench)` / `Poet's Scansion Patti`
- Subtitle: `بحر، وزن ۽ پٽي جو جديد اوزار`
- Toggle:
  - Sindhi: **علم عروض** ↔ **ڇند وديا**
  - English: **Ilm Arooz** ↔ **Chhand Widya**

`method = arooz` when Ilm Arooz is on.

### Editor card

- Tabs: **سنڌي (Perso)** · **Sindhi (roman)**
- Info hint: long vowels in roman are `aa`, `ii`, `uu`
- **Clear** resets `inputText` and `result`
- Textarea
  - `perso`: RTL, Sindhi font, placeholder `پنهنجي شاعري هتي لکو...`
  - `roman`: LTR, Latin font, placeholder `pahnji shayari hity likho...`
- Primary button: **وزن چيڪ ڪريو** / **Check Vazn**



### After scan

Black result bar:

- Arooz: title `دريافت ٿيل بحر (پاڻمرادو)`, pattern `تقطيعي نمونو`
- Chhand: title `ڪل ماترائون: {n}`, pattern `چال: متفرق (ماترا)`  
`{n}` is the **first line** matra total.

Then one card per line:

- Each word is a column
- Above the word: one tile per syllable
  - **long**: black tile
  - **short**: gray tile
  - Arooz label: `-` (long) or `v` (short)
  - Chhand label: `2` or `1` (`weight`)
- Under the tiles: the syllable letters
- Under that: the full word
- Footer: `Analysis • Line N` plus Long / Short legend

**Play Beat** is disabled on web. **Save** asks for login; do not implement save unless we add an API later.

---



## 3. How the scan works

Call this locally when the user taps Check. Do not POST the poem to the server.

```
scanPoetry(text, method, lang) → { meter, lines } | null
```



### Step A — split and clean

1. If `text` is empty → return `null`.
2. Split on `\n`. Drop blank lines.
3. For each line: trim, then strip Arabic diacritics `\u064B`–`\u065F` (fatha, damma, kasra, shadda, sukun, etc.).



### Step B — words

Split each cleaned line on whitespace. Each token is a word.

### Step C — syllables

For each word, run `tokenize(word)`.

1. Today **Arooz and Chhand share the same tokenizer**.  
Chhand then **sums** `weight`. Arooz only shows `-` / `v` from `type`.

### Step D — result object

```json
{
  "meter": {
    "name_arooz": "دريافت ٿيل بحر (پاڻمرادو)",
    "pattern_arooz": "تقطيعي نمونو",
    "name_chhand": "ڪل ماترائون: 14",
    "pattern_chhand": "چال: متفرق (ماترا)"
  },
  "lines": [
    {
      "original": "وطن منهنجو",
      "scanned": [
        {
          "word": "وطن",
          "syllables": [
            { "text": "و", "weight": 1, "type": "short" },
            { "text": "ط", "weight": 1, "type": "short" },
            { "text": "ن", "weight": 1, "type": "short" }
          ]
        }
      ],
      "meta": { "total": 3 }
    }
  ]
}
```

- `meta.total` is required for Chhand (sum of all syllable weights on that line).
- Arooz can omit `meta`.
- `meter.name_chhand` uses **line 1** `meta.total` only.

---



## 4. Tokenizer (port this exactly)



### Roman input

If the word contains `[A-Za-z]`:

- One syllable: `{ text: word, weight: word.length > 3 ? 2 : 1, type: "long" }`
- This is a placeholder. Perso-Arabic is the real engine.



### Perso-Arabic input

Walk the word left-to-right (string index 0 → end). Sindhi letters are still stored LTR in the string.

**Long vowels:** `ا و ي ى آ`  
**Always-long specials:** `آ` and `۾`  
**Vowels-only (not consonants):** `آ ا و ي ى ۾`  
Everything else is a **consonant**, including `ه` / `ھ`.

`و` and `ي` are dual:

- Treat as **consonant** if index is `0`, or next char is `ا` or `آ` (يا، وا).
- Otherwise treat as a **vowel** (consumed with the previous consonant, or skipped if loose).



#### Loop

For each index `i`:

1. `آ` **or** `۾`
  Emit `{ text: char, weight: 2, type: "long" }`. `i += 1`.
2. **Initial** `ا` **(**`i === 0`**)**
  - Next is `و` or `ي` → emit `{ text: "او" or "اي", weight: 2, type: "long" }`. `i += 2`.  
  - Else → emit `{ text: "ا", weight: 1, type: "short" }`. `i += 1`.
3. **Consonant**
  - Next is a long vowel → emit `{ text: consonant + vowel, weight: 2, type: "long" }`. `i += 2`.  
  - Else → emit `{ text: consonant, weight: 1, type: "short" }`. `i += 1`.  
   Implicit short vowel on a bare consonant = 1 matra.
4. **Loose vowel / leftover** `و` ****`ي`
  Skip (`i += 1`). Do not emit.



### Kotlin-style sketch

```kotlin
fun isConsonant(ch: Char): Boolean {
    return ch !in setOf('آ', 'ا', 'و', 'ي', 'ى', '۾')
}

fun tokenize(word: String): List<Syllable> {
    if (word.any { it.isLetter() && it.code < 128 }) {
        return listOf(Syllable(word, if (word.length > 3) 2 else 1, "long"))
    }

    val longVowels = setOf('ا', 'و', 'ي', 'ى', 'آ')
    val out = mutableListOf<Syllable>()
    var i = 0

    while (i < word.length) {
        val c = word[i]
        val n = word.getOrNull(i + 1)

        if (c == 'آ' || c == '۾') {
            out += Syllable(c.toString(), 2, "long")
            i++
            continue
        }

        if (c == 'ا' && i == 0) {
            if (n == 'و' || n == 'ي') {
                out += Syllable("$c$n", 2, "long")
                i += 2
            } else {
                out += Syllable("ا", 1, "short")
                i++
            }
            continue
        }

        var consonant = isConsonant(c)
        if (c == 'ي' || c == 'و') {
            consonant = (i == 0 || n == 'ا' || n == 'آ')
        }

        if (consonant) {
            if (n != null && n in longVowels) {
                out += Syllable("$c$n", 2, "long")
                i += 2
            } else {
                out += Syllable(c.toString(), 1, "short")
                i++
            }
        } else {
            i++
        }
    }
    return out
}
```

---



## 5. Examples (expected tiles)

These follow the current web rules, not a textbook that treats closed syllables as 2.


| Word                 | Syllables   | Weights | Notes                      |
| -------------------- | ----------- | ------- | -------------------------- |
| `وطن`                | `و` `ط` `ن` | 1+1+1   | `و` is initial → consonant |
| `آڄ`                 | `آ` `ڄ`     | 2+1     | `آ` always long            |
| `۾`                  | `۾`         | 2       | special long               |
| `يا`                 | `يا`        | 2       | `ي` + `ا`                  |
| `اي` (start of word) | `اي`        | 2       | initial `ا` + `ي`          |


Chhand line total = sum of all weights on that line.

Classic teaching example from the glossary: **وطن = 3 matras**. That matches this engine.

The glossary also mentions Doha **13+11** and Soratha **11+13**. The current scanner **does not classify** those forms. It only shows the count. You may later compare line totals to 13/11 and label Doha / Soratha; the website does not do that yet.

---



## 6. What the two methods mean



### ڇند وديا / Chhand Widya (`method = chhand`)

Indigenous Sindhi quantitative meter.

- Unit = **matra** (time).
- Laghoo (short) = 1, Guroo (long) = 2.
- Show the number on each tile.
- Header shows first-line total.

Used for bait, doha, soratha, waai, kafi.

### علم عروض / Ilm Arooz (`method = arooz`)

Perso-Arabic meter (ghazal, nazm).

- Same syllable cut as Chhand today.
- Tiles show `-` (long) and `v` (short), not 1/2.
- Header is generic: “detected meter (auto)”. It does **not** yet name a beher (Hazaj, Ramal, etc.) or map to Arkan (`فعولن`, `فاعلاتن`).

Do not invent a named-beher API. Match the website.

---



## 7. Copy (Sindhi / English)


| Key             | `sd`                                                 | `en`                                                                                                           |
| --------------- | ---------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- |
| Home title      | علم عروض ۽ ڇند وديا                                  | Sindhi Prosody (Arooz & Chhand)                                                                                |
| Home intro      | شاعريءَ جي فني بنيادن، ماترائن، بحرن ۽ تال کي سمجهو. | Understand the technical foundations of Sindhi poetry, from indigenous Chhand Widya to classical Ilm-ul-Arooz. |
| Banner title    | شاعري جي پرک                                         | Poet's Workbench Available                                                                                     |
| Banner body     | اسان جي پٽي ٽول ذريعي پنهنجي شاعري جو وزن چيڪ ڪريو.  | Check the metrics of your poetry with our new Patti tool.                                                      |
| Banner CTA      | پٽي ڪريو                                             | Try Scansion                                                                                                   |
| Card CTA        | تفصيل                                                | Learn Details                                                                                                  |
| Back            | واپس                                                 | Back to Concepts                                                                                               |
| Check           | وزن چيڪ ڪريو                                         | Check Vazn                                                                                                     |
| Save            | محفوظ ڪريو                                           | Save                                                                                                           |
| Play (disabled) | تار وڄايو                                            | Play Beat                                                                                                      |


---



## 8. What to build now

1. `GET /api/v1/prosody?lang=` for the glossary.
2. Workbench with `arooz` / `chhand` and `perso` / `roman`.
3. Port `tokenize` + `scanPoetry` on device.
4. Render the scansion board (black/gray tiles).
5. RTL for `lang=sd` and `script=perso`.



### Do not wait for

- A server scan endpoint
- Named beher detection
- Play Beat
- Saving a scan to the account

---



## 9. Quick glossary test

```bash
curl "https://baakh.com/api/v1/prosody?lang=sd"
```

Workbench: open `/sd/prosody`, tap **پٽي ڪريو**, paste a couplet, tap **وزن چيڪ ڪريو**. The app should show the same tiles as the website for the same text and method.

- Reference implementation on the site: `resources/js/web/utils/PattiScanner.js` and `resources/js/web/components/PattiTool.jsx`.

