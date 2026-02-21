import React, { useContext, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import Logo from '../../web/components/Logo';
import {
    LayoutDashboard,
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
    Terminal,
    Bug,
    MessageSquare,
    Zap,
    Globe
} from 'lucide-react';

export const SidebarContext = React.createContext({ onLinkClick: () => { } });

export const SidebarLink = ({ to, icon: Icon, children, disabled }) => {
    const location = useLocation();
    const { onLinkClick } = useContext(SidebarContext);
    const isActive = location.pathname === to;

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

export const SidebarGroup = ({ icon: Icon, label, children, disabled }) => {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <div className="flex flex-col gap-1">
            <button
                onClick={() => !disabled && setIsOpen(!isOpen)}
                className={`flex items-center justify-between px-3 py-2 rounded-md transition-colors w-full text-left ${disabled
                    ? 'opacity-50 cursor-not-allowed text-muted-foreground select-none'
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
            <div className="h-full flex flex-col gap-4 py-4 overflow-y-auto">
                <Link to="/" className="px-6 flex items-center gap-2 group">
                    <Logo className="h-8 w-8 text-primary group-hover:scale-110 transition-transform" />
                    <span className="font-bold text-xl">Baakh Admin</span>
                </Link>
                <nav className="flex-1 px-4 flex flex-col gap-1">
                    <SidebarLink to="/admin" icon={LayoutDashboard}>Dashboard</SidebarLink>

                    <div className="my-2 border-t" />
                    <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">Content</div>

                    <SidebarLink to="/admin/poets" icon={Feather}>Poets</SidebarLink>
                    <SidebarLink to="/admin/books" icon={Book}>Poet Books</SidebarLink>

                    <SidebarGroup icon={BookOpen} label="Poetry">
                        <SidebarLink to="/admin/poetry" icon={Book}>Main Poetry</SidebarLink>
                        <SidebarLink to="/admin/couplets" icon={AlignCenter}>Couplets</SidebarLink>
                    </SidebarGroup>

                    <SidebarGroup icon={Tags} label="Topics">
                        <SidebarLink to="/admin/topic-categories" icon={Layers}>Topic Categories</SidebarLink>
                        <SidebarLink to="/admin/tags" icon={Tags}>Tags</SidebarLink>
                    </SidebarGroup>

                    <SidebarLink to="/admin/categories" icon={AlignCenter}>Poetry Forms</SidebarLink>
                    <SidebarLink to="/admin/hesudhar" icon={Type}>Hesudhar</SidebarLink>
                    <SidebarLink to="/admin/romanizer" icon={Languages}>Romanizer</SidebarLink>

                    <SidebarGroup icon={Book} label="Dictionary">
                        <SidebarLink to="/admin/dictionary" icon={Book}>Dictionary Home</SidebarLink>
                        <SidebarLink to="/admin/dictionary/sindhila-scraper" icon={Globe}>Sindhila Scraper</SidebarLink>
                        <SidebarLink to="/admin/dictionary/lemma-inbox" icon={Layers}>Lemma Inbox</SidebarLink>
                        <SidebarLink to="/admin/dictionary/sense-editor" icon={Feather}>Sense Editor</SidebarLink>
                        <SidebarLink to="/admin/dictionary/morphology-lab" icon={Type}>Morphology Lab</SidebarLink>
                        <SidebarLink to="/admin/dictionary/variants" icon={AlignJustify}>Variants & Misspellings</SidebarLink>
                        <SidebarLink to="/admin/dictionary/qa-search" icon={Shield}>QA & Search</SidebarLink>
                    </SidebarGroup>

                    <SidebarGroup icon={Database} label="Corpus">
                        <SidebarLink to="/admin/corpus/sentence-explorer" icon={BookOpen}>Sentence Explorer</SidebarLink>
                        <SidebarLink to="/admin/corpus/context-clusters" icon={AlignCenter}>Context Clusters</SidebarLink>
                    </SidebarGroup>

                    <SidebarGroup icon={LayoutDashboard} label="Analytics">
                        <SidebarLink to="/admin/analytics/frequency" icon={Layers}>Frequency Stats</SidebarLink>
                        <SidebarLink to="/admin/analytics/dialect" icon={Map}>Dialect Coverage</SidebarLink>
                        <SidebarLink to="/admin/analytics/trends" icon={Type}>Usage Trends</SidebarLink>
                    </SidebarGroup>

                    <div className="my-2 border-t" />
                    <div className="px-3 text-xs font-semibold text-muted-foreground mb-2 mt-2">Locations</div>
                    <SidebarGroup icon={MapPin} label="Locations">
                        <SidebarLink to="/admin/locations/countries" icon={Flag}>Countries</SidebarLink>
                        <SidebarLink to="/admin/locations/provinces" icon={Map}>Provinces</SidebarLink>
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
                    <SidebarLink to="/admin/system/server" icon={Terminal}>Server Management</SidebarLink>
                    <SidebarLink to="/admin/system/errors" icon={Bug}>Error Management</SidebarLink>
                    <SidebarLink to="/admin/system/activity-logs" icon={AlignJustify}>Activity Logs</SidebarLink>
                    <SidebarLink to="/admin/system/performance" icon={Zap}>Heap Analysis</SidebarLink>
                    <SidebarLink to="/admin/mokhii" icon={Bot}>Mokhii GEO</SidebarLink>
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
