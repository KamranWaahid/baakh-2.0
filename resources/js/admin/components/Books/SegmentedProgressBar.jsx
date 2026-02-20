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
            <div
                className={cn("w-full h-2 flex flex-row rounded-full bg-gray-100 border border-gray-200/50 relative", className)}
                dir="ltr"
            >
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
                                "h-full relative group cursor-pointer transition-all hover:brightness-110 border-r last:border-r-0 border-white/20",
                                isFirst && "rounded-l-full",
                                isLast && "rounded-r-full",
                                bgColor
                            )}
                            style={{ width: `${segment.width_percent}%` }}
                        >
                            <TooltipContent
                                side="top"
                                className="bg-black text-white px-3 py-1.5 text-xs rounded shadow-xl border-none animate-in fade-in zoom-in duration-200 z-[100] whitespace-nowrap mb-1"
                            >
                                <div className="space-y-0.5" dir={segment.title ? "rtl" : "ltr"}>
                                    <p className="font-bold flex items-center justify-between gap-4" dir="ltr">
                                        <span>Pages {segment.start}-{segment.end}</span>
                                        <span className="opacity-70 uppercase tracking-tighter text-[9px]">{type}</span>
                                    </p>
                                    {segment.title && (
                                        <p className="opacity-90 font-arabic text-sm mt-0.5">
                                            {segment.title}
                                        </p>
                                    )}
                                    <p className={cn("text-[10px]", isCompleted ? "text-green-400" : "text-gray-400")} dir="ltr">
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
