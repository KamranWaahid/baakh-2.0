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
            <div className="space-y-8 mt-0 animate-fade-in-up">
                {feed.loading ? <LoadingState /> : (feed.posts && feed.posts.length > 0) ? (
                    <>
                        {feed.posts.map((post, i) => {
                            const isLastElement = feed.posts.length === i + 1;
                            return (
                                <React.Fragment key={post.id || `${feedType}-${i}`}>
                                    <div ref={isLastElement ? lastPostElementRef : null}>
                                        <PostCard lang={lang} {...post} showStar={feedType === 'featured'} />
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
                    <div className="py-20 flex flex-col items-center justify-center text-center">
                        <div className="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <svg className="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                            {isRtl ? 'ڪوبه مواد نه مليو' : 'No content found'}
                        </h3>
                        <p className="text-gray-500 mb-6 max-w-sm">
                            {isRtl ? 'مهرباني ڪري بعد ۾ واپس چيڪ ڪريو يا ٻيو ڪيٽيگري ڏسو.' : 'Check back later or explore other categories.'}
                        </p>
                    </div>
                )}
            </div>
        );
    };

    return (
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 py-6" dir={isRtl ? 'rtl' : 'ltr'}>
            <Tabs defaultValue="for-you" className="w-full" onValueChange={setActiveTab} dir={isRtl ? 'rtl' : 'ltr'}>
                <div className="sticky top-[65px] bg-white pt-2 pb-0 z-40 border-b border-gray-100 mb-8">
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
