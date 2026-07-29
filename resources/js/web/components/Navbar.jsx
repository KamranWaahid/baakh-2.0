import React, { useEffect, useState, useRef, useLayoutEffect } from 'react';
import { Search, Menu, User as UserIcon, LogOut, Settings, Home, Feather, BookOpen, Scroll, Music, Tags, History, Scale, Shield } from 'lucide-react';
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
import { getImageUrl } from '../utils/url';
import NotificationBell from '../../admin/components/NotificationBell';

const Navbar = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { user, loading, logout } = useAuth();
    const { openMenu } = useMobileMenu();
    const [searchOpen, setSearchOpen] = useState(false);

    useEffect(() => {
        const handleKeyDown = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen((prev) => !prev);
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        const handleOpenSearch = () => setSearchOpen(true);
        document.addEventListener('open-search', handleOpenSearch);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
            document.removeEventListener('open-search', handleOpenSearch);
        };
    }, []);

    const NavItems = () => {
        const { lang } = useParams();
        const location = useLocation();
        const { user, logout } = useAuth();
        const navigate = useNavigate();

        const targetLang = lang === 'en' ? 'sd' : 'en';

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
                            <NotificationBell variant="web" />
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="relative h-8 w-8 rounded-full" aria-label="User account menu">
                                    <Avatar className="h-8 w-8 border border-gray-200">
                                        <AvatarImage src={getImageUrl(user.avatar, 'user')} alt={user.name} />
                                        <AvatarFallback className="text-xs font-semibold uppercase">
                                            {(user.name?.trim()?.charAt(0)?.toUpperCase()) || (
                                                <UserIcon className="h-4 w-4 text-muted-foreground" aria-hidden />
                                            )}
                                        </AvatarFallback>
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
                                {user?.roles?.some(role => ['super_admin', 'admin'].includes(role)) && (
                                    <DropdownMenuItem onClick={() => window.location.href = '/admin'}>
                                        <Shield className="mr-2 h-4 w-4 text-primary" />
                                        <span className="font-semibold">{isRtl ? 'ايڊمن پينل' : 'Admin Panel'}</span>
                                    </DropdownMenuItem>
                                )}
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

    const headerRef = useRef(null);

    useLayoutEffect(() => {
        const el = headerRef.current;
        if (!el) return undefined;

        const publishHeight = () => {
            document.documentElement.style.setProperty('--baakh-header-h', `${el.offsetHeight}px`);
        };

        publishHeight();
        const ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(publishHeight) : null;
        ro?.observe(el);
        window.addEventListener('resize', publishHeight);

        return () => {
            ro?.disconnect();
            window.removeEventListener('resize', publishHeight);
            document.documentElement.style.removeProperty('--baakh-header-h');
        };
    }, [lang, isRtl]);

    return (
        <>
            <SearchDialog open={searchOpen} onOpenChange={setSearchOpen} lang={lang} />
            <div
                ref={headerRef}
                className="fixed top-0 inset-x-0 z-[50] bg-white"
            >
                <nav className="h-[56px] lg:h-[65px] border-b border-gray-100 flex items-center justify-between px-4 md:px-8 bg-white shadow-sm">
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

                        <Link
                            to={`/${lang}`}
                            aria-label={lang === 'sd' ? "باک هوم" : "Baakh Home"}
                            className="flex items-center gap-2 hover:opacity-80 transition-opacity active:scale-95 duration-200"
                        >
                            <Logo className="h-7 w-7 md:h-8 md:w-8 text-black" />
                        </Link>

                        <div className="relative w-64 hidden md:block ml-4" role="search">
                            <Search className={`absolute z-10 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground ${isRtl ? 'right-3' : 'left-3'}`} />
                            <div
                                onClick={() => setSearchOpen(true)}
                                className={`flex h-10 w-full items-center rounded-full border border-gray-100 bg-gray-50/50 text-sm text-muted-foreground hover:bg-gray-100 hover:border-gray-200 cursor-pointer transition-all ${isRtl ? 'text-right pr-9 pl-12' : 'text-left pl-9 pr-12'}`}
                                dir={isRtl ? 'rtl' : 'ltr'}
                            >
                                <span className="truncate">{isRtl ? 'ڳوليو...' : 'Search'}</span>
                                <div className={`absolute top-1/2 -translate-y-1/2 hidden lg:flex items-center gap-1 text-[10px] uppercase font-medium text-gray-400 bg-white px-1.5 py-0.5 rounded border border-gray-100 shadow-sm ${isRtl ? 'left-3' : 'right-3'}`}>
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
            </div>
            {/* Reserve space so page content is not under the fixed header */}
            <div
                aria-hidden
                className="w-full shrink-0"
                style={{ height: 'var(--baakh-header-h, 97px)' }}
            />
        </>
    );
};

export default Navbar;
