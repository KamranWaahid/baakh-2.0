import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import PostCard from './PostCard';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import PostCardSkeleton from './skeletons/PostCardSkeleton';
import { useAuth } from '../contexts/AuthContext';

// Reuse FeedContent logic or create simplified version
const TopicFeedContent = ({ posts, loading, isFetchingMore, isRtl, lastPostElementRef, lang }) => {
    return (
        <div className="space-y-8 mt-0 animate-fade-in-up">
            {loading ? (
                <div className="space-y-8 mt-0">
                    {[1, 2, 3].map((i) => (
                        <div key={i}>
                            <PostCardSkeleton />
                            {i < 3 && <Separator className="bg-gray-100" />}
                        </div>
                    ))}
                </div>
            ) : (posts && posts.length > 0) ? (
                <>
                    {posts.map((post, i) => {
                        const isLastElement = posts.length === i + 1;
                        return (
                            <React.Fragment key={post.id || i}>
                                <div ref={isLastElement ? lastPostElementRef : null}>
                                    <PostCard
                                        lang={lang}
                                        {...post}
                                        // Pass specific prop to show category in header ONLY for this feed
                                        showCategoryHeader={true}
                                    />
                                </div>
                                {i < posts.length - 1 && <Separator className="bg-gray-100" />}
                            </React.Fragment>
                        );
                    })}
                    {isFetchingMore && (
                        <div className="py-8">
                            <PostCardSkeleton />
                        </div>
                    )}
                </>
            ) : (
                <div className="py-20 flex flex-col items-center justify-center text-center">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                        {isRtl ? 'هن موضوع ۾ ڪو به مواد ناهي' : 'No stories in this topic'}
                    </h3>
                </div>
            )}
        </div>
    );
};

const TopicFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { slug } = useParams(); // Topic slug
    const [categoryDetails, setCategoryDetails] = useState(null);
    const [feed, setFeed] = useState({ posts: [], loading: true, page: 1, hasMore: true, isFetchingMore: false });

    // Fetch Category Details
    useEffect(() => {
        const fetchDetails = async () => {
            try {
                const module = await import('../../admin/api/axios');
                const api = module.default;
                const response = await api.get(`/api/v1/categories/${slug}`, { params: { lang } });
                setCategoryDetails(response.data);
            } catch (error) {
                console.error("Failed to fetch category details", error);
            }
        };
        fetchDetails();
    }, [slug, lang]);

    // Fetch Feed Data
    const fetchFeed = async (pageNumber, isInitial = false) => {
        if (isInitial) {
            setFeed(prev => ({ ...prev, loading: true }));
        } else {
            setFeed(prev => ({ ...prev, isFetchingMore: true }));
        }

        try {
            const module = await import('../../admin/api/axios');
            const api = module.default;
            const response = await api.get('/api/v1/feed', {
                params: {
                    lang,
                    page: pageNumber,
                    category: slug // Use the slug as category filter
                }
            });

            const newPosts = response.data.data;
            setFeed(prev => ({
                ...prev,
                posts: isInitial ? newPosts : [...prev.posts, ...newPosts],
                hasMore: response.data.current_page < response.data.last_page,
                loading: false,
                isFetchingMore: false
            }));
        } catch (error) {
            console.error("Failed to fetch topic feed", error);
            setFeed(prev => ({ ...prev, loading: false, isFetchingMore: false }));
        }
    };

    useEffect(() => {
        setFeed({ posts: [], loading: true, page: 1, hasMore: true, isFetchingMore: false });
        fetchFeed(1, true);
    }, [slug, lang]);

    // Infinite Scroll
    const observer = useRef();
    const lastPostElementRef = useCallback(node => {
        if (feed.loading || feed.isFetchingMore) return;
        if (observer.current) observer.current.disconnect();
        observer.current = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && feed.hasMore) {
                setFeed(prev => ({ ...prev, page: prev.page + 1 }));
            }
        });
        if (node) observer.current.observe(node);
    }, [feed.loading, feed.isFetchingMore, feed.hasMore]);

    useEffect(() => {
        if (feed.page > 1) {
            fetchFeed(feed.page);
        }
    }, [feed.page]);

    return (
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 pt-8 md:pt-12 pb-6 bg-white" dir={isRtl ? 'rtl' : 'ltr'}>
            {/* Category Header */}
            <div className="text-center mb-12 border-b border-gray-100 pb-12">
                <h1 className={`text-3xl md:text-5xl font-bold text-gray-900 mb-4 ${isRtl ? 'font-arabic' : ''}`}>
                    {categoryDetails?.name || <div className="h-10 w-40 bg-gray-100 rounded-lg mx-auto animate-pulse" />}
                </h1>
                <div className="flex items-center justify-center gap-2 text-gray-500 text-sm mb-6">
                    <span>{isRtl ? 'موضوع' : 'Topic'}</span>
                    <span>·</span>
                    <span>{categoryDetails?.count ? `${categoryDetails.count} ${isRtl ? 'پوسٽون' : 'stories'}` : (isRtl ? 'ڪي به پوسٽون ناهن' : 'No stories yet')}</span>
                </div>
                <Button
                    disabled
                    className="rounded-full bg-black hover:bg-black/80 text-white px-6 h-9 text-sm font-medium"
                >
                    {isRtl ? 'فالو ڪري رهيا آهيو' : 'Following'}
                </Button>
            </div>

            {/* Feed Header */}
            <div className="mb-6">
                <h2 className={`text-xl font-bold text-gray-900 ${isRtl ? 'font-arabic' : ''}`}>
                    {isRtl ? 'تجويز ڪيل ڪهاڻيون' : 'Recommended stories'}
                </h2>
            </div>

            {/* Feed Content */}
            <TopicFeedContent
                posts={feed.posts}
                loading={feed.loading}
                isFetchingMore={feed.isFetchingMore}
                isRtl={isRtl}
                lastPostElementRef={lastPostElementRef}
                lang={lang}
            />
        </div>
    );
};

export default TopicFeed;
