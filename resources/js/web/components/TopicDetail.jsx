import React, { useState, useEffect } from 'react';
import { useParams, Link, useLocation } from 'react-router-dom';
import axios from 'axios';
import { Skeleton } from '@/components/ui/skeleton';
import PostCard from './PostCard';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from '@/components/ui/button';
import { User, BookOpen, ChevronRight, ChevronLeft } from 'lucide-react';
import { getImageUrl } from '../utils/url';

const TopicDetail = () => {
    const { lang, slug } = useParams();
    const isRtl = lang === 'sd';
    const location = useLocation();
    const isCategory = location.pathname.includes('/topic/');
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchTopic = async () => {
            setLoading(true);
            try {
                const endpoint = isCategory
                    ? `/api/v1/topic-categories/${slug}`
                    : `/api/v1/tags/${slug}`;

                const response = await axios.get(endpoint, {
                    headers: { 'Accept-Language': lang }
                });
                setData(response.data);
            } catch (err) {
                console.error("Failed to fetch topic details", err);
                setError(err);
            } finally {
                setLoading(false);
            }
        };

        if (slug) {
            fetchTopic();
        }
    }, [slug, lang]);

    if (loading) {
        return (
            <div className="flex-1 max-w-[1000px] w-full mx-auto px-4 md:px-8 py-12 md:py-20 animate-pulse">
                <div className="h-8 w-48 bg-gray-200 rounded mb-8"></div>
                <div className="h-12 w-64 bg-gray-200 rounded mb-12"></div>
                <div className="space-y-8">
                    <Skeleton className="h-40 w-full" />
                    <Skeleton className="h-40 w-full" />
                </div>
            </div>
        );
    }

    if (error || !data) {
        return (
            <div className="flex-1 max-w-[1000px] w-full mx-auto px-4 md:px-8 py-20 text-center">
                <h2 className="text-2xl font-bold text-gray-900 mb-2">
                    {isRtl ? 'موضوع نه مليو' : 'Topic not found'}
                </h2>
                <Link to={`/${lang}/explore`} className="text-gray-500 hover:text-gray-900 hover:underline transition-colors">
                    {isRtl ? 'ڳولا ڏانهن واپس وڃو' : 'Go back to explore'}
                </Link>
            </div>
        );
    }

    const { data: topicData, parent, counts } = data;
    // Normalize data structure since backend returns 'data' wrapper for tag/category details
    const currentTopic = topicData;
    const parentCategory = parent;
    const poetry = data.poetry || [];
    const poets = data.poets || [];

    // Copied PoetCard from PoetsFeed.jsx
    const PoetCard = ({ poet }) => (
        <div className="flex items-center gap-4 md:gap-6 p-4 md:p-6 border border-gray-100 rounded-xl bg-white transition-all hover:shadow-sm group mb-4">
            <Link to={`/${lang}/poet/${poet.slug}`} className="shrink-0">
                <Avatar className="h-14 w-14 md:h-16 md:w-16 border border-gray-100">
                    <AvatarImage
                        src={getImageUrl(poet.avatar, 'poet')}
                        alt={isRtl ? poet.name_sd : poet.name_en}
                        className="object-cover"
                    />
                    <AvatarFallback className="text-lg font-bold text-gray-400 bg-gray-50">
                        {poet.name_en?.charAt(0) || 'P'}
                    </AvatarFallback>
                </Avatar>
            </Link>

            <div className="flex-1 min-w-0">
                <Link to={`/${lang}/poet/${poet.slug}`} className="hover:underline block w-fit">
                    <h3 className={`text-lg font-bold text-gray-900 truncate mb-1 ${isRtl ? 'font-arabic' : ''}`}>
                        {isRtl ? poet.name_sd : poet.name_en}
                    </h3>
                </Link>

                <p className="text-gray-500 text-sm line-clamp-1 mb-2 font-arabic">
                    {isRtl ? poet.bio_sd : poet.bio_en}
                </p>

                <div className="flex items-center gap-3 text-xs text-gray-400 font-medium">
                    <span className="flex items-center gap-1">
                        <BookOpen className="h-3 w-3" /> {poet.entries_count || 0} {isRtl ? 'لکڻيون' : 'Entries'}
                    </span>
                </div>
            </div>
        </div>
    );

    const ChevronIcon = isRtl ? ChevronLeft : ChevronRight;

    return (
        <div className="flex-1 max-w-[1000px] w-full mx-auto px-4 md:px-8 py-8 md:py-12 animate-in fade-in duration-500">
            {/* Breadcrumbs */}
            <nav className={`flex items-center flex-wrap gap-y-2 text-sm text-gray-500 mb-8 ${isRtl ? 'font-arabic' : ''}`}>
                <Link to={`/${lang}/explore`} className="hover:text-black transition-colors">
                    {isRtl ? 'موضوعن جي ڳولا' : 'Explore'}
                </Link>
                <ChevronIcon className="h-4 w-4 mx-2" />

                {parentCategory && (
                    <>
                        <Link to={`/${lang}/topic/${parentCategory.slug}`} className="text-gray-900 font-medium hover:underline">
                            {parentCategory.name}
                        </Link>
                        <ChevronIcon className="h-4 w-4 mx-2" />
                    </>
                )}

                <span className="text-black font-bold">
                    {currentTopic.name}
                </span>
            </nav>

            {/* Header */}
            <div className={`mb-12 ${isRtl ? 'text-right' : 'text-left'}`}>
                <h1 className={`text-3xl md:text-5xl font-bold text-gray-900 mb-4 tracking-tight leading-tight ${isRtl ? 'font-arabic' : ''}`}>
                    {currentTopic.name}
                </h1>
                <div className={`flex items-center flex-wrap gap-2 text-gray-500 font-medium ${isRtl ? 'flex-row-reverse justify-end' : ''}`}>
                    <span>{counts?.poetry || 0} {isRtl ? 'شاعري' : 'poetries'}</span>
                    {(counts?.poets > 0) && (
                        <>
                            <span>·</span>
                            <span>{counts?.poets} {isRtl ? 'شاعر' : 'poets'}</span>
                        </>
                    )}
                </div>
            </div>

            {/* Main Layout Grid */}
            <div className={`grid grid-cols-1 ${poets.length > 0 ? 'lg:grid-cols-12' : 'grid-cols-1'} gap-12`}>

                {/* Main Content - Poetry */}
                <div className={`${poets.length > 0 ? 'lg:col-span-8' : 'w-full'} space-y-8`}>
                    <h2 className={`text-xl font-bold text-gray-900 flex items-center gap-2 ${isRtl ? 'font-arabic' : ''}`}>
                        <BookOpen className="h-5 w-5" />
                        {isRtl ? 'شاعري' : 'Poetry'}
                    </h2>

                    <div className={poets.length > 0 ? "divide-y divide-gray-100" : "grid grid-cols-1 md:grid-cols-2 gap-6"}>
                        {poetry.length > 0 ? (
                            poetry.map(poem => (
                                <div key={poem.id} className={poets.length > 0 ? "" : "h-full"}>
                                    <PostCard
                                        {...poem}
                                        lang={lang}
                                        showStar={false}
                                    />
                                </div>
                            ))
                        ) : (
                            <div className="col-span-full py-10 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                                {isRtl ? 'اڃا تائين ڪا به شاعري نه آهي' : 'No poetry found for this topic yet.'}
                            </div>
                        )}
                    </div>
                </div>

                {/* Sidebar - Poets (Only show if poets exist) */}
                {poets.length > 0 && (
                    <div className="lg:col-span-4 space-y-8">
                        <h2 className={`text-xl font-bold text-gray-900 flex items-center gap-2 ${isRtl ? 'font-arabic' : ''}`}>
                            <User className="h-5 w-5" />
                            {isRtl ? 'شاعر' : 'Poets'}
                        </h2>

                        <div className="space-y-4">
                            {poets.map(poet => (
                                <PoetCard key={poet.id} poet={poet} />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default TopicDetail;
