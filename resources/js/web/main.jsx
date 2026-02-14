import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate, useParams, useLocation } from 'react-router-dom';
import '../../css/app.css';

// Components
import Navbar from './components/Navbar';
import BottomNav from './components/BottomNav';
import FeedbackBanner from './components/FeedbackBanner';
import MobileMenu from './components/MobileMenu';
import CategoryNav from './components/CategoryNav';
import { MobileMenuProvider, useMobileMenu } from './contexts/MobileMenuContext';
import { useSwipeGesture } from './hooks/useSwipeGesture';

// Lazy Load Components for better performance (Code Splitting)
const SidebarLeft = React.lazy(() => import('./components/SidebarLeft'));
const SidebarRight = React.lazy(() => import('./components/SidebarRight'));
const Feed = React.lazy(() => import('./components/Feed'));
const PoetsFeed = React.lazy(() => import('./components/PoetsFeed'));
const PoetProfile = React.lazy(() => import('./components/PoetProfile'));
const PoetryFeed = React.lazy(() => import('./components/PoetryFeed'));
const CoupletsFeed = React.lazy(() => import('./components/CoupletsFeed'));
const GenreFeed = React.lazy(() => import('./components/GenreFeed'));
const PeriodFeed = React.lazy(() => import('./components/PeriodFeed'));
const ProsodyFeed = React.lazy(() => import('./components/ProsodyFeed'));
const PoemDetail = React.lazy(() => import('./components/PoemDetail'));
const About = React.lazy(() => import('./pages/About'));
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

const PageLoader = () => (
    <div className="flex-1 space-y-8 p-8 animate-pulse">
        <Skeleton className="h-10 w-3/4" />
        <Skeleton className="h-4 w-full" />
        <Skeleton className="h-4 w-5/6" />
        <Skeleton className="h-64 w-full" />
    </div>
);

const MainLayout = ({ children, lang }) => {
    const isRtl = lang === 'sd';
    const location = useLocation();
    const hideRightSidebar = location.pathname.includes('/poets') || location.pathname.includes('/poet/') || location.pathname.includes('/poetry') || location.pathname.includes('/couplets') || location.pathname.includes('/genre') || location.pathname.includes('/period') || location.pathname.includes('/prosody') || location.pathname.includes('/explore') || location.pathname.includes('/topic') || location.pathname.includes('/tag');
    const showCategoryNav = location.pathname.includes('/explore') || location.pathname.includes('/topic/') || location.pathname.includes('/tag/');

    const { isMenuOpen, openMenu, closeMenu } = useMobileMenu();
    useSwipeGesture({ isMenuOpen, openMenu, closeMenu, isRtl });

    return (
        <div className="min-h-screen bg-white overflow-x-hidden">
            <MobileMenu lang={lang} />
            <div className="min-h-screen bg-white">
                <header role="banner">
                    <Navbar lang={lang} />
                </header>
                <div className={`max-w-[1504px] mx-auto flex justify-center min-h-[calc(100vh-57px)] pb-[60px] lg:pb-0`}>
                    <SidebarLeft lang={lang} />
                    <div className="flex-1 flex flex-col min-w-0">
                        <FeedbackBanner lang={lang} />
                        {showCategoryNav && <CategoryNav lang={lang} />}
                        <div className="flex flex-1">
                            <main id="main-content" role="main" className="flex-1 flex flex-col min-w-0">
                                {children}
                            </main>
                            {!hideRightSidebar && <SidebarRight lang={lang} />}
                        </div>
                    </div>
                </div>
                <footer role="contentinfo">
                    <BottomNav lang={lang} />
                </footer>

                {/* Overlay for content when menu is open (optional, but good for UX to indicate content is inactive) */}
                {isMenuOpen && (
                    <div
                        className="absolute inset-0 z-40 bg-white/50"
                        onClick={closeMenu}
                    />
                )}
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

import { AuthProvider } from './contexts/AuthContext';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: Infinity, // Data remains fresh until page reload
            gcTime: 1000 * 60 * 60 * 24, // Keep in cache for 24 hours
            refetchOnWindowFocus: false, // Don't refetch when switching tabs
            retry: 1,
        },
    },
});

const App = () => {
    return (
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <BrowserRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
                    <MobileMenuProvider>
                        <React.Suspense fallback={<PageLoader />}>
                            <Routes>
                                <Route path="/:lang" element={
                                    <LanguageWrapper>
                                        <Home />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/poets" element={
                                    <LanguageWrapper>
                                        <Poets />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/poetry" element={
                                    <LanguageWrapper>
                                        <Poetry />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/couplets" element={
                                    <LanguageWrapper>
                                        <Couplets />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/genre" element={
                                    <LanguageWrapper>
                                        <Genre />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/period" element={
                                    <LanguageWrapper>
                                        <Period />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/poet/:slug" element={
                                    <LanguageWrapper>
                                        <Poet />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/poet/:slug/:category/:poemSlug" element={
                                    <LanguageWrapper>
                                        <SinglePoem />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/prosody" element={
                                    <LanguageWrapper>
                                        <Prosody />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/explore" element={
                                    <LanguageWrapper>
                                        <Explore />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/tag/:slug" element={
                                    <LanguageWrapper>
                                        <TopicDetail />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/topic/:slug" element={
                                    <LanguageWrapper>
                                        <TopicDetail />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/about" element={
                                    <LanguageWrapper withLayout={false}>
                                        <About />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/privacy" element={
                                    <LanguageWrapper withLayout={false}>
                                        <Privacy />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/terms" element={
                                    <LanguageWrapper withLayout={false}>
                                        <Terms />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/help" element={
                                    <LanguageWrapper withLayout={false}>
                                        <Help />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/status" element={
                                    <LanguageWrapper withLayout={false}>
                                        <Status />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/auth/social-callback" element={
                                    <LanguageWrapper withLayout={false}>
                                        <SocialCallback />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/auth/set-password" element={
                                    <LanguageWrapper withLayout={false}>
                                        <SetPassword />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/auth/forgot-password" element={
                                    <LanguageWrapper withLayout={false}>
                                        <ForgotPassword />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/password-reset/:token" element={
                                    <LanguageWrapper withLayout={false}>
                                        <ResetPassword />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/profile" element={
                                    <LanguageWrapper withLayout={false}>
                                        <Profile />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/settings" element={
                                    <LanguageWrapper withLayout={false}>
                                        <SettingsPage />
                                    </LanguageWrapper>
                                } />
                                <Route path="/:lang/:category" element={
                                    <LanguageWrapper>
                                        <Home />
                                    </LanguageWrapper>
                                } />
                                <Route path="/" element={<Navigate to="/sd" replace />} />
                                <Route path="*" element={<Navigate to="/sd" replace />} />
                            </Routes>
                        </React.Suspense>
                    </MobileMenuProvider>
                </BrowserRouter>
            </AuthProvider>
        </QueryClientProvider>
    );
};

const LanguageWrapper = ({ children, withLayout = true }) => {
    const { lang } = useParams();
    const validLangs = ['en', 'sd'];
    const isRtl = lang === 'sd';

    useEffect(() => {
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
        document.documentElement.lang = lang;

        // Update tab title based on language
        document.title = isRtl
            ? 'باک - سنڌي شاعريءَ جو آرڪائيو'
            : 'Baakh - Archive of Sindhi Poetry';

        if (isRtl) {
            document.body.classList.add('font-arabic');
        } else {
            document.body.classList.remove('font-arabic');
        }
    }, [isRtl, lang]);

    if (!validLangs.includes(lang)) {
        return <Navigate to="/sd" replace />;
    }

    if (withLayout) {
        return <MainLayout lang={lang}>{children}</MainLayout>;
    }

    return children;
};

const root = createRoot(document.getElementById('root'));
root.render(<App />);
