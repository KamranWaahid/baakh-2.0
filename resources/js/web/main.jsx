import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate, useParams } from 'react-router-dom';
import '../../css/app.css';

// Components
import Navbar from './components/Navbar';
import SidebarLeft from './components/SidebarLeft';
import SidebarRight from './components/SidebarRight';
import Feed from './components/Feed';

const MainLayout = ({ children, lang }) => {
    const isRtl = lang === 'sd';

    useEffect(() => {
        document.documentElement.dir = isRtl ? 'rtl' : 'ltr';
        document.documentElement.lang = lang;
        // Apply SF Arabic font to body if Sindhi
        if (isRtl) {
            document.body.classList.add('font-arabic');
        } else {
            document.body.classList.remove('font-arabic');
        }
    }, [isRtl, lang]);

    return (
        <div className={`min-h-screen bg-white transition-opacity duration-500`}>
            <Navbar lang={lang} />
            <div className={`max-w-[1504px] mx-auto flex justify-center min-h-[calc(100vh-57px)]`}>
                <SidebarLeft lang={lang} />
                <main className="flex-1 flex flex-col min-w-0">
                    {children}
                </main>
                <SidebarRight lang={lang} />
            </div>
        </div>
    );
};

const Home = () => {
    const { lang } = useParams();
    return <Feed lang={lang} />;
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
