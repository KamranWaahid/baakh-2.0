import React, { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Loader2, ArrowUp, BookOpen, Link2, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const KASRA = '\u0650';

function cleanToken(raw = '') {
    return String(raw)
        .replace(/[\u060C\u061B\u061F\u06D4\u0640\u00AB\u00BB\u2018-\u201F\p{P}\p{S}]+/gu, '')
        .trim();
}

function isSindhiToken(token = '') {
    return /[\u0600-\u06FF\u0750-\u077F]/.test(token);
}

function hasTrailingKasra(token = '') {
    return String(token).endsWith(KASRA);
}

function senseLabel(sense) {
    return sense?.short_gloss
        || sense?.definition_sd
        || sense?.definition
        || sense?.definition_en
        || (Array.isArray(sense?.english_equivalents) ? sense.english_equivalents.join(', ') : '')
        || '—';
}

function annotationKey(coupletIndex, tokenIndex) {
    return `${coupletIndex}:${tokenIndex}`;
}

function expressionKey(coupletIndex, start, end) {
    return `${coupletIndex}:${start}-${end}`;
}

/**
 * Clickable couplet tokens → pick Baakh Lughat sense OR multiword expression (جامِ محبت).
 */
export default function PoetryLughatSensePicker({
    content,
    poetryId = null,
    annotations = [],
    onChange,
    expressionAnnotations = [],
    onExpressionChange,
    contentStyle = 'center',
}) {
    const [active, setActive] = useState(null); // word sense
    const [activeExpr, setActiveExpr] = useState(null); // { coupletIndex, start, end, surface, type }
    const [note, setNote] = useState('');
    const [promote, setPromote] = useState(true);
    const [literalGloss, setLiteralGloss] = useState('');
    const [poeticGloss, setPoeticGloss] = useState('');
    const [exprNote, setExprNote] = useState('');
    const [exprType, setExprType] = useState('izafat');
    const [rangeAnchor, setRangeAnchor] = useState(null); // { coupletIndex, tokenIndex, surface }

    const annotationMap = useMemo(() => {
        const map = new Map();
        for (const a of annotations) {
            map.set(annotationKey(a.couplet_index, a.token_index), a);
        }
        return map;
    }, [annotations]);

    const expressionMap = useMemo(() => {
        const map = new Map();
        for (const a of expressionAnnotations) {
            map.set(expressionKey(a.couplet_index, a.start_token_index, a.end_token_index), a);
        }
        return map;
    }, [expressionAnnotations]);

    const couplets = useMemo(() => {
        return String(content || '')
            .split(/\n\s*\n/)
            .map((text) => text.trim())
            .filter((text) => text.length > 0)
            .map((text, coupletIndex) => {
                const parts = text.split(/(\s+)/u);
                let tokenIndex = 0;
                const wordTokens = [];
                const tokens = parts.map((part) => {
                    if (/^\s+$/u.test(part)) {
                        return { type: 'space', text: part };
                    }
                    const surface = cleanToken(part);
                    if (!surface || !isSindhiToken(surface)) {
                        return { type: 'other', text: part };
                    }
                    const idx = tokenIndex;
                    tokenIndex += 1;
                    const word = {
                        type: 'word',
                        text: part,
                        surface,
                        tokenIndex: idx,
                        coupletIndex,
                    };
                    wordTokens.push(word);
                    return word;
                });
                return { coupletIndex, text, tokens, wordTokens };
            });
    }, [content]);

    const { data: lookup, isFetching } = useQuery({
        queryKey: ['poetry-lughat-senses', active?.surface, poetryId],
        queryFn: async () => {
            const res = await api.get('/api/admin/poetry/lughat-senses', {
                params: { q: active.surface, poetry_id: poetryId || undefined },
            });
            return res.data;
        },
        enabled: !!active?.surface,
    });

    const { data: exprLookup, isFetching: exprFetching } = useQuery({
        queryKey: ['poetry-lughat-expressions', activeExpr?.surface],
        queryFn: async () => {
            const res = await api.get('/api/admin/poetry/lughat-expressions', {
                params: { q: activeExpr.surface },
            });
            return res.data;
        },
        enabled: !!activeExpr?.surface,
    });

    const alignClass =
        contentStyle === 'center' ? 'text-center'
            : contentStyle === 'start' || contentStyle === 'right' ? 'text-right'
                : contentStyle === 'end' || contentStyle === 'left' ? 'text-left'
                    : 'text-justify';

    const openExpression = ({ coupletIndex, start, end, surface, type = 'izafat' }) => {
        const existing = expressionMap.get(expressionKey(coupletIndex, start, end));
        setActiveExpr({ coupletIndex, start, end, surface, type: existing?.expression_type || type });
        setLiteralGloss(existing?.literal_gloss || '');
        setPoeticGloss(existing?.poetic_gloss || '');
        setExprNote(existing?.note || '');
        setExprType(existing?.expression_type || type);
        setRangeAnchor(null);
        setActive(null);
    };

    const openWord = (token, ann) => {
        setActive({
            coupletIndex: token.coupletIndex,
            tokenIndex: token.tokenIndex,
            surface: token.surface,
            nextSurface: null,
        });
        // attach next surface for "make expression" shortcut
        const couplet = couplets.find((c) => c.coupletIndex === token.coupletIndex);
        const next = couplet?.wordTokens?.find((w) => w.tokenIndex === token.tokenIndex + 1);
        setActive({
            coupletIndex: token.coupletIndex,
            tokenIndex: token.tokenIndex,
            surface: token.surface,
            nextSurface: next?.surface || null,
            nextText: next?.text || null,
        });
        setNote(ann?.note || '');
        setPromote(ann?.promote !== false);
        setRangeAnchor(null);
        setActiveExpr(null);
    };

    const selectSense = (sense) => {
        if (!active || !sense) return;
        const next = {
            couplet_index: active.coupletIndex,
            token_index: active.tokenIndex,
            surface_form: active.surface,
            sense_id: sense.id,
            lemma_id: lookup?.lemma?.id ?? null,
            note: note.trim() || null,
            promote,
            sense: {
                id: sense.id,
                short_gloss: sense.short_gloss,
                definition: sense.definition,
                definition_sd: sense.definition_sd,
                definition_en: sense.definition_en,
            },
            lemma: lookup?.lemma?.lemma ?? active.surface,
        };

        const filtered = annotations.filter(
            (a) => !(a.couplet_index === next.couplet_index && a.token_index === next.token_index)
        );
        onChange([...filtered, next]);
        toast.success(promote
            ? 'Sense pinned for this line and moved to top in Baakh Lughat.'
            : 'Sense pinned for this line.');
        setActive(null);
        setNote('');
        setPromote(true);
    };

    const saveExpression = (fromMatch = null) => {
        if (!activeExpr) return;
        const surface = fromMatch?.expression?.expression || activeExpr.surface;
        const next = {
            couplet_index: activeExpr.coupletIndex,
            start_token_index: activeExpr.start,
            end_token_index: activeExpr.end,
            surface_text: surface,
            expression_type: fromMatch?.expression?.expression_type || exprType || 'izafat',
            expression_id: fromMatch?.expression?.id || null,
            literal_gloss: fromMatch?.expression?.literal_gloss || literalGloss.trim() || null,
            poetic_gloss: fromMatch?.expression?.poetic_gloss || poeticGloss.trim() || null,
            note: exprNote.trim() || null,
        };

        const filtered = expressionAnnotations.filter(
            (a) => !(
                a.couplet_index === next.couplet_index
                && a.start_token_index === next.start_token_index
                && a.end_token_index === next.end_token_index
            )
        );
        onExpressionChange([...filtered, next]);
        toast.success('Poetic expression pinned for this line.');
        setActiveExpr(null);
        setLiteralGloss('');
        setPoeticGloss('');
        setExprNote('');
        setExprType('izafat');
    };

    const clearAnnotation = (coupletIndex, tokenIndex) => {
        onChange(annotations.filter(
            (a) => !(a.couplet_index === coupletIndex && a.token_index === tokenIndex)
        ));
    };

    const clearExpression = (coupletIndex, start, end) => {
        onExpressionChange(expressionAnnotations.filter(
            (a) => !(
                a.couplet_index === coupletIndex
                && a.start_token_index === start
                && a.end_token_index === end
            )
        ));
    };

    const handleWordClick = (token, e) => {
        const couplet = couplets.find((c) => c.coupletIndex === token.coupletIndex);
        const words = couplet?.wordTokens || [];
        const ann = annotationMap.get(annotationKey(token.coupletIndex, token.tokenIndex));

        // Shift-click: link adjacent words into an expression
        if (e.shiftKey && rangeAnchor && rangeAnchor.coupletIndex === token.coupletIndex) {
            const start = Math.min(rangeAnchor.tokenIndex, token.tokenIndex);
            const end = Math.max(rangeAnchor.tokenIndex, token.tokenIndex);
            if (end > start) {
                const surfaces = words
                    .filter((w) => w.tokenIndex >= start && w.tokenIndex <= end)
                    .map((w) => w.surface);
                openExpression({
                    coupletIndex: token.coupletIndex,
                    start,
                    end,
                    surface: surfaces.join(' '),
                    type: end - start === 1 && hasTrailingKasra(surfaces[0]) ? 'izafat' : 'collocation',
                });
                return;
            }
        }

        // First click of a range (set anchor) when shift held alone
        if (e.shiftKey) {
            setRangeAnchor({
                coupletIndex: token.coupletIndex,
                tokenIndex: token.tokenIndex,
                surface: token.surface,
            });
            toast.message('Shift-click the next word to form an expression.');
            return;
        }

        // Izafat head: open expression with following word
        if (hasTrailingKasra(token.surface)) {
            const next = words.find((w) => w.tokenIndex === token.tokenIndex + 1);
            if (next) {
                openExpression({
                    coupletIndex: token.coupletIndex,
                    start: token.tokenIndex,
                    end: next.tokenIndex,
                    surface: `${token.surface} ${next.surface}`,
                    type: 'izafat',
                });
                return;
            }
        }

        openWord(token, ann);
    };

    const renderCoupletTokens = (couplet) => {
        const words = couplet.wordTokens;
        const covered = new Set();
        for (const a of expressionAnnotations) {
            if (a.couplet_index !== couplet.coupletIndex) continue;
            for (let t = a.start_token_index; t <= a.end_token_index; t++) {
                covered.add(t);
            }
        }

        // Precompute izafat suggestion starts (not already covered)
        const izafatStarts = new Set();
        for (const w of words) {
            if (covered.has(w.tokenIndex)) continue;
            if (!hasTrailingKasra(w.surface)) continue;
            const next = words.find((x) => x.tokenIndex === w.tokenIndex + 1);
            if (next && !covered.has(next.tokenIndex)) {
                izafatStarts.add(w.tokenIndex);
            }
        }

        const out = [];
        for (let i = 0; i < couplet.tokens.length; i++) {
            const token = couplet.tokens[i];
            if (token.type !== 'word') {
                out.push(<span key={i}>{token.text}</span>);
                continue;
            }
            if (covered.has(token.tokenIndex) && ![...expressionAnnotations].some(
                (a) => a.couplet_index === couplet.coupletIndex && a.start_token_index === token.tokenIndex
            )) {
                continue; // rendered as part of earlier expression span
            }

            const exprStart = expressionAnnotations.find(
                (a) => a.couplet_index === couplet.coupletIndex && a.start_token_index === token.tokenIndex
            );
            if (exprStart) {
                const spanWords = words.filter(
                    (w) => w.tokenIndex >= exprStart.start_token_index && w.tokenIndex <= exprStart.end_token_index
                );
                out.push(
                    <button
                        key={`expr-${i}`}
                        type="button"
                        onClick={() => openExpression({
                            coupletIndex: couplet.coupletIndex,
                            start: exprStart.start_token_index,
                            end: exprStart.end_token_index,
                            surface: exprStart.surface_text,
                            type: exprStart.expression_type || 'izafat',
                        })}
                        className="mx-[1px] rounded px-1 transition-colors border-b-2 border-violet-500 bg-violet-50 text-violet-950 hover:bg-violet-100/80"
                        title={exprStart.poetic_gloss || exprStart.literal_gloss || 'Poetic expression'}
                    >
                        {spanWords.map((w) => w.text).join(' ')}
                    </button>
                );
                continue;
            }

            if (izafatStarts.has(token.tokenIndex)) {
                const next = words.find((w) => w.tokenIndex === token.tokenIndex + 1);
                covered.add(next.tokenIndex);
                out.push(
                    <button
                        key={`iza-${i}`}
                        type="button"
                        onClick={() => openExpression({
                            coupletIndex: couplet.coupletIndex,
                            start: token.tokenIndex,
                            end: next.tokenIndex,
                            surface: `${token.surface} ${next.surface}`,
                            type: 'izafat',
                        })}
                        className="mx-[1px] rounded px-1 transition-colors border-b-2 border-dashed border-amber-500 bg-amber-50/70 text-amber-950 hover:bg-amber-100/80"
                        title="Poetic expression (izafat) — click to pin meaning"
                    >
                        {token.text}{' '}{next.text}
                        <Link2 className="inline h-3 w-3 mx-1 opacity-50 align-middle" />
                    </button>
                );
                continue;
            }

            if (covered.has(token.tokenIndex)) {
                continue;
            }

            const ann = annotationMap.get(annotationKey(token.coupletIndex, token.tokenIndex));
            const isAnchor = rangeAnchor
                && rangeAnchor.coupletIndex === token.coupletIndex
                && rangeAnchor.tokenIndex === token.tokenIndex;

            out.push(
                <button
                    key={i}
                    type="button"
                    onClick={(e) => handleWordClick(token, e)}
                    className={cn(
                        'mx-[1px] rounded px-0.5 transition-colors border-b-2 border-transparent hover:bg-amber-100/80 hover:border-amber-400',
                        ann && 'bg-emerald-50 border-emerald-400 text-emerald-900',
                        isAnchor && 'ring-2 ring-violet-400'
                    )}
                    title={ann ? senseLabel(ann.sense) : 'Pick sense · Shift+click for expression'}
                >
                    {token.text}
                </button>
            );
        }
        return out;
    };

    return (
        <div className="space-y-4">
            <div className="rounded-lg border border-amber-200/80 bg-amber-50/50 px-3 py-2 text-xs text-amber-900/80 space-y-1">
                <p>Click a word to pin its Baakh Lughat sense for this line.</p>
                <p>
                    Phrases like <span className="font-arabic">جامِ محبت</span> (izafat) appear underlined —
                    click to pin the poetic expression. Shift-click two words to link any collocation.
                </p>
            </div>

            <div
                dir="rtl"
                lang="sd"
                className={cn(
                    'min-h-[280px] h-auto space-y-8 text-2xl leading-relaxed font-arabic',
                    alignClass
                )}
            >
                {couplets.length === 0 ? (
                    <p className="text-muted-foreground/40 text-lg">پهرين شاعري لکو، پوءِ لفظ چونڊيو.</p>
                ) : couplets.map((couplet) => (
                    <div key={couplet.coupletIndex} className="space-y-2">
                        <div className="text-[10px] uppercase tracking-wide text-muted-foreground/50 font-sans" dir="ltr">
                            Couplet {String(couplet.coupletIndex + 1).padStart(2, '0')}
                        </div>
                        <p className="whitespace-pre-wrap">{renderCoupletTokens(couplet)}</p>
                    </div>
                ))}
            </div>

            {(annotations.length > 0 || expressionAnnotations.length > 0) && (
                <div className="space-y-3">
                    {expressionAnnotations.length > 0 && (
                        <div className="rounded-lg border border-violet-200 bg-violet-50/40 p-3 space-y-2" dir="rtl">
                            <div className="flex items-center gap-2 text-xs font-medium text-violet-900/80" dir="ltr">
                                <Link2 className="h-3.5 w-3.5" />
                                Poetic expressions ({expressionAnnotations.length})
                            </div>
                            <ul className="space-y-2">
                                {expressionAnnotations
                                    .slice()
                                    .sort((a, b) => a.couplet_index - b.couplet_index || a.start_token_index - b.start_token_index)
                                    .map((a) => (
                                        <li
                                            key={expressionKey(a.couplet_index, a.start_token_index, a.end_token_index)}
                                            className="flex items-start justify-between gap-3 rounded-md bg-white border px-3 py-2 text-sm"
                                        >
                                            <div className="min-w-0 space-y-0.5">
                                                <div className="font-arabic text-base font-semibold">{a.surface_text}</div>
                                                <div className="text-muted-foreground text-xs font-arabic">
                                                    {a.poetic_gloss || a.literal_gloss || '—'}
                                                </div>
                                                <div className="text-[10px] text-muted-foreground font-sans" dir="ltr">
                                                    Couplet {a.couplet_index + 1} · tokens {a.start_token_index + 1}–{a.end_token_index + 1}
                                                    <Badge variant="secondary" className="ml-2 text-[10px]">{a.expression_type || 'izafat'}</Badge>
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7 shrink-0"
                                                onClick={() => clearExpression(a.couplet_index, a.start_token_index, a.end_token_index)}
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </Button>
                                        </li>
                                    ))}
                            </ul>
                        </div>
                    )}

                    {annotations.length > 0 && (
                        <div className="rounded-lg border bg-muted/20 p-3 space-y-2" dir="rtl">
                            <div className="flex items-center gap-2 text-xs font-medium text-muted-foreground" dir="ltr">
                                <BookOpen className="h-3.5 w-3.5" />
                                Pinned senses ({annotations.length})
                            </div>
                            <ul className="space-y-2">
                                {annotations
                                    .slice()
                                    .sort((a, b) => a.couplet_index - b.couplet_index || a.token_index - b.token_index)
                                    .map((a) => (
                                        <li
                                            key={annotationKey(a.couplet_index, a.token_index)}
                                            className="flex items-start justify-between gap-3 rounded-md bg-white border px-3 py-2 text-sm"
                                        >
                                            <div className="min-w-0 space-y-0.5">
                                                <div className="font-arabic text-base font-semibold">{a.surface_form}</div>
                                                <div className="text-muted-foreground text-xs font-arabic">
                                                    {senseLabel(a.sense)}
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7 shrink-0"
                                                onClick={() => clearAnnotation(a.couplet_index, a.token_index)}
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </Button>
                                        </li>
                                    ))}
                            </ul>
                        </div>
                    )}
                </div>
            )}

            {/* Word sense dialog */}
            <Dialog open={!!active} onOpenChange={(open) => { if (!open) setActive(null); }}>
                <DialogContent className="sm:max-w-lg" dir="rtl">
                    <DialogHeader className="text-right">
                        <DialogTitle className="font-arabic text-2xl">
                            {active?.surface}
                        </DialogTitle>
                        <DialogDescription dir="ltr" className="text-left">
                            Choose the meaning the poet intends in this line.
                        </DialogDescription>
                    </DialogHeader>

                    {active?.nextSurface && (
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full font-arabic justify-center"
                            onClick={() => openExpression({
                                coupletIndex: active.coupletIndex,
                                start: active.tokenIndex,
                                end: active.tokenIndex + 1,
                                surface: `${active.surface} ${active.nextSurface}`,
                                type: hasTrailingKasra(active.surface) ? 'izafat' : 'collocation',
                            })}
                        >
                            <Link2 className="h-4 w-4 ml-2" />
                            {active.surface} {active.nextSurface} — poetic expression
                        </Button>
                    )}

                    {isFetching && (
                        <div className="flex items-center justify-center py-8 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin mr-2" /> Loading senses…
                        </div>
                    )}

                    {!isFetching && lookup && !lookup.found && (
                        <div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground text-center font-arabic">
                            هي لفظ اڃا باک لغت ۾ ناهي. پهرين لغت ۾ شامل ڪريو.
                        </div>
                    )}

                    {!isFetching && lookup?.found && (
                        <div className="space-y-3">
                            <div className="space-y-2 max-h-[280px] overflow-y-auto">
                                {(lookup.senses || []).length === 0 ? (
                                    <p className="text-sm text-muted-foreground text-center py-4">
                                        No senses yet for this lemma.
                                    </p>
                                ) : (lookup.senses || []).map((sense, index) => (
                                    <button
                                        key={sense.id}
                                        type="button"
                                        onClick={() => selectSense(sense)}
                                        className={cn(
                                            'w-full text-right rounded-lg border px-3 py-3 transition-colors hover:border-emerald-400 hover:bg-emerald-50/60',
                                            sense.is_preferred && 'border-emerald-500 bg-emerald-50'
                                        )}
                                    >
                                        <div className="flex items-center justify-between gap-2 mb-1" dir="ltr">
                                            <span className="text-[10px] uppercase tracking-wide text-muted-foreground">
                                                Sense {index + 1}
                                                {index === 0 && (
                                                    <span className="inline-flex items-center gap-1 ml-2 text-emerald-700">
                                                        <ArrowUp className="h-3 w-3" /> top
                                                    </span>
                                                )}
                                            </span>
                                        </div>
                                        <div className="font-arabic text-base leading-snug">
                                            {senseLabel(sense)}
                                        </div>
                                    </button>
                                ))}
                            </div>

                            <div className="space-y-2 pt-1">
                                <Textarea
                                    dir="rtl"
                                    lang="sd"
                                    className="font-arabic min-h-[70px]"
                                    placeholder="هن شعر ۾ هن لفظ جو مطلب…"
                                    value={note}
                                    onChange={(e) => setNote(e.target.value)}
                                />
                                <label className="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer" dir="ltr">
                                    <input
                                        type="checkbox"
                                        checked={promote}
                                        onChange={(e) => setPromote(e.target.checked)}
                                        className="rounded border-muted-foreground/40"
                                    />
                                    Move this sense to the top in Baakh Lughat
                                </label>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="sm:justify-between" dir="ltr">
                        <Button type="button" variant="ghost" onClick={() => setActive(null)}>Cancel</Button>
                        {active && annotationMap.has(annotationKey(active.coupletIndex, active.tokenIndex)) && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    clearAnnotation(active.coupletIndex, active.tokenIndex);
                                    setActive(null);
                                }}
                            >
                                Clear pin
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Expression dialog */}
            <Dialog open={!!activeExpr} onOpenChange={(open) => { if (!open) setActiveExpr(null); }}>
                <DialogContent className="sm:max-w-lg" dir="rtl">
                    <DialogHeader className="text-right">
                        <DialogTitle className="font-arabic text-2xl">
                            {activeExpr?.surface}
                        </DialogTitle>
                        <DialogDescription dir="ltr" className="text-left">
                            Multiword poetic expression — two (or more) words, one meaning in this line.
                        </DialogDescription>
                    </DialogHeader>

                    {exprFetching && (
                        <div className="flex items-center justify-center py-6 text-muted-foreground">
                            <Loader2 className="h-5 w-5 animate-spin mr-2" /> Looking up expressions…
                        </div>
                    )}

                    {!exprFetching && (exprLookup?.matches || []).length > 0 && (
                        <div className="space-y-2">
                            <p className="text-xs text-muted-foreground" dir="ltr">Existing in Baakh Lughat</p>
                            {(exprLookup.matches || []).map((hit) => (
                                <button
                                    key={hit.expression?.id || hit.matched_text}
                                    type="button"
                                    onClick={() => saveExpression(hit)}
                                    className="w-full text-right rounded-lg border border-violet-200 px-3 py-3 hover:bg-violet-50/70"
                                >
                                    <div className="font-arabic text-base font-semibold">{hit.expression?.expression || hit.matched_text}</div>
                                    <div className="text-xs text-muted-foreground font-arabic mt-0.5">
                                        {hit.expression?.poetic_gloss || hit.expression?.literal_gloss || '—'}
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="space-y-3 border-t pt-3">
                        <p className="text-xs text-muted-foreground" dir="ltr">Or define / refine for this line</p>
                        <div className="grid grid-cols-2 gap-2" dir="ltr">
                            <div>
                                <label className="text-[10px] text-muted-foreground">Type</label>
                                <select
                                    className="w-full h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                    value={exprType}
                                    onChange={(e) => setExprType(e.target.value)}
                                >
                                    {['izafat', 'collocation', 'metaphor', 'idiom', 'compound', 'fixed_phrase', 'other'].map((t) => (
                                        <option key={t} value={t}>{t}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <Input
                            dir="ltr"
                            placeholder="Literal gloss — cup of love"
                            value={literalGloss}
                            onChange={(e) => setLiteralGloss(e.target.value)}
                        />
                        <Textarea
                            dir="rtl"
                            lang="sd"
                            className="font-arabic min-h-[70px]"
                            placeholder="Poetic meaning in this line…"
                            value={poeticGloss}
                            onChange={(e) => setPoeticGloss(e.target.value)}
                        />
                        <Textarea
                            dir="rtl"
                            lang="sd"
                            className="font-arabic min-h-[50px]"
                            placeholder="Note (optional)"
                            value={exprNote}
                            onChange={(e) => setExprNote(e.target.value)}
                        />
                    </div>

                    <DialogFooter className="sm:justify-between gap-2" dir="ltr">
                        <Button type="button" variant="ghost" onClick={() => setActiveExpr(null)}>Cancel</Button>
                        <div className="flex gap-2">
                            {activeExpr && expressionMap.has(expressionKey(activeExpr.coupletIndex, activeExpr.start, activeExpr.end)) && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => {
                                        clearExpression(activeExpr.coupletIndex, activeExpr.start, activeExpr.end);
                                        setActiveExpr(null);
                                    }}
                                >
                                    Clear
                                </Button>
                            )}
                            <Button type="button" onClick={() => saveExpression()}>
                                Pin expression
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
