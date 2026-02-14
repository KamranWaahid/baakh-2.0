import React from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTrigger, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { MoreHorizontal, User, BookOpen } from 'lucide-react';
import { useParams, Link } from 'react-router-dom';
import PostCard from './PostCard';
import { useQuery, useInfiniteQuery } from '@tanstack/react-query';
import { useInView } from 'react-intersection-observer';
import api from '@/admin/api/axios';
import { formatDate } from '@/lib/date-utils';
import ReportModal from './ReportModal';
import { getImageUrl } from '../utils/url';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Flag } from 'lucide-react';

const PoetProfile = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { slug } = useParams();
    const [activeTab, setActiveTab] = React.useState('poetry');
    const [reportModalOpen, setReportModalOpen] = React.useState(false);

    const { ref, inView } = useInView();

    const { data: poet, isLoading: isPoetLoading } = useQuery({
        queryKey: ['poet', slug, lang],
        queryFn: async () => {
            const response = await api.get(`/api/v1/poets/${slug}`);
            return response.data;
        }
    });

    // Poetry Query
    const {
        data: poetryData,
        fetchNextPage: fetchPoetryNext,
        hasNextPage: hasPoetryNext,
        isFetchingNextPage: isPoetryFetching,
        isLoading: isPoetryLoading
    } = useInfiniteQuery({
        queryKey: ['poet-poetry', slug, activeTab, lang],
        enabled: !!slug && (activeTab === 'poetry' || !['poetry', 'couplets'].includes(activeTab)),
        queryFn: async ({ pageParam = 1 }) => {
            const catParam = activeTab === 'poetry' ? '' : `&category=${activeTab}`;
            const response = await api.get(`/api/v1/poets/${slug}/poetry?page=${pageParam}${catParam}`);
            return response.data;
        },
        getNextPageParam: (lastPage) => {
            return lastPage.next_page_url ? lastPage.current_page + 1 : undefined;
        }
    });

    // Categories Query
    const { data: categories } = useQuery({
        queryKey: ['poet-categories', slug, lang],
        queryFn: async () => {
            const response = await api.get(`/api/v1/poets/${slug}/categories`);
            return response.data;
        },
        enabled: !!slug
    });

    // Couplets Query
    const {
        data: coupletsData,
        fetchNextPage: fetchCoupletsNext,
        hasNextPage: hasCoupletsNext,
        isFetchingNextPage: isCoupletsFetching,
        isLoading: isCoupletsLoading
    } = useInfiniteQuery({
        queryKey: ['poet-couplets', slug, lang],
        queryFn: async ({ pageParam = 1 }) => {
            const response = await api.get(`/api/v1/poets/${slug}/couplets?page=${pageParam}`);
            return response.data;
        },
        getNextPageParam: (lastPage) => {
            return lastPage.next_page_url ? lastPage.current_page + 1 : undefined;
        },
        enabled: !!slug && activeTab === 'couplets'
    });

    React.useEffect(() => {
        if (inView) {
            if (activeTab === 'couplets' && hasCoupletsNext) {
                fetchCoupletsNext();
            } else if (hasPoetryNext) {
                fetchPoetryNext();
            }
        }
    }, [inView, activeTab, hasPoetryNext, fetchPoetryNext, hasCoupletsNext, fetchCoupletsNext]);

    // Mock posts for now, or correct if API provided posts
    // For now we will keep the static posts structure but ideally this should also come from API
    // The controller returns 'poetry' relation, if we want to show that.
    // But user asked to remove hardcoded structure.
    // Let's assume for now we just show the profile data correctly.
    // User said "menu: home, couplets, then all catogries"

    // We didn't implement 'couplets' fetching in show method yet specifically as a separate list, 
    // but we can just show the menu items as requested.

    if (isPoetLoading) {
        return (
            <div className="w-full flex justify-center py-10 px-4 md:px-8">
                <div className="w-full max-w-[1000px] flex gap-12">
                    <div className="flex-1 space-y-8">
                        <Skeleton className="h-10 w-3/4" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>
                </div>
            </div>
        );
    }

    if (!poet) return null;

    return (
        <div className="w-full flex justify-center py-10 px-4 md:px-8">
            <div className="w-full max-w-[1000px] flex gap-12">

                {/* Main Content (Left) */}
                <div className="flex-1 min-w-0">
                    <header className="mb-8">
                        {/* Mobile Profile Header */}
                        <div className="lg:hidden mb-8">
                            <div className="flex items-center gap-4 mb-4">
                                <div className="h-16 w-16 md:h-20 md:w-20 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 shrink-0 border border-gray-100 overflow-hidden shadow-sm">
                                    <img
                                        src={getImageUrl(poet.avatar, 'poet')}
                                        alt={isRtl ? poet.name_sd : poet.name_en}
                                        className="w-full h-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                </div>
                                <div>
                                    <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-gray-900 capitalize leading-tight mb-0.5">
                                        {isRtl ? poet.laqab_sd : poet.laqab_en}
                                    </h1>
                                    <p className="text-gray-500 text-xs md:text-sm font-bold uppercase tracking-wider">
                                        {isRtl ? poet.name_sd : poet.name_en}
                                    </p>
                                </div>
                            </div>
                            <p className="text-gray-700 font-serif text-[17px] leading-[1.6] mb-6 font-arabic antialiased">
                                {isRtl ? poet.bio_sd : poet.bio_en}
                            </p>
                            <div className="flex gap-3 w-full">
                                <Button className="flex-1 rounded-full bg-black hover:bg-gray-800 text-white font-medium h-10">
                                    {isRtl ? 'شاعر بابت' : 'About Poet'}
                                </Button>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" size="icon" className="rounded-full border-gray-300 h-10 w-10" aria-label="More profile options">
                                            <MoreHorizontal className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48 bg-white p-1 z-50">
                                        <DropdownMenuItem
                                            className="cursor-pointer py-2 text-red-600 focus:text-red-700 focus:bg-red-50 flex items-center gap-2"
                                            onClick={(e) => { e.preventDefault(); e.stopPropagation(); setReportModalOpen(true); }}
                                        >
                                            <Flag className="h-4 w-4" />
                                            <span>{isRtl ? 'شاعر رپورٽ ڪريو' : 'Report this poet'}</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>

                        {/* Desktop Title */}
                        <h1 className="hidden lg:block text-4xl md:text-5xl font-bold tracking-tight mb-2 text-gray-900 capitalize">
                            {isRtl ? poet.laqab_sd : poet.laqab_en}
                        </h1>
                        <p className="hidden lg:block text-gray-500 text-xl mb-6 font-medium">
                            {isRtl ? poet.name_sd : poet.name_en}
                        </p>

                        {/* Tabs with scroll hint */}
                        <div className="relative border-b border-gray-100 mb-6 sticky top-[56px] lg:top-[64px] bg-white/70 backdrop-blur-xl z-30 pt-4 -mx-4 px-4 md:static md:bg-transparent md:backdrop-blur-none md:p-0 md:m-0 transition-all duration-300">
                            <div className="flex items-center gap-6 md:gap-8 overflow-x-auto no-scrollbar scroll-smooth">
                                <button
                                    onClick={() => setActiveTab('poetry')}
                                    className={`pb-4 px-1 text-sm font-medium whitespace-nowrap transition-all duration-200 relative ${activeTab === 'poetry'
                                        ? 'text-black'
                                        : 'text-gray-400 hover:text-gray-600'
                                        }`}
                                >
                                    {isRtl ? 'اصل' : 'Poetry'}
                                    {activeTab === 'poetry' && (
                                        <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-black animate-in fade-in slide-in-from-bottom-1 duration-300" />
                                    )}
                                </button>
                                <button
                                    onClick={() => setActiveTab('couplets')}
                                    className={`pb-4 px-1 text-sm font-medium whitespace-nowrap transition-all duration-200 relative ${activeTab === 'couplets'
                                        ? 'text-black'
                                        : 'text-gray-400 hover:text-gray-600'
                                        }`}
                                >
                                    {isRtl ? 'بيت' : 'Couplets'}
                                    {activeTab === 'couplets' && (
                                        <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-black animate-in fade-in slide-in-from-bottom-1 duration-300" />
                                    )}
                                </button>
                                {categories?.filter(cat => cat.slug !== 'bait' && cat.slug !== 'couplets').map((cat) => (
                                    <button
                                        key={cat.id}
                                        onClick={() => setActiveTab(cat.slug)}
                                        className={`pb-4 px-1 text-sm font-medium whitespace-nowrap transition-all duration-200 relative ${activeTab === cat.slug
                                            ? 'text-black'
                                            : 'text-gray-400 hover:text-gray-600'
                                            }`}
                                    >
                                        {cat.name}
                                        {activeTab === cat.slug && (
                                            <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-black animate-in fade-in slide-in-from-bottom-1 duration-300" />
                                        )}
                                    </button>
                                ))}
                                <button
                                    onClick={() => setActiveTab('about')}
                                    className={`pb-4 px-1 text-sm font-medium whitespace-nowrap transition-all duration-200 relative ${activeTab === 'about'
                                        ? 'text-black'
                                        : 'text-gray-400 hover:text-gray-600'
                                        }`}
                                >
                                    {isRtl ? 'شاعر بابت' : 'About'}
                                    {activeTab === 'about' && (
                                        <div className="absolute bottom-0 left-0 right-0 h-0.5 bg-black animate-in fade-in slide-in-from-bottom-1 duration-300" />
                                    )}
                                </button>
                            </div>
                            {/* Visual indicator for more content (mobile only) */}
                            <div className={`absolute top-0 bottom-0 pointer-events-none w-12 bg-gradient-to-l from-white/90 to-transparent md:hidden transition-opacity duration-300 ${isRtl ? 'left-0' : 'right-0'}`} />
                        </div>
                    </header>

                    <div className="space-y-0">
                        {activeTab !== 'couplets' ? (
                            isPoetryLoading ? (
                                <div className="space-y-8 py-8">
                                    {[1, 2, 3].map((i) => (
                                        <div key={i} className="space-y-4">
                                            <Skeleton className="h-8 w-3/4" />
                                            <Skeleton className="h-20 w-full" />
                                        </div>
                                    ))}
                                </div>
                            ) : poetryData?.pages[0]?.data.length > 0 ? (
                                <>
                                    {poetryData.pages.map((page, i) => (
                                        <React.Fragment key={i}>
                                            {page.data.map((post) => (
                                                <PostCard key={post.id} {...post} lang={lang} />
                                            ))}
                                        </React.Fragment>
                                    ))}

                                    <div ref={ref} className="py-8 flex justify-center">
                                        {isPoetryFetching && (
                                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <div className="py-20 text-center">
                                    <BookOpen className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                    <p className="text-gray-500 font-medium">
                                        {isRtl ? 'هن وقت ڪا به شاعري موجود ناهي.' : 'No poetry available at the moment.'}
                                    </p>
                                </div>
                            )
                        ) : (
                            isCoupletsLoading ? (
                                <div className="space-y-8 py-8">
                                    {[1, 2, 3].map((i) => (
                                        <div key={i} className="space-y-4">
                                            <Skeleton className="h-20 w-full" />
                                        </div>
                                    ))}
                                </div>
                            ) : coupletsData?.pages[0]?.data.length > 0 ? (
                                <>
                                    {coupletsData.pages.map((page, i) => (
                                        <React.Fragment key={i}>
                                            {page.data.map((post) => (
                                                <PostCard key={post.id} {...post} lang={lang} />
                                            ))}
                                        </React.Fragment>
                                    ))}

                                    <div ref={ref} className="py-8 flex justify-center">
                                        {isCoupletsFetching && (
                                            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                                        )}
                                    </div>
                                </>
                            ) : (
                                <div className="py-20 text-center">
                                    <BookOpen className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                    <p className="text-gray-500 font-medium">
                                        {['sd', 'ur'].includes(lang) ? 'هن وقت ڪو به بيت موجود ناهي.' : 'No couplets available at the moment.'}
                                    </p>
                                </div>
                            )
                        )}
                    </div>
                </div>

                {/* Profile Sidebar (Right) - Desktop */}
                <aside className="hidden lg:block w-[320px] shrink-0 sticky top-24 h-fit border-l border-gray-100 pl-12 -ml-6">
                    <div className="flex flex-col items-start">
                        <div className="h-32 w-32 rounded-full bg-gray-50 mb-6 flex items-center justify-center text-gray-400 overflow-hidden border border-gray-100">
                            <img
                                src={getImageUrl(poet.avatar, 'poet')}
                                alt={isRtl ? poet.name_sd : poet.name_en}
                                className="w-full h-full object-cover"
                                loading="lazy"
                                decoding="async"
                            />
                        </div>

                        <h3 className="font-bold tracking-tight text-lg mb-1 capitalize text-gray-900">
                            {isRtl ? poet.laqab_sd : poet.laqab_en}
                        </h3>
                        <p className="text-gray-500 text-sm mb-4 font-bold">
                            {isRtl ? poet.name_sd : poet.name_en}
                        </p>

                        <p className="font-serif text-sm text-gray-600 leading-relaxed mb-6 font-arabic line-clamp-3">
                            {isRtl ? poet.bio_sd : poet.bio_en}
                        </p>

                        <div className="flex gap-2 w-full mb-6">
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button className="flex-1 rounded-full bg-black hover:bg-gray-800 text-white font-medium">
                                        {isRtl ? 'شاعر بابت' : 'About Poet'}
                                    </Button>
                                </DialogTrigger>
                                <DialogContent className="sm:max-w-[500px] bg-white h-auto max-h-[85vh] flex flex-col p-0 gap-0 overflow-hidden" dir={isRtl ? 'rtl' : 'ltr'}>
                                    <DialogHeader className="p-6 pb-2">
                                        <div className="flex flex-col items-center gap-4 mb-2">
                                            <div className="h-24 w-24 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100 shrink-0">
                                                <User className="h-12 w-12" />
                                            </div>
                                            <div className="text-center w-full">
                                                <DialogTitle className="text-2xl font-bold mb-1 text-center">
                                                    {isRtl ? poet.name_sd : poet.name_en}
                                                </DialogTitle>
                                                <DialogDescription className="text-base font-medium text-gray-500 text-center">
                                                    {isRtl ? poet.laqab_sd : poet.laqab_en}
                                                </DialogDescription>
                                            </div>
                                        </div>
                                    </DialogHeader>

                                    <div className="flex-1 overflow-y-auto p-6 pt-0">
                                        <div className="space-y-5 text-sm">
                                            {(isRtl ? poet.pen_name_sd : poet.pen_name_en) && (
                                                <div className="bg-gray-50 p-4 rounded-lg">
                                                    <span className="text-gray-400 block text-xs uppercase tracking-wide mb-1 font-semibold">
                                                        {isRtl ? 'تخلص' : 'Pen Name'}
                                                    </span>
                                                    <span className="font-medium text-gray-900">
                                                        {isRtl ? poet.pen_name_sd : poet.pen_name_en}
                                                    </span>
                                                </div>
                                            )}

                                            <div className="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                                                {/* Birth Info */}
                                                <div>
                                                    <span className="text-gray-400 block text-xs uppercase tracking-wide mb-1 font-semibold">
                                                        {isRtl ? 'جنم' : 'Born'}
                                                    </span>
                                                    <span className="font-medium text-gray-900 block mb-3">
                                                        {formatDate(poet.dob, lang)}
                                                    </span>

                                                    {(isRtl ? poet.birth_location_sd : poet.birth_location_en) && (
                                                        <>
                                                            <span className="text-gray-400 block text-xs uppercase tracking-wide mb-1 font-semibold">
                                                                {isRtl ? 'جنم جو هنڌ' : 'Birth Place'}
                                                            </span>
                                                            <span className="font-medium text-gray-900 block">
                                                                {isRtl ? poet.birth_location_sd : poet.birth_location_en}
                                                            </span>
                                                        </>
                                                    )}
                                                </div>

                                                {/* Death Info */}
                                                <div>
                                                    {poet.dod && (
                                                        <>
                                                            <span className="text-gray-400 block text-xs uppercase tracking-wide mb-1 font-semibold">
                                                                {isRtl ? 'وفات' : 'Died'}
                                                            </span>
                                                            <span className="font-medium text-gray-900 block mb-3">
                                                                {formatDate(poet.dod, lang)}
                                                            </span>
                                                        </>
                                                    )}

                                                    {(isRtl ? poet.death_location_sd : poet.death_location_en) && (
                                                        <>
                                                            <span className="text-gray-400 block text-xs uppercase tracking-wide mb-1 font-semibold">
                                                                {isRtl ? 'وفات جو هنڌ' : 'Death Place'}
                                                            </span>
                                                            <span className="font-medium text-gray-900 block">
                                                                {isRtl ? poet.death_location_sd : poet.death_location_en}
                                                            </span>
                                                        </>
                                                    )}
                                                </div>
                                            </div>

                                            <Separator />

                                            <div>
                                                <span className="text-gray-400 block text-xs uppercase tracking-wide mb-2 font-semibold">
                                                    {isRtl ? 'بايو' : 'Biography'}
                                                </span>
                                                <p className="font-serif text-gray-600 leading-relaxed font-arabic whitespace-pre-line text-base">
                                                    {isRtl ? poet.bio_sd : poet.bio_en}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="icon" className="rounded-full border-gray-300 h-10 w-10 shrink-0" aria-label="More options">
                                        <MoreHorizontal className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-48 bg-white p-1 z-50">
                                    <DropdownMenuItem
                                        className="cursor-pointer py-2 text-red-600 focus:text-red-700 focus:bg-red-50 flex items-center gap-2"
                                        onClick={(e) => { e.preventDefault(); e.stopPropagation(); setReportModalOpen(true); }}
                                    >
                                        <Flag className="h-4 w-4" />
                                        <span>{isRtl ? 'شاعر رپورٽ ڪريو' : 'Report this poet'}</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <div className="w-full pt-6 border-t border-gray-100">
                            <h4 className="font-medium text-gray-900 mb-4 text-sm uppercase tracking-wide">
                                {isRtl ? 'تجويز ڪيل شاعر' : 'Recommended Poets'}
                            </h4>
                            <div className="space-y-4">
                                {poet.suggested?.map((p, i) => (
                                    <div key={i} className="flex items-center justify-between group cursor-pointer">
                                        <Link to={`/${lang}/poet/${p.slug}`} className="flex items-center gap-3 flex-1">
                                            <div className="h-8 w-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-200 overflow-hidden">
                                                <img
                                                    src={getImageUrl(p.avatar, 'poet')}
                                                    alt={isRtl ? p.name_sd : p.name_en}
                                                    className="w-full h-full object-cover"
                                                />
                                            </div>
                                            <span className="text-sm font-medium text-gray-700 group-hover:text-black transition-colors">
                                                {isRtl ? p.name_sd : p.name_en}
                                            </span>
                                        </Link>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            className="h-7 w-auto px-3 text-xs rounded-full border-gray-300 hover:border-black hover:bg-black hover:text-white transition-all"
                                            asChild
                                        >
                                            <Link to={`/${lang}/poet/${p.slug}`}>
                                                {isRtl ? 'کاتو' : 'Profile'}
                                            </Link>
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        </div>

                    </div>
                </aside>

            </div >
            {/* Report Poet Modal */}
            < ReportModal
                open={reportModalOpen}
                onOpenChange={setReportModalOpen}
                isRtl={isRtl}
                poetId={poet?.id}
            />
        </div >
    );
};

export default PoetProfile;
