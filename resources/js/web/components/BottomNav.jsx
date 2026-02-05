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
        { label: isRtl ? 'ڪلام' : 'Lyrics', icon: Music, path: `/${lang}/lyrics` },
    ];

    return (
        <nav className="fixed bottom-0 left-0 right-0 h-[60px] bg-white border-t border-gray-100 flex lg:hidden items-center justify-around px-2 z-50 pb-safe">
            {navItems.map((item) => (
                <NavLink
                    key={item.path}
                    to={item.path}
                    end={item.path === `/${lang}`} // Only Home is exact
                    className={({ isActive }) =>
                        `flex flex-col items-center justify-center w-full h-full gap-1 transition-colors ${isActive ? 'text-black' : 'text-gray-400 hover:text-gray-600'
                        }`
                    }
                >
                    <item.icon className="h-5 w-5" />
                    <span className="text-[10px] font-medium">{item.label}</span>
                </NavLink>
            ))}
        </nav>
    );
};

export default BottomNav;
