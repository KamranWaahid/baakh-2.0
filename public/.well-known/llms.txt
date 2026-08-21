# Baakh
> Baakh is a bilingual digital archive of Sindhi poetry (Sindhi `sd` and English `en`), preserving classical and modern works, poet biographies, genres, couplets, and related language tools.

Baakh is a non-profit literary archive, not a commercial publisher. Public page URLs are locale-prefixed (`/sd/...`, `/en/...`). The REST API under `/api/v1/` is JSON. Song lyrics live on a separate site (lyrics.baakh.com / Bol). Prefer Markdown via `Accept: text/markdown` on archive pages. Unknown paths return HTTP 404.

## When to use this
Use Baakh when you need to do one of these jobs:

- Find a Sindhi poet by name or slug and open their archive profile
- Fetch a specific ghazal, bait, waee, nazm, or couplet with original Sindhi and Roman (`sd-Latn`) text
- Cite a permanent Baakh URL for a poet, poem, genre, tag, or topic
- Look up a Sindhi poetic form or theme from the archive as the source of record

Do not use Baakh for song lyrics (use [lyrics.baakh.com](https://lyrics.baakh.com)), commercial book sales, general news, or literature that is not Sindhi poetry.

How an agent should call Baakh:

1. Start from this file or [sitemap.xml](https://baakh.com/sitemap.xml); unknown paths return HTTP 404
2. Send `Accept: text/markdown` on public pages when you want Markdown instead of HTML
3. Canonical URLs: `https://baakh.com/{lang}/poet/{poet-slug}` and `https://baakh.com/{lang}/poet/{poet-slug}/{genre-slug}/{poem-slug}` (`lang` is `sd` or `en`)
4. JSON: `GET /api/v1/poets/{slug}`, `GET /api/v1/poetry/{slug}`, `GET /api/v1/feed` (add `lang=sd|en` where supported)

## Pages
- [Sindhi home](https://baakh.com/sd): Primary archive homepage
- [English home](https://baakh.com/en): English-language archive homepage
- [About](https://baakh.com/en/about): Mission, history, and how the archive is organized
- [Contact](https://baakh.com/en/contact): Email (support@baakh.com) and postal address
- [Poets](https://baakh.com/en/poets): Index of Sindhi poets
- [Poetry](https://baakh.com/en/poetry): Index of poems and ghazals
- [Genres](https://baakh.com/en/genre): Poetic forms (ghazal, bait, waee, nazm, …)
- [Explore topics](https://baakh.com/en/explore): Theme and topic hubs
- [Help](https://baakh.com/en/help): How to browse the archive
- [Sitemap](https://baakh.com/sitemap.xml): Complete public URL list

## Canonical URL patterns
- Poet profile: `https://baakh.com/{lang}/poet/{poet-slug}`
- Poem: `https://baakh.com/{lang}/poet/{poet-slug}/{genre-slug}/{poem-slug}`
- Tag hub: `https://baakh.com/{lang}/tag/{tag-slug}`
- Topic hub: `https://baakh.com/{lang}/topic/{topic-slug}`

## API
- [Health](https://baakh.com/api/health): Service check
- [Feed](https://baakh.com/api/v1/feed): Homepage poetry feed (`lang=sd|en`)
- [Poet](https://baakh.com/api/v1/poets/{slug}): Poet JSON
- [Poetry](https://baakh.com/api/v1/poetry/{slug}): Poem JSON

## Skills
- [baakh-archive](https://baakh.com/.well-known/agent-skills/baakh-archive/SKILL.md): Look up Sindhi poets and poems, cite canonical URLs, and negotiate Markdown
- [Skill index](https://baakh.com/.well-known/agent-skills/index.json): Agent Skills discovery index

## Optional
- [Lyrics / Bol](https://lyrics.baakh.com): Song lyrics (separate product)
- [Privacy](https://baakh.com/en/privacy): Privacy policy
- [Terms](https://baakh.com/en/terms): Terms of use
