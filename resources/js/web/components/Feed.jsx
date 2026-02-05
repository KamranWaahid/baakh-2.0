import React from 'react';
import PostCard from './PostCard';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';

const Feed = ({ lang }) => {
    const isRtl = lang === 'sd';

    const [posts, setPosts] = React.useState([]);
    const [loading, setLoading] = React.useState(true);
    const [page, setPage] = React.useState(1);
    const [hasMore, setHasMore] = React.useState(true);
    const [isFetchingMore, setIsFetchingMore] = React.useState(false);
    const observer = React.useRef();

    const lastPostElementRef = React.useCallback(node => {
        if (loading || isFetchingMore) return;
        if (observer.current) observer.current.disconnect();
        observer.current = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && hasMore) {
                setPage(prevPage => prevPage + 1);
            }
        }, {
            rootMargin: '100px', // Start loading 100px before reaching the bottom
            threshold: 0.1
        });
        if (node) observer.current.observe(node);
    }, [loading, isFetchingMore, hasMore]);

    const fetchFeed = async (pageNumber, isInitial = false) => {
        if (isInitial) setLoading(true);
        else setIsFetchingMore(true);

        try {
            const module = await import('../../admin/api/axios');
            const api = module.default;
            const response = await api.get('/api/v1/feed', {
                params: { lang, page: pageNumber }
            });

            const newPosts = response.data.data;
            setPosts(prev => isInitial ? newPosts : [...prev, ...newPosts]);

            // Laravel paginator keys: current_page, last_page, next_page_url
            const moreAvailable = response.data.current_page < response.data.last_page;
            setHasMore(moreAvailable);
        } catch (error) {
            console.error("Failed to fetch feed", error);
            setHasMore(false);
        } finally {
            if (isInitial) setLoading(false);
            else setIsFetchingMore(false);
        }
    };

    React.useEffect(() => {
        setPage(1);
        setPosts([]);
        fetchFeed(1, true);
    }, [lang]);

    React.useEffect(() => {
        if (page > 1) {
            fetchFeed(page);
        }
    }, [page]);

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

    const FeedContent = ({ items }) => (
        <>
            {items.map((post, i) => {
                const isLastElement = items.length === i + 1;
                return (
                    <React.Fragment key={post.id || i}>
                        <div ref={isLastElement ? lastPostElementRef : null}>
                            <PostCard lang={lang} {...post} />
                        </div>
                        {i < items.length - 1 && <Separator className="bg-gray-100" />}
                    </React.Fragment>
                );
            })}
            {isFetchingMore && (
                <div className="py-8">
                    <PostCardSkeleton />
                </div>
            )}
        </>
    );

    return (
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 py-6" dir={isRtl ? 'rtl' : 'ltr'}>
            <Tabs defaultValue="for-you" className="w-full" dir={isRtl ? 'rtl' : 'ltr'}>
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

                <TabsContent value="for-you" className="space-y-8 mt-0">
                    {loading ? <LoadingState /> : (posts && posts.length > 0) ? (
                        <FeedContent items={posts} />
                    ) : (
                        <div className="py-20 text-center text-gray-500">
                            {isRtl ? 'ڪوبه مواد نه مليو.' : 'No content found.'}
                        </div>
                    )}
                </TabsContent>

                <TabsContent value="featured" className="space-y-8 mt-0">
                    {loading ? <LoadingState /> : (posts && posts.length > 0) ? (
                        <FeedContent items={posts.slice(0, 10)} />
                    ) : (
                        <div className="py-20 text-center text-gray-500">
                            {isRtl ? 'ڪوبه مواد نه مليو.' : 'No content found.'}
                        </div>
                    )}
                </TabsContent>
            </Tabs>
        </div>
    );
};

export default Feed;
