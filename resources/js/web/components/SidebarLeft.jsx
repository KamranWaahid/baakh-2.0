import React from 'react';
import { Home, Feather, BookOpen, Scroll, Music, Tags, History, Scale, Plus } from 'lucide-react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';

const SidebarLeft = ({ lang }) => {
    const isRtl = lang === 'sd';
    const location = useLocation();

    const navItems = [
        { label: isRtl ? 'گھر' : 'Home', icon: Home, path: `/${lang}` },
        { label: isRtl ? 'شاعر' : 'Poets', icon: Feather, path: `/${lang}/poets` },
        { label: isRtl ? 'شاعري' : 'Poetry', icon: BookOpen, path: `/${lang}/poetry` },
        { label: isRtl ? 'بيت' : 'Couplets', icon: Scroll, path: `/${lang}/couplets` },
        { label: isRtl ? 'ڪلام' : 'Lyrics', icon: Music, path: `/${lang}/lyrics` },
        { label: isRtl ? 'صنف' : 'Genre', icon: Tags, path: `/${lang}/genre` },
        { label: isRtl ? 'دور' : 'Period', icon: History, path: `/${lang}/period` },
        { label: isRtl ? 'علم عروض' : 'Prosody', icon: Scale, path: `/${lang}/prosody` },
    ];

    return (
        <aside className={`w-[240px] border-e border-gray-100 h-[calc(100vh-57px)] sticky top-[57px] hidden lg:flex flex-col p-6 bg-white shrink-0`}>
            <nav className="space-y-1 mb-8">
                {navItems.map((item) => {
                    const isActive = location.pathname === item.path;
                    return (
                        <Button
                            key={item.path}
                            variant="ghost"
                            className={`w-full justify-start gap-4 px-3 py-6 relative hover:bg-transparent ${isActive ? '' : 'hover:no-underline'}`}
                            asChild
                        >
                            <Link to={item.path}>
                                <div className={`flex items-center gap-4 transition-colors ${isActive ? 'text-black' : 'text-gray-500 hover:text-black'}`}>
                                    <item.icon
                                        className={`h-6 w-6 stroke-[1.5px] ${isActive ? 'fill-current stroke-inherit' : ''}`}
                                        strokeWidth={isActive ? 0 : 2}
                                        fill={isActive ? "currentColor" : "none"}
                                    />
                                    <span className={`text-[15px] ${isActive ? 'font-bold' : 'font-medium'}`}>{item.label}</span>
                                </div>
                            </Link>
                        </Button>
                    );
                })}
            </nav>
        </aside>
    );
};

export default SidebarLeft;
