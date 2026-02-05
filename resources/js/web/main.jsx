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
import PoemDetail from './components/PoemDetail';
import FeedbackBanner from './components/FeedbackBanner';

const MainLayout = ({ children, lang }) => {
    const isRtl = lang === 'sd';
    const location = useLocation();
    const hideRightSidebar = location.pathname.includes('/poets') || location.pathname.includes('/poet/');

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

const Poet = () => {
    const { lang } = useParams();
    return <PoetProfile lang={lang} />;
};

const SinglePoem = () => {
    const { lang } = useParams();
    return <PoemDetail lang={lang} />;
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
                <Route path="/" element={<Navigate to="/en" replace />} />
                <Route path="*" element={<Navigate to="/en" replace />} />
            </Routes>
        </BrowserRouter>
    );
};

const LanguageWrapper = ({ children }) => {
    const { lang } = useParams();
    const validLangs = ['en', 'sd'];

    if (!validLangs.includes(lang)) {
        return <Navigate to="/en" replace />;
    }

    return <MainLayout lang={lang}>{children}</MainLayout>;
};

const root = createRoot(document.getElementById('root'));
root.render(<App />);
