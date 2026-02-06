import React, { useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { useInfiniteQuery } from '@tanstack/react-query';
import { useInView } from 'react-intersection-observer';
import axios from 'axios';
import PostCard from './PostCard';
import { Separator } from '@/components/ui/separator';
import PostCardSkeleton from './skeletons/PostCardSkeleton';
import { BookOpen } from 'lucide-react';

const GenreDetailsModal = ({ isOpen, onClose, genre, lang }) => {
    const isRtl = lang === 'sd';
    const { ref, inView } = useInView();

    const {
        data,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage,
        isLoading,
        isError
    } = useInfiniteQuery({
        queryKey: ['genre-poetry', genre?.slug, lang],
        queryFn: async ({ pageParam = 1 }) => {
            const response = await axios.get(`/api/v1/feed`, {
                params: {
                    lang,
                    category: genre.slug,
                    page: pageParam
                }
            });
            return response.data;
        },
        getNextPageParam: (lastPage) => {
            if (lastPage.current_page < lastPage.last_page) {
                return lastPage.current_page + 1;
            }
            return undefined;
        },
        enabled: !!genre && isOpen,
    });

    useEffect(() => {
        if (inView && hasNextPage && !isFetchingNextPage) {
            fetchNextPage();
        }
    }, [inView, hasNextPage, isFetchingNextPage, fetchNextPage]);

    if (!genre) return null;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl w-[95vw] max-h-[90vh] flex flex-col p-0 overflow-hidden gap-0">
                <DialogHeader className="p-6 pb-4 border-b">
                    <DialogTitle className={`text-2xl font-bold ${isRtl ? 'font-arabic text-right' : 'text-left'}`}>
                        {isRtl ? genre.sd_name : genre.en_name}
                    </DialogTitle>
                    <DialogDescription className={`${isRtl ? 'text-right' : 'text-left'} mt-2 text-gray-600`}>
                        {genre.desc || (isRtl ? 'هن صنف جي شاعري جو مجموعو.' : 'A collection of poetry in this genre.')}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto no-scrollbar">
                    <div className="p-6 pt-0">
                        {isLoading ? (
                            <div className="space-y-8 mt-6">
                                {[1, 2, 3].map((i) => (
                                    <div key={i}>
                                        <PostCardSkeleton />
                                        {i < 3 && <Separator className="bg-gray-100" />}
                                    </div>
                                ))}
                            </div>
                        ) : data?.pages[0]?.data.length > 0 ? (
                            <div className="space-y-0">
                                {data.pages.map((page, i) => (
                                    <React.Fragment key={i}>
                                        {page.data.map((post, idx) => (
                                            <React.Fragment key={post.id}>
                                                <PostCard {...post} lang={lang} />
                                                {(i < data.pages.length - 1 || idx < page.data.length - 1) && (
                                                    <Separator className="bg-gray-50/50" />
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </React.Fragment>
                                ))}

                                <div ref={ref} className="py-8 flex justify-center">
                                    {isFetchingNextPage && (
                                        <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                                    )}
                                </div>
                            </div>
                        ) : (
                            <div className="py-20 text-center">
                                <BookOpen className="h-10 w-10 text-gray-200 mx-auto mb-4" />
                                <p className="text-gray-500 font-medium">
                                    {isRtl ? 'هن وقت ڪا بہ شاعري موجود ناهي.' : 'No poetry available for this genre yet.'}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default GenreDetailsModal;
