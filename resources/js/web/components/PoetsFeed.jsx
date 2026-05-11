import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { User, BookOpen } from 'lucide-react';
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Link } from 'react-router-dom';
import { useInView } from 'react-intersection-observer';
import { getImageUrl } from '../utils/url';
// import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"; // Removing Tabs for now as we are just listing

const PoetsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const [search, setSearch] = useState('');
    const [selectedTag, setSelectedTag] = useState('all');
    const slugToTitle = (slug = '') =>
        slug
            .split('-')
            .filter(Boolean)
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');

    // Fetch tags
    const { data: tagsData } = useQuery({
        queryKey: ['poet-tags', lang],
        queryFn: async () => {
            try {
                const response = await api.get('/v1/poet-tags');
                return response.data;
            } catch (error) {
                return [];
            }
        },
        retry: false,
        refetchOnWindowFocus: false,
        staleTime: 5 * 60 * 1000,
    });

    const tags = tagsData || [];

    // Fetch poets from API with Infinite Scroll
    const {
        data,
        isLoading,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage
    } = useInfiniteQuery({
        queryKey: ['poets', search, selectedTag, lang],
        queryFn: async ({ pageParam = 1 }) => {
            try {
                // In real app, might want to debounce search here or in the UI
                const params = { search, page: pageParam };
                if (selectedTag !== 'all') {
                    params.tag = selectedTag;
                }
                const response = await api.get('/v1/poets', {
                    params
                });
                return response.data;
            } catch (error) {
                return {
                    data: [],
                    current_page: pageParam,
                    last_page: pageParam,
                    total: 0,
                    per_page: 20,
                    from: null,
                    to: null,
                };
            }
        },
        getNextPageParam: (lastPage) => {
            if (lastPage.current_page < lastPage.last_page) {
                return lastPage.current_page + 1;
            }
            return undefined;
        },
        retry: false,
        refetchOnWindowFocus: false,
        staleTime: 60 * 1000,
    });

    const poets = (data?.pages.flatMap(page => page.data) || []);

    // If API is down in production, hydrate with local static poets list.
    const {
        data: staticPoets = [],
        isLoading: isLoadingStaticPoets,
    } = useQuery({
        queryKey: ['static-poets-fallback', lang, search],
        queryFn: async () => {
            const response = await fetch('/json/poets.json', { cache: 'no-store' });
            if (!response.ok) return [];
            const data = await response.json();
            const normalized = (Array.isArray(data) ? data : []).map((item, idx) => {
                const route = String(item.route || '');
                const slug = route.split('/').filter(Boolean).pop() || `poet-${idx}`;
                const nameSd = String(item.keyword || '').trim() || slugToTitle(slug);
                return {
                    id: `static-${idx}`,
                    slug,
                    avatar: null,
                    name_sd: nameSd,
                    name_en: slugToTitle(slug),
                    bio_sd: '',
                    bio_en: '',
                    entries_count: 0,
                };
            });
            if (!search.trim()) return normalized;
            const q = search.trim().toLowerCase();
            return normalized.filter(p =>
                p.name_en.toLowerCase().includes(q) ||
                p.slug.toLowerCase().includes(q) ||
                p.name_sd.includes(search.trim())
            );
        },
        enabled: poets.length === 0 && !isLoading,
        retry: false,
        refetchOnWindowFocus: false,
        staleTime: 60 * 1000,
    });

    const displayedPoets = poets.length > 0 ? poets : staticPoets;

    // Intersection Observer for infinite scroll
    const { ref } = useInView({
        threshold: 0,
        onChange: (inView) => {
            if (inView && hasNextPage && !isFetchingNextPage) {
                fetchNextPage();
            }
        },
    });

    const PoetCard = ({ poet }) => (
        <div className="flex items-center gap-6 p-6 border-b border-gray-100 bg-white transition-colors group">
            <Link to={`/${lang}/poet/${poet.slug}`}>
                <Avatar className="h-16 w-16 md:h-20 md:w-20 border border-gray-100">
                    <AvatarImage
                        src={getImageUrl(poet.avatar, 'poet')}
                        alt=""
                        className="object-cover"
                        loading="lazy"
                        decoding="async"
                    />
                                    <AvatarFallback className="bg-muted">
                                        <User className="h-7 w-7 md:h-10 md:w-10 text-muted-foreground" strokeWidth={1.75} />
                                    </AvatarFallback>
                </Avatar>
            </Link>

            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    <Link to={`/${lang}/poet/${poet.slug}`} className="hover:underline">
                        <h3 className={`text-lg md:text-xl font-bold text-gray-900 truncate ${isRtl ? 'font-arabic' : ''}`}>
                            {isRtl ? poet.name_sd : poet.name_en}
                        </h3>
                    </Link>
                </div>

                <p className="text-gray-500 text-sm md:text-base line-clamp-2 mb-2 font-arabic">
                    {isRtl ? poet.bio_sd : poet.bio_en}
                </p>

                <div className="flex items-center gap-4 text-xs text-gray-400 font-medium">
                    <span className="flex items-center gap-1">
                        <BookOpen className="h-3 w-3" /> {poet.entries_count || 0} {isRtl ? 'لکڻيون' : 'Entries'}
                    </span>
                </div>
            </div>

            <Button
                variant="outline"
                asChild
                className="rounded-full hidden sm:flex items-center gap-2 hover:bg-gray-50 transition-colors"
            >
                <Link to={`/${lang}/poet/${poet.slug}`}>
                    <User className="h-4 w-4" />
                    <span>{isRtl ? 'کاتو' : 'Profile'}</span>
                </Link>
            </Button>
        </div>
    );

    const shouldShowSkeleton = displayedPoets.length === 0 && (isLoading || isLoadingStaticPoets);

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-6">
            <div className="mb-8">
                <h1 className="text-3xl font-bold mb-6">{isRtl ? 'شاعر' : 'Poets'}</h1>

                {/* Tags Menu */}
                <div className="flex items-center gap-8 border-b border-gray-100 overflow-x-auto no-scrollbar pb-1">
                    <button
                        onClick={() => setSelectedTag('all')}
                        className={`pb-3 text-base font-medium whitespace-nowrap transition-colors relative
                            ${selectedTag === 'all'
                                ? 'text-black font-bold'
                                : 'text-gray-400 hover:text-gray-600'
                            }`}
                    >
                        {isRtl ? 'سڀ' : 'All'}
                        {selectedTag === 'all' && (
                            <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-black rounded-full" />
                        )}
                    </button>

                    {tags.map(tag => (
                        <button
                            key={tag.slug}
                            onClick={() => setSelectedTag(tag.slug)}
                            className={`pb-3 text-base font-medium whitespace-nowrap transition-colors relative
                                ${selectedTag === tag.slug
                                    ? 'text-black font-bold'
                                    : 'text-gray-400 hover:text-gray-600'
                                }`}
                        >
                            {tag.tag}
                            {selectedTag === tag.slug && (
                                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-black rounded-full" />
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-4">
                {shouldShowSkeleton ? (
                    Array(5).fill(0).map((_, i) => (
                        <div key={i} className="flex items-center gap-4 p-4 border rounded-lg bg-white shadow-sm border-gray-100">
                            <Skeleton className="h-16 w-16 rounded-full" />
                            <div className="flex-1 space-y-2">
                                <Skeleton className="h-5 w-1/3" />
                                <Skeleton className="h-4 w-2/3" />
                            </div>
                            <Skeleton className="h-9 w-24 rounded-full" />
                        </div>
                    ))
                ) : displayedPoets.length > 0 ? (
                    displayedPoets.map(poet => <PoetCard key={poet.id} poet={poet} />)
                ) : (
                    <div className="py-20 text-center text-gray-500">
                        {isRtl ? 'ڪو به شاعر نه مليو' : 'No poets found.'}
                    </div>
                )}

                {/* Loading indicator for next page */}
                {isFetchingNextPage && (
                    <div className="py-4 text-center">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div>
                    </div>
                )}

                {/* Sentinel for infinite scroll */}
                <div ref={ref} className="h-4 w-full" />
            </div>
        </div>
    );
};

export default PoetsFeed;
