import React, { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
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
    X,
    BellOff,
} from 'lucide-react';

const ICONS = {
    BookOpen, Feather, Trash2, UserPlus, LogIn, Shield, Bug,
    ShieldCheck, CheckCircle, Globe, Layers, Tags, Bot, Rocket,
    MessageSquare, Bell,
};

const COLOR_MAP = {
    red: 'bg-red-100 text-red-700',
    blue: 'bg-blue-100 text-blue-700',
    sky: 'bg-sky-100 text-sky-700',
    green: 'bg-green-100 text-green-700',
    emerald: 'bg-emerald-100 text-emerald-700',
    purple: 'bg-purple-100 text-purple-700',
    violet: 'bg-violet-100 text-violet-700',
    amber: 'bg-amber-100 text-amber-700',
    orange: 'bg-orange-100 text-orange-700',
    gray: 'bg-gray-100 text-gray-600',
    teal: 'bg-teal-100 text-teal-700',
    indigo: 'bg-indigo-100 text-indigo-700',
    cyan: 'bg-cyan-100 text-cyan-700',
    fuchsia: 'bg-fuchsia-100 text-fuchsia-700',
    pink: 'bg-pink-100 text-pink-700',
};

const NotificationBell = ({ variant = 'admin', isAdmin = false }) => {
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const [isOpen, setIsOpen] = useState(false);
    const dropdownRef = useRef(null);

    // Fetch notifications
    const { data } = useQuery({
        queryKey: ['notifications', variant],
        queryFn: async () => {
            const endpoint = isAdmin ? '/api/admin/notifications' : '/api/auth/notifications';
            const res = await api.get(endpoint);
            return res.data;
        },
        refetchInterval: 10000, // Poll every 10s
    });

    const unreadCount = data?.unread_count || 0;
    const notifications = data?.notifications || [];

    // Close dropdown on outside click
    useEffect(() => {
        const handleClick = (e) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

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

    const handleNotificationClick = (n) => {
        if (!n.read_at) markReadMutation.mutate(n.id);
        if (n.link) {
            navigate(n.link);
            setIsOpen(false);
        }
    };

    const getTimeAgo = (date) => {
        const now = new Date();
        const d = new Date(date);
        const diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        return `${Math.floor(diff / 86400)}d ago`;
    };

    const getIcon = (iconName) => {
        const IconComp = ICONS[iconName] || Bell;
        return IconComp;
    };

    return (
        <div className="relative" ref={dropdownRef}>
            {/* Bell Button */}
            <Button
                variant="ghost"
                size="icon"
                className={`relative rounded-full transition-colors ${variant === 'web' ? 'hover:bg-gray-100 h-9 w-9' : 'h-9 w-9'}`}
                onClick={() => setIsOpen(!isOpen)}
            >
                <Bell className={`${variant === 'web' ? 'h-4 w-4' : 'h-5 w-5'} text-gray-600`} />
                {unreadCount > 0 && (
                    variant === 'web' ? (
                        <span className="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white" />
                    ) : (
                        <span className="absolute -top-0.5 -right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white px-1 animate-pulse">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </span>
                    )
                )}
            </Button>

            {/* Dropdown Panel */}
            {isOpen && (
                <div className={`absolute right-0 top-11 w-[380px] bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden animate-in fade-in slide-in-from-top-1 duration-200 ${variant === 'web' ? 'mt-2' : ''}`}>
                    {/* Header */}
                    <div className="flex items-center justify-between px-4 py-3 border-b bg-gray-50/80">
                        <div className="flex items-center gap-2">
                            <h3 className="font-semibold text-sm">Notifications</h3>
                            {unreadCount > 0 && (
                                <Badge variant="destructive" className="text-[10px] h-5 px-1.5">{unreadCount} new</Badge>
                            )}
                        </div>
                        <div className="flex gap-1">
                            {unreadCount > 0 && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-xs h-7 px-2 text-blue-600 hover:text-blue-700"
                                    onClick={() => markAllReadMutation.mutate()}
                                    disabled={markAllReadMutation.isPending}
                                >
                                    <Check className="h-3 w-3 mr-1" /> Read all
                                </Button>
                            )}
                            {notifications.length > 0 && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-xs h-7 px-2 text-gray-400 hover:text-red-500"
                                    onClick={() => clearMutation.mutate()}
                                    disabled={clearMutation.isPending}
                                >
                                    <X className="h-3 w-3" />
                                </Button>
                            )}
                        </div>
                    </div>

                    {/* Notification List */}
                    <ScrollArea className="max-h-[420px]">
                        {notifications.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-gray-400">
                                <BellOff className="h-10 w-10 mb-3 opacity-40" />
                                <p className="text-sm font-medium">No notifications yet</p>
                                <p className="text-xs mt-1">Activity will appear here</p>
                            </div>
                        ) : (
                            <div className="divide-y divide-gray-100">
                                {notifications.map((n) => {
                                    const IconComp = getIcon(n.icon);
                                    const colorClasses = COLOR_MAP[n.color] || COLOR_MAP.gray;
                                    const isUnread = !n.read_at;

                                    return (
                                        <div
                                            key={n.id}
                                            className={`flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors hover:bg-gray-50 ${isUnread ? 'bg-blue-50/40' : ''}`}
                                            onClick={() => handleNotificationClick(n)}
                                        >
                                            <div className={`shrink-0 mt-0.5 h-8 w-8 rounded-full flex items-center justify-center ${colorClasses}`}>
                                                <IconComp className="h-4 w-4" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <p className={`text-sm truncate ${isUnread ? 'font-semibold text-gray-900' : 'text-gray-700'}`}>
                                                        {n.title}
                                                    </p>
                                                    {isUnread && (
                                                        <span className="shrink-0 h-2 w-2 rounded-full bg-blue-500" />
                                                    )}
                                                </div>
                                                <p className="text-xs text-gray-500 truncate mt-0.5">{n.message}</p>
                                                <p className="text-[10px] text-gray-400 mt-1">{getTimeAgo(n.created_at)}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </ScrollArea>
                </div>
            )}
        </div>
    );
};

export default NotificationBell;
