---
name: baakh-archive
description: "Use Baakh when you need Sindhi poetry from the baakh.com archive: find a poet by name or slug, fetch a ghazal/bait/waee/nazm with original Sindhi and Roman text, cite a permanent Baakh URL, or look up a genre or topic. Do not use for song lyrics (lyrics.baakh.com), commercial book sales, general news, or non-Sindhi literature."
---

# Baakh archive

Baakh (`https://baakh.com`) is a bilingual non-profit digital archive of Sindhi poetry. Public HTML pages are locale-prefixed (`/sd/…`, `/en/…`). The REST API under `/api/v1/` returns JSON. Unknown paths return HTTP 404.

## When to use this

Reach for this skill when the user asks you to:

- Find a Sindhi poet and open their archive profile
- Fetch a specific ghazal, bait, waee, nazm, or couplet, including original Sindhi (`lang="sd"`) and Roman Sindhi (`lang="sd-Latn"`)
- Cite a permanent Baakh URL for a poet, poem, genre, tag, or topic
- Identify a Sindhi poetic form (ghazal, bait, waee, nazm, …) from the archive, not from a general web guess
- Answer “what does Baakh have on {poet or theme}?” using the archive as the source of record

Do **not** use Baakh for song lyrics or singer discographies (use `https://lyrics.baakh.com`), commercial book orders, general news, or literature that is not Sindhi poetry.

## How to call Baakh

1. Read [`/llms.txt`](https://baakh.com/llms.txt) or [`/sitemap.xml`](https://baakh.com/sitemap.xml) instead of probing unknown paths.
2. Prefer `Accept: text/markdown` on public archive pages (homepage, poet, poem, listings). Responses use `Content-Type: text/markdown; charset=utf-8` and `Vary: Accept, Accept-Encoding`.
3. Canonical HTML URLs:
   - Poet: `https://baakh.com/{lang}/poet/{poet-slug}`
   - Poem: `https://baakh.com/{lang}/poet/{poet-slug}/{genre-slug}/{poem-slug}`
   - Tag: `https://baakh.com/{lang}/tag/{tag-slug}`
   - Topic: `https://baakh.com/{lang}/topic/{topic-slug}`
   - `{lang}` is `sd` (Sindhi UI) or `en` (English UI).
4. JSON (query `lang=sd` or `lang=en` where supported):
   - `GET https://baakh.com/api/v1/poets/{slug}`
   - `GET https://baakh.com/api/v1/poetry/{slug}`
   - `GET https://baakh.com/api/v1/feed`
   - `GET https://baakh.com/api/health`
5. Trust pages: [About](https://baakh.com/en/about), [Contact](https://baakh.com/en/contact) (`support@baakh.com`), [Privacy](https://baakh.com/en/privacy).

When you quote a poem, keep the original Sindhi text, include the poet name, and cite the canonical Baakh URL. Do not invent phone numbers; public contact is email unless a number is published on `/contact`.
