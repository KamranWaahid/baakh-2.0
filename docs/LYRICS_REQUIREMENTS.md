# Baakh Lyrics (ٻول) — Project Requirements Brief

Use this document as source material when writing AI prompts for design, copy, code, or marketing. Do **not** mix this product into the main Baakh poetry archive UI.

---

## 1. Product one-liner

**ٻول (Bol)** is Baakh’s **separate Sindhi song-lyrics product**: admin CRUD for songs and artists in this repo, plus a **public site on `lyrics.baakh.com`**.

- **Admin + API + DB:** this repo (`baakh-2.0`)
- **Public frontend:** separate repo [`baakh-bol`](https://github.com/KamranWaahid/baakh-bol) (Vite/React). A Laravel-served SPA still exists under `/lyrics-site` for local preview / transitional hosting.

---

## 2. Brand & naming

| Context | Name |
|--------|------|
| Sindhi brand | **ٻول** |
| English / roman | **Bol** (or “Baakh Lyrics” in subtitles) |
| Parent brand | **Baakh** (archive link-out only) |
| Public host | `https://lyrics.baakh.com` |
| Archive host | `https://baakh.com` (do not restyle or fold lyrics into archive layouts) |

- UI chrome language: mostly **English** in admin; public site supports **Sindhi (`sd`) and English (`en`)** via `/sd` and `/en`.
- Sindhi writing appears in content fields and public song/artist text.
- Nav label on archive for lyrics (if any) should deep-link / redirect to the lyrics site, not open archive chrome.

---

## 3. Separation rules (critical)

1. **Separate public SPA** — own Vite entry, own blade shell, own routes/layout. Not nested under archive `MainLayout` (no archive sidebars, feed, poet nav).
2. **Same Laravel backend** — shared DB, shared `/api/v1` APIs, same deploy tree; subdomain points at same `public_html`.
3. **Do not change archive typography/layout** when styling lyrics (lyrics uses its own CSS scope, e.g. `.lyrics-site`).
4. Local preview without DNS: `/lyrics-site/sd` (and `/lyrics-site/en/...`).
5. Archive path `/{lang}/lyrics` redirects to the lyrics site.

---

## 4. Domain model

### 4.1 Singer / Artist (فنڪار)

- Profile: Sindhi + roman name, laqab/stage name, tagline, bio, birth/death place & dates, slug, photo, visibility, featured.
- Soft-delete / trash in admin.
- Public: only `visibility = true`.

### 4.2 Lyrics / Song (گيت / ٻول)

- Work-level: title (sd + roman), slug, optional notes/source, tags, visibility, featured, content style.
- **Cover image**.
- Optional **music**: URL, title, type (`youtube` | `audio` | `other`).
- Optional link to one **singer**.
- Soft-delete / trash in admin.
- Public: only visible songs.

### 4.3 Song parts (ordered timeline)

Each song is an ordered list of parts. Structure content by **logical song sections**, not by performance ad-libs.

| Kind | Purpose |
|------|---------|
| `sung` | Sung lyric lines |
| `couplet` | Couplet-style segment (often from poetry) |
| `spoken` | Spoken bridge |
| `explanation` | Singer explaining a line |
| `music` | Music cue / instrumental |
| `other` | Catch-all |

Optional per part:

- **Section** (logical label): `intro`, `verse_1`…`verse_4`, `pre_chorus`, `chorus`, `post_chorus`, `bridge`, `instrumental`, `interlude`, `solo`, `spoken`, `outro`, `other`
- **Role**: `intro` | `mid` | `body` | `outro` | `other`
- **Relation** to source: `exact` | `adapted` | `inspired` | `original` | `unknown`
- Text: `text_sd`, `text_roman`
- Links: poet, poetry work, couplet, **or another lyrics work / part** (reuse like couplet picking)

**Formatting rules when adding lyrics**

- Prefer Genius-style headers: `[Intro]`, `[Verse 1]`, `[Chorus]`, `[Bridge]`, `[Outro]`, `[Instrumental]`.
- Always write the full chorus each time it appears (never “Repeat Chorus”).
- Keep repeated lines as sung; keep meaningful vocalizations (`آ…`); drop pure noise.
- Metadata (artist, album, theme notes) stays in separate fields — not inside part text.

**Admin JSON helper** (`Lyrics JSON` on create/edit)

- Copy for AI → paste ChatGPT reply via Input JSON, or paste labeled lyrics text.
- Schema: `baakh.lyrics.editor_json.v1` with `lyrics_title`, `roman_title`, `parts[]`.

---

## 5. Admin requirements (`/admin`)

### 5.1 Navigation

Under **Music**:

- **Artists** → singers list/CRUD  
- **Lyrics** → lyrics list/CRUD  

### 5.2 Lyrics editor

Single-page create/edit (poetry-like):

- Sindhi / Roman script tabs; auto-romanizer when useful.
- Parts timeline: add, reorder, insert music cue; **first part cannot be deleted**.
- Per sung/couplet part: **Link poetry** (search → pick couplet, insert as couplet parts, or full poem text) and **Link lyrics** (search other songs → pick part or whole song text).
- Song-level optional **Full poetry**: attach a complete archive poem (`lyrics.poetry_id`). Public song page shows lyric parts **and** the full poem couplets when set.
- Music card (URL / title / type) + YouTube preview when applicable.
- Cover image upload / change / remove.
- Singer picker + “Add new singer” dialog (full profile fields).
- Publishing: visibility, featured, slug, notes, source.

### 5.3 Artists admin

Full CRUD: list, search, create, edit, trash/restore/permanent delete, visibility/featured toggles, image, bilingual fields.

### 5.4 Permissions

Seeded permissions such as `view_lyrics`, `create_lyrics`, `edit_lyrics`, `delete_lyrics`, and parallel `*_singers` permissions.

---

## 6. Public site requirements (`lyrics.baakh.com`)

### 6.1 Pages

| Route pattern | Purpose |
|---------------|---------|
| `/{lang}` | Home: brand hero **ٻول**, search, featured song, song grid |
| `/{lang}/artists` | Artist grid |
| `/{lang}/artist/{slug}` | Artist profile + their songs |
| `/{lang}/song/{slug}` | Song detail: cover, artist link, music embed/link, ordered parts |

Language toggle sd ↔ en; outbound link to Baakh **Archive** (external).

### 6.2 Public APIs

- `GET /api/v1/lyrics` — list (search, singer filter, featured, pagination); visibility only  
- `GET /api/v1/lyrics/{slug}` — detail + parts  
- `GET /api/v1/singers` — list  
- `GET /api/v1/singers/{slug}` — detail  
- `GET /api/v1/singers/{slug}/lyrics` — that artist’s songs  

Locale via `lang` query / `Accept-Language` (`sd` default).

### 6.3 Visual / UX direction (public only)

- Distinct from poetry archive; **not** a dashboard.
- First viewport: **brand-first** (ٻول as hero), one short line, search/CTA, one featured visual — avoid clutter (no stats strips, no archive sidebars).
- Atmosphere: soft paper / ink / forest accents (avoid generic purple gradients and “AI cream + terracotta” clichés).
- **Sindhi typography**: SF Arabic design language — prefer `SF Arabic` on Apple; web fallback **IBM Plex Sans Arabic** (or equivalent modern UI Arabic). Latin UI may use a clean geometric sans (DM Sans / SF Pro–like).
- Motion: subtle, purposeful (2–3 intentional motions max if adding animation).
- Responsive: mobile-first; no horizontal page scroll from chrome.

### 6.4 SEO

Lyrics SPA controller sets title/description/OG per home, song, and artist; song OG may use cover image when present.

---

## 7. Deploy / ops

- cPanel: subdomain `lyrics.baakh.com` → **same** document root as `baakh.com`.
- Env: `LYRICS_URL`, `LYRICS_HOSTS`; CORS must allow `https://lyrics.baakh.com`.
- After deploy: migrate lyrics tables, seed permissions, `npm run build` (includes `resources/js/lyrics/main.jsx`).
- Vite inputs include lyrics entry alongside web + admin.

---

## 8. Non-goals

- Not a replacement for the poetry archive.
- Not required to embed full archive poet/poem browsers inside lyrics chrome (linking/attribution is enough).
- No requirement that public lyrics live under `baakh.com/{lang}/lyrics` as an in-archive section.
- Admin Hesudhar / dictionary / poets tooling is out of scope except incidental shared UI fixes.

---

## 9. User journeys (for prompt scenarios)

1. **Editor** creates a singer, then a song with intro couplet (linked from poetry), music cue, sung verses, optional spoken line; uploads cover; pastes YouTube URL; publishes.  
2. **Listener** opens `lyrics.baakh.com/sd`, searches a title, opens song, reads Sindhi lines, plays embed, taps artist, browses more songs.  
3. **Bilingual user** switches to `/en` and sees roman titles/text where available.  
4. **Archive visitor** hitting old `/sd/lyrics` lands on the Bol site, not the archive feed.

---

## 10. Glossary

| Term | Meaning |
|------|---------|
| ٻول / Bol | Product name for lyrics |
| فنڪار / Artist | Singer entity |
| گيت / Song | Lyrics work |
| Part | One ordered segment of a song |
| Archive | Main Baakh poetry site |
| Music (admin nav) | Parent of Artists + Lyrics |

---

## 11. Suggested AI prompt starters (optional)

**Design:**  
“Using the Baakh Bol requirements, redesign only the public lyrics.baakh.com home and song detail. Brand ٻول as the hero. SF Arabic–like Sindhi type. Do not reference or restyle the poetry archive layout.”

**Copy:**  
“Write Sindhi + English microcopy for Bol public nav, empty states, and song part labels (sung, spoken, explanation, music cue).”

**Engineering:**  
“Implement/extend only the lyrics public SPA and `/api/v1/lyrics|singers` contracts described in the Bol requirements. Do not modify archive MainLayout or archive fonts.”

**QA:**  
“Test plan for Bol: admin publish → public list/detail; subdomain vs `/lyrics-site` local; visibility hidden songs excluded; cover + YouTube; poetry/lyrics link flows.”
