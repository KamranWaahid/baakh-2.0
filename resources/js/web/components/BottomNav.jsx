import React from 'react';
import { Home, Library, User, FileText } from 'lucide-react';
import { NavLink } from 'react-router-dom';

const BottomNav = ({ lang }) => {
    const isRtl = lang === 'sd';

    const navItems = [
        { label: isRtl ? 'گھر' : 'Home', icon: Home, path: `/${lang}` },
        { label: isRtl ? 'لائبريري' : 'Library', icon: Library, path: `/${lang}/library` },
        { label: isRtl ? 'ڪهاڻيون' : 'Stories', icon: FileText, path: `/${lang}/stories` },
        { label: isRtl ? 'پروفائل' : 'Profile', icon: User, path: `/${lang}/profile` },
    ];

    return (
        <nav className="fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-gray-100 flex lg:hidden items-center justify-around px-4 z-50">
            {navItems.map((item) => (
                <NavLink
                    key={item.path}
                    to={item.path}
                    className={({ isActive }) =>
                        `flex flex-col items-center gap-1 transition-colors ${isActive ? 'text-black' : 'text-gray-400 hover:text-gray-600'
                        }`
                    }
                >
                    <item.icon className="h-6 w-6" />
                    <span className="text-[10px] uppercase font-bold tracking-tight">{item.label}</span>
                </NavLink>
            ))}
        </nav>
    );
};

export default BottomNav;
