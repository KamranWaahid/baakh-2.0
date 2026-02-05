import React from 'react';
import { Skeleton } from '@/components/ui/skeleton';

const PostCardSkeleton = () => {
    return (
        <div className="py-8 first:pt-4 border-b border-gray-100 animate-pulse">
            <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                    <Skeleton className="h-5 w-5 rounded-full" />
                    <Skeleton className="h-4 w-24" />
                </div>

                <Skeleton className="h-8 w-3/4 mb-4" />

                <div className="flex items-center gap-3">
                    <Skeleton className="h-4 w-4" />
                    <Skeleton className="h-4 w-16" />
                    <Skeleton className="h-4 w-16" />
                </div>
            </div>
        </div>
    );
};

export default PostCardSkeleton;
