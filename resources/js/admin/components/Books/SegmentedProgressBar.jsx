import React, { useCallback, useEffect, useId, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Single floating tooltip — only one segment label is visible at a time.
 * Avoids CSS group-hover stacking that mixed adjacent labels on the public poet profile.
 */
const SegmentedProgressBar = ({ segments, className }) => {
    const barRef = useRef(null);
    const tooltipId = useId();
    const [activeIndex, setActiveIndex] = useState(null);
    const [tooltipLeft, setTooltipLeft] = useState(50);
    const leaveTimer = useRef(null);

    const clearLeaveTimer = () => {
        if (leaveTimer.current) {
            clearTimeout(leaveTimer.current);
            leaveTimer.current = null;
        }
    };

    const updateTooltipPosition = useCallback((index, el) => {
        const bar = barRef.current;
        if (!bar || !el) return;
        const barRect = bar.getBoundingClientRect();
        const segRect = el.getBoundingClientRect();
        if (barRect.width <= 0) return;
        const center = segRect.left - barRect.left + segRect.width / 2;
        const pct = (center / barRect.width) * 100;
        // Keep tooltip on-screen within the bar.
        setTooltipLeft(Math.min(92, Math.max(8, pct)));
        setActiveIndex(index);
    }, []);

    const handleEnter = (index, el) => {
        clearLeaveTimer();
        updateTooltipPosition(index, el);
    };

    const handleLeave = () => {
        clearLeaveTimer();
        leaveTimer.current = setTimeout(() => setActiveIndex(null), 80);
    };

    useEffect(() => () => clearLeaveTimer(), []);

    if (!segments || segments.length === 0) return null;

    const active = activeIndex !== null ? segments[activeIndex] : null;

    return (
        <div
            ref={barRef}
            className={cn('relative w-full', className)}
            onMouseLeave={handleLeave}
        >
            <div
                className="flex h-2 w-full flex-row overflow-visible rounded-full border border-gray-200/50 bg-gray-100"
                dir="ltr"
                role="img"
                aria-label="Book digitization progress"
            >
                {segments.map((segment, index) => {
                    const isCompleted = Boolean(segment.is_completed);
                    const type = segment.type || 'page';
                    const isActive = activeIndex === index;

                    let bgColor = 'bg-gray-200';
                    if (isCompleted) {
                        bgColor = 'bg-gray-900';
                        if (type === 'cover') bgColor = 'bg-slate-800';
                    }

                    const width = Math.max(Number(segment.width_percent) || 0, 0.35);

                    return (
                        <button
                            key={`${segment.start}-${segment.end}-${index}`}
                            type="button"
                            aria-describedby={isActive ? tooltipId : undefined}
                            className={cn(
                                'relative h-full min-w-[3px] shrink-0 border-r border-white/25 last:border-r-0 transition-[filter] duration-150',
                                'focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/40',
                                index === 0 && 'rounded-l-full',
                                index === segments.length - 1 && 'rounded-r-full',
                                isActive && 'brightness-125 z-[1]',
                                bgColor
                            )}
                            style={{ width: `${width}%` }}
                            onMouseEnter={(e) => handleEnter(index, e.currentTarget)}
                            onFocus={(e) => handleEnter(index, e.currentTarget)}
                            onBlur={handleLeave}
                            onClick={(e) => handleEnter(index, e.currentTarget)}
                        />
                    );
                })}
            </div>

            {active && (
                <div
                    id={tooltipId}
                    role="tooltip"
                    className="pointer-events-none absolute bottom-[calc(100%+8px)] z-[120] w-max max-w-[min(280px,90vw)] -translate-x-1/2 rounded-md border-none bg-black px-3 py-2 text-xs text-white shadow-xl"
                    style={{ left: `${tooltipLeft}%` }}
                    dir="ltr"
                >
                    <div className="space-y-1">
                        <p className="flex items-baseline justify-between gap-3 font-semibold leading-tight">
                            <span>
                                Pages {active.start}–{active.end}
                            </span>
                            {active.type && (
                                <span className="text-[9px] font-medium uppercase tracking-wider text-white/60">
                                    {active.type}
                                </span>
                            )}
                        </p>
                        {active.title ? (
                            <p className="font-arabic text-sm leading-snug text-white/90" dir="rtl">
                                {active.title}
                            </p>
                        ) : null}
                        <p
                            className={cn(
                                'text-[10px] font-medium',
                                active.is_completed ? 'text-green-400' : 'text-gray-400'
                            )}
                        >
                            {active.is_completed ? 'Completed' : 'Pending'}
                        </p>
                    </div>
                    <span
                        className="absolute left-1/2 top-full -mt-px h-2 w-2 -translate-x-1/2 rotate-45 bg-black"
                        aria-hidden
                    />
                </div>
            )}
        </div>
    );
};

export default SegmentedProgressBar;
