import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate, useParams, useLocation, Outlet } from 'react-router-dom';
import '../../css/app.css';

// Components
import Navbar from './components/Navbar';
import BottomNav from './components/BottomNav';
import FeedbackBanner from './components/FeedbackBanner';
import MobileMenu from './components/MobileMenu';
import CategoryNav from './components/CategoryNav';
import { MobileMenuProvider, useMobileMenu } from './contexts/MobileMenuContext';
import { useSwipeGesture } from './hooks/useSwipeGesture';
import { listingDocumentTitle } from './utils/pageTitle';

// Lazy Load Components for better performance (Code Splitting)
import Feed from './components/Feed';

const SidebarLeft = React.lazy(() => import('./components/SidebarLeft'));
const SidebarRight = React.lazy(() => import('./components/SidebarRight'));
const PoetsFeed = React.lazy(() => import('./components/PoetsFeed'));
const PoetProfile = React.lazy(() => import('./components/PoetProfile'));
const PoetryFeed = React.lazy(() => import('./components/PoetryFeed'));
const CoupletsFeed = React.lazy(() => import('./components/CoupletsFeed'));
const GenreFeed = React.lazy(() => import('./components/GenreFeed'));
const PeriodFeed = React.lazy(() => import('./components/PeriodFeed'));
const ProsodyFeed = React.lazy(() => import('./components/ProsodyFeed'));
const PoemDetail = React.lazy(() => import('./components/PoemDetail'));
const About = React.lazy(() => import('./pages/About'));
const Contact = React.lazy(() => import('./pages/Contact'));
const Privacy = React.lazy(() => import('./pages/Privacy'));
const Terms = React.lazy(() => import('./pages/Terms'));
const Help = React.lazy(() => import('./pages/Help'));
const Status = React.lazy(() => import('./pages/Status'));
const SocialCallback = React.lazy(() => import('./components/SocialCallback'));
const SetPassword = React.lazy(() => import('./pages/SetPassword'));
const ForgotPassword = React.lazy(() => import('./pages/ForgotPassword'));
const ResetPassword = React.lazy(() => import('./pages/ResetPassword'));
const Profile = React.lazy(() => import('./pages/Profile'));
const SettingsPage = React.lazy(() => import('./pages/Settings'));
const ExploreTopics = React.lazy(() => import('./components/ExploreTopics'));
const TopicDetail = React.lazy(() => import('./components/TopicDetail'));

import { Skeleton } from '@/components/ui/skeleton';
import { AuthProvider } from './contexts/AuthContext';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const PageLoader = () => (
    <div className="flex-1 space-y-8 p-8 animate-pulse">
        <Skeleton className="h-10 w-3/4" />
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-5/6" />
        <Skeleton className="h-64 w-full" />
    </div>
);

const MainLayout = ({ lang }) => {
    const isRtl = lang === 'sd';
    const location = useLocation();
    const hideRightSidebar = location.pathname.includes('/poets') || location.pathname.includes('/poet/') || location.pathname.includes('/poetry') || location.pathname.includes('/couplets') || location.pathname.includes('/genre') || location.pathname.includes('/period') || location.pathname.includes('/prosody') || location.pathname.includes('/explore') || location.pathname.includes('/topic') || location.pathname.includes('/tag');
    const showCategoryNav = location.pathname.includes('/explore') || location.pathname.includes('/topic/') || location.pathname.includes('/tag/');

    const { isMenuOpen, openMenu, closeMenu } = useMobileMenu();
    useSwipeGesture({ isMenuOpen, openMenu, closeMenu, isRtl });

    return (
        <div className="min-h-screen bg-white">
            <MobileMenu lang={lang} />
            <div className="min-h-screen bg-white">
                <header role="banner">
                    <Navbar lang={lang} />
                </header>
                <div className="max-w-[1504px] mx-auto flex justify-center items-start min-h-[calc(100dvh-var(--baakh-header-h,57px))] pb-[60px] lg:pb-0">
                    <React.Suspense fallback={null}>
                        <SidebarLeft lang={lang} />
                    </React.Suspense>
                    <div className="flex-1 flex flex-col min-w-0 overflow-x-clip">
                        <FeedbackBanner lang={lang} />
                        {showCategoryNav && <CategoryNav lang={lang} />}
                        <div className="flex flex-1">
                            <main id="main-content" role="main" className="flex-1 flex flex-col min-w-0">
                                <React.Suspense fallback={<PageLoader />}>
                                    <Outlet />
                                </React.Suspense>
                            </main>
                            {!hideRightSidebar && (
                                <React.Suspense fallback={null}>
                                    <SidebarRight lang={lang} />
                                </React.Suspense>
                            )}
                        </div>
                    </div>
                </div>
                <footer role="contentinfo">
                    <BottomNav lang={lang} />
                </footer>
            </div>
        </div>
    );
};

const Home = () => {
    const { lang } = useParams();
    return <Feed lang={lang} />;
};

const Poets = () => {
    const { lang } = useParams();
    return <PoetsFeed lang={lang} />;
};

const Poetry = () => {
    const { lang } = useParams();
    return <PoetryFeed lang={lang} />;
};

const Couplets = () => {
    const { lang } = useParams();
    return <CoupletsFeed lang={lang} />;
};

const Genre = () => {
    const { lang } = useParams();
    return <GenreFeed lang={lang} />;
};

const Period = () => {
    const { lang } = useParams();
    return <PeriodFeed lang={lang} />;
};

const Poet = () => {
    const { lang } = useParams();
    return <PoetProfile lang={lang} />;
};

const SinglePoem = () => {
    const { lang } = useParams();
    return <PoemDetail lang={lang} />;
};

const Prosody = () => {
    const { lang } = useParams();
    return <ProsodyFeed lang={lang} />;
};

const Explore = () => {
    const { lang } = useParams();
    return <ExploreTopics lang={lang} />;
};

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: Infinity,
            gcTime: 1000 * 60 * 60 * 24,
            refetchOnWindowFocus: false,
            retry: 1,
        },
    },
});

const ScrollToTop = () => {
    const { pathname } = useLocation();
    useEffect(() => {
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }, [pathname]);
    return null;
};

const BARE_ROUTE_RE = /^\/(en|sd)\/(about|contact|privacy|terms|help|status|profile|settings|auth\/|password-reset\/)/;

const LanguageShell = () => {
    const { lang } = useParams();
    const location = useLocation();
    const validLangs = ['en', 'sd'];
    const isRtl = lang === 'sd';
    const isBare = BARE_ROUTE_RE.test(location.pathname);

    useEffect(() => {
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
        document.documentElement.lang = lang;

        if (isRtl) {
            document.body.classList.add('font-arabic');
        } else {
            document.body.classList.remove('font-arabic');
        }
    }, [isRtl, lang]);

    useEffect(() => {
        const title = listingDocumentTitle(location.pathname, isRtl);
        if (title) {
            document.title = title;
        }
    }, [location.pathname, isRtl]);

    if (!validLangs.includes(lang)) {
        return <Navigate to="/sd" replace />;
    }

    if (isBare) {
        return (
            <React.Suspense fallback={<PageLoader />}>
                <Outlet />
            </React.Suspense>
        );
    }

    return <MainLayout lang={lang} />;
};

const App = () => {
    return (
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
                    <ScrollToTop />
                    <MobileMenuProvider>
                        <Routes>
                            <Route path="/:lang" element={<LanguageShell />}>
                                <Route index element={<Home />} />
                                <Route path="poets" element={<Poets />} />
                                <Route path="poetry" element={<Poetry />} />
                                <Route path="couplets" element={<Couplets />} />
                                <Route path="genre" element={<Genre />} />
                                <Route path="period" element={<Period />} />
                                <Route path="poet/:slug" element={<Poet />} />
                                <Route path="poet/:slug/:category/:poemSlug" element={<SinglePoem />} />
                                <Route path="prosody" element={<Prosody />} />
                                <Route path="explore" element={<Explore />} />
                                <Route path="tag/:slug" element={<TopicDetail />} />
                                <Route path="topic/:slug" element={<TopicDetail />} />
                                <Route path="about" element={<About />} />
                                <Route path="contact" element={<Contact />} />
                                <Route path="privacy" element={<Privacy />} />
                                <Route path="terms" element={<Terms />} />
                                <Route path="help" element={<Help />} />
                                <Route path="status" element={<Status />} />
                                <Route path="auth/social-callback" element={<SocialCallback />} />
                                <Route path="auth/set-password" element={<SetPassword />} />
                                <Route path="auth/forgot-password" element={<ForgotPassword />} />
                                <Route path="password-reset/:token" element={<ResetPassword />} />
                                <Route path="profile" element={<Profile />} />
                                <Route path="settings" element={<SettingsPage />} />
                                <Route path=":category" element={<Home />} />
                            </Route>
                            <Route path="/" element={<Navigate to="/sd" replace />} />
                            <Route path="*" element={<Navigate to="/sd" replace />} />
                        </Routes>
                    </MobileMenuProvider>
                </BrowserRouter>
            </AuthProvider>
        </QueryClientProvider>
    );
};

const root = createRoot(document.getElementById('root'));
root.render(<App />);
