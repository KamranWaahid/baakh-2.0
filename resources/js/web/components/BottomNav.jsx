import React from 'react';
import { Home, Feather, BookOpen, Scroll, Music } from 'lucide-react';
import { NavLink } from 'react-router-dom';

const BottomNav = ({ lang }) => {
    const isRtl = lang === 'sd';

    const navItems = [
        { label: isRtl ? 'گھر' : 'Home', icon: Home, path: `/${lang}` },
        { label: isRtl ? 'شاعر' : 'Poets', icon: Feather, path: `/${lang}/poets` },
        { label: isRtl ? 'شاعري' : 'Poetry', icon: BookOpen, path: `/${lang}/poetry` },
        { label: isRtl ? 'بيت' : 'Couplets', icon: Scroll, path: `/${lang}/couplets` },
    ];

    return (
        <nav className="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-sm border-t border-gray-100 flex lg:hidden items-center justify-around px-2 z-50 shadow-[0_-1px_3px_rgba(0,0,0,0.05)]" style={{ paddingBottom: 'env(safe-area-inset-bottom, 8px)' }}>
            {navItems.map((item) => (
                <NavLink
                    key={item.path}
                    to={item.path}
                    end={item.path === `/${lang}`}
                    className={({ isActive }) =>
                        `flex flex-col items-center justify-center min-w-[64px] min-h-[56px] py-2 gap-1 transition-all duration-200 rounded-lg ${isActive
                            ? 'text-black scale-105'
                            : 'text-gray-400 hover:text-gray-600 active:scale-95'
                        }`
                    }
                >
                    {({ isActive }) => (
                        <>
                            <item.icon className={`h-5 w-5 transition-transform duration-200 ${isActive ? 'stroke-[2.5px]' : ''}`} />
                            <span className={`text-[10px] transition-all duration-200 ${isActive ? 'font-bold' : 'font-medium'}`}>{item.label}</span>
                        </>
                    )}
                </NavLink>
            ))}
        </nav>
    );
};

export default BottomNav;
