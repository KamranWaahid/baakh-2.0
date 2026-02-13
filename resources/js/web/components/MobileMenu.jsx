import React, { useState } from 'react';
import { Link, useLocation, useParams, useNavigate } from 'react-router-dom';
import { Search, X, Home, Feather, BookOpen, Scroll, Music, Tags, History, Scale, PenTool, LogOut, UserIcon, Settings, ChevronRight } from 'lucide-react';
import { useMobileMenu } from '../contexts/MobileMenuContext';
import { useAuth } from '../contexts/AuthContext';
import Logo from './Logo';
import LoginModal from './LoginModal';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Button } from '@/components/ui/button';

const MobileMenu = ({ lang }) => {
    const isRtl = lang === 'sd';
    const { isMenuOpen, closeMenu } = useMobileMenu();
    const { user, loading, logout } = useAuth();
    const location = useLocation();
    const navigate = useNavigate();
    const [loginModalOpen, setLoginModalOpen] = useState(false);
    const [loginMode, setLoginMode] = useState('initial');

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

    const targetLang = lang === 'en' ? 'sd' : 'en';
    const pathSegments = location.pathname.split('/').filter(Boolean);
    if (pathSegments.length > 0 && (pathSegments[0] === 'en' || pathSegments[0] === 'sd')) {
        pathSegments[0] = targetLang;
    }
    const langSwitchPath = '/' + pathSegments.join('/');

    const isActive = (path) => {
        if (path === `/${lang}`) return location.pathname === `/${lang}` || location.pathname === `/${lang}/`;
        return location.pathname.startsWith(path);
    };

    const handleNavClick = () => {
        closeMenu();
    };

    const handleLogout = async () => {
        await logout();
        closeMenu();
        navigate(`/${lang}`);
    };

    return (
        <>
            {/* Menu Panel */}
            <div
                className={`fixed top-0 ${isRtl ? 'right-0' : 'left-0'} h-full w-[280px] bg-white z-[200] flex flex-col transition-transform duration-300 ease-[cubic-bezier(0.25,0.1,0.25,1)] will-change-transform ${isMenuOpen
                    ? 'translate-x-0'
                    : isRtl
                        ? 'translate-x-full'
                        : '-translate-x-full'
                    }`}
                style={{ boxShadow: isMenuOpen ? '4px 0 24px rgba(0,0,0,0.08)' : 'none' }}
            >
                {/* Header */}
                <div className="flex items-center justify-between px-5 h-[56px] border-b border-gray-100 flex-shrink-0">
                    <Link to={`/${lang}`} className="flex items-center gap-2.5" onClick={handleNavClick}>
                        <Logo className="h-7 w-7 text-black" />
                        <span className="font-semibold text-lg tracking-tight">Baakh</span>
                    </Link>
                    <button
                        onClick={closeMenu}
                        className="h-9 w-9 flex items-center justify-center rounded-full hover:bg-gray-100 active:bg-gray-200 transition-colors"
                        aria-label="Close menu"
                    >
                        <X className="h-5 w-5 text-gray-500" />
                    </button>
                </div>

                {/* Scrollable Content */}
                <div className="flex-1 overflow-y-auto overscroll-contain">
                    {/* Search */}
                    <div className="px-4 pt-4 pb-2">
                        <button
                            onClick={() => {
                                closeMenu();
                                // Small delay so menu closes first
                                setTimeout(() => {
                                    document.dispatchEvent(new CustomEvent('open-search'));
                                }, 150);
                            }}
                            className={`flex items-center gap-3 w-full h-11 px-4 rounded-xl bg-gray-50 hover:bg-gray-100 active:bg-gray-200 transition-all text-sm text-gray-400 ${isRtl ? 'flex-row-reverse' : ''}`}
                        >
                            <Search className="h-4 w-4 text-gray-400 flex-shrink-0" />
                            <span>{isRtl ? 'ڳوليو...' : 'Search Baakh...'}</span>
                        </button>
                    </div>

                    {/* Navigation */}
                    <nav className="px-3 py-2">
                        <div className="space-y-0.5">
                            {navItems.map(item => {
                                const active = isActive(item.path);
                                return (
                                    <Link
                                        key={item.path}
                                        to={item.path}
                                        onClick={handleNavClick}
                                        className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium transition-all duration-150 active:scale-[0.98] ${active
                                            ? 'bg-black text-white'
                                            : 'text-gray-700 hover:bg-gray-50 active:bg-gray-100'
                                            } ${isRtl ? 'flex-row-reverse' : ''}`}
                                    >
                                        <item.icon className={`h-[18px] w-[18px] flex-shrink-0 ${active ? 'text-white' : 'text-gray-400'}`} />
                                        <span className="flex-1">{item.label}</span>
                                        {active && <ChevronRight className={`h-4 w-4 opacity-50 ${isRtl ? 'rotate-180' : ''}`} />}
                                    </Link>
                                );
                            })}
                        </div>
                    </nav>

                    {/* Divider */}
                    <div className="mx-4 my-2 h-px bg-gray-100" />

                    {/* Language Toggle */}
                    <div className="px-3 py-1">
                        <Link
                            to={langSwitchPath}
                            onClick={handleNavClick}
                            className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium text-gray-700 hover:bg-gray-50 active:bg-gray-100 transition-all active:scale-[0.98] ${isRtl ? 'flex-row-reverse' : ''}`}
                        >
                            <span className="h-[18px] w-[18px] flex items-center justify-center text-xs font-bold text-gray-400 flex-shrink-0">
                                {lang === 'en' ? 'سِ' : 'En'}
                            </span>
                            <span>{lang === 'en' ? <span className="font-arabic">سنڌي</span> : 'English'}</span>
                        </Link>
                    </div>

                    {/* Divider */}
                    <div className="mx-4 my-2 h-px bg-gray-100" />

                    {/* User Section */}
                    <div className="px-3 py-2 pb-6">
                        {loading ? (
                            <div className="flex items-center gap-3 px-3 py-3">
                                <div className="h-10 w-10 rounded-full bg-gray-100 animate-pulse" />
                                <div className="flex-1 space-y-2">
                                    <div className="h-3 w-24 bg-gray-100 rounded animate-pulse" />
                                    <div className="h-2.5 w-32 bg-gray-100 rounded animate-pulse" />
                                </div>
                            </div>
                        ) : user ? (
                            <div className="space-y-1">
                                {/* User Info */}
                                <div className={`flex items-center gap-3 px-3 py-3 ${isRtl ? 'flex-row-reverse' : ''}`}>
                                    <Avatar className="h-10 w-10 border-2 border-gray-100">
                                        <AvatarImage src={user.avatar && (user.avatar.startsWith('http') ? user.avatar : `/${user.avatar}`)} alt={user.name} />
                                        <AvatarFallback className="bg-black text-white font-semibold text-sm">
                                            {user.name?.charAt(0)?.toUpperCase()}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className={`flex-1 min-w-0 ${isRtl ? 'text-right' : ''}`}>
                                        <p className="text-sm font-semibold text-gray-900 truncate">{user.name}</p>
                                        <p className="text-xs text-gray-400 truncate">{user.email}</p>
                                    </div>
                                </div>

                                {/* User Actions */}
                                <Link
                                    to={`/${lang}/profile`}
                                    onClick={handleNavClick}
                                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium text-gray-700 hover:bg-gray-50 active:bg-gray-100 transition-all active:scale-[0.98] ${isRtl ? 'flex-row-reverse' : ''}`}
                                >
                                    <UserIcon className="h-[18px] w-[18px] text-gray-400 flex-shrink-0" />
                                    <span>{isRtl ? 'پروفائل' : 'Profile'}</span>
                                </Link>
                                <Link
                                    to={`/${lang}/settings`}
                                    onClick={handleNavClick}
                                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium text-gray-700 hover:bg-gray-50 active:bg-gray-100 transition-all active:scale-[0.98] ${isRtl ? 'flex-row-reverse' : ''}`}
                                >
                                    <Settings className="h-[18px] w-[18px] text-gray-400 flex-shrink-0" />
                                    <span>{isRtl ? 'سيٽنگون' : 'Settings'}</span>
                                </Link>

                                <div className="mx-0 my-1.5 h-px bg-gray-100" />

                                <button
                                    onClick={handleLogout}
                                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[15px] font-medium text-red-500 hover:bg-red-50 active:bg-red-100 transition-all w-full active:scale-[0.98] ${isRtl ? 'flex-row-reverse' : ''}`}
                                >
                                    <LogOut className="h-[18px] w-[18px] flex-shrink-0" />
                                    <span>{isRtl ? 'لاگ آئوٽ' : 'Sign Out'}</span>
                                </button>
                            </div>
                        ) : (
                            <div className="space-y-2 px-1">
                                <p className={`text-xs text-gray-400 px-2 mb-3 ${isRtl ? 'text-right' : ''}`}>
                                    {isRtl ? 'پنھنجي اڪائونٽ ۾ سائن ان ڪريو' : 'Sign in to your account'}
                                </p>
                                <LoginModal
                                    open={loginModalOpen}
                                    onOpenChange={(open) => {
                                        setLoginModalOpen(open);
                                        if (open) closeMenu();
                                    }}
                                    trigger={
                                        <Button
                                            className="w-full rounded-xl h-11 bg-black text-white hover:bg-gray-800 font-medium text-[15px] active:scale-[0.98] transition-all"
                                            onClick={() => {
                                                closeMenu();
                                                setTimeout(() => setLoginModalOpen(true), 200);
                                            }}
                                        >
                                            {isRtl ? 'سائن ان / رجسٽر' : 'Sign In / Register'}
                                        </Button>
                                    }
                                    isRtl={isRtl}
                                />
                            </div>
                        )}
                    </div>
                </div>

                {/* Footer */}
                <div className="flex-shrink-0 border-t border-gray-100 px-5 py-3">
                    <div className="flex items-center justify-center gap-4 text-xs text-gray-500 mb-2 flex-wrap">
                        <Link to={`/${lang}/help`} className="hover:text-black transition-colors">{isRtl ? 'مدد' : 'Help'}</Link>
                        <Link to={`/${lang}/status`} className="hover:text-black transition-colors">{isRtl ? 'حالت' : 'Status'}</Link>
                        <Link to={`/${lang}/about`} className="hover:text-black transition-colors">{isRtl ? 'بابت' : 'About'}</Link>
                        <Link to={`/${lang}/privacy`} className="hover:text-black transition-colors">{isRtl ? 'رازداري' : 'Privacy'}</Link>
                        <Link to={`/${lang}/terms`} className="hover:text-black transition-colors">{isRtl ? 'شرطون' : 'Terms'}</Link>
                    </div>
                    <p className="text-[11px] text-gray-300 text-center">
                        © {new Date().getFullYear()} Baakh
                    </p>
                </div>
            </div>

            {/* Tap-to-close overlay (only when menu is open) */}
            {isMenuOpen && (
                <div
                    className="fixed inset-0 z-[199] bg-black/20 backdrop-blur-[1px] transition-opacity duration-300"
                    onClick={closeMenu}
                    aria-hidden="true"
                />
            )}
        </>
    );
};

export default MobileMenu;
