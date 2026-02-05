import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate, useParams, useLocation } from 'react-router-dom';
import '../../css/app.css';

// Components
import Navbar from './components/Navbar';
import SidebarLeft from './components/SidebarLeft';
import SidebarRight from './components/SidebarRight';
import Feed from './components/Feed';
import BottomNav from './components/BottomNav';
import PoetsFeed from './components/PoetsFeed';
import PoetProfile from './components/PoetProfile';
import PoetryFeed from './components/PoetryFeed';
import CoupletsFeed from './components/CoupletsFeed';
import GenreFeed from './components/GenreFeed';
import PeriodFeed from './components/PeriodFeed';
import FeedbackBanner from './components/FeedbackBanner';
import ProsodyFeed from './components/ProsodyFeed';
import PoemDetail from './components/PoemDetail';

const MainLayout = ({ children, lang }) => {
    const isRtl = lang === 'sd';
    const location = useLocation();
    const hideRightSidebar = location.pathname.includes('/poets') || location.pathname.includes('/poet/') || location.pathname.includes('/poetry') || location.pathname.includes('/couplets') || location.pathname.includes('/genre') || location.pathname.includes('/period') || location.pathname.includes('/prosody');

    useEffect(() => {
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
        document.documentElement.lang = lang;
        if (isRtl) {
            document.body.classList.add('font-arabic');
        } else {
            document.body.classList.remove('font-arabic');
        }
    }, [isRtl, lang]);

    return (
        <div className={`min-h-screen bg-white transition-opacity duration-500`}>
            <Navbar lang={lang} />
            <div className={`max-w-[1504px] mx-auto flex justify-center min-h-[calc(100vh-57px)] pb-[60px] lg:pb-0`}>
                <SidebarLeft lang={lang} />
                <div className="flex-1 flex flex-col min-w-0">
                    <FeedbackBanner lang={lang} />
                    <div className="flex flex-1">
                        <main className="flex-1 flex flex-col min-w-0">
                            {children}
                        </main>
                        {!hideRightSidebar && <SidebarRight lang={lang} />}
                    </div>
                </div>
            </div>
            <BottomNav lang={lang} />
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

const App = () => {
    return (
        <BrowserRouter>
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
                <Route path="/:lang/:category" element={
                    <LanguageWrapper>
                        <Home />
                    </LanguageWrapper>
                } />
                <Route path="/:lang/prosody" element={
                    <LanguageWrapper>
                        <Prosody />
                    </LanguageWrapper>
                } />
                <Route path="/" element={<Navigate to="/sd" replace />} />
                <Route path="*" element={<Navigate to="/sd" replace />} />
            </Routes>
        </BrowserRouter>
    );
};

const LanguageWrapper = ({ children }) => {
    const { lang } = useParams();
    const validLangs = ['en', 'sd'];

    if (!validLangs.includes(lang)) {
        return <Navigate to="/sd" replace />;
    }

    return <MainLayout lang={lang}>{children}</MainLayout>;
};

const root = createRoot(document.getElementById('root'));
root.render(<App />);
