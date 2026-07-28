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
 */
function cleanLookupWord(token = '') {
    return String(token)
        .replace(/[\u060C\u061B\u061F\u06D4\u0640\u00AB\u00BB\u2018-\u201F\p{P}\p{S}]+/gu, '')
        .replace(/[^\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/gu, '')
        .trim();
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
    const th = tooltipEl?.offsetHeight || 120;

    let left = anchorRect.left + anchorRect.width / 2 - tw / 2;
    left = Math.max(VIEW_PAD, Math.min(left, vw - tw - VIEW_PAD));

    const spaceBelow = vh - anchorRect.bottom - VIEW_PAD;
    const spaceAbove = anchorRect.top - VIEW_PAD;
    const placeBelow = spaceBelow >= th + GAP || spaceBelow >= spaceAbove;

    let top = placeBelow
        ? anchorRect.bottom + GAP
        : anchorRect.top - th - GAP;

    top = Math.max(VIEW_PAD, Math.min(top, vh - th - VIEW_PAD));

    const arrowLeft = Math.max(
        16,
        Math.min(tw - 16, anchorRect.left + anchorRect.width / 2 - left)
    );

    return { top, left, placeBelow, arrowLeft };
}

/**
 * WordTooltip — compact dictionary card near a clicked word.
 */
const WordTooltip = ({ word, onClose, anchorRect, isRtl }) => {
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
        api.get(`/api/v1/word/${encodeURIComponent(word)}`)
            .then(res => { if (!cancelled) setData(res.data); })
            .catch(() => { if (!cancelled) setData({ found: false }); })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                    setBodyKey((k) => k + 1);
                }
            });
        return () => { cancelled = true; };
    }, [word]);

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
                visibility: position ? 'visible' : 'hidden',
                '--wt-origin-x': originX,
                '--wt-origin-y': originY,
                '--wt-from-y': fromY,
            }}
            className={[
                'word-tooltip fixed z-[9999] w-[260px] max-w-[calc(100vw-16px)] bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden max-h-[min(320px,70vh)] overflow-y-auto',
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

            <div className="px-3 py-2.5">
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-2 min-w-0">
                        <span className="text-xl font-bold text-gray-900 font-arabic truncate">{data?.word || word}</span>
                        {posLabel && (
                            <span className="text-[11px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded shrink-0">
                                {posLabel}
                            </span>
                        )}
                        {data?.completion_status && (
                            <span className={`text-[11px] px-1.5 py-0.5 rounded shrink-0 ${data.completion_status === 'complete' ? 'text-green-700 bg-green-50' : 'text-amber-700 bg-amber-50'}`}>
                                {data.completion_status === 'complete' ? 'Complete' : 'Pending'}
                            </span>
                        )}
                    </div>
                    <button type="button" onClick={requestClose} className="text-gray-400 hover:text-gray-600 p-0.5 shrink-0" aria-label="Close">
                        <X className="h-3.5 w-3.5" />
                    </button>
                </div>
                {data?.romanized && (
                    <div className="flex items-center gap-1.5 mt-1">
                        <span className="text-xs text-gray-400">/{data.romanized}/</span>
                        <button type="button" className="text-gray-300 hover:text-gray-500">
                            <Volume2 className="h-3 w-3" />
                        </button>
                    </div>
                )}
                {data?.gender && (
                    <span className="text-[11px] text-gray-400 mt-0.5 block">
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
                        {structuredSenses.slice(0, 2).map((sense, i) => (
                            <div key={sense.public_id || sense.id || i} className="rounded-md bg-gray-50 px-2 py-1.5">
                                {sense.short_gloss && <p className="text-xs font-medium text-gray-600">{sense.short_gloss}</p>}
                                <p className="text-sm text-gray-800 font-arabic leading-snug" dir="auto">
                                    {sense.definition || sense.full_definition}
                                </p>
                                {sense.source && <p className="text-[10px] text-gray-400 mt-0.5">{sense.source}</p>}
                            </div>
                        ))}
                    </div>
                </>
            )}

            {!loading && data?.found && (shownMeanings.length > 0 || shownMeaningsSd.length > 0 || shownMeaningsEn.length > 0) && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2 space-y-2">
                        {shownMeanings.length > 0 && (
                            <div>
                                <span className="text-[11px] text-gray-400 block mb-1">
                                    {isRtl ? 'معنى (Primary)' : 'Primary Meaning'}
                                </span>
                                <div className="space-y-0.5">
                                    {shownMeanings.map((m, i) => (
                                        <p key={i} className="text-sm text-gray-800 font-arabic leading-snug">
                                            {shownMeanings.length > 1 ? `${i + 1}. ` : '• '}{m}
                                        </p>
                                    ))}
                                </div>
                            </div>
                        )}
                        {shownMeaningsSd.length > 0 && (
                            <div>
                                <span className="text-[11px] text-gray-400 block mb-1">
                                    {isRtl ? 'سنڌي معنى' : 'Sindhi Meaning'}
                                </span>
                                <div className="space-y-0.5">
                                    {shownMeaningsSd.map((m, i) => (
                                        <p key={`sd-${i}`} className="text-sm text-gray-800 font-arabic leading-snug">
                                            {shownMeaningsSd.length > 1 ? `${i + 1}. ` : '• '}{m}
                                        </p>
                                    ))}
                                </div>
                            </div>
                        )}
                        {shownMeaningsEn.length > 0 && (
                            <div dir="ltr" className={isRtl ? 'text-right' : 'text-left'}>
                                <span className="text-[11px] text-gray-400 block mb-1">English Meaning</span>
                                <div className="space-y-0.5">
                                    {shownMeaningsEn.map((m, i) => (
                                        <p key={`en-${i}`} className="text-sm text-gray-800 leading-snug">
                                            {shownMeaningsEn.length > 1 ? `${i + 1}. ` : '• '}{m}
                                        </p>
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
                    <div className="px-3 py-2">
                        <span className="text-[11px] text-gray-400">
                            {isRtl ? 'هم معنى' : 'Synonyms'}:{' '}
                        </span>
                        <span className="text-sm text-gray-700 font-arabic">
                            {synonyms.join('، ')}
                        </span>
                    </div>
                </>
            )}

            {!loading && data?.found && antonyms.length > 0 && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2">
                        <span className="text-[11px] text-gray-400">
                            {isRtl ? 'ضد' : 'Antonym'}:{' '}
                        </span>
                        <span className="text-sm text-gray-700 font-arabic">
                            {antonyms.join('، ')}
                        </span>
                    </div>
                </>
            )}

            {!loading && data?.found && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2">
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

/**
 * ClickableWord — renders a word as a clickable <span>.
 */
export const ClickableWord = ({ word, isRtl }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [rect, setRect] = useState(null);
    const wordRef = useRef(null);

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
    if (!cleanWord) return <span>{word}</span>;

    return (
        <>
            <span
                ref={wordRef}
                onClick={handleClick}
                className={`cursor-pointer rounded-sm underline-offset-4 decoration-gray-300 transition-all duration-200 ease-out ${
                    isOpen
                        ? 'bg-amber-100/80 underline text-gray-900 ring-1 ring-amber-200 scale-[1.03]'
                        : 'hover:underline hover:bg-gray-100/80'
                }`}
            >
                {word}
            </span>
            {isOpen && rect && (
                <WordTooltip
                    word={cleanWord}
                    anchorRect={rect}
                    isRtl={isRtl}
                    onClose={() => setIsOpen(false)}
                />
            )}
        </>
    );
};

/**
 * CoupletWithWords — splits a couplet string into clickable words.
 */
export const CoupletWithWords = ({ text, isRtl }) => {
    const tokens = text.split(/(\s+)/);
    return (
        <>
            {tokens.map((token, i) =>
                /^\s+$/.test(token)
                    ? <span key={i}>{token}</span>
                    : <ClickableWord key={i} word={token} isRtl={isRtl} />
            )}
        </>
    );
};

export default WordTooltip;
