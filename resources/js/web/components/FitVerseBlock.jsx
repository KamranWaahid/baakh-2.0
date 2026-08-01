import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { CoupletWithWords, countSindhiWordTokens } from './WordTooltip';

/**
 * Measure line widths at a given font size without mutating live React nodes.
 */
export function measureLinesWidth(lines, {
    fontSize,
    isRtl,
    sampleEl,
}) {
    if (!sampleEl || typeof document === 'undefined') return 0;

    const probe = document.createElement('div');
    const computed = window.getComputedStyle(sampleEl);
    probe.style.cssText = [
        'position:absolute',
        'visibility:hidden',
        'pointer-events:none',
        'white-space:nowrap',
        'left:-99999px',
        'top:0',
        `font-size:${fontSize}px`,
        'letter-spacing:normal',
        'transform:none',
        `font-family:${computed.fontFamily}`,
        `font-weight:${computed.fontWeight}`,
        `font-style:${computed.fontStyle}`,
        `line-height:1.45`,
        `direction:${isRtl ? 'rtl' : 'ltr'}`,
    ].join(';');

    document.body.appendChild(probe);
    let maxWidth = 0;
    for (const line of lines) {
        probe.textContent = line === '' ? '\u00A0' : line;
        maxWidth = Math.max(maxWidth, probe.scrollWidth);
    }
    document.body.removeChild(probe);
    return maxWidth;
}

/**
 * Compute a single fit (fontSize / scale / letterSpacing) for a set of lines
 * so they stay on one row within `available` width.
 */
export function computeVerseFit(lines, {
    available,
    baseFontSize,
    minFontSize,
    isRtl,
    sampleEl,
}) {
    if (available <= 0 || !sampleEl) {
        return { fontSize: baseFontSize, scale: 1, letterSpacing: 'normal' };
    }

    const maxWidth = measureLinesWidth(lines, {
        fontSize: baseFontSize,
        isRtl,
        sampleEl,
    });
    if (maxWidth <= 0) {
        return { fontSize: baseFontSize, scale: 1, letterSpacing: 'normal' };
    }

    if (maxWidth <= available) {
        return { fontSize: baseFontSize, scale: 1, letterSpacing: 'normal' };
    }

    const fitRatio = (available * 0.995) / maxWidth;
    const nextSize = Math.max(minFontSize, Math.floor(baseFontSize * fitRatio * 100) / 100);
    let nextScale = 1;

    if (nextSize <= minFontSize + 0.05) {
        const atMinWidth = measureLinesWidth(lines, {
            fontSize: minFontSize,
            isRtl,
            sampleEl,
        });
        if (atMinWidth > available) {
            nextScale = Math.min(1, Math.max(0.82, (available * 0.995) / atMinWidth));
        }
    }

    return {
        fontSize: nextSize,
        scale: nextScale,
        letterSpacing: nextSize < baseFontSize * 0.92 ? '-0.015em' : 'normal',
    };
}

export function splitVerseLines(text) {
    return String(text ?? '')
        .replace(/\r\n/g, '\n')
        .split('\n')
        .map((line) => line.replace(/[ \t]+$/g, ''));
}

/**
 * Renders a couplet as original verse lines (split on newlines) and shrinks
 * font-size so each line stays on a single row across container widths.
 *
 * Pass `lockedFit` from a parent when multiple blocks should share one size
 * (e.g. all couplets on a poem detail page).
 */
const FitVerseBlock = ({
    text,
    isRtl = false,
    align = 'right',
    baseFontSize = 28,
    minFontSize = 17,
    /** Space between the two lines inside one couplet (px). */
    lineGap = 6,
    className = '',
    interactive = true,
    lineClassName = '',
    /** When set, skip local fitting and use this shared size. */
    lockedFit = null,
    dictionarySource = 'general',
    poetryId = null,
    coupletIndex = 0,
    expressionAnnotations = [],
}) => {
    const containerRef = useRef(null);
    const sampleRef = useRef(null);
    const [localFit, setLocalFit] = useState({
        fontSize: baseFontSize,
        scale: 1,
        letterSpacing: 'normal',
    });

    const lines = useMemo(() => splitVerseLines(text), [text]);
    const lineTokenOffsets = useMemo(() => {
        let offset = 0;
        return lines.map((line) => {
            const current = offset;
            offset += countSindhiWordTokens(line);
            return current;
        });
    }, [lines]);
    const { fontSize, scale, letterSpacing } = lockedFit ?? localFit;

    const alignClass =
        align === 'center'
            ? 'text-center'
            : align === 'left'
                ? 'text-left'
                : align === 'justify'
                    ? 'text-justify'
                    : 'text-right';

    const originClass =
        align === 'center' || align === 'justify'
            ? 'origin-center'
            : isRtl || align === 'right'
                ? 'origin-right'
                : 'origin-left';

    useLayoutEffect(() => {
        if (lockedFit) return undefined;

        const container = containerRef.current;
        if (!container) return undefined;

        let frame = 0;

        const measure = () => {
            const available = container.clientWidth;
            if (available <= 0) return;

            const sampleEl = sampleRef.current || container;
            setLocalFit(computeVerseFit(lines, {
                available,
                baseFontSize,
                minFontSize,
                isRtl,
                sampleEl,
            }));
        };

        const schedule = () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(measure);
        };

        schedule();

        const observer = new ResizeObserver(schedule);
        observer.observe(container);

        const onFontsReady = () => schedule();
        if (document.fonts?.ready) {
            document.fonts.ready.then(onFontsReady).catch(() => {});
        }
        document.fonts?.addEventListener?.('loadingdone', onFontsReady);

        return () => {
            cancelAnimationFrame(frame);
            observer.disconnect();
            document.fonts?.removeEventListener?.('loadingdone', onFontsReady);
        };
    }, [baseFontSize, minFontSize, lines, align, isRtl, lockedFit]);

    useEffect(() => {
        if (lockedFit) return;
        setLocalFit({
            fontSize: baseFontSize,
            scale: 1,
            letterSpacing: 'normal',
        });
    }, [baseFontSize, text, lockedFit]);

    return (
        <div
            ref={containerRef}
            className={`w-full max-w-full overflow-x-hidden flex flex-col ${className}`}
            style={{ gap: `${lineGap}px` }}
        >
            {lines.map((line, index) => (
                <div
                    key={`${index}-${line.slice(0, 12)}`}
                    ref={index === 0 ? sampleRef : undefined}
                    className={`block max-w-full ${align === 'justify' ? 'whitespace-normal' : 'whitespace-nowrap'} ${alignClass} ${originClass} ${lineClassName}`}
                    style={{
                        fontSize: `${fontSize}px`,
                        letterSpacing,
                        lineHeight: 1.45,
                        transform: scale < 0.999 ? `scale(${scale})` : undefined,
                        // Single-line couplets need last-line justify; text-justify alone only
                        // stretches wrapped lines (not the final/only line).
                        ...(align === 'justify'
                            ? { textAlignLast: 'justify', textJustify: 'inter-word' }
                            : null),
                    }}
                    dir={isRtl ? 'rtl' : 'ltr'}
                >
                    {line === ''
                        ? '\u00A0'
                        : interactive
                            ? (
                                <CoupletWithWords
                                    text={line}
                                    isRtl={isRtl}
                                    dictionarySource={dictionarySource}
                                    poetryId={poetryId}
                                    coupletIndex={coupletIndex}
                                    tokenOffset={lineTokenOffsets[index] || 0}
                                    expressionAnnotations={expressionAnnotations}
                                />
                            )
                            : line}
                </div>
            ))}
        </div>
    );
};

/**
 * Fits every couplet to one shared font size (driven by the longest line
 * in the whole poem) so stanzas don't look uneven on narrow screens.
 */
export function FitVerseGroup({
    couplets,
    isRtl = false,
    align = 'right',
    baseFontSize = 28,
    minFontSize = 17,
    lineGap = 6,
    coupletClassName = '',
    interactive = true,
    className = '',
    dictionarySource = 'general',
    poetryId = null,
    expressionAnnotations = [],
}) {
    const containerRef = useRef(null);
    const sampleRef = useRef(null);
    const [lockedFit, setLockedFit] = useState({
        fontSize: baseFontSize,
        scale: 1,
        letterSpacing: 'normal',
    });

    const allLines = useMemo(() => {
        if (!Array.isArray(couplets)) return [];
        return couplets.flatMap((couplet) => splitVerseLines(couplet));
    }, [couplets]);

    useLayoutEffect(() => {
        const container = containerRef.current;
        if (!container || allLines.length === 0) return undefined;

        let frame = 0;

        const measure = () => {
            const available = container.clientWidth;
            if (available <= 0) return;

            const sampleEl = sampleRef.current || container;
            const next = computeVerseFit(allLines, {
                available,
                baseFontSize,
                minFontSize,
                isRtl,
                sampleEl,
            });
            setLockedFit((prev) => (
                prev.fontSize === next.fontSize
                && prev.scale === next.scale
                && prev.letterSpacing === next.letterSpacing
                    ? prev
                    : next
            ));
        };

        const schedule = () => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(measure);
        };

        schedule();

        const observer = new ResizeObserver(schedule);
        observer.observe(container);

        const onFontsReady = () => schedule();
        if (document.fonts?.ready) {
            document.fonts.ready.then(onFontsReady).catch(() => {});
        }
        document.fonts?.addEventListener?.('loadingdone', onFontsReady);

        return () => {
            cancelAnimationFrame(frame);
            observer.disconnect();
            document.fonts?.removeEventListener?.('loadingdone', onFontsReady);
        };
    }, [allLines, baseFontSize, minFontSize, isRtl]);

    useEffect(() => {
        setLockedFit({
            fontSize: baseFontSize,
            scale: 1,
            letterSpacing: 'normal',
        });
    }, [baseFontSize, couplets]);

    return (
        <div ref={containerRef} className={`w-full max-w-full ${className}`}>
            {/* Font metrics probe — inherits serif / arabic from parent */}
            <div
                ref={sampleRef}
                aria-hidden
                className="absolute opacity-0 pointer-events-none whitespace-nowrap"
                style={{ fontSize: `${baseFontSize}px`, lineHeight: 1.45 }}
            >
                {'\u00A0'}
            </div>
            {Array.isArray(couplets) && couplets.map((couplet, index) => (
                <FitVerseBlock
                    key={index}
                    text={couplet}
                    isRtl={isRtl}
                    align={align}
                    baseFontSize={baseFontSize}
                    minFontSize={minFontSize}
                    lineGap={lineGap}
                    className={coupletClassName}
                    interactive={interactive}
                    lockedFit={lockedFit}
                    dictionarySource={dictionarySource}
                    poetryId={poetryId}
                    coupletIndex={index}
                    expressionAnnotations={expressionAnnotations}
                />
            ))}
        </div>
    );
}

export default FitVerseBlock;
