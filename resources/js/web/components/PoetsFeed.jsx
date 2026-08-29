import React, { useState, useEffect } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { User, BookOpen } from 'lucide-react';
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Avatar } from "@/components/ui/avatar";
import { Link } from 'react-router-dom';
import { useInView } from 'react-intersection-observer';
import AvatarImgOrIcon from './AvatarImgOrIcon';
import { useStickyBelowNavbar } from '../hooks/useStickyBelowNavbar';
import { htmlToPlainText } from '../utils/html';

const TAG_TRANSLATIONS = {
    'Revolutionary Poet': 'انقلابي شاعر',
    'Classical Poet': 'ڪلاسيڪل شاعر',
    'Young Poets': 'نوجوان شاعر',
    'Sufi Shair': 'صوفي شاعر',
    'Naujwan Shair': 'نوجوان شاعر',
    'Jadeed Shair': 'جديد شاعر',
    'Modern Poet': 'جديد شاعر',
    'Romantic Poet': 'رومانوي شاعر',
    'Poetees': 'شاعره',
    'Poetess': 'شاعره',
};

const TAG_SLUG_TRANSLATIONS = {
    'revolutionary-poet': 'انقلابي شاعر',
    'classical-poet': 'ڪلاسيڪل شاعر',
    'young-poets': 'نوجوان شاعر',
    'sufi-shair': 'صوفي شاعر',
    'naujwan-shair': 'نوجوان شاعر',
    'jadeed-shair': 'جديد شاعر',
    'modern-poet': 'جديد شاعر',
    'romantic-poet': 'رومانوي شاعر',
    'poetees': 'شاعره',
    'poetess': 'شاعره',
    'انقلابي شاعر': 'انقلابي شاعر',
};

const poetDisplayLaqab = (poet, isRtl) => {
    if (isRtl) {
        return poet.laqab_sd || poet.name_sd || poet.laqab_en || poet.name_en || poet.name || '';
    }
    return poet.laqab_en || poet.name_en || poet.laqab_sd || poet.name_sd || poet.name || '';
};

const poetDisplayName = (poet, isRtl) => {
    if (isRtl) {
        return poet.name_sd || poet.name_en || '';
    }
    return poet.name_en || poet.name_sd || '';
};

const PoetCard = ({ poet, lang, isRtl }) => {
    const laqab = poetDisplayLaqab(poet, isRtl);
    const fullName = poetDisplayName(poet, isRtl);
    const showFullName = fullName && fullName !== laqab;

    return (
        <div className="flex items-center gap-6 p-6 border-b border-gray-100 bg-white transition-colors group">
            <Link to={`/${lang}/poet/${poet.slug}`}>
                <Avatar className="h-16 w-16 md:h-20 md:w-20 border border-gray-100">
                    <AvatarImgOrIcon
                        src={poet.avatar || poet.poet_pic || poet.image}
                        imageType="poet"
                        alt={laqab}
                        iconClassName="h-7 w-7 md:h-10 md:w-10"
                    />
                </Avatar>
            </Link>

            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    <Link to={`/${lang}/poet/${poet.slug}`} className="hover:underline">
                        <h3 className={`text-lg md:text-xl font-bold text-gray-900 truncate ${isRtl ? 'font-arabic' : ''}`}>
                            {laqab}
                        </h3>
                    </Link>
                    {showFullName && (
                        <p className={`text-xs md:text-sm font-bold uppercase tracking-wider text-gray-400 truncate ${isRtl ? 'font-arabic' : ''}`}>
                            {fullName}
                        </p>
                    )}
                </div>

                <p className="text-gray-500 text-sm md:text-base line-clamp-2 mb-2 font-arabic">
                    {htmlToPlainText(isRtl ? poet.bio_sd : poet.bio_en)}
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
};

const PoetsFeed = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { stickyTopClass } = useStickyBelowNavbar();
    const [search, setSearch] = useState('');
    const [selectedTag, setSelectedTag] = useState('all');

    const getLocalizedTag = (tag, slug = '') => {
        if (!isRtl) return tag;
        return TAG_TRANSLATIONS[tag]
            || TAG_SLUG_TRANSLATIONS[slug]
            || TAG_SLUG_TRANSLATIONS[tag]
            || tag;
    };

    const slugToTitle = (slug = '') =>
        slug
            .split('-')
            .filter(Boolean)
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');

    const readBootstrapPoets = () => {
        if (search || selectedTag !== 'all') return undefined;
        const boot = typeof window !== 'undefined' ? window.__BAAKH_BOOTSTRAP_POETS__ : null;
        if (!boot || boot.lang !== lang || !boot.payload?.data) return undefined;
        return {
            pages: [boot.payload],
            pageParams: [1],
        };
    };

    const { data: tagsData } = useQuery({
        queryKey: ['poet-tags', lang],
        queryFn: async () => {
            try {
                const response = await api.get('/api/v1/poet-tags');
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

    const {
        data,
        isLoading,
        isError,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage
    } = useInfiniteQuery({
        queryKey: ['poets-feed', search, selectedTag, lang],
        queryFn: async ({ pageParam = 1 }) => {
            const params = { search, page: pageParam, lang };
            if (selectedTag !== 'all') {
                params.tag = selectedTag;
            }
            const response = await api.get('/api/v1/poets', { params });
            return response.data;
        },
        getNextPageParam: (lastPage) => {
            if (lastPage?.current_page < lastPage?.last_page) {
                return lastPage.current_page + 1;
            }
            return undefined;
        },
        initialData: readBootstrapPoets,
        initialDataUpdatedAt: () => {
            const boot = typeof window !== 'undefined' ? window.__BAAKH_BOOTSTRAP_POETS__ : null;
            return boot?.generated_at || Date.now();
        },
        retry: 1,
        refetchOnWindowFocus: false,
        staleTime: 60 * 1000,
    });

    const poets = (data?.pages.flatMap(page => page?.data || []) || []);

    // Offline / API-down fallback only (no avatars in the static file)
    const {
        data: staticPoets = [],
        isLoading: isLoadingStaticPoets,
    } = useQuery({
        queryKey: ['static-poets-fallback', lang, search],
        queryFn: async () => {
            const response = await fetch('/json/poets.json', { cache: 'no-store' });
            if (!response.ok) return [];
            const raw = await response.json();
            const normalized = (Array.isArray(raw) ? raw : []).map((item, idx) => {
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
        enabled: isError && poets.length === 0 && !isLoading,
        retry: false,
        refetchOnWindowFocus: false,
        staleTime: 60 * 1000,
    });

    const displayedPoets = poets.length > 0 ? poets : staticPoets;

    const { ref, inView } = useInView({
        threshold: 0,
        rootMargin: '320px 0px',
    });

    useEffect(() => {
        if (inView && hasNextPage && !isFetchingNextPage) {
            fetchNextPage();
        }
    }, [inView, hasNextPage, isFetchingNextPage, fetchNextPage]);

    const shouldShowSkeleton = displayedPoets.length === 0 && (isLoading || isLoadingStaticPoets);

    return (
        <div className="flex-1 max-w-[1080px] w-full mx-auto px-4 md:px-8 py-6">
            <h1 className="text-3xl font-bold mb-6">{isRtl ? 'شاعر' : 'Poets'}</h1>

            <div className={`sticky ${stickyTopClass} bg-white z-40 border-b border-gray-100 mb-8 overflow-x-auto no-scrollbar`}>
                <div className="flex items-center gap-8 min-w-max pb-1">
                    <button
                        type="button"
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
                            type="button"
                            key={tag.slug}
                            onClick={() => setSelectedTag(tag.slug)}
                            className={`pb-3 text-base font-medium whitespace-nowrap transition-colors relative
                                ${selectedTag === tag.slug
                                    ? 'text-black font-bold'
                                    : 'text-gray-400 hover:text-gray-600'
                                }`}
                        >
                            {getLocalizedTag(tag.tag, tag.slug)}
                            {selectedTag === tag.slug && (
                                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-black rounded-full" />
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-0">
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
                    displayedPoets.map(poet => (
                        <PoetCard
                            key={poet.id ?? poet.slug}
                            poet={poet}
                            lang={lang}
                            isRtl={isRtl}
                        />
                    ))
                ) : (
                    <div className="py-20 text-center text-gray-500">
                        {isRtl ? 'ڪو به شاعر نه مليو' : 'No poets found.'}
                    </div>
                )}

                {isFetchingNextPage && (
                    <div className="py-4 text-center">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto" />
                    </div>
                )}

                <div ref={ref} className="h-4 w-full" aria-hidden />
            </div>
        </div>
    );
};

export default PoetsFeed;
