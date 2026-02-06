import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import LoginModal from './LoginModal';
import ReportModal from './ReportModal';
import {
    Share2,
    MoreHorizontal,
    Link2,
    Facebook,
    Twitter,
    Linkedin,
    MessageCircle,
    Flag
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator
} from "@/components/ui/dropdown-menu";

const PoemActionBar = ({ poem, lang, className, leftContent }) => {
    const isRtl = lang === 'sd';
    const { user } = useAuth();
    const [loginModalOpen, setLoginModalOpen] = useState(false);
    const [reportModalOpen, setReportModalOpen] = useState(false);
    const [isCopied, setIsCopied] = useState(false);

    // Construct URL for sharing
    // Use window.location.origin to get base URL, then construct path
    // If we are on detail page, window.location.href is fine
    // But for PostCard, we need to construct the URL from poem data
    const getShareUrl = () => {
        if (!poem.slug || !poem.poet_slug) return window.location.href; // Fallback
        const baseUrl = window.location.origin;
        // Construct path: /:lang/poet/:poet_slug/:cat_slug/:poem_slug
        // Note: PostCard might not have cat_slug or poet_slug fully populated depending on API
        // For now, let's assume we can construct it or fallback to current page if on detail
        // Actually, easiest is to fallback to current URL if we are on detail page, 
        // but for Feed, we need the specific poem URL.

        // Let's use the provided slugs if available
        if (poem.poet_slug && poem.slug) {
            // Handle cat_slug being optional or empty string
            const catPart = poem.cat_slug ? `/${poem.cat_slug}` : '';
            return `${baseUrl}/${lang}/poet/${poem.poet_slug}${catPart}/${poem.slug}`;
        }

        return window.location.href;
    };

    const shareUrl = getShareUrl();

    const handleAuthAction = (action) => {
        if (!user) {
            setLoginModalOpen(true);
            return;
        }
        action();
    };

    const handleClap = (e) => {
        e.preventDefault(); // Prevent link navigation if inside a Link
        e.stopPropagation();
        handleAuthAction(() => {
            // TODO: Implement clap logic
            console.log('Clapped!');
        });
    };

    const handleSave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        handleAuthAction(() => {
            // TODO: Implement save logic
            console.log('Saved!');
        });
    };

    const shareLinks = [
        {
            name: 'Copy link', icon: Link2, action: () => {
                navigator.clipboard.writeText(shareUrl);
                setIsCopied(true);
                setTimeout(() => setIsCopied(false), 2000);
            }
        },
        { name: 'Share on Facebook', icon: Facebook, url: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}` },
        { name: 'Share on X', icon: Twitter, url: `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl)}` },
        { name: 'Share on LinkedIn', icon: Linkedin, url: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl)}` },
        { name: 'Share on WhatsApp', icon: MessageCircle, url: `https://wa.me/?text=${encodeURIComponent(shareUrl)}` },
    ];

    const handleShareClick = (e, link) => {
        e.preventDefault();
        e.stopPropagation();
        if (link.action) {
            link.action();
        } else {
            window.open(link.url, '_blank');
        }
    };

    return (
        <div className={`flex items-center justify-between text-gray-500 w-full ${className || ''}`} onClick={(e) => e.preventDefault()}>
            <div className="flex items-center gap-6">
                {leftContent && (
                    <div className="flex items-center gap-3">
                        {leftContent}
                    </div>
                )}
                <button
                    onClick={handleClap}
                    className="flex items-center gap-2 hover:text-black transition-colors z-10 relative"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" aria-label="clap"><path fillRule="evenodd" d="M11.37.828 12 3.282l.63-2.454zM13.916 3.953l1.523-2.112-1.184-.39zM8.589 1.84l1.522 2.112-.337-2.501zM18.523 18.92c-.86.86-1.75 1.246-2.62 1.33a6 6 0 0 0 .407-.372c2.388-2.389 2.86-4.951 1.399-7.623l-.912-1.603-.79-1.672c-.26-.56-.194-.98.203-1.288a.7.7 0 0 1 .546-.132c.283.046.546.231.728.5l2.363 4.157c.976 1.624 1.141 4.237-1.324 6.702m-10.999-.438L3.37 14.328a.828.828 0 0 1 .585-1.408.83.83 0 0 1 .585.242l2.158 2.157a.365.365 0 0 0 .516-.516l-2.157-2.158-1.449-1.449a.826.826 0 0 1 1.167-1.17l3.438 3.44a.363.363 0 0 0 .516 0 .364.364 0 0 0 0-.516L5.293 9.513l-.97-.97a.826.826 0 0 1 0-1.166.84.84 0 0 1 1.167 0l.97.968 3.437 3.436a.36.36 0 0 0 .517 0 .366.366 0 0 0 0-.516L6.977 7.83a.82.82 0 0 1-.241-.584.82.82 0 0 1 .824-.826c.219 0 .43.087.584.242l5.787 5.787a.366.366 0 0 0 .587-.415l-1.117-2.363c-.26-.56-.194-.98.204-1.289a.7.7 0 0 1 .546-.132c.283.046.545.232.727.501l2.193 3.86c1.302 2.38.883 4.59-1.277 6.75-1.156 1.156-2.602 1.627-4.19 1.367-1.418-.236-2.866-1.033-4.079-2.246M10.75 5.971l2.12 2.12c-.41.502-.465 1.17-.128 1.89l.22.465-3.523-3.523a.8.8 0 0 1-.097-.368c0-.22.086-.428.241-.584a.847.847 0 0 1 1.167 0m7.355 1.705c-.31-.461-.746-.758-1.23-.837a1.44 1.44 0 0 0-1.11.275c-.312.24-.505.543-.59.881a1.74 1.74 0 0 0-.906-.465 1.47 1.47 0 0 0-.82.106l-2.182-2.182a1.56 1.56 0 0 0-2.2 0 1.54 1.54 0 0 0-.396.701 1.56 1.56 0 0 0-2.21-.01 1.55 1.55 0 0 0-.416.753c-.624-.624-1.649-.624-2.237-.037a1.557 1.557 0 0 0 0 2.2c-.239.1-.501.238-.715.453a1.56 1.56 0 0 0 0 2.2l.516.515a1.556 1.556 0 0 0-.753 2.615L7.01 19c1.32 1.319 2.909 2.189 4.475 2.449q.482.08.971.08c.85 0 1.653-.198 2.393-.579.231.033.46.054.686.054 1.266 0 2.457-.52 3.505-1.567 2.763-2.763 2.552-5.734 1.439-7.586z" clipRule="evenodd"></path></svg>
                    <span className="text-sm">{poem.likes || 0}</span>
                </button>
            </div>
            <div className="flex items-center gap-4">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="hover:text-black outline-none z-10 relative">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path fill="currentColor" fillRule="evenodd" d="M15.218 4.931a.4.4 0 0 1-.118.132l.012.006a.45.45 0 0 1-.292.074.5.5 0 0 1-.3-.13l-2.02-2.02v7.07c0 .28-.23.5-.5.5s-.5-.22-.5-.5v-7.04l-2 2a.45.45 0 0 1-.57.04h-.02a.4.4 0 0 1-.16-.3.4.4 0 0 1 .1-.32l2.8-2.8a.5.5 0 0 1 .7 0l2.8 2.79a.42.42 0 0 1 .068.498m-.106.138.008.004v-.01zM16 7.063h1.5a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-11c-1.1 0-2-.9-2-2v-10a2 2 0 0 1 2-2H8a.5.5 0 0 1 .35.15.5.5 0 0 1 .15.35.5.5 0 0 1-.35.15H6.4c-.5 0-.9.4-.9.9v10.2a.9.9 0 0 0 .9.9h11.2c.5 0 .9-.4.9-.9v-10.2c0-.5-.4-.9-.9-.9H16a.5.5 0 0 1 0-1" clipRule="evenodd"></path></svg>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56 bg-white p-2 z-50">
                        {shareLinks.map((link, index) => (
                            <React.Fragment key={index}>
                                <DropdownMenuItem
                                    className="cursor-pointer py-3 text-gray-600 focus:text-black focus:bg-gray-50 flex items-center gap-3"
                                    onClick={(e) => handleShareClick(e, link)}
                                >
                                    <link.icon className="h-4 w-4" />
                                    <span>{link.name} {link.name === 'Copy link' && isCopied && '(Copied!)'}</span>
                                </DropdownMenuItem>
                                {index === 0 && <DropdownMenuSeparator className="my-1 bg-gray-100" />}
                            </React.Fragment>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>

                <button onClick={handleSave} className="hover:text-black z-10 relative">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" className="cc"><path fill="#000" d="M17.5 1.25a.5.5 0 0 1 1 0v2.5H21a.5.5 0 0 1 0 1h-2.5v2.5a.5.5 0 0 1-1 0v-2.5H15a.5.5 0 0 1 0-1h2.5zm-11 4.5a1 1 0 0 1 1-1H11a.5.5 0 0 0 0-1H7.5a2 2 0 0 0-2 2v14a.5.5 0 0 0 .8.4l5.7-4.4 5.7 4.4a.5.5 0 0 0 .8-.4v-8.5a.5.5 0 0 0-1 0v7.48l-5.2-4a.5.5 0 0 0-.6 0l-5.2 4z"></path></svg>
                </button>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <button className="hover:text-black outline-none z-10 relative">
                            <MoreHorizontal className="h-5 w-5" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48 bg-white p-1 z-50">
                        <DropdownMenuItem
                            className="cursor-pointer py-2 text-red-600 focus:text-red-700 focus:bg-red-50 flex items-center gap-2"
                            onClick={(e) => { e.preventDefault(); e.stopPropagation(); setReportModalOpen(true); }}
                        >
                            <Flag className="h-4 w-4" />
                            <span>{isRtl ? 'رپورٽ ڪريو' : 'Report this poem'}</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            {/* Modals placed outside the clickable area if possible, but triggers are inside */}
            {/* Using controlled state passed to modals */}
            <LoginModal open={loginModalOpen} onOpenChange={setLoginModalOpen} isRtl={isRtl} />
            <ReportModal open={reportModalOpen} onOpenChange={setReportModalOpen} isRtl={isRtl} poemId={poem?.id} />
        </div>
    );
};

export default PoemActionBar;
