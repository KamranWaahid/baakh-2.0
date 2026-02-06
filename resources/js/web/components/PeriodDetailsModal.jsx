import React, { useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { useInView } from 'react-intersection-observer';
import axios from 'axios';
import PostCard from './PostCard';
import { Separator } from '@/components/ui/separator';
import PostCardSkeleton from './skeletons/PostCardSkeleton';
import { BookOpen, User } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

const PeriodDetailsModal = ({ isOpen, onClose, period, lang }) => {
    const isRtl = lang === 'sd';
    const { ref, inView } = useInView();

    // Fetch Poets for this period
    const { data: poets, isLoading: isPoetsLoading } = useQuery({
        queryKey: ['period-poets', period?.id, lang],
        queryFn: async () => {
            const response = await axios.get(`/api/v1/periods/${period.id}/poets?lang=${lang}`);
            return response.data;
        },
        enabled: !!period && isOpen,
    });

    const {
        data,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage,
        isLoading,
        isError
    } = useInfiniteQuery({
        queryKey: ['period-poetry', period?.id, lang],
        queryFn: async ({ pageParam = 1 }) => {
            const response = await axios.get(`/api/v1/feed`, {
                params: {
                    lang,
                    period_id: period.id,
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
        enabled: !!period && isOpen,
    });

    useEffect(() => {
        if (inView && hasNextPage && !isFetchingNextPage) {
            fetchNextPage();
        }
    }, [inView, hasNextPage, isFetchingNextPage, fetchNextPage]);

    if (!period) return null;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-2xl w-[95vw] max-h-[90vh] flex flex-col p-0 overflow-hidden gap-0">
                <DialogHeader className="p-6 pb-4 border-b">
                    <DialogTitle className={`text-2xl font-bold ${isRtl ? 'font-arabic text-right' : 'text-left'}`}>
                        {isRtl ? period.title_sd : period.title_en}
                    </DialogTitle>
                    <div className={`flex items-center gap-1.5 mt-1 text-xs font-semibold text-gray-400 uppercase tracking-wider ${isRtl ? 'justify-end' : 'justify-start'}`}>
                        <span>{period.date_range}</span>
                    </div>
                    <DialogDescription className={`${isRtl ? 'text-right' : 'text-left'} mt-4 text-gray-600`}>
                        {isRtl ? period.description_sd : period.description_en}
                    </DialogDescription>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto no-scrollbar">
                    {/* Poets Section */}
                    {poets?.length > 0 && (
                        <div className="p-6 pb-2">
                            <h4 className={`text-sm font-bold text-gray-900 mb-4 ${isRtl ? 'text-right font-arabic' : 'text-left'}`}>
                                {isRtl ? 'هن دور جا شاعر' : 'Poets of this Period'}
                            </h4>
                            <div className={`flex items-center gap-4 overflow-x-auto no-scrollbar pb-2 ${isRtl ? 'flex-row-reverse' : 'flex-row'}`}>
                                {poets.map((poet) => (
                                    <Link
                                        key={poet.id}
                                        to={`/${lang}/poet/${poet.slug}`}
                                        className="flex flex-col items-center gap-2 min-w-[80px] group"
                                    >
                                        <Avatar className="h-14 w-14 border border-gray-100 group-hover:border-black transition-colors">
                                            <AvatarImage src={poet.image} />
                                            <AvatarFallback><User className="h-6 w-6 text-gray-300" /></AvatarFallback>
                                        </Avatar>
                                        <span className={`text-[10px] font-medium text-center line-clamp-1 group-hover:text-black ${isRtl ? 'font-arabic' : ''}`}>
                                            {poet.laqab || poet.name}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                            <Separator className="mt-4 bg-gray-50" />
                        </div>
                    )}

                    <div className="p-6 pt-2">
                        <h4 className={`text-sm font-bold text-gray-900 mb-6 mt-2 ${isRtl ? 'text-right font-arabic' : 'text-left'}`}>
                            {isRtl ? 'هن دور جي شاعري' : 'Poetry of this Period'}
                        </h4>

                        {isLoading ? (
                            <div className="space-y-8 mt-2">
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
                                <p className="text-gray-500 font-medium text-sm">
                                    {isRtl ? 'هن دور سان لاڳاپيل ڪو به ڪم نه مليو.' : 'No works found for this period yet.'}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default PeriodDetailsModal;
