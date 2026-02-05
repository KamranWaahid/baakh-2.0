import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { Button } from '@/components/ui/button';
import { Star, MessageCircle, Share2, MoreHorizontal, BookmarkPlus } from 'lucide-react';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import PaywallCTA from './PaywallCTA';

const PoemDetail = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data: poem, isLoading, isError, error } = useQuery({
        queryKey: ['poetry', slug, lang],
        queryFn: async () => {
            const response = await api.get(`/api/v1/poetry/${slug}?lang=${lang}`);
            return response.data;
        },
        retry: false
    });

    if (isLoading) {
        return (
            <div className="w-full flex flex-col items-center py-12 px-4 md:px-8 bg-white">
                <article className="w-full max-w-[680px] mb-20 space-y-8">
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
        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center p-8 text-center">
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Poem Not Found</h2>
                <p className="text-gray-600 mb-6">The poetry you are looking for does not exist or has been removed.</p>
                <Button onClick={() => navigate('/')}>Go Home</Button>
            </div>
        );
    }

    return (
        <div className="w-full flex flex-col items-center py-12 px-4 md:px-8 bg-white">
            <article className="w-full max-w-[680px] mb-20">
                {/* Header */}
                <header className="mb-8">
                    {/* Badge */}
                    <div className="flex items-center gap-2 mb-6 text-yellow-500">
                        <Star className="h-4 w-4 fill-yellow-500" />
                        <span className="text-sm font-medium text-gray-700">Member-only story</span>
                    </div>

                    <h1 className={`text-3xl md:text-[40px] font-bold tracking-tight text-gray-900 leading-tight mb-6 ${isRtl ? 'font-arabic' : ''}`}>
                        {poem.title}
                    </h1>

                    <div className="flex items-center gap-4 mb-8">
                        <Link to={`/poet/${poem.poet.slug}`}>
                            {poem.poet.avatar ? (
                                <img src={poem.poet.avatar} alt={poem.poet.name} className="h-11 w-11 rounded-full object-cover bg-gray-200" />
                            ) : (
                                <div className="h-11 w-11 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold shrink-0 text-gray-500">
                                    {poem.poet.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                                </div>
                            )}
                        </Link>
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <Link to={`/poet/${poem.poet.slug}`} className="font-medium text-gray-900 hover:underline">
                                    {poem.poet.name}
                                </Link>
                                <button className="text-gray-500 hover:text-black hover:underline text-sm">Follow</button>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                <span>{poem.date}</span>
                            </div>
                        </div>
                    </div>

                    <Separator className="my-6" />

                    <div className="flex items-center justify-between text-gray-500 mb-8">
                        <div className="flex items-center gap-6">
                            <button className="flex items-center gap-2 hover:text-black transition-colors">
                                <span role="img" aria-label="claps">👏</span>
                                <span className="text-sm">{poem.likes || 0}</span>
                            </button>
                            <button className="flex items-center gap-2 hover:text-black transition-colors">
                                <MessageCircle className="h-5 w-5" />
                                <span className="text-sm">{poem.comments || 0}</span>
                            </button>
                        </div>
                        <div className="flex items-center gap-4">
                            <button className="hover:text-black"><Share2 className="h-5 w-5" /></button>
                            <button className="hover:text-black"><BookmarkPlus className="h-5 w-5" /></button>
                            <button className="hover:text-black"><MoreHorizontal className="h-5 w-5" /></button>
                        </div>
                    </div>
                </header>

                {/* Body */}
                <div className={`prose prose-lg max-w-none text-gray-900 font-serif leading-relaxed text-[20px] ${isRtl ? 'font-arabic' : ''} ${poem.content_style === 'center' ? 'text-center' : 'text-right'}`}>
                    {Array.isArray(poem.content) ? (
                        poem.content.map((couplet, index) => (
                            <p key={index} className="whitespace-pre-wrap mb-6">{couplet}</p>
                        ))
                    ) : (
                        <div dangerouslySetInnerHTML={{ __html: poem.content }} />
                    )}

                    {poem.info && (
                        <div className="bg-gray-50 p-6 rounded-lg mt-8 text-base text-gray-600 italic border-l-4 border-gray-200">
                            {poem.info}
                        </div>
                    )}

                    {poem.category && (
                        <p className="mt-8 text-sm text-gray-500">
                            Collection: <span className="font-medium text-gray-900">{poem.category.name}</span>
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap gap-2 mt-8">
                    {poem.tags?.map(tag => (
                        <span key={tag.id} className="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                            #{tag.tag}
                        </span>
                    ))}
                </div>

                <Separator className="my-12 opacity-50" />

                {/* Author Footer */}
                <div className="flex items-start justify-between mb-12 font-sans">
                    <div className="flex gap-4">
                        <Link to={`/poet/${poem.poet.slug}`}>
                            {poem.poet.avatar ? (
                                <img src={poem.poet.avatar} alt={poem.poet.name} className="h-[64px] w-[64px] rounded-full object-cover bg-gray-100" />
                            ) : (
                                <div className="h-[64px] w-[64px] rounded-full bg-gray-100 flex items-center justify-center text-xl font-bold text-gray-500 shrink-0 overflow-hidden">
                                    {poem.poet.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                                </div>
                            )}
                        </Link>
                        <div className="flex flex-col pt-1">
                            <Link to={`/poet/${poem.poet.slug}`}>
                                <h3 className="text-[20px] font-bold text-gray-900 mb-1 leading-tight">Written by {poem.poet.name}</h3>
                            </Link>
                            <div className="flex items-center gap-1 text-[14px] text-gray-500 mb-2">
                                <span>{poem.poet.followers} followers</span>
                            </div>
                            <p className="text-[15px] text-gray-600 leading-snug">Revolutionary poet of Sindh.</p>
                        </div>
                    </div>
                    <Button variant="outline" className="rounded-full border-gray-300 text-black hover:border-black hover:bg-transparent px-5 h-[38px] text-[14px]">
                        Follow
                    </Button>
                </div>

                <Separator className="my-12 opacity-50" />

                <PaywallCTA authorName={poem.poet.name} isRtl={isRtl} />

            </article>

            {/* Footer Recommendations */}
            <div className="w-full max-w-[680px] bg-gray-50/50 p-8 rounded-xl border border-gray-100">

                {/* Section 1: More from Author */}
                {poem.more_from_author && poem.more_from_author.length > 0 && (
                    <div className="mb-12">
                        <h3 className="font-bold text-base text-gray-900 mb-6">More from {poem.poet.name}</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                            {poem.more_from_author.map((p, i) => (
                                <Link to={`/poetry/${p.slug}`} key={i} className="flex flex-col gap-2 group">
                                    <div className="text-xs text-gray-500 mb-1">{p.date}</div>
                                    <h4 className="font-bold text-lg text-gray-900 leading-tight group-hover:underline">{p.title}</h4>
                                    <p className="text-gray-600 text-[14px] leading-snug line-clamp-3 font-serif">{p.excerpt}</p>
                                    <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <div className="flex items-center gap-1">
                                            <span role="img" aria-label="claps">👏</span>
                                            <span>{p.claps}</span>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <MessageCircle className="h-3 w-3" />
                                            <span>{p.comments}</span>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                        </div>
                        <div className="flex justify-center mt-8">
                            <Link to={`/poet/${poem.poet.slug}`}>
                                <Button variant="outline" className="rounded-full border-gray-300 text-gray-900 hover:border-black px-8 py-6 h-auto text-[15px]">
                                    See all from {poem.poet.name}
                                </Button>
                            </Link>
                        </div>
                    </div>
                )}

                {/* Section 2: Recommended */}
                {poem.recommended && poem.recommended.length > 0 && (
                    <div className="mb-12">
                        <h3 className="font-bold text-base text-gray-900 mb-6">Recommended from Baakh</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                            {poem.recommended.map((p, i) => (
                                <Link to={`/poetry/${p.slug}`} key={i} className="flex flex-col gap-2 group">
                                    <div className="flex items-center gap-2 mb-1">
                                        <div className="h-5 w-5 rounded-full bg-gray-200 flex items-center justify-center text-[8px] font-bold text-gray-600">
                                            {p.author.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()}
                                        </div>
                                        <span className="text-xs font-bold text-gray-900">{p.author}</span>
                                    </div>
                                    <h4 className="font-bold text-lg text-gray-900 leading-tight group-hover:underline">{p.title}</h4>
                                    <p className="text-gray-600 text-[14px] leading-snug line-clamp-3 font-serif">{p.excerpt}</p>
                                    <div className="flex items-center gap-4 mt-2 text-xs text-gray-500">
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
        </div>
    );
};

export default PoemDetail;
