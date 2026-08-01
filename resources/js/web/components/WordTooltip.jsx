import React, { useState, useRef, useEffect, useLayoutEffect, useCallback } from 'react';
import { createPortal } from 'react-dom';
import { X, Volume2, ChevronLeft, Loader2 } from 'lucide-react';
import api from '@/admin/api/axios';

const TOOLTIP_WIDTH = 260;
const VIEW_PAD = 8;
const GAP = 8;

/**
 * Strip punctuation from a poetry token before dictionary lookup.
 * Arabic comma (،) sits inside the Arabic Unicode block, so a letter-only
 * keep-list is not enough — punctuation must be removed explicitly.
 *
 * Do NOT use \p{S}: Unicode miscategorises Sindhi letters ۾ (U+06FE) and
 * ۽ (U+06FD) as Symbol, which would wipe them before lookup.
 */
function cleanLookupWord(token = '') {
    return String(token)
        .replace(/[\u060C\u061B\u061F\u06D4\u0640\u0606-\u0608\u060B\u060E\u060F\u06DE\u06E9\u00AB\u00BB\u2018-\u201F\p{P}]+/gu, '')
        .replace(/[^\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/gu, '')
        .trim();
}

/** True when text is primarily Arabic/Sindhi script (should render RTL). */
function isRtlScript(text = '') {
    return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/.test(String(text));
}

function MeaningLine({ text, index, total }) {
    const rtl = isRtlScript(text);
    const prefix = total > 1 ? `${index + 1}. ` : '• ';
    return (
        <p
            className={`text-sm text-gray-800 leading-snug break-words ${rtl ? 'font-arabic' : ''}`}
            dir={rtl ? 'rtl' : 'ltr'}
            lang={rtl ? 'sd' : 'en'}
        >
            {prefix}{text}
        </p>
    );
}

/**
 * Place a fixed tooltip near an anchor rect (viewport coords), flipping above
 * when there isn't enough room below. Safe with CSS-transformed ancestors
 * because the tooltip is portaled to document.body.
 */
function computePosition(anchorRect, tooltipEl) {
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const tw = tooltipEl?.offsetWidth || TOOLTIP_WIDTH;
    const naturalH = tooltipEl?.scrollHeight || tooltipEl?.offsetHeight || 120;

    let left = anchorRect.left + anchorRect.width / 2 - tw / 2;
    left = Math.max(VIEW_PAD, Math.min(left, vw - tw - VIEW_PAD));

    const spaceBelow = vh - anchorRect.bottom - VIEW_PAD - GAP;
    const spaceAbove = anchorRect.top - VIEW_PAD - GAP;
    const placeBelow = spaceBelow >= Math.min(naturalH, 160) || spaceBelow >= spaceAbove;

    const available = Math.max(120, placeBelow ? spaceBelow : spaceAbove);
    const maxHeight = Math.min(420, Math.floor(vh * 0.75), available);
    const th = Math.min(naturalH, maxHeight);

    let top = placeBelow
        ? anchorRect.bottom + GAP
        : anchorRect.top - th - GAP;

    top = Math.max(VIEW_PAD, Math.min(top, vh - th - VIEW_PAD));

    const arrowLeft = Math.max(
        16,
        Math.min(tw - 16, anchorRect.left + anchorRect.width / 2 - left)
    );

    return { top, left, placeBelow, arrowLeft, maxHeight };
}

/**
 * WordTooltip — compact dictionary card near a clicked word.
 * Pass `expressionPayload` for a pinned izafat / multiword expression
 * (skips single-word lookup and shows the phrase meaning).
 */
const WordTooltip = ({
    word,
    onClose,
    anchorRect,
    isRtl,
    dictionarySource = 'general',
    poetryId = null,
    coupletIndex = null,
    tokenIndex = null,
    expressionPayload = null,
}) => {
    const tooltipRef = useRef(null);
    const [position, setPosition] = useState(null);
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [phase, setPhase] = useState('enter'); // enter | open | exit
    const [bodyKey, setBodyKey] = useState(0);
    const [showComingSoon, setShowComingSoon] = useState(false);
    const closingRef = useRef(false);

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        if (expressionPayload) {
            setData(expressionPayload);
            setLoading(false);
            setBodyKey((k) => k + 1);
            return () => { cancelled = true; };
        }

        const params = new URLSearchParams();
        if (dictionarySource === 'lughat') {
            params.set('dictionary', 'lughat');
        }
        if (poetryId != null) {
            params.set('poetry_id', String(poetryId));
        }
        // Prefer pinned izafat/expression when this token is inside a span.
        if (coupletIndex != null && tokenIndex != null) {
            params.set('couplet_index', String(coupletIndex));
            params.set('token_index', String(tokenIndex));
        }
        const qs = params.toString();
        api.get(`/api/v1/word/${encodeURIComponent(word)}${qs ? `?${qs}` : ''}`)
            .then(res => { if (!cancelled) setData(res.data); })
            .catch(() => { if (!cancelled) setData({ found: false }); })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                    setBodyKey((k) => k + 1);
                }
            });
        return () => { cancelled = true; };
    }, [word, dictionarySource, poetryId, coupletIndex, tokenIndex, expressionPayload]);

    const requestClose = useCallback(() => {
        if (closingRef.current) return;
        closingRef.current = true;
        setPhase('exit');
    }, []);

    const handleAnimationEnd = useCallback((e) => {
        if (e.target !== tooltipRef.current) return;
        if (phase === 'enter') {
            setPhase('open');
            return;
        }
        if (phase === 'exit') {
            onClose();
        }
    }, [phase, onClose]);

    const updatePosition = useCallback(() => {
        if (!anchorRect || !tooltipRef.current) return;
        setPosition(computePosition(anchorRect, tooltipRef.current));
    }, [anchorRect]);

    useLayoutEffect(() => {
        updatePosition();
    }, [updatePosition, loading, data]);

    useEffect(() => {
        const onScrollOrResize = () => updatePosition();
        window.addEventListener('resize', onScrollOrResize);
        // Capture scroll on any scrollable ancestor
        window.addEventListener('scroll', onScrollOrResize, true);
        return () => {
            window.removeEventListener('resize', onScrollOrResize);
            window.removeEventListener('scroll', onScrollOrResize, true);
        };
    }, [updatePosition]);

    useEffect(() => {
        const handler = (e) => { if (e.key === 'Escape') requestClose(); };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [requestClose]);

    useEffect(() => {
        const handler = (e) => {
            if (showComingSoon) return;
            if (tooltipRef.current && !tooltipRef.current.contains(e.target)) requestClose();
        };
        const t = setTimeout(() => document.addEventListener('mousedown', handler), 50);
        return () => { clearTimeout(t); document.removeEventListener('mousedown', handler); };
    }, [requestClose, showComingSoon]);

    // Reduced-motion / animation fallback: still close after exit phase starts
    useEffect(() => {
        if (phase !== 'exit') return undefined;
        const reduce = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
        const t = setTimeout(() => onClose(), reduce ? 40 : 280);
        return () => clearTimeout(t);
    }, [phase, onClose]);

    const posLabel = data?.pos || null;
    const meanings = data?.meanings || [];
    const meaningsEn = data?.meanings_en || [];
    const meaningsSd = data?.meanings_sd || [];
    const structuredSenses = data?.senses || [];
    const shownMeanings = meanings.slice(0, 2);
    const shownMeaningsEn = meaningsEn.slice(0, 2);
    const shownMeaningsSd = meaningsSd.slice(0, 2);
    const extraMeanings = Math.max(meanings.length - 2, 0);
    const synonyms = data?.synonyms || [];
    const antonyms = data?.antonyms || [];

    if (typeof document === 'undefined') return null;

    const originX = position
        ? `${Math.round((position.arrowLeft / TOOLTIP_WIDTH) * 100)}%`
        : '50%';
    const originY = position?.placeBelow ? '0%' : '100%';
    const fromY = position?.placeBelow ? '10px' : '-10px';

    return createPortal(
        <div
            ref={tooltipRef}
            role="dialog"
            aria-label={word}
            onAnimationEnd={handleAnimationEnd}
            style={{
                top: position?.top ?? -9999,
                left: position?.left ?? -9999,
                maxHeight: position?.maxHeight ?? 'min(420px, 75vh)',
                visibility: position ? 'visible' : 'hidden',
                '--wt-origin-x': originX,
                '--wt-origin-y': originY,
                '--wt-from-y': fromY,
            }}
            className={[
                'word-tooltip fixed z-[9999] w-[260px] max-w-[calc(100vw-16px)] bg-white rounded-xl border border-gray-200 shadow-lg',
                'overflow-x-hidden overflow-y-auto overscroll-contain',
                phase === 'enter' ? 'word-tooltip-enter' : '',
                phase === 'exit' ? 'word-tooltip-exit' : '',
            ].filter(Boolean).join(' ')}
            dir={isRtl ? 'rtl' : 'ltr'}
        >
            {/* Anchor caret pointing at the word */}
            {position && (
                <div
                    aria-hidden
                    className="pointer-events-none absolute w-2.5 h-2.5 bg-white border-gray-200 rotate-45 transition-opacity duration-200"
                    style={
                        position.placeBelow
                            ? {
                                top: -5,
                                left: position.arrowLeft - 5,
                                borderLeftWidth: 1,
                                borderTopWidth: 1,
                            }
                            : {
                                bottom: -5,
                                left: position.arrowLeft - 5,
                                borderRightWidth: 1,
                                borderBottomWidth: 1,
                            }
                    }
                />
            )}

            <div className="px-3 py-2.5 min-w-0">
                <div className="flex items-start justify-between gap-2 min-w-0">
                    <span className="text-xl font-bold text-gray-900 font-arabic break-words min-w-0">
                        {data?.word || word}
                    </span>
                    <button type="button" onClick={requestClose} className="text-gray-400 hover:text-gray-600 p-0.5 shrink-0" aria-label="Close">
                        <X className="h-3.5 w-3.5" />
                    </button>
                </div>
                {(posLabel || data?.dictionary === 'lughat' || data?.source === 'baakh_lughat' || data?.completion_status) && (
                    <div className="mt-1.5 flex flex-wrap items-center gap-1 min-w-0">
                        {posLabel && (
                            <span className="text-[11px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded max-w-full break-words leading-snug">
                                {posLabel}
                            </span>
                        )}
                        {(data?.dictionary === 'lughat' || data?.source === 'baakh_lughat') && (
                            <span className="text-[10px] text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded font-arabic">
                                باک لغت
                            </span>
                        )}
                        {data?.is_expression && (
                            <span className="text-[10px] text-violet-800 bg-violet-50 px-1.5 py-0.5 rounded font-arabic">
                                {data.expression_type === 'izafat' ? 'اضافت' : (data.expression_type || 'expression')}
                            </span>
                        )}
                        {data?.completion_status && (
                            <span className={`text-[11px] px-1.5 py-0.5 rounded ${data.completion_status === 'complete' ? 'text-green-700 bg-green-50' : 'text-amber-700 bg-amber-50'}`}>
                                {data.completion_status === 'complete' ? 'Complete' : 'Pending'}
                            </span>
                        )}
                    </div>
                )}
                {data?.romanized && (
                    <div className="flex items-center gap-1.5 mt-1 min-w-0">
                        <span className="text-xs text-gray-400 truncate">/{data.romanized}/</span>
                        <button type="button" className="text-gray-300 hover:text-gray-500 shrink-0">
                            <Volume2 className="h-3 w-3" />
                        </button>
                    </div>
                )}
                {data?.gender && (
                    <span className="text-[11px] text-gray-400 mt-0.5 block break-words">
                        {data.gender}{data.number ? ` · ${data.number}` : ''}
                    </span>
                )}
            </div>

            <div key={bodyKey} className={!loading ? 'word-tooltip-body-enter' : undefined}>
            {loading && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-4 flex items-center justify-center">
                        <Loader2 className="h-4 w-4 text-gray-300 animate-spin" />
                    </div>
                </>
            )}

            {!loading && !data?.found && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-3 text-center">
                        <span className="text-xs text-gray-400 font-arabic">
                            {isRtl ? 'هي لفظ لغت ۾ موجود ناهي' : 'Word not found in dictionary'}
                        </span>
                    </div>
                </>
            )}

            {!loading && data?.found && structuredSenses.length > 0 && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2 space-y-1.5">
                        <span className="text-[11px] text-gray-400 block">
                            {isRtl ? 'Sense details' : 'Sense Details'}
                        </span>
                        {structuredSenses.slice(0, 2).map((sense, i) => {
                            const def = sense.definition || sense.full_definition || '';
                            const defRtl = isRtlScript(def);
                            const glossRtl = isRtlScript(sense.short_gloss);
                            return (
                            <div key={sense.public_id || sense.id || i} className="rounded-md bg-gray-50 px-2 py-1.5 min-w-0">
                                {sense.short_gloss && (
                                    <p
                                        className={`text-xs font-medium text-gray-600 break-words ${glossRtl ? 'font-arabic' : ''}`}
                                        dir={glossRtl ? 'rtl' : 'ltr'}
                                    >
                                        {sense.short_gloss}
                                    </p>
                                )}
                                <p
                                    className={`text-sm text-gray-800 leading-snug break-words ${defRtl ? 'font-arabic' : ''}`}
                                    dir={defRtl ? 'rtl' : 'ltr'}
                                    lang={defRtl ? 'sd' : 'en'}
                                >
                                    {def}
                                </p>
                                {sense.source && <p className="text-[10px] text-gray-400 mt-0.5" dir="ltr">{sense.source}</p>}
                            </div>
                            );
                        })}
                    </div>
                </>
            )}

            {!loading && data?.found && (shownMeanings.length > 0 || shownMeaningsSd.length > 0 || shownMeaningsEn.length > 0) && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2 space-y-2 min-w-0">
                        {shownMeanings.length > 0 && (
                            <div className="min-w-0">
                                <span className="text-[11px] text-gray-400 block mb-1 break-words">
                                    {isRtl ? 'معنى (Primary)' : 'Primary Meaning'}
                                </span>
                                <div className="space-y-0.5 min-w-0">
                                    {shownMeanings.map((m, i) => (
                                        <MeaningLine key={i} text={m} index={i} total={shownMeanings.length} />
                                    ))}
                                </div>
                            </div>
                        )}
                        {shownMeaningsSd.length > 0 && (
                            <div dir="rtl" className="min-w-0">
                                <span className="text-[11px] text-gray-400 block mb-1 break-words">
                                    {isRtl ? 'سنڌي معنى' : 'Sindhi Meaning'}
                                </span>
                                <div className="space-y-0.5 min-w-0">
                                    {shownMeaningsSd.map((m, i) => (
                                        <MeaningLine key={`sd-${i}`} text={m} index={i} total={shownMeaningsSd.length} />
                                    ))}
                                </div>
                            </div>
                        )}
                        {shownMeaningsEn.length > 0 && (
                            <div dir="ltr" className="text-left min-w-0">
                                <span className="text-[11px] text-gray-400 block mb-1">English Meaning</span>
                                <div className="space-y-0.5 min-w-0">
                                    {shownMeaningsEn.map((m, i) => (
                                        <MeaningLine key={`en-${i}`} text={m} index={i} total={shownMeaningsEn.length} />
                                    ))}
                                </div>
                            </div>
                        )}
                        {extraMeanings > 0 && (
                            <button type="button" className="text-xs text-gray-400 hover:text-gray-600 mt-1">
                                + {extraMeanings} {isRtl ? 'وڌيڪ مطلب' : 'more'}
                            </button>
                        )}
                    </div>
                </>
            )}

            {!loading && data?.found && synonyms.length > 0 && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2 min-w-0">
                        <span className="text-[11px] text-gray-400">
                            {isRtl ? 'هم معنى' : 'Synonyms'}:{' '}
                        </span>
                        <span className="text-sm text-gray-700 font-arabic break-words">
                            {synonyms.join('، ')}
                        </span>
                    </div>
                </>
            )}

            {!loading && data?.found && antonyms.length > 0 && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2 min-w-0">
                        <span className="text-[11px] text-gray-400">
                            {isRtl ? 'ضد' : 'Antonym'}:{' '}
                        </span>
                        <span className="text-sm text-gray-700 font-arabic break-words">
                            {antonyms.join('، ')}
                        </span>
                    </div>
                </>
            )}

            {!loading && data?.found && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2 pb-3">
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                setShowComingSoon(true);
                            }}
                            className="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 w-full justify-center"
                        >
                            {isRtl ? 'مڪمل تفصيل ڏسو' : 'Open full entry'}
                            <ChevronLeft className={`h-3 w-3 ${isRtl ? '' : 'rotate-180'}`} />
                        </button>
                    </div>
                </>
            )}
            </div>

            {showComingSoon && createPortal(
                <div
                    className="fixed inset-0 z-[10050] flex items-center justify-center p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="dict-coming-soon-title"
                >
                    <button
                        type="button"
                        className="absolute inset-0 bg-black/40 animate-in fade-in duration-200 border-0 cursor-default"
                        aria-label={isRtl ? 'بند ڪريو' : 'Close'}
                        onClick={() => setShowComingSoon(false)}
                        onMouseDown={(e) => e.stopPropagation()}
                    />
                    <div
                        className="relative w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-5 shadow-xl animate-in fade-in zoom-in-95 duration-200"
                        dir={isRtl ? 'rtl' : 'ltr'}
                        onMouseDown={(e) => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            onClick={() => setShowComingSoon(false)}
                            className={`absolute top-3 ${isRtl ? 'left-3' : 'right-3'} text-gray-400 hover:text-gray-600 p-1`}
                            aria-label={isRtl ? 'بند ڪريو' : 'Close'}
                        >
                            <X className="h-4 w-4" />
                        </button>
                        <h2
                            id="dict-coming-soon-title"
                            className={`text-base font-semibold text-gray-900 mb-2 pr-6 ${isRtl ? 'font-arabic pl-6 pr-0' : ''}`}
                        >
                            {isRtl ? 'جلد ايندڙ' : 'Coming soon'}
                        </h2>
                        <p className={`text-sm text-gray-600 leading-relaxed ${isRtl ? 'font-arabic' : ''}`}>
                            {isRtl
                                ? 'فلحال اسان سنڌي ڊڪشنري تي ڪم ڪري رھيا آھيون. ھي فيچر جلد فحال ڪيو ويندو.'
                                : 'We are currently working on the Sindhi dictionary. This feature will be activated soon.'}
                        </p>
                        <div className={`mt-4 flex ${isRtl ? 'justify-start' : 'justify-end'}`}>
                            <button
                                type="button"
                                onClick={() => setShowComingSoon(false)}
                                className="rounded-full bg-black px-4 py-1.5 text-sm font-medium text-white hover:bg-gray-800"
                            >
                                {isRtl ? 'ٺيڪ آهي' : 'OK'}
                            </button>
                        </div>
                    </div>
                </div>,
                document.body
            )}
        </div>,
        document.body
    );
};

function isSindhiToken(token = '') {
    return /[\u0600-\u06FF\u0750-\u077F]/.test(token);
}

function expressionLookupPayload(expr) {
    if (!expr) return null;
    const surface = expr.surface_text || '';
    const poetic = (expr.poetic_gloss || '').trim();
    const literal = (expr.literal_gloss || '').trim();
    const type = expr.expression_type || 'izafat';
    const meanings = [poetic, literal].filter(Boolean);
    return {
        found: meanings.length > 0 || !!surface,
        word: surface,
        pos: type === 'izafat' ? 'izafat' : type,
        meanings,
        meanings_en: literal ? [literal] : [],
        meanings_sd: poetic ? [poetic] : [],
        senses: meanings.length ? [{
            short_gloss: type === 'izafat' ? 'اضافت' : type,
            definition: poetic || literal,
            definition_en: literal || null,
            definition_sd: poetic || null,
            is_preferred: true,
        }] : [],
        is_expression: true,
        expression_type: type,
        source: 'baakh_lughat',
        dictionary: 'lughat',
    };
}

/**
 * ClickableWord — renders a word as a clickable <span>.
 */
export const ClickableWord = ({
    word,
    isRtl,
    dictionarySource = 'general',
    poetryId = null,
    coupletIndex = null,
    tokenIndex = null,
    expression = null,
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [rect, setRect] = useState(null);
    const wordRef = useRef(null);
    const expressionPayload = expressionLookupPayload(expression);

    const refreshRect = useCallback(() => {
        const el = wordRef.current;
        if (!el) return null;
        const next = el.getBoundingClientRect();
        setRect(next);
        return next;
    }, []);

    const handleClick = (e) => {
        e.stopPropagation();
        e.preventDefault();
        refreshRect();
        setIsOpen(true);
    };

    useEffect(() => {
        if (!isOpen) return undefined;
        const onScrollOrResize = () => refreshRect();
        window.addEventListener('resize', onScrollOrResize);
        window.addEventListener('scroll', onScrollOrResize, true);
        return () => {
            window.removeEventListener('resize', onScrollOrResize);
            window.removeEventListener('scroll', onScrollOrResize, true);
        };
    }, [isOpen, refreshRect]);

    const cleanWord = cleanLookupWord(word);
    if (!cleanWord && !expressionPayload) return <span>{word}</span>;

    const isExpr = !!expressionPayload;

    return (
        <>
            <span
                ref={wordRef}
                onClick={handleClick}
                title={isExpr ? (expression?.poetic_gloss || expression?.literal_gloss || expression?.surface_text) : undefined}
                className={`cursor-pointer rounded-sm transition-all duration-200 ease-out ${
                    isExpr
                        ? (isOpen
                            ? 'bg-violet-100/80 text-gray-900 ring-1 ring-violet-200 scale-[1.03]'
                            : 'hover:bg-violet-50/80')
                        : (isOpen
                            ? 'bg-amber-100/80 text-gray-900 ring-1 ring-amber-200 scale-[1.03]'
                            : 'hover:bg-gray-100/80')
                }`}
            >
                {word}
            </span>
            {isOpen && rect && (
                <WordTooltip
                    word={expressionPayload?.word || cleanWord}
                    anchorRect={rect}
                    isRtl={isRtl}
                    dictionarySource={dictionarySource}
                    poetryId={poetryId}
                    coupletIndex={coupletIndex}
                    tokenIndex={tokenIndex}
                    expressionPayload={expressionPayload}
                    onClose={() => setIsOpen(false)}
                />
            )}
        </>
    );
};

/**
 * CoupletWithWords — splits a couplet/line into clickable words.
 * When expressionAnnotations cover tokens, those words become one connected
 * phrase: click either part → izafat/expression meaning (no separate word pick).
 */
export const CoupletWithWords = ({
    text,
    isRtl,
    dictionarySource = 'general',
    poetryId = null,
    coupletIndex = 0,
    tokenOffset = 0,
    expressionAnnotations = [],
}) => {
    const parts = String(text ?? '').split(/(\s+)/u);
    let tokenIndex = tokenOffset;
    const nodes = [];

    for (let i = 0; i < parts.length; i++) {
        const part = parts[i];
        if (/^\s+$/u.test(part)) {
            // Keep spaces as real text nodes so CSS text-justify can stretch them.
            // Wrapped <span> spaces are ignored by many browsers for justification.
            nodes.push(part);
            continue;
        }

        const surface = cleanLookupWord(part);
        if (!surface || !isSindhiToken(surface)) {
            nodes.push(<span key={`o-${i}`}>{part}</span>);
            continue;
        }

        const idx = tokenIndex;
        tokenIndex += 1;

        const expr = (expressionAnnotations || []).find(
            (a) => a.couplet_index === coupletIndex
                && idx >= a.start_token_index
                && idx <= a.end_token_index
        );

        // Start of an expression span on this line: join following covered words.
        if (expr && idx === expr.start_token_index) {
            const spanParts = [part];
            let look = i + 1;
            let nextIdx = idx + 1;
            while (look < parts.length && nextIdx <= expr.end_token_index) {
                const nxt = parts[look];
                if (/^\s+$/u.test(nxt)) {
                    spanParts.push(nxt);
                    look += 1;
                    continue;
                }
                const nxtSurface = cleanLookupWord(nxt);
                if (!nxtSurface || !isSindhiToken(nxtSurface)) {
                    break;
                }
                if (nextIdx > expr.end_token_index) break;
                spanParts.push(nxt);
                look += 1;
                nextIdx += 1;
            }
            // Consume joined tokens from the loop
            const consumedWords = nextIdx - idx;
            tokenIndex = idx + consumedWords;
            i = look - 1;

            nodes.push(
                <ClickableWord
                    key={`e-${idx}`}
                    word={spanParts.join('')}
                    isRtl={isRtl}
                    dictionarySource={dictionarySource}
                    poetryId={poetryId}
                    coupletIndex={coupletIndex}
                    tokenIndex={idx}
                    expression={expr}
                />
            );
            continue;
        }

        // Covered by an expression that started on a previous line — still open expression.
        if (expr) {
            nodes.push(
                <ClickableWord
                    key={`ew-${idx}`}
                    word={part}
                    isRtl={isRtl}
                    dictionarySource={dictionarySource}
                    poetryId={poetryId}
                    coupletIndex={coupletIndex}
                    tokenIndex={idx}
                    expression={expr}
                />
            );
            continue;
        }

        nodes.push(
            <ClickableWord
                key={`w-${idx}`}
                word={part}
                isRtl={isRtl}
                dictionarySource={dictionarySource}
                poetryId={poetryId}
                coupletIndex={coupletIndex}
                tokenIndex={idx}
            />
        );
    }

    return <>{nodes}</>;
};

/** Count Sindhi word tokens in a line (matches admin / annotation indexing). */
export function countSindhiWordTokens(text = '') {
    return String(text)
        .split(/(\s+)/u)
        .filter((part) => {
            if (/^\s+$/u.test(part)) return false;
            const surface = cleanLookupWord(part);
            return surface && isSindhiToken(surface);
        }).length;
}

export default WordTooltip;
