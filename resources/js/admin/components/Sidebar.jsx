import React, { useContext, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import Logo from '../../web/components/Logo';
import {
    LayoutDashboard,
    Scale,
    Users,
    BookOpen,
    Feather,
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
    AlignJustify,
    Book,
    Plus,
    Info,
    MessageSquare,
    Globe,
    Music2,
    Mic2,
    Bell,
} from 'lucide-react';

export const SidebarContext = React.createContext({ onLinkClick: () => { } });

export const SidebarLink = ({ to, icon: Icon, children, disabled }) => {
    const location = useLocation();
    const { onLinkClick } = useContext(SidebarContext);
    const isActive = to === '/admin'
        ? location.pathname === '/admin'
        : location.pathname === to || location.pathname.startsWith(`${to}/`);

    if (disabled) {
        return (
            <div className="flex items-center gap-3 px-3 py-2 rounded-md opacity-50 cursor-not-allowed text-muted-foreground select-none">
                <Icon className="h-4 w-4" />
                <span>{children}</span>
            </div>
        );
    }

    return (
        <Link
            onClick={() => onLinkClick && onLinkClick()}
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

export const SidebarGroup = ({ icon: Icon, label, children, disabled, defaultOpen = false }) => {
    const location = useLocation();
    const childPaths = React.Children.toArray(children)
        .map((child) => child?.props?.to)
        .filter(Boolean);
    const childActive = childPaths.some(
        (path) => location.pathname === path || location.pathname.startsWith(`${path}/`)
    );
    const [isOpen, setIsOpen] = useState(defaultOpen || childActive);

    React.useEffect(() => {
        if (childActive) setIsOpen(true);
    }, [childActive]);

    return (
        <div className="flex flex-col gap-1">
            <button
                onClick={() => !disabled && setIsOpen(!isOpen)}
                className={`flex items-center justify-between px-3 py-2 rounded-md transition-colors w-full text-left ${disabled
                    ? 'opacity-50 cursor-not-allowed text-muted-foreground select-none'
                    : childActive
                        ? 'bg-muted text-foreground'
                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    }`}
                disabled={disabled}
            >
                <div className="flex items-center gap-3">
                    <Icon className="h-4 w-4" />
                    <span>{label}</span>
                </div>
                {!disabled && (isOpen ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />)}
            </button>
            {isOpen && !disabled && (
                <div className="pl-6 flex flex-col gap-1">
                    {children}
                </div>
            )}
        </div>
    );
};

const Sidebar = ({ onLinkClick }) => {
    const { isSuperAdmin } = useAuth();

    return (
        <SidebarContext.Provider value={{ onLinkClick }}>
            <div className="h-full min-h-0 flex flex-col gap-4 py-4 overflow-y-auto overscroll-contain">
                <a
                    href="/"
                    className="px-6 flex items-center min-w-0 no-underline hover:opacity-100 hover:bg-transparent focus:outline-none focus-visible:ring-0"
                    aria-label="Baakh home"
                    title="Baakh home"
                >
                    <Logo className="h-8 w-8 text-primary shrink-0" />
                </a>
                <nav className="flex-1 px-4 flex flex-col gap-1">
                    <SidebarLink to="/admin" icon={LayoutDashboard}>Dashboard</SidebarLink>
                    <SidebarLink to="/admin/mobile-notifications" icon={Bell}>App Notifications</SidebarLink>

                    <div className="my-2 border-t" />
                    <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">Content</div>

                    <SidebarLink to="/admin/poets" icon={Feather}>Poets</SidebarLink>
                    <SidebarLink to="/admin/books" icon={Book}>Poet Books</SidebarLink>
                    <SidebarLink to="/admin/hesudhar" icon={Type}>Hesudhar</SidebarLink>

                    <SidebarGroup icon={BookOpen} label="Poetry">
                        <SidebarLink to="/admin/poetry" icon={Book}>Main Poetry</SidebarLink>
                        <SidebarLink to="/admin/couplets" icon={AlignCenter}>Couplets</SidebarLink>
                    </SidebarGroup>

                    <SidebarGroup icon={Feather} label="Baakh Lughat">
                        <SidebarLink to="/admin/baakh-lughat" icon={Book}>Lughat Home</SidebarLink>
                        <SidebarLink to="/admin/baakh-lughat/lemma-inbox" icon={Layers}>Lughat Inbox</SidebarLink>
                        <SidebarLink to="/admin/baakh-lughat/sense-editor" icon={Feather}>Sense Editor</SidebarLink>
                        <SidebarLink to="/admin/baakh-lughat/morphology-lab" icon={Type}>Morphology Lab</SidebarLink>
                        <SidebarLink to="/admin/baakh-lughat/variants" icon={AlignJustify}>Variants</SidebarLink>
                        <SidebarLink to="/admin/baakh-lughat/qa-search" icon={Shield}>QA & Search</SidebarLink>
                    </SidebarGroup>

                    <SidebarLink to="/admin/romanizer" icon={Languages}>Romanizer</SidebarLink>

                    <SidebarGroup icon={Music2} label="Music">
                        <SidebarLink to="/admin/singers" icon={Mic2}>Artists</SidebarLink>
                        <SidebarLink to="/admin/bands" icon={Users}>Bands</SidebarLink>
                        <SidebarLink to="/admin/lyrics" icon={Music2}>Lyrics</SidebarLink>
                        <SidebarLink to="/admin/lyrics-genres" icon={Layers}>Genre</SidebarLink>
                    </SidebarGroup>

                    <SidebarGroup icon={Tags} label="Topics">
                        <SidebarLink to="/admin/topic-categories" icon={Layers}>Topic Categories</SidebarLink>
                        <SidebarLink to="/admin/tags" icon={Tags}>Tags</SidebarLink>
                    </SidebarGroup>

                    <SidebarLink to="/admin/categories" icon={AlignCenter}>Poetry Forms</SidebarLink>
                    <SidebarLink to="/admin/prosody" icon={Scale}>Prosody</SidebarLink>

                    <SidebarGroup icon={Book} label="Dictionary">
                        <SidebarLink to="/admin/dictionary" icon={Book}>Dictionary Home</SidebarLink>
                        <SidebarLink to="/admin/dictionary/lemma-inbox" icon={Layers}>Lemma Inbox</SidebarLink>
                        <SidebarLink to="/admin/dictionary/sense-editor" icon={Feather}>Sense Editor</SidebarLink>
                        <SidebarLink to="/admin/dictionary/morphology-lab" icon={Type}>Morphology Lab</SidebarLink>
                        <SidebarLink to="/admin/dictionary/variants" icon={AlignJustify}>Variants & Misspellings</SidebarLink>
                        <SidebarLink to="/admin/dictionary/qa-search" icon={Shield}>QA & Search</SidebarLink>
                    </SidebarGroup>

                    <div className="my-2 border-t" />
                    <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">Locations</div>
                    <SidebarGroup icon={MapPin} label="Locations">
                        <SidebarLink to="/admin/locations/countries" icon={Flag}>Countries</SidebarLink>
                        <SidebarLink to="/admin/locations/provinces" icon={Map}>Provinces</SidebarLink>
                        <SidebarLink to="/admin/locations/districts" icon={Map}>Districts</SidebarLink>
                        <SidebarLink to="/admin/locations/talukas" icon={MapPin}>Talukas</SidebarLink>
                        <SidebarLink to="/admin/locations/cities" icon={MapPin}>Cities</SidebarLink>
                    </SidebarGroup>

                    <div className="my-2 border-t" />
                    <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">Moderation</div>
                    <SidebarGroup icon={Shield} label="Moderation">
                        <SidebarLink to="/admin/moderation/reports" icon={Flag}>Reports</SidebarLink>
                        <SidebarLink to="/admin/moderation/feedback" icon={MessageSquare}>User Feedback</SidebarLink>
                    </SidebarGroup>

                    <div className="my-2 border-t" />
                    <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">System</div>

                    <SidebarLink to="/admin/system/info" icon={Info}>Information System</SidebarLink>
                    <SidebarLink to="/admin/teams" icon={Users}>Admins & Teams</SidebarLink>
                    {isSuperAdmin && (
                        <SidebarLink to="/admin/roles" icon={Shield}>Roles & Permissions</SidebarLink>
                    )}
                    <SidebarLink to="/admin/languages" icon={Languages}>Languages</SidebarLink>
                    <SidebarLink to="/admin/databases" icon={Database}>Databases</SidebarLink>
                </nav>
            </div>
        </SidebarContext.Provider>
    );
};

export default Sidebar;
