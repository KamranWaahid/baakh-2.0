import React from 'react';
import {
    TooltipContent,
    TooltipProvider,
} from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";

const SegmentedProgressBar = ({ segments, className }) => {
    if (!segments || segments.length === 0) return null;

    return (
        <TooltipProvider>
            <div className={cn("w-full h-2 flex rounded-full bg-gray-100 border border-gray-200/50 relative", className)}>
                {segments.map((segment, index) => {
                    const isCompleted = segment.is_completed;
                    const type = segment.type;

                    // Branded colors based on type and completion
                    let bgColor = "bg-gray-200"; // Default pending
                    if (isCompleted) {
                        bgColor = "bg-black"; // Standard completed
                        if (type === 'poetry') bgColor = "bg-gray-900";
                        if (type === 'cover') bgColor = "bg-slate-800";
                    }

                    const isFirst = index === 0;
                    const isLast = index === segments.length - 1;

                    return (
                        <div
                            key={index}
                            className={cn(
                                "h-full relative group cursor-pointer transition-all hover:brightness-110",
                                isFirst && "rounded-l-full",
                                isLast && "rounded-r-full",
                                bgColor
                            )}
                            style={{ width: `${segment.width_percent}%` }}
                        >
                            {/* Inner border for segments */}
                            {!isLast && <div className="absolute right-0 top-0 w-[0.5px] h-full bg-white/20 z-10" />}

                            {/* Tooltip Content positioned relative to the segment */}
                            <TooltipContent
                                side="top"
                                className="bg-black text-white px-3 py-1.5 text-xs rounded shadow-xl border-none animate-in fade-in zoom-in duration-200 z-[100] whitespace-nowrap mb-1"
                            >
                                <div className="space-y-0.5">
                                    <p className="font-bold flex items-center justify-between gap-4">
                                        <span>Pages {segment.start}-{segment.end}</span>
                                        <span className="opacity-70 uppercase tracking-tighter text-[9px]">{type}</span>
                                    </p>
                                    {segment.title && (
                                        <p className="opacity-90 font-arabic text-sm mt-0.5" dir="rtl">
                                            {segment.title}
                                        </p>
                                    )}
                                    <p className={cn("text-[10px]", isCompleted ? "text-green-400" : "text-gray-400")}>
                                        {isCompleted ? "Completed" : "Pending"}
                                    </p>
                                </div>
                            </TooltipContent>
                        </div>
                    );
                })}
            </div>
        </TooltipProvider>
    );
};

export default SegmentedProgressBar;
