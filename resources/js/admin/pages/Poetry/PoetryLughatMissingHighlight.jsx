import React, { useMemo } from 'react';
import { Eye, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

/** Arabic/Latin punctuation & symbols that must not join a Lughat surface. */
const PUNCT_CLASS = '[\\u060C\\u061B\\u061F\\u06D4\\u0640\\u00AB\\u00BB\\u2039\\u203A\\u2018-\\u201F\\p{P}\\p{S}]';

function stripPunctuation(raw = '') {
    return String(raw)
        .replace(new RegExp(`${PUNCT_CLASS}+`, 'gu'), '')
        .trim();
}

/** Split "پل،" → { lead: '', word: 'پل', trail: '،' } */
function splitAffixedPunctuation(raw = '') {
    const text = String(raw);
    const re = new RegExp(`^(${PUNCT_CLASS}*)(.*?)(${PUNCT_CLASS}*)$`, 'u');
    const m = text.match(re);
    if (!m) {
        return { lead: '', word: text, trail: '' };
    }
    return { lead: m[1] || '', word: m[2] || '', trail: m[3] || '' };
}

/** Identity key — keeps zer/zabar/pesh so نَھن ≠ نُھن. */
function normalizeForIdentity(raw = '') {
    return stripPunctuation(raw).replace(/\s+/gu, ' ').trim().toLowerCase();
}

function isSindhiToken(token = '') {
    return /[\u0600-\u06FF\u0750-\u077F]/.test(token);
}

/**
 * Highlight poetry tokens that are missing from Baakh Lughat or lack Roman.
 * Eye opens the same AI JSON modal used on Lughat Home.
 */
export default function PoetryLughatMissingHighlight({
    content,
    unresolvedWords = [],
    contentStyle = 'center',
    openingSurface = null,
    onOpenWord,
}) {
    const unresolvedMap = useMemo(() => {
        const map = new Map();
        for (const w of unresolvedWords) {
            const key = w?.normalized || w?.surface;
            if (key) map.set(String(key).replace(/\s+/gu, ' ').trim().toLowerCase(), w);
        }
        return map;
    }, [unresolvedWords]);

    const alignClass =
        contentStyle === 'center' ? 'text-center'
            : contentStyle === 'start' || contentStyle === 'right' ? 'text-right'
                : contentStyle === 'end' || contentStyle === 'left' ? 'text-left'
                    : 'text-justify';

    const couplets = useMemo(() => {
        return String(content || '')
            .split(/\n\s*\n/)
            .map((text) => text.trim())
            .filter((text) => text.length > 0);
    }, [content]);

    const renderLine = (text, keyPrefix) => {
        const parts = String(text).split(/(\s+)/u);
        return parts.map((part, i) => {
            if (/^\s+$/u.test(part)) {
                return <span key={`${keyPrefix}-s-${i}`}>{part}</span>;
            }

            const { lead, word, trail } = splitAffixedPunctuation(part);
            const surface = stripPunctuation(word);
            if (!surface || !isSindhiToken(surface)) {
                return <span key={`${keyPrefix}-o-${i}`}>{part}</span>;
            }

            const identity = normalizeForIdentity(surface);
            const info = unresolvedMap.get(identity);
            if (!info) {
                return <span key={`${keyPrefix}-w-${i}`}>{part}</span>;
            }

            const isMissingWord = info.status === 'missing_word';
            const isAmbiguous = info.status === 'ambiguous';
            const busy = openingSurface === info.surface;
            const label = info.surface || surface;

            return (
                <span key={`${keyPrefix}-m-${i}`} className="inline">
                    {lead}
                    <button
                        type="button"
                        onClick={() => onOpenWord?.(info)}
                        disabled={busy}
                        title={
                            isMissingWord ? 'Missing from Baakh Lughat — view AI JSON'
                                : isAmbiguous ? 'Ambiguous without zer/zabar/pesh — pick or add the marked form'
                                    : 'No Roman spelling — view AI JSON'
                        }
                        className={cn(
                            'mx-[1px] inline-flex items-baseline gap-1 rounded px-0.5 border-b-2 transition-colors align-baseline',
                            isMissingWord
                                ? 'bg-red-50 border-red-400 text-red-950 hover:bg-red-100/80'
                                : isAmbiguous
                                    ? 'bg-violet-50 border-violet-400 text-violet-950 hover:bg-violet-100/80'
                                    : 'bg-amber-50 border-amber-400 text-amber-950 hover:bg-amber-100/80'
                        )}
                    >
                        <span>{label}</span>
                        {busy
                            ? <Loader2 className="inline h-3 w-3 animate-spin opacity-70" />
                            : <Eye className="inline h-3 w-3 opacity-70" />}
                    </button>
                    {trail}
                </span>
            );
        });
    };

    if (couplets.length === 0) {
        return (
            <p className="text-muted-foreground/40 text-lg min-h-[280px]">
                پهرين شاعري لکو…
            </p>
        );
    }

    return (
        <div
            dir="rtl"
            lang="sd"
            className={cn(
                'min-h-[280px] h-auto space-y-8 text-2xl leading-relaxed font-arabic',
                alignClass
            )}
        >
            {couplets.map((couplet, ci) => (
                <div key={ci} className="space-y-1">
                    {couplet.split('\n').map((line, li) => (
                        <p key={`${ci}-${li}`} className="whitespace-pre-wrap">
                            {renderLine(line, `${ci}-${li}`)}
                        </p>
                    ))}
                </div>
            ))}
        </div>
    );
}
