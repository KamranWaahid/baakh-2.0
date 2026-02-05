import React from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '@/admin/api/axios';
import { useAuth } from '../contexts/AuthContext';
import LoginModal from './LoginModal';
import ReportModal from './ReportModal';
import { Button } from '@/components/ui/button';
import { Share2, MoreHorizontal, Sparkles, User, Link2, Facebook, Twitter, Linkedin, MessageCircle, Flag } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from "@/components/ui/dropdown-menu";
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import PaywallCTA from './PaywallCTA';
import { formatSindhiDate } from '../utils/dateUtils';

const PoemDetail = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { poemSlug } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const [loginModalOpen, setLoginModalOpen] = React.useState(false);
    const [reportModalOpen, setReportModalOpen] = React.useState(false);
    const [isCopied, setIsCopied] = React.useState(false);

    const handleAuthAction = (action) => {
        if (!user) {
            setLoginModalOpen(true);
            return;
        }
        action();
    };

    const handleClap = () => {
        handleAuthAction(() => {
            // TODO: Implement clap logic
            console.log('Clapped!');
        });
    };

    const handleSave = () => {
        handleAuthAction(() => {
            // TODO: Implement save logic
            console.log('Saved!');
        });
    };

    const currentUrl = window.location.href;

    const shareLinks = [
        {
            name: 'Copy link', icon: Link2, action: () => {
                navigator.clipboard.writeText(currentUrl);
                setIsCopied(true);
                setTimeout(() => setIsCopied(false), 2000);
            }
        },
        { name: 'Share on Facebook', icon: Facebook, url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}` },
        { name: 'Share on X', icon: Twitter, url: `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentUrl)}` },
        { name: 'Share on LinkedIn', icon: Linkedin, url: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(currentUrl)}` },
        { name: 'Share on WhatsApp', icon: MessageCircle, url: `https://wa.me/?text=${encodeURIComponent(currentUrl)}` },
    ];

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

    return (
        <div className="w-full flex flex-col items-center py-12 px-4 md:px-8 bg-white" dir={isRtl ? 'rtl' : 'ltr'}>
            <article className="w-full max-w-[680px] mb-20 animate-fade-in-up">
                {/* Header */}
                <header className="mb-8">
                    {/* Badge */}
                    <div className="flex items-center gap-2 mb-6 text-yellow-500">
                        <Sparkles className="h-4 w-4 fill-yellow-500" />
                        <span className="text-sm font-medium text-gray-700">{poem.category?.name || (isRtl ? 'رڪني ڪهاڻي' : 'Member-only story')}</span>
                    </div>

                    <h1 className={`text-3xl md:text-[40px] font-bold tracking-tight text-gray-900 leading-tight mb-6 ${isRtl ? 'font-arabic' : ''}`}>
                        {poem.title}
                    </h1>

                    <div className="flex items-center gap-4 mb-8">
                        <Link to={`/${lang}/poet/${poem.poet?.slug}`} className="shrink-0">
                            {poem.poet?.avatar ? (
                                <img src={poem.poet.avatar.startsWith('http') ? poem.poet.avatar : `/${poem.poet.avatar}`} alt={poem.poet.name} className="h-11 w-11 rounded-full object-cover bg-gray-200" />
                            ) : (
                                <div className="h-11 w-11 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 shrink-0 border border-gray-100">
                                    <User className="h-6 w-6" />
                                </div>
                            )}
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
                        <div className="flex items-center gap-6">
                            <button
                                onClick={handleClap}
                                className="flex items-center gap-2 hover:text-black transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-label="clap"><path fillRule="evenodd" d="M11.37.828 12 3.282l.63-2.454zM13.916 3.953l1.523-2.112-1.184-.39zM8.589 1.84l1.522 2.112-.337-2.501zM18.523 18.92c-.86.86-1.75 1.246-2.62 1.33a6 6 0 0 0 .407-.372c2.388-2.389 2.86-4.951 1.399-7.623l-.912-1.603-.79-1.672c-.26-.56-.194-.98.203-1.288a.7.7 0 0 1 .546-.132c.283.046.546.231.728.5l2.363 4.157c.976 1.624 1.141 4.237-1.324 6.702m-10.999-.438L3.37 14.328a.828.828 0 0 1 .585-1.408.83.83 0 0 1 .585.242l2.158 2.157a.365.365 0 0 0 .516-.516l-2.157-2.158-1.449-1.449a.826.826 0 0 1 1.167-1.17l3.438 3.44a.363.363 0 0 0 .516 0 .364.364 0 0 0 0-.516L5.293 9.513l-.97-.97a.826.826 0 0 1 0-1.166.84.84 0 0 1 1.167 0l.97.968 3.437 3.436a.36.36 0 0 0 .517 0 .366.366 0 0 0 0-.516L6.977 7.83a.82.82 0 0 1-.241-.584.82.82 0 0 1 .824-.826c.219 0 .43.087.584.242l5.787 5.787a.366.366 0 0 0 .587-.415l-1.117-2.363c-.26-.56-.194-.98.204-1.289a.7.7 0 0 1 .546-.132c.283.046.545.232.727.501l2.193 3.86c1.302 2.38.883 4.59-1.277 6.75-1.156 1.156-2.602 1.627-4.19 1.367-1.418-.236-2.866-1.033-4.079-2.246M10.75 5.971l2.12 2.12c-.41.502-.465 1.17-.128 1.89l.22.465-3.523-3.523a.8.8 0 0 1-.097-.368c0-.22.086-.428.241-.584a.847.847 0 0 1 1.167 0m7.355 1.705c-.31-.461-.746-.758-1.23-.837a1.44 1.44 0 0 0-1.11.275c-.312.24-.505.543-.59.881a1.74 1.74 0 0 0-.906-.465 1.47 1.47 0 0 0-.82.106l-2.182-2.182a1.56 1.56 0 0 0-2.2 0 1.54 1.54 0 0 0-.396.701 1.56 1.56 0 0 0-2.21-.01 1.55 1.55 0 0 0-.416.753c-.624-.624-1.649-.624-2.237-.037a1.557 1.557 0 0 0 0 2.2c-.239.1-.501.238-.715.453a1.56 1.56 0 0 0 0 2.2l.516.515a1.556 1.556 0 0 0-.753 2.615L7.01 19c1.32 1.319 2.909 2.189 4.475 2.449q.482.08.971.08c.85 0 1.653-.198 2.393-.579.231.033.46.054.686.054 1.266 0 2.457-.52 3.505-1.567 2.763-2.763 2.552-5.734 1.439-7.586z" clipRule="evenodd"></path></svg>
                                <span className="text-sm">{poem.likes || 0}</span>
                            </button>
                        </div>
                        <div className="flex items-center gap-4">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button className="hover:text-black outline-none">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="currentColor" fillRule="evenodd" d="M15.218 4.931a.4.4 0 0 1-.118.132l.012.006a.45.45 0 0 1-.292.074.5.5 0 0 1-.3-.13l-2.02-2.02v7.07c0 .28-.23.5-.5.5s-.5-.22-.5-.5v-7.04l-2 2a.45.45 0 0 1-.57.04h-.02a.4.4 0 0 1-.16-.3.4.4 0 0 1 .1-.32l2.8-2.8a.5.5 0 0 1 .7 0l2.8 2.79a.42.42 0 0 1 .068.498m-.106.138.008.004v-.01zM16 7.063h1.5a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-11c-1.1 0-2-.9-2-2v-10a2 2 0 0 1 2-2H8a.5.5 0 0 1 .35.15.5.5 0 0 1 .15.35.5.5 0 0 1-.35.15H6.4c-.5 0-.9.4-.9.9v10.2a.9.9 0 0 0 .9.9h11.2c.5 0 .9-.4.9-.9v-10.2c0-.5-.4-.9-.9-.9H16a.5.5 0 0 1 0-1" clipRule="evenodd"></path></svg>
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-56 bg-white p-2">
                                    {shareLinks.map((link, index) => (
                                        <React.Fragment key={index}>
                                            <DropdownMenuItem
                                                className="cursor-pointer py-3 text-gray-600 focus:text-black focus:bg-gray-50 flex items-center gap-3"
                                                onClick={() => link.action ? link.action() : window.open(link.url, '_blank')}
                                            >
                                                <link.icon className="h-4 w-4" />
                                                <span>{link.name} {link.name === 'Copy link' && isCopied && '(Copied!)'}</span>
                                            </DropdownMenuItem>
                                            {index === 0 && <DropdownMenuSeparator className="my-1 bg-gray-100" />}
                                        </React.Fragment>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <button onClick={handleSave} className="hover:text-black">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" className="cc"><path fill="#000" d="M17.5 1.25a.5.5 0 0 1 1 0v2.5H21a.5.5 0 0 1 0 1h-2.5v2.5a.5.5 0 0 1-1 0v-2.5H15a.5.5 0 0 1 0-1h2.5zm-11 4.5a1 1 0 0 1 1-1H11a.5.5 0 0 0 0-1H7.5a2 2 0 0 0-2 2v14a.5.5 0 0 0 .8.4l5.7-4.4 5.7 4.4a.5.5 0 0 0 .8-.4v-8.5a.5.5 0 0 0-1 0v7.48l-5.2-4a.5.5 0 0 0-.6 0l-5.2 4z"></path></svg>
                            </button>

                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <button className="hover:text-black outline-none">
                                        <MoreHorizontal className="h-5 w-5" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-48 bg-white p-1">
                                    <DropdownMenuItem
                                        className="cursor-pointer py-2 text-red-600 focus:text-red-700 focus:bg-red-50 flex items-center gap-2"
                                        onClick={() => setReportModalOpen(true)}
                                    >
                                        <Flag className="h-4 w-4" />
                                        <span>{isRtl ? 'رپورٽ ڪريو' : 'Report this poem'}</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
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
                        <div className={`bg-gray-50 p-6 rounded-lg mt-8 text-base text-gray-600 italic border-l-4 border-gray-200 ${isRtl ? 'border-l-0 border-r-4' : ''}`}>
                            {poem.info}
                        </div>
                    )}

                    {poem.category && (
                        <p className="mt-8 text-sm text-gray-500">
                            {isRtl ? 'مجموعو: ' : 'Collection: '} <span className="font-medium text-gray-900">{poem.category.name}</span>
                        </p>
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
                <div className={`flex items-start justify-between mb-12 font-sans ${isRtl ? 'flex-row-reverse text-right' : ''}`}>
                    <div className={`flex gap-4 ${isRtl ? 'flex-row-reverse' : ''}`}>
                        <Link to={`/${lang}/poet/${poem.poet.slug}`}>
                            {poem.poet.avatar ? (
                                <img src={poem.poet.avatar} alt={poem.poet.name} className="h-[64px] w-[64px] rounded-full object-cover bg-gray-200" />
                            ) : (
                                <div className="h-[64px] w-[64px] rounded-full bg-gray-50 flex items-center justify-center text-gray-400 shrink-0 border border-gray-100">
                                    <User className="h-8 w-8" />
                                </div>
                            )}
                        </Link>
                        <div className="flex flex-col pt-1">
                            <Link to={`/${lang}/poet/${poem.poet.slug}`}>
                                <h3 className="text-[20px] font-bold text-gray-900 mb-1 leading-tight">{isRtl ? 'ليکڪ: ' : 'Written by '} {poem.poet.name}</h3>
                            </Link>
                            <div className="flex items-center gap-1 text-[14px] text-gray-500 mb-2">
                                <span>{poem.poet.followers} {isRtl ? 'فالوئرز' : 'followers'}</span>
                            </div>
                            <p className="text-[15px] text-gray-600 leading-snug">
                                {poem.poet.tagline || (poem.poet.bio ? poem.poet.bio.substring(0, 100) + '...' : (isRtl ? 'سنڌ جو انقلابي شاعر.' : 'Author at Baakh'))}
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" className="rounded-full border-gray-300 text-black hover:border-black hover:bg-transparent px-5 h-[38px] text-[14px]">
                        {isRtl ? 'فالو ڪريو' : 'Follow'}
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
                                        <div className="flex items-center gap-1">
                                            <MessageCircle className="h-3 w-3" />
                                            <span>{p.comments}</span>
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
                                        <div className="h-5 w-5 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100">
                                            <User className="h-3 w-3" />
                                        </div>
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
            <LoginModal open={loginModalOpen} onOpenChange={setLoginModalOpen} isRtl={isRtl} />
            <ReportModal open={reportModalOpen} onOpenChange={setReportModalOpen} isRtl={isRtl} poemId={poem?.id} />
        </div>
    );
};

export default PoemDetail;
