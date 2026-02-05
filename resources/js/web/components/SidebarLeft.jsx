import React from 'react';
import { Home, Library, User, BarChart2, FileText, Plus } from 'lucide-react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';

const SidebarLeft = ({ lang }) => {
    const isRtl = lang === 'sd';
    const location = useLocation();
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        const timer = setTimeout(() => {
            setLoading(false);
        }, 2000);
        return () => clearTimeout(timer);
    }, []);

    const navItems = [
        { label: isRtl ? 'گھر' : 'Home', icon: Home, path: `/${lang}` },
        { label: isRtl ? 'لائبريري' : 'Library', icon: Library, path: `/${lang}/library` },
        { label: isRtl ? 'پروفائل' : 'Profile', icon: User, path: `/${lang}/profile` },
        { label: isRtl ? 'ڪهاڻيون' : 'Stories', icon: FileText, path: `/${lang}/stories` },
        { label: isRtl ? 'انگ اکر' : 'Stats', icon: BarChart2, path: `/${lang}/stats` },
    ];

    const following = [
        { name: 'Sheikh Ayaz', status: 'online', avatar: 'SA' },
        { name: 'Shah Latif', status: 'offline', avatar: 'SL' },
        { name: 'Amar Jaleel', status: 'online', avatar: 'AJ' },
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

            <div className="flex-1 overflow-auto">
                <h3 className="px-3 text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">
                    {isRtl ? 'پيروي' : 'Following'}
                </h3>
                <div className="space-y-4">
                    {loading ? (
                        Array(3).fill(0).map((_, i) => (
                            <div key={i} className="flex items-center gap-3 px-3">
                                <Skeleton className="h-8 w-8 rounded-full" />
                                <Skeleton className="h-4 w-24" />
                            </div>
                        ))
                    ) : (
                        <>
                            {following.map((user) => (
                                <div key={user.name} className="flex items-center justify-between px-3 group cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-all">
                                    <div className="flex items-center gap-3">
                                        <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-[10px] font-bold text-primary border border-primary/20">
                                            {user.avatar}
                                        </div>
                                        <span className="text-[14px] text-gray-700 group-hover:text-black">{user.name}</span>
                                    </div>
                                    <div className={`h-1.5 w-1.5 rounded-full ${user.status === 'online' ? 'bg-green-500' : 'bg-gray-300'}`} />
                                </div>
                            ))}

                            <Button variant="ghost" className="w-full justify-start gap-3 px-3 text-gray-400 hover:text-black">
                                <Plus className="h-5 w-5" />
                                <span>{isRtl ? 'وڌيڪ ڳوليو' : 'See suggestions'}</span>
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </aside>
    );
};

export default SidebarLeft;
