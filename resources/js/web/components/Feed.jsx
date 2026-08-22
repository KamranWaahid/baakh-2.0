import React from 'react';
import { useParams } from 'react-router-dom';
import PostCard from './PostCard';
import api from '@/admin/api/axios';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import PostCardSkeleton from './skeletons/PostCardSkeleton';
import { useStickyBelowNavbar } from '../hooks/useStickyBelowNavbar';
import { useAuth } from '../contexts/AuthContext';
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { useEffect } from 'react';

const slugLabel = (slug) => {
    if (!slug) return '';
    return String(slug)
        .replace(/[-_]+/g, ' ')
        .replace(/\b\w/g, (ch) => ch.toUpperCase());
};

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

const readBootstrapFeed = (lang, activeTab, urlCategory) => {
    if (activeTab !== 'for-you' || urlCategory) return undefined;
    const boot = typeof window !== 'undefined' ? window.__BAAKH_BOOTSTRAP_FEED__ : null;
    if (!boot || boot.lang !== lang || !boot.payload?.data) return undefined;
    return {
        pages: [boot.payload],
        pageParams: [1],
    };
};

const FeedContent = ({ feedType, feeds, lang, isRtl, lastPostElementRef }) => {
    const feed = feeds[feedType];
    return (
        <div className="space-y-8 mt-0">
            {feed.loading ? <LoadingState /> : feed.error ? (
                <div className="py-20 flex flex-col items-center justify-center text-center">
                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                        {isRtl ? 'فيڊ لوڊ نه ٿي سگهي' : 'Could not load feed'}
                    </h3>
                    <p className="text-gray-500 mb-6 max-w-sm">
                        {feed.errorMessage || (isRtl ? 'مهرباني ڪري صفحو ريفريش ڪريو.' : 'Please refresh the page and try again.')}
                    </p>
                    {feed.onRetry && (
                        <Button variant="outline" onClick={feed.onRetry}>
                            {isRtl ? 'ٻيهر ڪوشش' : 'Retry'}
                        </Button>
                    )}
                </div>
            ) : (feed.posts && feed.posts.length > 0) ? (
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

const Feed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { category: urlCategory } = useParams();
    const { user } = useAuth();
    const [activeTab, setActiveTab] = React.useState('for-you');
    const { stickyTopClass } = useStickyBelowNavbar();

    const { data: genres } = useQuery({
        queryKey: ['genres', lang],
        queryFn: async () => (await api.get(`/api/v1/categories?lang=${lang}`)).data,
        enabled: Boolean(urlCategory),
        staleTime: 60_000,
    });

    const activeGenre = React.useMemo(() => {
        if (!urlCategory || !Array.isArray(genres)) return null;
        const slug = String(urlCategory).toLowerCase();
        return genres.find((genre) => String(genre.slug || '').toLowerCase() === slug) || null;
    }, [genres, urlCategory]);

    const forYouLabel = urlCategory
        ? (isRtl
            ? (activeGenre?.sd_name || activeGenre?.name || urlCategory)
            : (activeGenre?.en_name || activeGenre?.name || slugLabel(urlCategory)))
        : (isRtl ? 'توهان لاءِ' : 'For you');

    // Unified Infinite Query for all tabs
    const {
        data,
        isLoading,
        isError,
        error,
        refetch,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage,
    } = useInfiniteQuery({
        queryKey: ['feed', activeTab, lang, urlCategory, user?.id],
        queryFn: async ({ pageParam = 1 }) => {
            const response = await api.get('/api/v1/feed', {
                params: {
                    lang,
                    page: pageParam,
                    filter: activeTab === 'featured' ? 'featured' : (activeTab === 'bookmarked' ? 'bookmarked' : undefined),
                    category: urlCategory || undefined
                }
            });
            return response.data;
        },
        getNextPageParam: (lastPage) => {
            return lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined;
        },
        placeholderData: () => readBootstrapFeed(lang, activeTab, urlCategory),
        staleTime: 0,
        gcTime: 60_000,
        refetchOnMount: 'always',
        refetchOnWindowFocus: true,
        enabled: activeTab !== 'bookmarked' || !!user,
        retry: 1,
    });

    const observer = React.useRef();
    const lastPostElementRef = React.useCallback(node => {
        if (isLoading || isFetchingNextPage) return;
        if (observer.current) observer.current.disconnect();
        observer.current = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && hasNextPage) {
                fetchNextPage();
            }
        }, {
            rootMargin: '100px',
            threshold: 0.1
        });
        if (node) observer.current.observe(node);
    }, [isLoading, isFetchingNextPage, hasNextPage, fetchNextPage]);

    const posts = (data?.pages ?? [])
        .flatMap((page) => Array.isArray(page?.data) ? page.data : [])
        .filter((post) => post && typeof post === 'object');

    const feedState = {
        posts,
        loading: isLoading,
        isFetchingMore: isFetchingNextPage,
        hasMore: hasNextPage,
        error: isError,
        errorMessage: error?.response?.data?.message || error?.message,
        onRetry: () => refetch(),
    };

    // Construct feeds object with same shape as before for FeedContent
    const feeds = {
        'for-you': activeTab === 'for-you' ? feedState : { posts: [], loading: false },
        'featured': activeTab === 'featured' ? feedState : { posts: [], loading: false },
        'bookmarked': activeTab === 'bookmarked' ? feedState : { posts: [], loading: false }
    };

    // Switch away from bookmarked if empty (only after load)
    useEffect(() => {
        if (activeTab === 'bookmarked' && !isLoading && posts.length === 0) {
            setActiveTab('for-you');
        }
    }, [activeTab, isLoading, posts.length]);

    const showBookmarked = !!user;

    return (
        <div className="flex-1 max-w-[720px] w-full mx-auto px-4 md:px-8 pt-2 pb-6 bg-white" dir={isRtl ? 'rtl' : 'ltr'}>
            <h1 className="sr-only">
                {urlCategory
                    ? (isRtl ? `${forYouLabel} | باک` : `${forYouLabel} | Baakh`)
                    : (isRtl ? 'باک - سنڌي شاعريءَ جو آرڪائيو' : 'Baakh - Archive of Sindhi Poetry')}
            </h1>
            <Tabs defaultValue="for-you" className="w-full" onValueChange={setActiveTab} dir={isRtl ? 'rtl' : 'ltr'}>
                <div
                    className={`sticky ${stickyTopClass} bg-white pt-0 pb-0 z-40 border-b border-gray-100 mb-8`}
                >
                    <TabsList className="bg-transparent p-0 h-auto justify-start border-b-0 w-full rounded-none">
                        <TabsTrigger
                            value="for-you"
                            className={`rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3 ${urlCategory && isRtl ? 'font-arabic' : ''}`}
                        >
                            {forYouLabel}
                        </TabsTrigger>
                        <TabsTrigger
                            value="featured"
                            className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3"
                        >
                            {isRtl ? 'چونڊيل' : 'Featured'}
                        </TabsTrigger>
                        {showBookmarked && (
                            <TabsTrigger
                                value="bookmarked"
                                className="rounded-none border-b-2 border-transparent data-[state=active]:border-black data-[state=active]:shadow-none data-[state=active]:text-black text-gray-500 pb-3"
                            >
                                {isRtl ? 'بوڪ مارڪ ڪيل' : 'Bookmarked'}
                            </TabsTrigger>
                        )}
                    </TabsList>
                </div>

                <TabsContent value="for-you" className="mt-0">
                    <FeedContent feedType="for-you" feeds={feeds} lang={lang} isRtl={isRtl} lastPostElementRef={lastPostElementRef} />
                </TabsContent>

                <TabsContent value="featured" className="mt-0">
                    <FeedContent feedType="featured" feeds={feeds} lang={lang} isRtl={isRtl} lastPostElementRef={lastPostElementRef} />
                </TabsContent>

                {showBookmarked && (
                    <TabsContent value="bookmarked" className="mt-0">
                        <FeedContent feedType="bookmarked" feeds={feeds} lang={lang} isRtl={isRtl} lastPostElementRef={lastPostElementRef} />
                    </TabsContent>
                )}
            </Tabs>
        </div>
    );
};

export default Feed;
