import React, { useState, useEffect } from 'react';
import PostCard from './PostCard';
import { Separator } from '@/components/ui/separator';
import PostCardSkeleton from './skeletons/PostCardSkeleton';
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { useInView } from 'react-intersection-observer';
import axios from 'axios';
import { ScrollText } from 'lucide-react';

const CoupletsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [activeTab, setActiveTab] = useState('all');
    const { ref, inView } = useInView();

    // Fetch Couplet Tags
    const { data: tagsData } = useQuery({
        queryKey: ['couplet-tags', lang],
        queryFn: async () => {
            const response = await axios.get(`/api/v1/couplet-tags?lang=${lang}`);
            return response.data;
        }
    });

    const topics = [
        { slug: 'all', name: isRtl ? 'سڀ' : 'All' },
        ...(tagsData || [])
    ];

    // Fetch Couplets Feed
    const {
        data: coupletsData,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage,
        isLoading
    } = useInfiniteQuery({
        queryKey: ['couplets-feed', activeTab, lang],
        queryFn: async ({ pageParam = 1 }) => {
            const response = await axios.get(`/api/v1/couplets`, {
                params: {
                    lang,
                    tag: activeTab,
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
    });

    useEffect(() => {
        if (inView && hasNextPage && !isFetchingNextPage) {
            fetchNextPage();
        }
    }, [inView, hasNextPage, isFetchingNextPage, fetchNextPage]);

    const LoadingState = () => (
        <div className="space-y-8 mt-0">
            {[1, 2, 3].map((i) => (
                <div key={i}>
                    <PostCardSkeleton is_couplet={true} />
                    {i < 3 && <Separator className="bg-gray-100" />}
                </div>
            ))}
        </div>
    );

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-6">
            <div className="sticky top-[65px] bg-white pt-2 pb-0 z-40 border-b border-gray-100 mb-8 overflow-x-auto no-scrollbar">
                <div className="flex items-center gap-8 min-w-max pb-4">
                    {topics.map((topic) => (
                        <button
                            key={topic.slug}
                            onClick={() => setActiveTab(topic.slug)}
                            className={`text-sm font-medium whitespace-nowrap transition-colors relative ${activeTab === topic.slug ? 'text-black' : 'text-gray-500 hover:text-gray-800'}`}
                        >
                            {topic.name}
                            {activeTab === topic.slug && (
                                <div className="absolute -bottom-4 left-0 right-0 h-0.5 bg-black" />
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-0">
                {isLoading ? (
                    <LoadingState />
                ) : coupletsData?.pages[0]?.data.length > 0 ? (
                    <>
                        {coupletsData.pages.map((page, i) => (
                            <React.Fragment key={i}>
                                {page.data.map((post, idx) => (
                                    <React.Fragment key={post.id}>
                                        <PostCard {...post} lang={lang} is_couplet={true} />
                                        {(i < coupletsData.pages.length - 1 || idx < page.data.length - 1) && (
                                            <Separator className="bg-gray-50/50" />
                                        )}
                                    </React.Fragment>
                                ))}
                            </React.Fragment>
                        ))}

                        <div ref={ref} className="py-12 flex justify-center">
                            {isFetchingNextPage && (
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                            )}
                        </div>
                    </>
                ) : (
                    <div className="py-20 text-center">
                        <ScrollText className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500 font-medium">
                            {isRtl ? 'هن وقت ڪو به بيت موجود ناهي.' : 'No couplets available at the moment.'}
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default CoupletsFeed;
