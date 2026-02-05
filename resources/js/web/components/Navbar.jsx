import React, { useEffect, useState } from 'react';
import { Search, Bell, Menu, User as UserIcon, LogOut, Settings, PenTool, Home, Feather, BookOpen, Scroll, Music, Tags, History, Scale, Plus } from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import Logo from './Logo';
import api from '../../admin/api/axios';
import LoginModal from './LoginModal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

// ... 

// ...
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui/sheet";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";

import SearchDialog from './SearchDialog';

import { useAuth } from '../contexts/AuthContext';

const Navbar = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { user, loading, logout } = useAuth();
    const [searchOpen, setSearchOpen] = useState(false);

    const navItems = [
        // ... items ...
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
        // Keyboard Shortcut for Search (Cmd+K / Ctrl+K)
        const handleKeyDown = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen((prev) => !prev);
            }
        };

        document.addEventListener('keydown', handleKeyDown);
        return () => document.removeEventListener('keydown', handleKeyDown);
    }, []);

    const location = useLocation();

    const NavItems = ({ mobile = false }) => {
        const targetLang = lang === 'en' ? 'sd' : 'en';
        // Ensure we only replace the first occurrence (the prefix)
        const newPath = location.pathname.replace(`/${lang}`, `/${targetLang}`);

        return (
            <>
                <Link
                    to={newPath}
                    className={`text-sm font-normal hover:bg-gray-100 px-3 py-2 rounded-md transition-colors flex items-center gap-2 ${mobile ? 'w-full' : ''}`}
                >
                    {lang === 'en' ? <span className="font-arabic text-base pb-1">سنڌي</span> : 'English'}
                </Link>
                {!mobile && (
                    <div className="h-6 w-px bg-gray-200 mx-2"></div>
                )}
                {loading ? (
                    <Skeleton className="h-8 w-8 rounded-full" />
                ) : user ? (
                    <div className={`flex items-center gap-2 ${mobile ? 'flex-col items-start w-full' : ''}`}>
                        {!mobile && (
                            <>
                                <Button variant="ghost" size="sm" className="hidden md:flex gap-2 text-gray-600">
                                    <PenTool className="h-4 w-4" />
                                    <span>{isRtl ? 'لکيو' : 'Write'}</span>
                                </Button>
                                <Button variant="ghost" size="icon" className="text-gray-500 relative">
                                    <Bell className="h-5 w-5" />
                                    <span className="absolute top-2 right-2 h-2 w-2 bg-black rounded-full border-2 border-white"></span>
                                </Button>
                            </>
                        )}

                        {mobile ? (
                            <>
                                <Link to="/write" className="flex items-center gap-2 px-3 py-2 w-full hover:bg-gray-100 rounded-md">
                                    <PenTool className="h-4 w-4" />
                                    <span>{isRtl ? 'لکيو' : 'Write'}</span>
                                </Link>
                                <Separator className="my-2" />
                                <div className="flex items-center gap-3 px-3 py-2">
                                    <Avatar className="h-8 w-8">
                                        <AvatarImage src={user.avatar} alt={user.name} />
                                        <AvatarFallback>{user.name?.charAt(0)}</AvatarFallback>
                                    </Avatar>
                                    <div className="flex flex-col">
                                        <span className="text-sm font-medium">{user.name}</span>
                                        <span className="text-xs text-muted-foreground">{user.email}</span>
                                    </div>
                                </div>
                                <Button variant="ghost" className="w-full justify-start text-black hover:text-black/80 hover:bg-gray-100" onClick={logout}>
                                    <LogOut className="mr-2 h-4 w-4" />
                                    {isRtl ? 'لاگ آئوٽ' : 'Logout'}
                                </Button>
                            </>
                        ) : (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="relative h-8 w-8 rounded-full">
                                        <Avatar className="h-8 w-8 border border-gray-200">
                                            <AvatarImage src={user.avatar} alt={user.name} />
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
                                    <DropdownMenuItem>
                                        <UserIcon className="mr-2 h-4 w-4" />
                                        <span>{isRtl ? 'پروفائل' : 'Profile'}</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        <Settings className="mr-2 h-4 w-4" />
                                        <span>{isRtl ? 'سيٽنگون' : 'Settings'}</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem className="focus:bg-gray-100" onClick={logout}>
                                        <LogOut className="mr-2 h-4 w-4" />
                                        <span>{isRtl ? 'لاگ آئوٽ' : 'Logout'}</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                    </div>
                ) : (
                    <div className={`flex items-center gap-2 ${mobile ? 'flex-col w-full' : ''}`}>
                        <LoginModal
                            trigger={
                                <Button variant="ghost" className={mobile ? 'w-full justify-start' : 'hover:bg-transparent hover:text-black/70'}>
                                    {isRtl ? 'لاگ ان' : 'Sign in'}
                                </Button>
                            }
                            isRtl={isRtl}
                        />

                        <LoginModal
                            trigger={
                                <Button className={`bg-black text-white hover:bg-gray-800 rounded-full ${mobile ? 'w-full' : ''}`}>
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

    return (
        <>
            <SearchDialog open={searchOpen} onOpenChange={setSearchOpen} lang={lang} />
            <nav className="h-[65px] border-b border-gray-100 flex items-center justify-between px-4 md:px-8 sticky top-0 bg-white/80 backdrop-blur-md z-[50]">
                <div className="flex items-center gap-4 flex-1">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="lg:hidden text-gray-500">
                                <Menu className="h-6 w-6" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side={isRtl ? "right" : "left"} className="w-[300px] sm:w-[400px]">
                            <SheetHeader>
                                <SheetTitle className="flex items-center gap-2">
                                    <Logo className="h-8 w-8 text-black" />
                                    <span className="font-medium text-xl">Baakh</span>
                                </SheetTitle>
                            </SheetHeader>
                            <div className="mt-8 flex flex-col gap-4">
                                <div className="relative w-full" onClick={() => setSearchOpen(true)}>
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <div className={`flex items-center h-10 w-full rounded-full border border-transparent bg-gray-50 px-3 pl-9 text-sm text-gray-500 hover:bg-gray-100 cursor-pointer transition-colors`}>
                                        {isRtl ? 'ڳوليو...' : 'Search...'}
                                    </div>
                                </div>
                                <Separator />
                                <div className="flex flex-col gap-1">
                                    {navItems.map(item => (
                                        <Button key={item.path} variant="ghost" asChild className="justify-start gap-3 px-3 font-normal">
                                            <Link to={item.path}>
                                                <item.icon className="h-5 w-5 text-gray-500" />
                                                <span className="text-base">{item.label}</span>
                                            </Link>
                                        </Button>
                                    ))}
                                </div>
                                <Separator />
                                <NavItems mobile />
                            </div>
                        </SheetContent>
                    </Sheet>

                    <Link to={`/${lang}`} className="flex items-center gap-2 hover:opacity-80 transition-opacity">
                        <Logo className="h-8 w-8 text-black" />
                    </Link>

                    <div className="relative w-64 hidden md:block ml-4" onClick={() => setSearchOpen(true)}>
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground z-10" />
                        <div
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
                    <Button variant="ghost" size="icon" className="text-gray-500" onClick={() => setSearchOpen(true)}>
                        <Search className="h-5 w-5" />
                    </Button>
                </div>
            </nav>
        </>
    );
};

export default Navbar;
