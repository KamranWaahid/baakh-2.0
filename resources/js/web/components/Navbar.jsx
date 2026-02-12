import React, { useEffect, useState } from 'react';
import { Search, Bell, Menu, User as UserIcon, LogOut, Settings, Home, Feather, BookOpen, Scroll, Music, Tags, History, Scale } from 'lucide-react';
import { useScrollDirection } from '../hooks/useScrollDirection';
import { Link, useLocation, useParams, useNavigate } from 'react-router-dom';
import Logo from './Logo';
import LoginModal from './LoginModal';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Skeleton } from "@/components/ui/skeleton";
import { useMobileMenu } from '../contexts/MobileMenuContext';
import SearchDialog from './SearchDialog';
import { useAuth } from '../contexts/AuthContext';

const Navbar = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { user, loading, logout } = useAuth();
    const { openMenu } = useMobileMenu();
    const [searchOpen, setSearchOpen] = useState(false);
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const checkMobile = () => {
            setIsMobile(window.innerWidth < 1024);
        };
        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

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

    useEffect(() => {
        const handleKeyDown = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen((prev) => !prev);
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        // Listen for open-search event from MobileMenu
        const handleOpenSearch = () => setSearchOpen(true);
        document.addEventListener('open-search', handleOpenSearch);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.removeEventListener('open-search', handleOpenSearch);
        };
    }, []);

    const location = useLocation();

    const NavItems = () => {
        const { lang } = useParams();
        const location = useLocation();
        const { user, logout } = useAuth();
        const navigate = useNavigate();

        const targetLang = lang === 'en' ? 'sd' : 'en';

        // Safer path replacement using URL segments
        const pathSegments = location.pathname.split('/').filter(Boolean);
        if (pathSegments.length > 0 && (pathSegments[0] === 'en' || pathSegments[0] === 'sd')) {
            pathSegments[0] = targetLang;
        }
        const newPath = '/' + pathSegments.join('/');

        return (
            <>
                <Link
                    to={newPath}
                    className="text-sm font-normal hover:bg-gray-100 px-3 py-2 rounded-md transition-colors flex items-center gap-2"
                    aria-label={lang === 'en' ? 'Switch to Sindhi' : 'Switch to English'}
                >
                    {lang === 'en' ? <span className="font-arabic text-base pb-1">سنڌي</span> : 'English'}
                </Link>
                <div className="h-6 w-px bg-gray-200 mx-2"></div>

                {loading ? (
                    <Skeleton className="h-8 w-8 rounded-full" />
                ) : user ? (
                    <div className="flex items-center gap-2">
                        <div className="flex items-center gap-2">

                            <Button
                                variant="ghost"
                                size="icon"
                                className="rounded-full hover:bg-gray-100 h-9 w-9 relative"
                                aria-label="Notifications"
                            >
                                <Bell className="h-4 w-4 text-gray-600" />
                                <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
                            </Button>
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="relative h-8 w-8 rounded-full" aria-label="User account menu">
                                    <Avatar className="h-8 w-8 border border-gray-200">
                                        <AvatarImage src={user.avatar && (user.avatar.startsWith('http') ? user.avatar : `/${user.avatar}`)} alt={user.name} />
                                        <AvatarFallback>{user.name?.charAt(0)}</AvatarFallback>
                                    </Avatar>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent className="w-56" align="end" forceMount>
                                <DropdownMenuLabel className="font-normal">
                                    <div className="flex flex-col space-y-1">
                                        <p className="text-sm font-medium leading-none">{user.name}</p>
                                        <p className="text-xs leading-none text-muted-foreground">
                                            {user.email}
                                        </p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => navigate(`/${lang}/profile`)}>
                                    <UserIcon className="mr-2 h-4 w-4" />
                                    <span>{isRtl ? 'پروفائل' : 'Profile'}</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => navigate(`/${lang}/settings`)}>
                                    <Settings className="mr-2 h-4 w-4" />
                                    <span>{isRtl ? 'سيٽنگون' : 'Settings'}</span>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="focus:bg-gray-100" onClick={async () => { await logout(); navigate(`/${lang}`); }}>
                                    <LogOut className="mr-2 h-4 w-4" />
                                    <span>{isRtl ? 'لاگ آئوٽ' : 'Logout'}</span>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                ) : (
                    <div className="flex items-center gap-2">
                        <LoginModal
                            trigger={
                                <Button variant="ghost" className="hover:bg-transparent hover:text-black/70">
                                    {isRtl ? 'لاگ ان' : 'Sign in'}
                                </Button>
                            }
                            isRtl={isRtl}
                        />

                        <LoginModal
                            trigger={
                                <Button className="bg-black text-white hover:bg-gray-800 rounded-full">
                                    {isRtl ? 'شروعات ڪريو' : 'Get started'}
                                </Button>
                            }
                            isRtl={isRtl}
                        />
                    </div>
                )}
            </>
        );
    };

    const scrollDirection = useScrollDirection();
    const isHidden = scrollDirection === 'down' && isMobile;

    return (
        <>
            <SearchDialog open={searchOpen} onOpenChange={setSearchOpen} lang={lang} />
            <nav className={`h-[56px] lg:h-[65px] border-b border-gray-100 flex items-center justify-between px-4 md:px-8 sticky top-0 bg-white z-[50] transition-all duration-300 ${isHidden ? 'translate-y-[-110%] opacity-0' : 'translate-y-0 opacity-100 shadow-sm'}`}>
                <div className="flex items-center gap-4 flex-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="lg:hidden text-gray-500 h-10 w-10 active:bg-gray-100 rounded-full transition-colors"
                        onClick={openMenu}
                        aria-label="Open menu"
                    >
                        <Menu className="h-5 w-5 md:h-6 md:w-6" />
                    </Button>

                    <Link to={`/${lang}`} className="flex items-center gap-2 hover:opacity-80 transition-opacity active:scale-95 duration-200">
                        <Logo className="h-7 w-7 md:h-8 md:w-8 text-black" />
                    </Link>

                    <div className="relative w-64 hidden md:block ml-4">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground z-10" />
                        <div
                            onClick={() => setSearchOpen(true)}
                            className={`flex h-10 w-full items-center rounded-full border border-gray-100 bg-gray-50/50 pl-9 pr-4 text-sm text-muted-foreground hover:bg-gray-100 hover:border-gray-200 cursor-pointer transition-all ${isRtl ? 'text-right pr-9 pl-4' : ''}`}
                            dir={isRtl ? 'rtl' : 'ltr'}
                        >
                            <span>{isRtl ? 'ڳوليو...' : 'Search'}</span>
                            <div className="absolute right-3 top-1/2 -translate-y-1/2 hidden lg:flex items-center gap-1 text-[10px] uppercase font-medium text-gray-400 bg-white px-1.5 py-0.5 rounded border border-gray-100 shadow-sm">
                                <span className="text-xs">⌘</span> K
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-2 md:gap-4 hidden lg:flex">
                    <NavItems />
                </div>
                <div className="flex items-center lg:hidden">
                    <Button
                        variant="ghost"
                        size="icon"
                        className="text-gray-500 h-10 w-10 active:bg-gray-100 rounded-full transition-colors md:hidden"
                        onClick={() => setSearchOpen(true)}
                        aria-label="Search"
                    >
                        <Search className="h-5 w-5" />
                    </Button>
                </div>
            </nav>
        </>
    );
};

export default Navbar;
