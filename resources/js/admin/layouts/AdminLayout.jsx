import React, { useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
    LayoutDashboard,
    Users,
    BookOpen,
    Feather,
    Menu,
    LogOut,
    Home,
    Tags,
    Layers,
    Type,
    Languages,
    MapPin,
    Shield,
    Database,
    ChevronDown,
    ChevronRight,
    Flag,
    Map,
    AlignCenter,
    Book,
    Plus
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
import api from '../api/axios';

const SidebarLink = ({ to, icon: Icon, children }) => {
    const location = useLocation();
    const isActive = location.pathname === to;

    return (
        <Link
            to={to}
            className={`flex items-center gap-3 px-3 py-2 rounded-md transition-colors ${isActive
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                }`}
        >
            <Icon className="h-4 w-4" />
            <span>{children}</span>
        </Link>
    );
};

const SidebarGroup = ({ icon: Icon, label, children }) => {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <div className="flex flex-col gap-1">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex items-center justify-between px-3 py-2 rounded-md text-muted-foreground hover:bg-muted hover:text-foreground transition-colors w-full text-left"
            >
                <div className="flex items-center gap-3">
                    <Icon className="h-4 w-4" />
                    <span>{label}</span>
                </div>
                {isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
            </button>
            {isOpen && (
                <div className="pl-6 flex flex-col gap-1">
                    {children}
                </div>
            )}
        </div>
    );
};

const Sidebar = () => {
    return (
        <div className="h-full flex flex-col gap-4 py-4 overflow-y-auto">
            <div className="px-6 flex items-center gap-2">
                <span className="font-bold text-xl">Baakh Admin</span>
            </div>
            <nav className="flex-1 px-4 flex flex-col gap-1">
                <SidebarLink to="/admin" icon={LayoutDashboard}>Dashboard</SidebarLink>

                <div className="my-2 border-t" />
                <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">Content</div>

                <SidebarLink to="/admin/poets" icon={Feather}>Poets</SidebarLink>

                <SidebarGroup icon={BookOpen} label="Poetry">
                    <SidebarLink to="/admin/poetry" icon={Book}>Main Poetry</SidebarLink>
                    <SidebarLink to="/admin/poetry/create" icon={Plus}>Add Poetry</SidebarLink>
                    <SidebarLink to="/admin/couplet/create" icon={Plus}>Add Couplet</SidebarLink>
                    <SidebarLink to="/admin/couplets" icon={AlignCenter}>Couplets</SidebarLink>
                </SidebarGroup>

                <SidebarLink to="/admin/tags" icon={Tags}>Tags</SidebarLink>
                <SidebarLink to="/admin/categories" icon={Layers}>Categories</SidebarLink>
                <SidebarLink to="/admin/hesudhar" icon={Type}>Hesudhar</SidebarLink>
                <SidebarLink to="/admin/romanizer" icon={Languages}>Romanizer</SidebarLink>

                <SidebarGroup icon={MapPin} label="Locations">
                    <SidebarLink to="/admin/locations/countries" icon={Flag}>Countries</SidebarLink>
                    <SidebarLink to="/admin/locations/cities" icon={Map}>Provinces/Cities</SidebarLink>
                </SidebarGroup>

                <div className="my-2 border-t" />
                <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">System</div>

                <SidebarLink to="/admin/teams" icon={Users}>Admins & Teams</SidebarLink>
                <SidebarLink to="/admin/roles" icon={Shield}>Roles & Permissions</SidebarLink>
                <SidebarLink to="/admin/languages" icon={Languages}>Languages</SidebarLink>
                <SidebarLink to="/admin/databases" icon={Database}>Databases</SidebarLink>
            </nav>
        </div>
    );
};

const AdminLayout = ({ children }) => {
    const location = useLocation();
    const handleLogout = async () => {
        try {
            await api.post('/api/auth/logout');
            localStorage.removeItem('auth_token');
            window.location.href = '/admin/login'; // Redirect to login
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
                    <Sheet>
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
                            <Sidebar />
                        </SheetContent>
                    </Sheet>

                    <div className="w-full flex-1">
                        {/* Search or Breadcrumbs could go here */}
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="secondary" size="icon" className="rounded-full">
                                <Avatar>
                                    <AvatarImage src="" /> {/* Add user avatar url here */}
                                    <AvatarFallback>AD</AvatarFallback>
                                </Avatar>
                                <span className="sr-only">Toggle user menu</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>My Account</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem>Settings</DropdownMenuItem>
                            <DropdownMenuItem>Support</DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem onClick={handleLogout}>
                                <LogOut className="mr-2 h-4 w-4" />
                                <span>Logout</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </header>
                <main className="flex flex-1 flex-col gap-4 p-4 lg:gap-6 lg:p-6 overflow-hidden">
                    <div
                        key={location.key}
                        className="flex-1 flex flex-col gap-4 lg:gap-6 animate-in fade-in slide-in-from-bottom-1 duration-500 ease-out fill-mode-forward"
                    >
                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
};

export default AdminLayout;
