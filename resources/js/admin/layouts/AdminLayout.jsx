import React, { useState, createContext, useContext } from 'react';
import { Link, useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import api from '../api/axios';
import {
    LayoutDashboard,
    LogOut,
    Menu,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTrigger } from '@/components/ui/sheet';
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
    const handleLogout = async () => {
        try {
            await api.post('/api/auth/logout');
            localStorage.removeItem('auth_token');
            window.location.href = '/'; // Redirect to public homepage
        } catch (error) {
            console.error('Logout failed', error);
        }
    };

    return (
        <div className="grid min-h-screen w-full md:grid-cols-[220px_1fr] lg:grid-cols-[280px_1fr]">
            <div className="hidden border-r bg-muted/40 md:block">
                <Sidebar />
            </div>
            <div className="flex flex-col">
                <header className="flex h-14 items-center gap-4 border-b bg-muted/40 px-4 lg:h-[60px] lg:px-6">
                    <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                className="shrink-0 md:hidden"
                            >
                                <Menu className="h-5 w-5" />
                                <span className="sr-only">Toggle navigation menu</span>
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="flex flex-col p-0">
                            <Sidebar onLinkClick={() => setSheetOpen(false)} />
                        </SheetContent>
                    </Sheet>

                    <div className="w-full flex-1">
                        {/* Search or Breadcrumbs could go here */}
                    </div>
                    <NotificationBell isAdmin={true} />
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="secondary" size="icon" className="rounded-full">
                                <Avatar>
                                    <AvatarImage src={user?.avatar ? (user.avatar.startsWith('http') ? user.avatar : `/${user.avatar}`) : ''} />
                                    <AvatarFallback>{user?.name ? user.name.substring(0, 2).toUpperCase() : 'AD'}</AvatarFallback>
                                </Avatar>
                                <span className="sr-only">Toggle user menu</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
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
                <main className="flex flex-1 flex-col gap-4 p-4 lg:gap-6 lg:p-6">
                    <div
                        key={location.key}
                        className="flex-1 flex flex-col gap-4 lg:gap-6 animate-in fade-in slide-in-from-bottom-1 duration-500 ease-out fill-mode-forward"
                    >
                        {children}
                    </div>
                </main>
            </div>
            <Toaster />
        </div>
    );
};

export default AdminLayout;
