import React from 'react';
import { Home, Feather, BookOpen, Scroll, Music } from 'lucide-react';
import { NavLink } from 'react-router-dom';
import { useScrollDirection } from '../hooks/useScrollDirection';

const BottomNav = ({ lang }) => {
    const isRtl = lang === 'sd';

    const navItems = [
        { label: isRtl ? 'گھر' : 'Home', icon: Home, path: `/${lang}` },
        { label: isRtl ? 'شاعر' : 'Poets', icon: Feather, path: `/${lang}/poets` },
        { label: isRtl ? 'شاعري' : 'Poetry', icon: BookOpen, path: `/${lang}/poetry` },
        { label: isRtl ? 'بيت' : 'Couplets', icon: Scroll, path: `/${lang}/couplets` },
    ];

    const scrollDirection = useScrollDirection();
    const isHidden = scrollDirection === 'down';

    return (
        <nav className={`fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100/50 flex lg:hidden items-center justify-around px-4 z-50 shadow-[0_-8px_30_rgb(0,0,0,0.04)] active:shadow-none transition-all duration-500 ${isHidden ? 'translate-y-[100%] opacity-0' : 'translate-y-0 opacity-100'}`} style={{ paddingBottom: 'calc(env(safe-area-inset-bottom, 8px) + 8px)', paddingTop: '8px' }}>
            {navItems.map((item) => (
                <NavLink
                    key={item.path}
                    to={item.path}
                    end={item.path === `/${lang}`}
                    aria-label={item.label}
                    className={({ isActive }) =>
                        `flex flex-col items-center justify-center min-w-[64px] transition-all duration-200 rounded-xl relative tap-highlight-none ${isActive
                            ? 'text-black'
                            : 'text-gray-400 hover:text-gray-600 active:scale-95'
                        }`
                    }
                >
                    {({ isActive }) => (
                        <>
                            <div className={`p-1.5 rounded-xl transition-all duration-300 ${isActive ? 'bg-black/5 scale-110' : 'bg-transparent'}`}>
                                <item.icon className={`h-5 w-5 transition-all duration-300 ${isActive ? 'stroke-[2.5px]' : 'stroke-[1.8px]'}`} />
                            </div>
                            <span className={`text-[10px] sm:text-[11px] mt-1 transition-all duration-300 tracking-tight ${isActive ? 'font-bold opacity-100 translate-y-0' : 'font-medium opacity-60 translate-y-0.5'}`}>
                                {item.label}
                            </span>
                        </>
                    )}
                </NavLink>
            ))}
        </nav>
    );
};

export default BottomNav;
