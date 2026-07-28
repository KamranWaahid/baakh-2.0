import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useLocation } from 'react-router-dom';
import api from '../api/axios';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Bell,
    BookOpen,
    Feather,
    Trash2,
    UserPlus,
    LogIn,
    Shield,
    Bug,
    ShieldCheck,
    CheckCircle,
    Globe,
    Layers,
    Tags,
    Bot,
    Rocket,
    MessageSquare,
    Check,
    BellOff,
} from 'lucide-react';

const ICONS = {
    BookOpen, Feather, Trash2, UserPlus, LogIn, Shield, Bug,
    ShieldCheck, CheckCircle, Globe, Layers, Tags, Bot, Rocket,
    MessageSquare, Bell,
};

const looksLikeSlug = (value) => {
    if (!value || typeof value !== 'string') return false;
    const text = value.trim();
    return /^[a-z0-9]+(?:-[a-z0-9]+)+$/i.test(text);
};

const humanizeSlug = (value) => {
    if (!value) return '';
    return String(value).replace(/[-_]+/g, ' ').trim();
};

const hasArabicScript = (value) => /[\u0600-\u06FF]/.test(value || '');

const getNotificationCopy = (n) => {
    const data = n.data || {};
    let entityName = (data.entity_name || '').trim();
    let poetName = (data.poet_name || '').trim();
    let message = (n.message || '').trim();

    // Prefer structured fields; fall back to parsing older "title" by poet messages.
    if ((!entityName || looksLikeSlug(entityName)) && message) {
        const quoted = message.match(/"([^"]+)"/);
        if (quoted?.[1]) {
            entityName = quoted[1];
        }
        const byMatch = message.match(/\bby\s+(.+?)(?:\s+has\b|$)/i);
        if (!poetName && byMatch?.[1]) {
            poetName = byMatch[1].replace(/[.“”"]/g, '').trim();
        }
    }

    if (looksLikeSlug(entityName)) {
        entityName = humanizeSlug(entityName);
    }

    // Keep message short when we already show entity/poet separately.
    if (entityName && message && (message.includes(entityName) || message.includes('"'))) {
        message = '';
    }

    return {
        headline: n.title || 'Update',
        entityName,
        poetName,
        message,
    };
};

const NotificationBell = ({ variant = 'admin', isAdmin = false }) => {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const location = useLocation();
    const [open, setOpen] = useState(false);

    const { data } = useQuery({
        queryKey: ['notifications', variant],
        queryFn: async () => {
            const endpoint = isAdmin ? '/api/admin/notifications' : '/api/auth/notifications';
            const res = await api.get(endpoint);
            return res.data;
        },
        refetchInterval: 15000,
    });

    const unreadCount = data?.unread_count || 0;
    const notifications = data?.notifications || [];

    const markReadMutation = useMutation({
        mutationFn: (id) => {
            const endpoint = isAdmin ? `/api/admin/notifications/${id}/read` : `/api/auth/notifications/${id}/read`;
            return api.post(endpoint);
        },
        onSuccess: () => queryClient.invalidateQueries(['notifications', variant]),
    });

    const markAllReadMutation = useMutation({
        mutationFn: () => {
            const endpoint = isAdmin ? '/api/admin/notifications/read-all' : '/api/auth/notifications/read-all';
            return api.post(endpoint);
        },
        onSuccess: () => queryClient.invalidateQueries(['notifications', variant]),
    });

    const clearMutation = useMutation({
        mutationFn: () => {
            const endpoint = isAdmin ? '/api/admin/notifications/clear' : '/api/auth/notifications/clear';
            return api.delete(endpoint);
        },
        onSuccess: () => queryClient.invalidateQueries(['notifications', variant]),
    });

    const getCurrentLang = () => {
        const firstSegment = location.pathname.split('/').filter(Boolean)[0];
        return firstSegment === 'en' || firstSegment === 'sd' ? firstSegment : 'sd';
    };

    const resolveNotificationLink = (link) => {
        if (!link) return null;
        if (/^https?:\/\//i.test(link)) return link;

        if (isAdmin) {
            return link;
        }

        const currentLang = getCurrentLang();
        const withLangPlaceholder = link.replace('{lang}', currentLang);

        if (/^\/(en|sd)(\/|$)/.test(withLangPlaceholder)) {
            return withLangPlaceholder.replace(/^\/(en|sd)(?=\/|$)/, `/${currentLang}`);
        }

        return withLangPlaceholder.startsWith('/') ? withLangPlaceholder : `/${withLangPlaceholder}`;
    };

    const handleNotificationClick = (n) => {
        if (!n.read_at) markReadMutation.mutate(n.id);
        const targetLink = resolveNotificationLink(n.link);

        if (targetLink) {
            setOpen(false);
            if (/^https?:\/\//i.test(targetLink)) {
                window.location.href = targetLink;
                return;
            }
            navigate(targetLink);
        }
    };

    const getTimeAgo = (date) => {
        const now = new Date();
        const d = new Date(date);
        const diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d`;
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className={`relative rounded-full transition-colors ${variant === 'web' ? 'hover:bg-gray-100 h-9 w-9' : 'h-9 w-9'}`}
                >
                    <Bell className={`${variant === 'web' ? 'h-4 w-4' : 'h-5 w-5'} text-gray-600`} />
                    {unreadCount > 0 && (
                        variant === 'web' ? (
                            <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
                        ) : (
                            <span className="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white px-1">
                                {unreadCount > 99 ? '99+' : unreadCount}
                            </span>
                        )
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="end"
                className="w-[min(22rem,calc(100vw-1rem))] max-w-[calc(100vw-1rem)] p-0 overflow-hidden rounded-xl"
            >
                <div className="flex items-center justify-between gap-2 px-3.5 py-2.5 border-b">
                    <h3 className="text-sm font-semibold text-foreground">Notifications</h3>
                    <div className="flex items-center gap-1 shrink-0">
                        {unreadCount > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 px-2 text-xs text-muted-foreground hover:text-foreground"
                                onClick={(e) => {
                                    e.preventDefault();
                                    markAllReadMutation.mutate();
                                }}
                                disabled={markAllReadMutation.isPending}
                            >
                                <Check className="h-3.5 w-3.5 mr-1" />
                                Read all
                            </Button>
                        )}
                        {notifications.length > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="h-7 px-2 text-xs text-muted-foreground hover:text-destructive"
                                onClick={(e) => {
                                    e.preventDefault();
                                    clearMutation.mutate();
                                }}
                                disabled={clearMutation.isPending}
                            >
                                Clear
                            </Button>
                        )}
                    </div>
                </div>

                <ScrollArea className="h-[min(28rem,65dvh)]">
                    {notifications.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-10 px-4 text-muted-foreground">
                            <BellOff className="h-8 w-8 mb-2 opacity-30" />
                            <p className="text-sm">No notifications</p>
                        </div>
                    ) : (
                        <div className="divide-y">
                            {notifications.map((n) => {
                                const IconComp = ICONS[n.icon] || Bell;
                                const isUnread = !n.read_at;
                                const copy = getNotificationCopy(n);
                                const entityRtl = hasArabicScript(copy.entityName);
                                const poetRtl = hasArabicScript(copy.poetName);

                                return (
                                    <button
                                        type="button"
                                        key={n.id}
                                        className={`w-full text-left px-3.5 py-3 transition-colors hover:bg-muted/40 ${isUnread ? 'bg-muted/20' : ''}`}
                                        onClick={() => handleNotificationClick(n)}
                                    >
                                        <div className="flex gap-3 min-w-0">
                                            <div className={`mt-0.5 h-8 w-8 shrink-0 rounded-full flex items-center justify-center ${isUnread ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                                                <IconComp className="h-3.5 w-3.5" />
                                            </div>

                                            <div className="min-w-0 flex-1 space-y-1">
                                                <div className="flex items-start justify-between gap-2">
                                                    <p className={`text-[11px] uppercase tracking-wide ${isUnread ? 'text-foreground font-semibold' : 'text-muted-foreground'}`}>
                                                        {copy.headline}
                                                    </p>
                                                    <span className="shrink-0 text-[11px] text-muted-foreground/70 tabular-nums">
                                                        {getTimeAgo(n.created_at)}
                                                    </span>
                                                </div>

                                                {copy.entityName ? (
                                                    <p
                                                        className={`text-sm leading-snug text-foreground break-words ${entityRtl ? 'font-arabic' : ''}`}
                                                        dir={entityRtl ? 'rtl' : 'auto'}
                                                    >
                                                        {copy.entityName}
                                                    </p>
                                                ) : null}

                                                {copy.poetName ? (
                                                    <p
                                                        className={`text-xs text-muted-foreground leading-snug break-words ${poetRtl ? 'font-arabic' : ''}`}
                                                        dir={poetRtl ? 'rtl' : 'auto'}
                                                    >
                                                        {copy.poetName}
                                                    </p>
                                                ) : null}

                                                {!copy.entityName && copy.message ? (
                                                    <p className="text-sm text-muted-foreground leading-snug break-words whitespace-normal" dir="auto">
                                                        {copy.message}
                                                    </p>
                                                ) : null}

                                                {copy.entityName && copy.message ? (
                                                    <p className="text-xs text-muted-foreground leading-snug break-words whitespace-normal line-clamp-2" dir="auto">
                                                        {copy.message}
                                                    </p>
                                                ) : null}
                                            </div>

                                            {isUnread ? (
                                                <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                            ) : (
                                                <span className="w-1.5 shrink-0" />
                                            )}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </ScrollArea>
            </DropdownMenuContent>
        </DropdownMenu>
    );
};

export default NotificationBell;
