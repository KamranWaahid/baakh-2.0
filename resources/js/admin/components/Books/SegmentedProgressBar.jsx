import React from 'react';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { cn } from "@/lib/utils";

const SegmentedProgressBar = ({ segments, className }) => {
    if (!segments || segments.length === 0) return null;

    return (
        <TooltipProvider>
            <div className={cn("w-full h-2 flex rounded-full overflow-hidden bg-gray-100 border border-gray-200/50", className)}>
                {segments.map((segment, index) => {
                    const isCompleted = segment.is_completed;
                    const type = segment.type;

                    // Branded colors based on type and completion
                    let bgColor = "bg-gray-200"; // Default pending
                    if (isCompleted) {
                        bgColor = "bg-black"; // Standard completed as per screenshot
                        if (type === 'poetry') bgColor = "bg-gray-900";
                        if (type === 'cover') bgColor = "bg-slate-800";
                    }

                    return (
                        <Tooltip key={index}>
                            <TooltipTrigger asChild>
                                <div
                                    className={cn(
                                        "h-full transition-all hover:brightness-110 cursor-help border-r last:border-r-0 border-white/20",
                                        bgColor
                                    )}
                                    style={{ width: `${segment.width_percent}%` }}
                                />
                            </TooltipTrigger>
                            <TooltipContent side="top" className="bg-black text-white px-3 py-1.5 text-xs rounded shadow-lg border-none animate-in fade-in zoom-in duration-200">
                                <div className="space-y-0.5">
                                    <p className="font-bold flex items-center justify-between gap-4">
                                        <span>Pages {segment.start}-{segment.end}</span>
                                        <span className="opacity-70 uppercase tracking-tighter text-[9px]">{type}</span>
                                    </p>
                                    {segment.title && <p className="opacity-90">{segment.title}</p>}
                                    <p className={cn("text-[10px]", isCompleted ? "text-green-400" : "text-gray-400")}>
                                        {isCompleted ? "Completed" : "Pending"}
                                    </p>
                                </div>
                            </TooltipContent>
                        </Tooltip>
                    );
                })}
            </div>
        </TooltipProvider>
    );
};

export default SegmentedProgressBar;
