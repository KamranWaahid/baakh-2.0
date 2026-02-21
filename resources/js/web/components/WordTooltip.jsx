import React, { useState, useRef, useEffect } from 'react';
import { X, Volume2, ChevronLeft, Loader2 } from 'lucide-react';
import api from '@/admin/api/axios';

/**
 * WordTooltip — compact dictionary card below a clicked word.
 * Fetches real data from /api/v1/word/{word}.
 */
const WordTooltip = ({ word, onClose, anchorRect, isRtl }) => {
    const tooltipRef = useRef(null);
    const [position, setPosition] = useState({ top: 0, left: 0 });
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    // ── Fetch word data ──
    useEffect(() => {
        let cancelled = false;
        setLoading(true);
        api.get(`/api/v1/word/${encodeURIComponent(word)}`)
            .then(res => { if (!cancelled) setData(res.data); })
            .catch(() => { if (!cancelled) setData({ found: false }); })
            .finally(() => { if (!cancelled) setLoading(false); });
        return () => { cancelled = true; };
    }, [word]);

    // ── Position below word ──
    useEffect(() => {
        if (!anchorRect || !tooltipRef.current) return;
        const tooltipWidth = tooltipRef.current.offsetWidth;
        const vw = window.innerWidth;

        let top = anchorRect.bottom;
        let left = anchorRect.left + anchorRect.width / 2 - tooltipWidth / 2;
        if (left < 4) left = 4;
        if (left + tooltipWidth > vw - 4) left = vw - tooltipWidth - 4;

        setPosition({ top, left });
    }, [anchorRect, loading]);

    // ── Close handlers ──
    useEffect(() => {
        const handler = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [onClose]);

    useEffect(() => {
        const handler = (e) => {
            if (tooltipRef.current && !tooltipRef.current.contains(e.target)) onClose();
        };
        const t = setTimeout(() => document.addEventListener('mousedown', handler), 50);
        return () => { clearTimeout(t); document.removeEventListener('mousedown', handler); };
    }, [onClose]);

    const posLabel = data?.pos || null;
    const meanings = data?.meanings || [];
    const meaningsEn = data?.meanings_en || [];
    const meaningsSd = data?.meanings_sd || [];
    const shownMeanings = meanings.slice(0, 2);
    const shownMeaningsEn = meaningsEn.slice(0, 2);
    const shownMeaningsSd = meaningsSd.slice(0, 2);
    const extraMeanings = Math.max(meanings.length - 2, 0);
    const synonyms = data?.synonyms || [];
    const antonyms = data?.antonyms || [];

    return (
        <div
            ref={tooltipRef}
            style={{ top: position.top, left: position.left }}
            className="fixed z-[9999] w-[260px] max-w-[calc(100vw-16px)] bg-white rounded-xl border border-gray-200 overflow-hidden max-h-[320px] overflow-y-auto"
            dir={isRtl ? 'rtl' : 'ltr'}
        >
            {/* ── Header: Word + POS + Pronunciation ── */}
            <div className="px-3 py-2.5">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <span className="text-xl font-bold text-gray-900 font-arabic">{data?.word || word}</span>
                        {posLabel && (
                            <span className="text-[11px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                                {posLabel}
                            </span>
                        )}
                    </div>
                    <button onClick={onClose} className="text-gray-400 hover:text-gray-600 p-0.5">
                        <X className="h-3.5 w-3.5" />
                    </button>
                </div>
                {data?.romanized && (
                    <div className="flex items-center gap-1.5 mt-1">
                        <span className="text-xs text-gray-400">/{data.romanized}/</span>
                        <button className="text-gray-300 hover:text-gray-500">
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

            {/* ── Loading state ── */}
            {loading && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-4 flex items-center justify-center">
                        <Loader2 className="h-4 w-4 text-gray-300 animate-spin" />
                    </div>
                </>
            )}

            {/* ── Not found ── */}
            {!loading && !data?.found && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-3 text-center">
                        <span className="text-xs text-gray-400">
                            {isRtl ? 'هي لفظ لغت ۾ موجود ناهي' : 'Word not found in dictionary'}
                        </span>
                    </div>
                </>
            )}

            {/* ── Meanings ── */}
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
                            <div dir="ltr" className={isRtl ? "text-right" : "text-left"}>
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
                            <button className="text-xs text-gray-400 hover:text-gray-600 mt-1">
                                + {extraMeanings} {isRtl ? 'وڌيڪ مطلب' : 'more'}
                            </button>
                        )}
                    </div>
                </>
            )}

            {/* ── Synonyms ── */}
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

            {/* ── Antonyms ── */}
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

            {/* ── Footer ── */}
            {!loading && data?.found && (
                <>
                    <div className="border-t border-gray-100" />
                    <div className="px-3 py-2">
                        <button className="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 w-full justify-center">
                            {isRtl ? 'مڪمل تفصيل ڏسو' : 'Open full entry'}
                            <ChevronLeft className={`h-3 w-3 ${isRtl ? '' : 'rotate-180'}`} />
                        </button>
                    </div>
                </>
            )}
        </div>
    );
};

/**
 * ClickableWord — renders a word as a clickable <span>.
 */
export const ClickableWord = ({ word, isRtl }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [rect, setRect] = useState(null);
    const wordRef = useRef(null);

    const handleClick = (e) => {
        e.stopPropagation();
        setRect(wordRef.current?.getBoundingClientRect());
        setIsOpen(true);
    };

    const cleanWord = word.replace(/[^\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF]/g, '');
    if (!cleanWord) return <span>{word}</span>;

    return (
        <>
            <span
                ref={wordRef}
                onClick={handleClick}
                className="cursor-pointer hover:underline decoration-gray-300 underline-offset-4"
            >
                {word}
            </span>
            {isOpen && (
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
