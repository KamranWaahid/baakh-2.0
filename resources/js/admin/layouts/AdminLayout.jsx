import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import api from '../api/axios';
import {
    LogOut,
    Menu,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger, SheetTitle } from '@/components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Toaster } from 'sonner';
import NotificationBell from '../components/NotificationBell';
import Sidebar from '../components/Sidebar';

const AdminLayout = ({ children }) => {
    const location = useLocation();
    const { user } = useAuth();
    const [sheetOpen, setSheetOpen] = useState(false);

    // Close mobile nav after route changes (back/forward + link clicks).
    useEffect(() => {
        setSheetOpen(false);
    }, [location.pathname]);

    // Ensure body scroll is restored if an overlay left pointer-events stuck.
    useEffect(() => {
        if (sheetOpen) return undefined;
        const unlock = () => {
            const openOverlay = document.querySelector(
                '[data-state="open"][role="dialog"], [data-state="open"].fixed.inset-0'
            );
            if (openOverlay) return;
            document.body.style.removeProperty('pointer-events');
            if (document.body.style.overflow === 'hidden') {
                document.body.style.removeProperty('overflow');
            }
            document.documentElement.style.removeProperty('overflow');
        };
        const t = window.setTimeout(unlock, 320);
        return () => window.clearTimeout(t);
    }, [sheetOpen]);

    const handleLogout = async () => {
        try {
            await api.post('/api/auth/logout');
            localStorage.removeItem('auth_token');
            window.location.href = '/';
        } catch (error) {
            console.error('Logout failed', error);
        }
    };

    return (
        <div className="admin-shell flex min-h-[100dvh] w-full">
            <aside className="hidden md:fixed md:inset-y-0 md:z-30 md:flex md:w-[220px] lg:w-[280px] md:flex-col border-r bg-muted/40 overflow-y-auto overscroll-contain">
                <Sidebar />
            </aside>
            <div className="flex min-w-0 flex-1 flex-col max-w-full md:pl-[220px] lg:pl-[280px]">
                <header className="sticky top-0 z-40 flex h-14 items-center gap-2 sm:gap-4 border-b bg-background px-3 sm:px-4 lg:h-[60px] lg:px-6">
                    <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                className="shrink-0 md:hidden"
                                aria-label="Open navigation menu"
                            >
                                <Menu className="h-5 w-5" />
                                <span className="sr-only">Toggle navigation menu</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            className="flex flex-col p-0 w-[min(100vw-2.5rem,20rem)] max-w-[20rem] sm:max-w-sm overflow-y-auto overscroll-contain"
                        >
                            <SheetTitle className="sr-only">Admin navigation</SheetTitle>
                            <Sidebar onLinkClick={() => setSheetOpen(false)} />
                        </SheetContent>
                    </Sheet>

                    <div className="min-w-0 flex-1 truncate text-sm font-medium text-muted-foreground md:hidden">
                        Baakh Admin
                    </div>
                    <div className="hidden md:block w-full flex-1" />
                    <NotificationBell isAdmin={true} />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="secondary" size="icon" className="rounded-full shrink-0">
                                <Avatar>
                                    <AvatarImage src={user?.avatar ? (user.avatar.startsWith('http') ? user.avatar : `/${user.avatar}`) : ''} />
                                    <AvatarFallback>{user?.name ? user.name.substring(0, 2).toUpperCase() : 'AD'}</AvatarFallback>
                                </Avatar>
                                <span className="sr-only">Toggle user menu</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-56">
                            <DropdownMenuLabel>My Account</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={() => window.location.href = '/admin/settings'}>
                                Settings
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={() => window.location.href = '/admin/moderation/feedback'}>
                                Support & Feedback
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={handleLogout}>
                                <LogOut className="mr-2 h-4 w-4" />
                                <span>Logout</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </header>
                <main className="flex min-w-0 flex-1 flex-col gap-4 overflow-x-clip p-3 sm:p-4 lg:gap-6 lg:p-6">
                    <div
                        key={location.key}
                        className="admin-page flex min-w-0 w-full max-w-full flex-1 flex-col gap-4 lg:gap-6 animate-in fade-in slide-in-from-bottom-1 duration-500 ease-out fill-mode-forward"
                    >
                        {children}
                    </div>
                </main>
            </div>
            <Toaster position="top-center" richColors closeButton />
        </div>
    );
};

export default AdminLayout;
