import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { useAuth } from '../contexts/AuthContext';
import PoemActionBar from './PoemActionBar';
import { Button } from '@/components/ui/button';
import { Sparkles, User } from 'lucide-react';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import PaywallCTA from './PaywallCTA';
import { formatSindhiDate } from '../utils/dateUtils';
import { getImageUrl } from '../utils/url';

const PoemDetail = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { poemSlug } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();

    const currentUrl = window.location.href;


    const { data: poem, isLoading, isError, error } = useQuery({
        queryKey: ['poetry', poemSlug, lang],
        queryFn: async () => {
            const response = await api.get(`/api/v1/poetry/${poemSlug}?lang=${lang}`);
            return response.data;
        },
        retry: 1,
        staleTime: 5 * 60 * 1000
    });

    if (isLoading) {
        return (
            <div className="w-full flex flex-col items-center py-6 md:py-12 px-4 md:px-8 bg-white">
                <article className="w-full max-w-[680px] mb-12 md:mb-20 space-y-8">
                    <Skeleton className="h-6 w-32" />
                    <Skeleton className="h-16 w-full" />
                    <div className="flex items-center gap-4">
                        <Skeleton className="h-11 w-11 rounded-full" />
                        <div className="flex flex-col gap-2">
                            <Skeleton className="h-4 w-32" />
                            <Skeleton className="h-3 w-24" />
                        </div>
                    </div>
                    <Separator className="my-6" />
                    <div className="space-y-4">
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-3/4" />
                        <br />
                        <Skeleton className="h-4 w-full" />
                        <Skeleton className="h-4 w-5/6" />
                    </div>
                </article>
            </div>
        );
    }

    if (isError) {
        console.error("PoemDetail Fetch Error:", error);
        const isNotFound = error?.response?.status === 404;

        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center p-8 text-center" dir={isRtl ? 'rtl' : 'ltr'}>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">
                    {isNotFound
                        ? (isRtl ? 'شاعري نہ ملي' : 'Poem Not Found')
                        : (isRtl ? 'ڪجهه غلط ٿي ويو' : 'Something went wrong')}
                </h2>
                <p className="text-gray-600 mb-6">
                    {isNotFound
                        ? (isRtl ? 'جيڪا شاعري توهان ڳولي رهيا آهيو اها موجود ناهي.' : 'The poetry you are looking for does not exist or has been removed.')
                        : (isRtl ? 'مهرباني ڪري ٿوري دير کان پوءِ ٻيهر ڪوشش ڪريو.' : 'Please try again later or refresh the page.')}
                </p>
                <Button onClick={() => isNotFound ? navigate(`/${lang}`) : window.location.reload()}>
                    {isNotFound ? (isRtl ? 'واپس وڃو' : 'Go Home') : (isRtl ? 'ٻيهر ڪوشش ڪريو' : 'Try Again')}
                </Button>
            </div>
        );
    }

    if (!poem || !poem.id) return null;

    // Justification Logic
    const isGhazal = poem.category?.name && (poem.category.name.toLowerCase().includes('ghazal') || poem.category.name.includes('غزل'));
    const alignmentClass = isGhazal ? 'text-justify [text-align-last:justify] w-fit mx-auto' : (poem.content_style === 'center' ? 'text-center' : 'text-right');


    return (
        <div className="w-full flex flex-col items-center py-6 md:py-12 px-4 md:px-8 bg-white" dir={isRtl ? 'rtl' : 'ltr'}>
            <article className="w-full max-w-[680px] mb-12 md:mb-20 animate-fade-in-up">
                {/* Header */}
                <header className="mb-8">
                    {/* Badge */}
                    <div className="flex items-center gap-2 mb-6 text-yellow-500">
                        <Sparkles className="h-4 w-4 fill-yellow-500" />
                        <span className="text-sm font-medium text-gray-700">{poem.category?.name || (isRtl ? 'رڪني ڪهاڻي' : 'Member-only story')}</span>
                    </div>

                    <h1 className={`text-2xl md:text-3xl lg:text-[40px] font-bold tracking-tight text-gray-900 leading-[1.2] mb-6 ${isRtl ? 'font-arabic' : ''}`}>
                        {poem.title}
                    </h1>

                    <div className="flex items-center gap-4 mb-8">
                        <Link to={`/${lang}/poet/${poem.poet?.slug}`} className="shrink-0">
                            <img src={getImageUrl(poem.poet.avatar, 'poet')} alt={poem.poet.name} className="h-11 w-11 rounded-full object-cover bg-gray-200" />
                        </Link>
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <Link to={`/${lang}/poet/${poem.poet?.slug}`} className="font-medium text-gray-900 hover:underline">
                                    {poem.poet?.name || 'Unknown'}
                                </Link>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                <span>{isRtl ? formatSindhiDate(poem.date) : poem.date}</span>
                            </div>
                        </div>
                    </div>

                    <Separator className="my-6" />

                    <div className="flex items-center justify-between text-gray-500 mb-8">
                        <PoemActionBar poem={poem} lang={lang} />
                    </div>
                </header>


                {/* Body */}
                {/* Body */}
                <div className={`prose prose-lg max-w-none text-gray-900 font-serif leading-[1.7] text-[18px] md:text-[20px] ${isRtl ? 'font-arabic' : ''} ${alignmentClass} whitespace-pre-line antialiased`}>
                    {Array.isArray(poem.content) ? (
                        poem.content.map((couplet, index) => (
                            <p key={index} className="mb-6 w-full">{couplet}</p>
                        ))
                    ) : (
                        <div dangerouslySetInnerHTML={{ __html: poem.content }} />
                    )}

                    {poem.info && (
                        <div className={`bg-gray-50 p-6 rounded-lg mt-8 text-base text-gray-600 italic border-l-4 border-gray-200 ${isRtl ? 'border-l-0 border-r-4' : ''}`}>
                            {poem.info}
                        </div>
                    )}


                </div>

                <div className={`flex flex-wrap gap-2 mt-8 ${isRtl ? 'flex-row-reverse' : ''}`}>
                    {poem.tags?.map(tag => (
                        <span key={tag.id} className="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                            #{tag.tag}
                        </span>
                    ))}
                </div>

                <Separator className="my-12 opacity-50" />

                {/* Author Footer */}
                <div className={`flex items-center mb-12 ${isRtl ? 'font-arabic' : 'font-sans'}`}>
                    <div className={`flex items-center gap-4 ${isRtl ? 'text-right' : 'text-left'}`}>
                        <Link to={`/${lang}/poet/${poem.poet.slug}`} className="shrink-0">
                            <img src={getImageUrl(poem.poet.avatar, 'poet')} alt={poem.poet.name} className="h-[64px] w-[64px] rounded-full object-cover bg-gray-200" />
                        </Link>
                        <div className="flex flex-col">
                            <Link to={`/${lang}/poet/${poem.poet.slug}`}>
                                <h3 className="text-[20px] font-bold text-gray-900 mb-1 leading-tight">
                                    {isRtl ? `${poem.poet.name} جي شاعري` : `Poetry of ${poem.poet.name}`}
                                </h3>
                            </Link>
                            <p className="text-[15px] text-gray-600 leading-snug">
                                {poem.poet.title || poem.poet.tagline || (poem.poet.bio ? poem.poet.bio.substring(0, 100) + '...' : (isRtl ? 'سنڌ جو انقلابي شاعر.' : 'Author at Baakh'))}
                            </p>
                        </div>
                    </div>
                </div>

                <Separator className="my-12 opacity-50" />

                <PaywallCTA authorName={poem.poet.name} categoryName={poem.category?.name} poets={poem.suggested_poets} isRtl={isRtl} />

            </article>

            {/* Footer Recommendations */}
            <div className="w-full max-w-[680px] bg-gray-50/50 p-8 rounded-xl border border-gray-100">

                {/* Section 1: More from Author */}
                {poem.more_from_author && poem.more_from_author.length > 0 && (
                    <div className="mb-12">
                        <h3 className={`font-bold text-base text-gray-900 mb-6 ${isRtl ? 'text-right' : ''}`}>{isRtl ? `${poem.poet.name} کان وڌيڪ` : `More from ${poem.poet.name}`}</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                            {poem.more_from_author.map((p, i) => (
                                <Link to={`/${lang}/poet/${p.poet_slug}/${p.cat_slug}/${p.slug}`} key={i} className={`flex flex-col gap-2 group ${isRtl ? 'text-right' : ''}`}>
                                    <div className="text-xs text-gray-500 mb-1">{p.date}</div>
                                    <h4 className="font-bold text-lg text-gray-900 leading-tight group-hover:underline">{p.title}</h4>
                                    <p className="text-gray-600 text-[14px] leading-snug line-clamp-3 font-serif">{p.excerpt}</p>
                                    <div className={`flex items-center gap-4 mt-2 text-xs text-gray-500 ${isRtl ? 'flex-row-reverse' : ''}`}>
                                        <div className="flex items-center gap-1">
                                            <span role="img" aria-label="claps">👏</span>
                                            <span>{p.claps}</span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                        <div className="flex justify-center mt-8">
                            <Link to={`/${lang}/poet/${poem.poet.slug}`}>
                                <Button variant="outline" className="rounded-full border-gray-300 text-gray-900 hover:border-black px-8 py-6 h-auto text-[15px]">
                                    {isRtl ? `سڀ ڏسو ${poem.poet.name}` : `See all from ${poem.poet.name}`}
                                </Button>
                            </Link>
                        </div>
                    </div>
                )}

                {/* Section 2: Recommended */}
                {poem.recommended && poem.recommended.length > 0 && (
                    <div className="mb-12">
                        <h3 className={`font-bold text-base text-gray-900 mb-6 ${isRtl ? 'text-right' : ''}`}>{isRtl ? 'باک پاران تجويز ڪيل' : 'Recommended from Baakh'}</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                            {poem.recommended.map((p, i) => (
                                <Link to={`/${lang}/poet/${p.poet_slug}/${p.cat_slug}/${p.slug}`} key={i} className={`flex flex-col gap-2 group ${isRtl ? 'text-right' : ''}`}>
                                    <div className={`flex items-center gap-2 mb-1 ${isRtl ? 'flex-row-reverse' : ''}`}>
                                        <img src={getImageUrl(p.avatar, 'poet')} alt={p.author} className="h-5 w-5 rounded-full object-cover bg-gray-200" />
                                        <span className="text-xs font-bold text-gray-900">{p.author}</span>
                                    </div>
                                    <h4 className="font-bold text-lg text-gray-900 leading-tight group-hover:underline">{p.title}</h4>
                                    <p className="text-gray-600 text-[14px] leading-snug line-clamp-3 font-serif">{p.excerpt}</p>
                                    <div className={`flex items-center gap-4 mt-2 text-xs text-gray-500 ${isRtl ? 'flex-row-reverse' : ''}`}>
                                        <span>{p.date}</span>
                                        <div className="flex items-center gap-1">
                                            <span role="img" aria-label="claps">👏</span>
                                            <span>{p.claps}</span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

            </div>
        </div >
    );
};

export default PoemDetail;
