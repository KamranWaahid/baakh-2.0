/** Song-structure section labels (Genius-style → Baakh parts). */

export const SECTION_META = {
    intro: { label: 'Intro', kind: 'sung', role: 'intro' },
    verse_1: { label: 'Verse 1', kind: 'sung', role: 'body' },
    verse_2: { label: 'Verse 2', kind: 'sung', role: 'body' },
    verse_3: { label: 'Verse 3', kind: 'sung', role: 'body' },
    verse_4: { label: 'Verse 4', kind: 'sung', role: 'body' },
    pre_chorus: { label: 'Pre-Chorus', kind: 'sung', role: 'mid' },
    chorus: { label: 'Chorus', kind: 'sung', role: 'body' },
    post_chorus: { label: 'Post-Chorus', kind: 'sung', role: 'mid' },
    bridge: { label: 'Bridge', kind: 'sung', role: 'mid' },
    instrumental: { label: 'Instrumental', kind: 'music', role: 'mid' },
    interlude: { label: 'Interlude', kind: 'music', role: 'mid' },
    solo: { label: 'Solo', kind: 'music', role: 'mid' },
    spoken: { label: 'Spoken', kind: 'spoken', role: 'body' },
    outro: { label: 'Outro', kind: 'sung', role: 'outro' },
    other: { label: 'Other', kind: 'other', role: 'other' },
};

export const LYRICS_SCHEMA = 'baakh.lyrics.editor_json.v1';

export const AI_LYRICS_PROMPT = `Format these song lyrics into Baakh lyrics JSON.

Return ONLY one valid JSON object. No markdown fences. No explanation before or after.

Keep this exact shape and keys:
_schema, _instructions, lyrics_title, roman_title, parts

Rules for parts[]:
- Split the song by LOGICAL structure, not by how a singer ad-libs.
- Always write full chorus text every time it appears (never "(Repeat Chorus)").
- Keep repeated lines exactly as sung (do not write ×3).
- Keep meaningful vocalizations (آ… / Ah… / Oh…). Drop pure noise.
- One blank line between sections in text_sd / text_roman (use \\n between lines).
- Prefer Sindhi Arabic script in text_sd; optional Roman/English in text_roman.
- Do NOT put metadata (artist, album, year, theme commentary) inside parts.

Each part object:
{
  "sort_order": 0,
  "section": "intro|verse_1|verse_2|verse_3|verse_4|pre_chorus|chorus|post_chorus|bridge|instrumental|interlude|solo|spoken|outro|other",
  "kind": "sung|couplet|spoken|explanation|music|other",
  "role": "intro|mid|body|outro|other",
  "relation": "original",
  "text_sd": "line1\\nline2",
  "text_roman": ""
}

Section → kind/role mapping:
- intro → kind sung, role intro
- verse_* → kind sung, role body
- chorus / pre_chorus / post_chorus → kind sung, role body/mid
- bridge → kind sung, role mid
- instrumental / interlude / solo → kind music, role mid (text can be ♪ موسيقي شروع)
- spoken → kind spoken, role body
- outro → kind sung, role outro

Example structure order:
[Intro] → [Verse 1] → [Chorus] → [Verse 2] → [Chorus] → [Bridge] → [Chorus] → [Outro]

JSON:
`;

export function extractJsonObject(raw) {
    const text = String(raw || '').trim();
    if (!text) throw new Error('Paste JSON first.');

    try {
        return JSON.parse(text);
    } catch {
        // fall through
    }

    const fence = text.match(/```(?:json)?\s*([\s\S]*?)```/i);
    if (fence?.[1]) {
        return JSON.parse(fence[1].trim());
    }

    const start = text.indexOf('{');
    const end = text.lastIndexOf('}');
    if (start >= 0 && end > start) {
        return JSON.parse(text.slice(start, end + 1));
    }

    throw new Error('Invalid JSON. Fix the syntax and try again.');
}

function normalizeSectionKey(raw) {
    const s = String(raw || '')
        .trim()
        .toLowerCase()
        .replace(/[\[\]]/g, '')
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ');

    if (!s) return 'other';

    if (/^(intro|افتتاح)/.test(s)) return 'intro';
    if (/^(outro|ending|آخري)/.test(s)) return 'outro';
    if (/^(pre[-\s]?chorus)/.test(s)) return 'pre_chorus';
    if (/^(post[-\s]?chorus)/.test(s)) return 'post_chorus';
    if (/^(chorus|hook|refrain|مُکڙو|مکرر)/.test(s)) return 'chorus';
    if (/^(bridge)/.test(s)) return 'bridge';
    if (/^(instrumental|music|موسيقي)/.test(s)) return 'instrumental';
    if (/^(interlude)/.test(s)) return 'interlude';
    if (/^(solo)/.test(s)) return 'solo';
    if (/^(spoken|dialogue|spoken word)/.test(s)) return 'spoken';

    const verse = s.match(/^(verse|انترو|بند)\s*(\d+)?/);
    if (verse) {
        const n = Math.min(Math.max(parseInt(verse[2] || '1', 10), 1), 4);
        return `verse_${n}`;
    }

    return 'other';
}

/**
 * Parse Genius-style labeled lyrics into Baakh parts.
 * Accepts headers like [Chorus], [Verse 1], مُکڙو / Chorus, etc.
 */
export function parseStructuredLyricsText(raw) {
    const text = String(raw || '').replace(/\r\n/g, '\n').trim();
    if (!text) return [];

    const lines = text.split('\n');
    const parts = [];
    let current = null;

    const headerRe = /^\s*(?:\[([^\]]+)\]|#{1,3}\s*(.+)|(مُکڙو|افتتاح|انترو\s*\d*|آخري\s*مُکڙو)(?:\s*[\/\-–—]\s*.+)?)\s*$/i;

    const flush = () => {
        if (!current) return;
        const body = current.lines.join('\n').trim();
        if (!body && current.section !== 'instrumental' && current.section !== 'interlude' && current.section !== 'solo') {
            current = null;
            return;
        }
        const meta = SECTION_META[current.section] || SECTION_META.other;
        parts.push({
            section: current.section,
            kind: meta.kind,
            role: meta.role,
            relation: 'original',
            text_sd: body || (meta.kind === 'music' ? '♪ موسيقي شروع' : ''),
            text_roman: meta.kind === 'music' && !body ? '♪ Music starts' : '',
        });
        current = null;
    };

    for (const line of lines) {
        const m = line.match(headerRe);
        if (m) {
            flush();
            const label = (m[1] || m[2] || m[3] || '').trim();
            current = { section: normalizeSectionKey(label), lines: [] };
            continue;
        }
        if (!current) {
            current = { section: 'verse_1', lines: [] };
        }
        current.lines.push(line);
    }
    flush();

    return parts.map((p, i) => ({ ...p, sort_order: i }));
}

export function buildEditorJson({
    id = null,
    lyrics_title = '',
    roman_title = '',
    parts = [],
} = {}) {
    return {
        _schema: LYRICS_SCHEMA,
        _instructions:
            'Format lyrics into ordered parts with section labels. Keep full repeated choruses. Use text_sd for Sindhi. Return the same JSON shape.',
        ...(id ? { id } : {}),
        lyrics_title: lyrics_title || '',
        roman_title: roman_title || '',
        parts: (parts || []).map((p, i) => ({
            sort_order: i,
            section: p.section || null,
            kind: p.kind || 'sung',
            role: p.role || 'body',
            relation: p.relation || 'original',
            text_sd: p.text_sd || '',
            text_roman: p.text_roman || '',
        })),
    };
}

export function normalizeImportedParts(parts, makeKey) {
    if (!Array.isArray(parts) || parts.length === 0) {
        throw new Error('JSON must include a non-empty parts array.');
    }

    return parts.map((p, i) => {
        const section = p.section ? normalizeSectionKey(p.section) : null;
        const meta = section ? SECTION_META[section] : null;
        const kind = p.kind || meta?.kind || 'sung';
        const role = p.role || meta?.role || (kind === 'music' ? 'mid' : 'body');
        let text_sd = (p.text_sd || '').trim();
        let text_roman = (p.text_roman || '').trim();
        if (kind === 'music' && !text_sd && !text_roman) {
            text_sd = '♪ موسيقي شروع';
            text_roman = '♪ Music starts';
        }
        return {
            _key: makeKey ? makeKey(i) : `import-${Date.now()}-${i}`,
            kind,
            section: section || null,
            role,
            relation: p.relation || 'original',
            poet_id: p.poet_id?.toString?.() || '',
            poetry_id: p.poetry_id?.toString?.() || '',
            poetry_title: p.poetry_title || '',
            couplet_id: p.couplet_id?.toString?.() || '',
            source_lyrics_id: p.source_lyrics_id?.toString?.() || '',
            source_lyrics_title: p.source_lyrics_title || '',
            source_part_id: p.source_part_id?.toString?.() || '',
            text_sd,
            text_roman,
        };
    }).filter((p) => p.text_sd || p.text_roman || p.kind === 'music');
}
