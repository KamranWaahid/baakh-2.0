import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { CoupletWithWords } from './WordTooltip';

/**
 * Measure line widths at a given font size without mutating live React nodes.
 */
function measureLinesWidth(lines, {
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
 * Renders a couplet as original verse lines (split on newlines) and shrinks
 * font-size so each line stays on a single row across container widths.
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
}) => {
    const containerRef = useRef(null);
    const sampleRef = useRef(null);
    const [fontSize, setFontSize] = useState(baseFontSize);
    const [scale, setScale] = useState(1);
    const [letterSpacing, setLetterSpacing] = useState('normal');

    const lines = useMemo(() => {
        return String(text ?? '')
            .replace(/\r\n/g, '\n')
            .split('\n')
            .map((line) => line.replace(/[ \t]+$/g, ''));
    }, [text]);

    const alignClass =
        align === 'center'
            ? 'text-center'
            : align === 'left'
                ? 'text-left'
                : 'text-right';

    const originClass =
        align === 'center'
            ? 'origin-center'
            : isRtl || align === 'right'
                ? 'origin-right'
                : 'origin-left';

    useLayoutEffect(() => {
        const container = containerRef.current;
        if (!container) return undefined;

        let frame = 0;

        const measure = () => {
            const available = container.clientWidth;
            if (available <= 0) return;

            const sampleEl = sampleRef.current || container;
            const maxWidth = measureLinesWidth(lines, {
                fontSize: baseFontSize,
                isRtl,
                sampleEl,
            });
            if (maxWidth <= 0) return;

            if (maxWidth <= available) {
                setFontSize(baseFontSize);
                setScale(1);
                setLetterSpacing('normal');
                return;
            }

            // Stick close to the edge; shrink only enough to avoid wrapping.
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

            setFontSize(nextSize);
            setScale(nextScale);
            setLetterSpacing(nextSize < baseFontSize * 0.92 ? '-0.015em' : 'normal');
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
    }, [baseFontSize, minFontSize, lines, align, isRtl]);

    useEffect(() => {
        setFontSize(baseFontSize);
        setScale(1);
        setLetterSpacing('normal');
    }, [baseFontSize, text]);

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
                    className={`block max-w-full whitespace-nowrap ${alignClass} ${originClass} ${lineClassName}`}
                    style={{
                        fontSize: `${fontSize}px`,
                        letterSpacing,
                        lineHeight: 1.45,
                        transform: scale < 0.999 ? `scale(${scale})` : undefined,
                    }}
                    dir={isRtl ? 'rtl' : 'ltr'}
                >
                    {line === ''
                        ? '\u00A0'
                        : interactive
                            ? <CoupletWithWords text={line} isRtl={isRtl} />
                            : line}
                </div>
            ))}
        </div>
    );
};

export default FitVerseBlock;
