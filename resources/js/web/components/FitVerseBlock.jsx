import React, { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { CoupletWithWords } from './WordTooltip';

/**
 * Renders a couplet as original verse lines (split on newlines) and shrinks
 * font-size so each line stays on a single row across mobile widths.
 */
const FitVerseBlock = ({
    text,
    isRtl = false,
    align = 'right',
    baseFontSize = 24,
    minFontSize = 15,
    className = '',
    interactive = true,
    lineClassName = '',
}) => {
    const containerRef = useRef(null);
    const lineRefs = useRef([]);
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
        lineRefs.current = lineRefs.current.slice(0, lines.length);

        const container = containerRef.current;
        if (!container) {
            return undefined;
        }

        let frame = 0;

        const measure = () => {
            const available = container.clientWidth;
            if (available <= 0) {
                return;
            }

            const els = lineRefs.current.filter(Boolean);
            if (!els.length) {
                return;
            }

            // Measure natural width at the preferred desktop/mobile base size.
            els.forEach((el) => {
                el.style.fontSize = `${baseFontSize}px`;
                el.style.letterSpacing = 'normal';
                el.style.transform = 'none';
            });

            const maxWidth = Math.max(...els.map((el) => el.scrollWidth), 0);
            if (maxWidth <= 0) {
                return;
            }

            if (maxWidth <= available) {
                setFontSize(baseFontSize);
                setScale(1);
                setLetterSpacing('normal');
                els.forEach((el) => {
                    el.style.fontSize = '';
                    el.style.letterSpacing = '';
                    el.style.transform = '';
                });
                return;
            }

            // Leave a small gutter so subpixel rounding never clips the last glyph.
            const fitRatio = (available * 0.98) / maxWidth;
            const nextSize = Math.max(minFontSize, Math.floor(baseFontSize * fitRatio * 100) / 100);
            let nextScale = 1;

            if (nextSize <= minFontSize + 0.05) {
                // Still too long at the readability floor — scale the line visually.
                const atMinRatio = (available * 0.98) / (maxWidth * (minFontSize / baseFontSize));
                nextScale = Math.min(1, Math.max(0.72, atMinRatio));
            }

            setFontSize(nextSize);
            setScale(nextScale);
            setLetterSpacing(nextSize < baseFontSize * 0.9 ? '-0.02em' : 'normal');

            els.forEach((el) => {
                el.style.fontSize = '';
                el.style.letterSpacing = '';
                el.style.transform = '';
            });
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
    }, [baseFontSize, text]);

    return (
        <div
            ref={containerRef}
            className={`w-full max-w-full overflow-x-hidden ${className}`}
        >
            {lines.map((line, index) => (
                <div
                    key={`${index}-${line.slice(0, 12)}`}
                    ref={(el) => {
                        lineRefs.current[index] = el;
                    }}
                    className={`block max-w-full whitespace-nowrap ${alignClass} ${originClass} ${lineClassName}`}
                    style={{
                        fontSize: `${fontSize}px`,
                        letterSpacing,
                        lineHeight: 2,
                        transform: scale < 0.999 ? `scale(${scale})` : undefined,
                        marginBottom: index === lines.length - 1 ? 0 : undefined,
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
