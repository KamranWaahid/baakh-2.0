import React from 'react';
import PostCard from './PostCard';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';

const Feed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [activeTab, setActiveTab] = React.useState('for-you');

    // Separate states for each tab to preserve scroll and items
    const [feeds, setFeeds] = React.useState({
        'for-you': { posts: [], loading: true, page: 1, hasMore: true, isFetchingMore: false },
        'featured': { posts: [], loading: true, page: 1, hasMore: true, isFetchingMore: false }
    });

    const currentFeed = feeds[activeTab];
    const observer = React.useRef();

    const lastPostElementRef = React.useCallback(node => {
        if (currentFeed.loading || currentFeed.isFetchingMore) return;
        if (observer.current) observer.current.disconnect();
        observer.current = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && currentFeed.hasMore) {
                setFeeds(prev => ({
                    ...prev,
                    [activeTab]: { ...prev[activeTab], page: prev[activeTab].page + 1 }
                }));
            }
        }, {
            rootMargin: '100px',
            threshold: 0.1
        });
        if (node) observer.current.observe(node);
    }, [activeTab, currentFeed.loading, currentFeed.isFetchingMore, currentFeed.hasMore]);

    const fetchFeedData = async (tab, pageNumber, isInitial = false) => {
        if (isInitial) {
            setFeeds(prev => ({ ...prev, [tab]: { ...prev[tab], loading: true } }));
        } else {
            setFeeds(prev => ({ ...prev, [tab]: { ...prev[tab], isFetchingMore: true } }));
        }

        try {
            const module = await import('../../admin/api/axios');
            const api = module.default;
            const response = await api.get('/api/v1/feed', {
                params: {
                    lang,
                    page: pageNumber,
                    filter: tab === 'featured' ? 'featured' : undefined
                }
            });

            const newPosts = response.data.data;
            setFeeds(prev => ({
                ...prev,
                [tab]: {
                    ...prev[tab],
                    posts: isInitial ? newPosts : [...prev[tab].posts, ...newPosts],
                    hasMore: response.data.current_page < response.data.last_page,
                    loading: false,
                    isFetchingMore: false
                }
            }));
        } catch (error) {
            console.error(`Failed to fetch ${tab} feed`, error);
            setFeeds(prev => ({ ...prev, [tab]: { ...prev[tab], loading: false, isFetchingMore: false, hasMore: false } }));
        }
    };

    // Initial load and lang change
    React.useEffect(() => {
        setFeeds({
            'for-you': { posts: [], loading: true, page: 1, hasMore: true, isFetchingMore: false },
            'featured': { posts: [], loading: true, page: 1, hasMore: true, isFetchingMore: false }
        });
        fetchFeedData('for-you', 1, true);
        fetchFeedData('featured', 1, true);
    }, [lang]);

    // Page change trigger
    React.useEffect(() => {
        if (currentFeed.page > 1) {
            fetchFeedData(activeTab, currentFeed.page);
        }
    }, [currentFeed.page, activeTab]);

    const LoadingState = () => (
        <div className="space-y-8 mt-0">
            {[1, 2, 3].map((i) => (
                <div key={i}>
                    <PostCardSkeleton />
                    {i < 3 && <Separator className="bg-gray-100" />}
                </div>
            ))}
        </div>
    );

    const FeedContent = ({ feedType }) => {
        const feed = feeds[feedType];
        return (
            <div className="space-y-8 mt-0">
                {feed.loading ? <LoadingState /> : (feed.posts && feed.posts.length > 0) ? (
                    <>
                        {feed.posts.map((post, i) => {
                            const isLastElement = feed.posts.length === i + 1;
                            return (
                                <React.Fragment key={post.id || `${feedType}-${i}`}>
                                    <div ref={isLastElement ? lastPostElementRef : null}>
                                        <PostCard lang={lang} {...post} />
                                    </div>
                                    {i < feed.posts.length - 1 && <Separator className="bg-gray-100" />}
                                </React.Fragment>
                            );
                        })}
                        {feed.isFetchingMore && (
                            <div className="py-8">
                                <PostCardSkeleton />
                            </div>
                        )}
                    </>
                ) : (
                    <div className="py-20 text-center text-gray-500">
                        {isRtl ? 'ڪوبه مواد نه مليو.' : 'No content found.'}
                    </div>
                )}
            </div>
        );
    };

    return (
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 py-6" dir={isRtl ? 'rtl' : 'ltr'}>
            <Tabs defaultValue="for-you" className="w-full" onValueChange={setActiveTab} dir={isRtl ? 'rtl' : 'ltr'}>
                <div className="sticky top-[65px] bg-white/95 backdrop-blur-sm pt-2 pb-0 z-40 border-b border-gray-100 mb-8">
                    <TabsList className="bg-transparent p-0 h-auto justify-start border-b-0 w-full rounded-none">
                        <TabsTrigger
                            value="for-you"
                            className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3"
                        >
                            {isRtl ? 'توهان لاءِ' : 'For you'}
                        </TabsTrigger>
                        <TabsTrigger
                            value="featured"
                            className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3"
                        >
                            {isRtl ? 'چونڊيل' : 'Featured'}
                        </TabsTrigger>
                    </TabsList>
                </div>

                <TabsContent value="for-you" className="mt-0">
                    <FeedContent feedType="for-you" />
                </TabsContent>

                <TabsContent value="featured" className="mt-0">
                    <FeedContent feedType="featured" />
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default Feed;
