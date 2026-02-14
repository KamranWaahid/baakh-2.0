import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useQuery } from '@tanstack/react-query';
import api from '../api/axios';
import { Link } from 'react-router-dom';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    BookOpen,
    Feather,
    Activity,
    ArrowUpRight,
    ArrowDownRight,
    Search,
    Plus,
    AlertCircle,
    Tag,
    Tags,
    Layers,
    AlignCenter,
    Languages,
    MessageSquare,
    Database,
    RefreshCw,
    Trash2,
    Settings
} from 'lucide-react';
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    BarChart,
    Bar,
    Legend
} from 'recharts';

const Dashboard = () => {
    const [openDialog, setOpenDialog] = useState(null);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['dashboard-stats'],
        queryFn: async () => {
            const response = await api.get('/api/admin/dashboard');
            return response.data;
        }
    });

    // Map API stats to display format
    const stats = data?.stats ? [
        {
            title: "Total Poets",
            value: data.stats.total_poets.value,
            change: data.stats.total_poets.change,
            trend: data.stats.total_poets.trend,
            icon: Feather,
            description: "Active poets in database"
        },
        {
            title: "Total Poetry",
            value: data.stats.total_poetry.value,
            change: data.stats.total_poetry.change,
            trend: data.stats.total_poetry.trend,
            icon: BookOpen,
            description: "Published poems"
        },
        {
            title: "Registered Users",
            value: data.stats.total_users.value,
            change: data.stats.total_users.change,
            trend: data.stats.total_users.trend,
            icon: Users,
            description: "Active community members"
        },
        {
            title: "Daily Views",
            value: data.stats.daily_views.value,
            change: data.stats.daily_views.change,
            trend: data.stats.daily_views.trend,
            icon: Activity,
            description: "Page views in last 24h"
        }
    ] : [
        {
            title: "Total Poets",
            value: "...",
            change: "...",
            trend: "up",
            icon: Feather,
            description: "Active poets in database"
        },
        {
            title: "Total Poetry",
            value: "...",
            change: "...",
            trend: "up",
            icon: BookOpen,
            description: "Published poems"
        },
        {
            title: "Registered Users",
            value: "...",
            change: "...",
            trend: "up",
            icon: Users,
            description: "Active community members"
        },
        {
            title: "Daily Views",
            value: "...",
            change: "...",
            trend: "up",
            icon: Activity,
            description: "Page views in last 24h"
        }
    ];

    const actionCards = [
        {
            id: 'missing_en_poetry',
            title: "Poetry Missing Translation",
            description: "Poems needing English content",
            icon: Languages,
            color: "orange",
            dataKey: 'missing_en_poetry',
            dialogTitle: "Poetry Missing Translation",
            dialogDescription: "Poems (Linked) that need English content."
        },
        {
            id: 'missing_en_couplets',
            title: "Couplets Missing Translation",
            description: "Couplets needing English content",
            icon: Languages,
            color: "blue",
            dataKey: 'missing_en_couplets',
            dialogTitle: "Couplets Missing Translation",
            dialogDescription: "Independent couplets needing English content."
        },
        {
            id: 'missing_tags_couplets',
            title: "Couplets Missing Tags",
            description: "Couplets without tags",
            icon: Tag,
            color: "green",
            dataKey: 'missing_tags_couplets',
            dialogTitle: "Couplets Missing Tags",
            dialogDescription: "All couplets that haven't been tagged yet."
        },
        {
            id: 'orthography_issues',
            title: "Orthography Issues",
            description: "Lines needing phonetic fix",
            icon: AlertCircle,
            color: "purple",
            dataKey: 'orthography_issues',
            dialogTitle: "Orthography & Spelling Issues",
            dialogDescription: "Sindhi text that deviates from phonetic-contextual standards or dictionary."
        }
    ];

    const getColorClasses = (color) => {
        const colors = {
            orange: {
                bg: 'bg-orange-50',
                text: 'text-orange-600',
                border: 'border-orange-200',
                hover: 'hover:bg-orange-100'
            },
            blue: {
                bg: 'bg-blue-50',
                text: 'text-blue-600',
                border: 'border-blue-200',
                hover: 'hover:bg-blue-100'
            },
            green: {
                bg: 'bg-green-50',
                text: 'text-green-600',
                border: 'border-green-200',
                hover: 'hover:bg-green-100'
            },
            purple: {
                bg: 'bg-purple-50',
                text: 'text-purple-600',
                border: 'border-purple-200',
                hover: 'hover:bg-purple-100'
            }
        };
        return colors[color] || colors.orange;
    };

    const ListItem = ({ item, link }) => (
        <div className="flex items-center justify-between py-2 border-b last:border-0">
            <div className="flex-1 truncate pr-4">
                <span className="font-medium text-sm text-gray-700">{item.title}</span>
                <span className="ml-2 text-xs text-gray-400">ID: {item.id}</span>
            </div>
            <Button variant="ghost" size="sm" asChild className="h-8">
                <Link to={link}>Edit</Link>
            </Button>
        </div>
    );

    return (
        <div className="flex flex-col gap-6 p-4 md:p-8 fade-in-bottom">
            {/* Header Section */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl md:text-4xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                    <p className="mt-1 md:mt-2 text-sm md:text-lg text-gray-500">Overview of your platform's performance and activity.</p>
                </div>
                <div className="flex flex-wrap gap-2 md:gap-4">
                    <Button variant="outline" className="gap-2 text-xs md:text-sm">
                        <Search className="h-3 w-3 md:h-4 md:w-4" />
                        Search
                    </Button>
                    <Button className="gap-2 bg-black hover:bg-gray-800 text-white text-xs md:text-sm" asChild>
                        <Link to="/admin/poetry/create">
                            <Plus className="h-3 w-3 md:h-4 md:w-4" />
                            New Entry
                        </Link>
                    </Button>
                </div>
            </div>

            {/* Stats Grid */}
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                {stats.map((stat, index) => {
                    const Icon = stat.icon;
                    return (
                        <Card key={index} className="transition-all hover:bg-gray-50 duration-300">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-500">
                                    {stat.title}
                                </CardTitle>
                                <Icon className="h-4 w-4 text-gray-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stat.value}</div>
                                <p className="text-xs text-gray-500 mt-1 flex items-center">
                                    {stat.trend === 'up' ? (
                                        <span className="text-green-600 flex items-center font-medium">
                                            <ArrowUpRight className="h-3 w-3 mr-1" />
                                            {stat.change}
                                        </span>
                                    ) : (
                                        <span className="text-red-600 flex items-center font-medium">
                                            <ArrowDownRight className="h-3 w-3 mr-1" />
                                            {stat.change}
                                        </span>
                                    )}
                                    <span className="ml-2 text-muted-foreground">from last month</span>
                                </p>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
            {/* Quick Actions Grid */}
            <div className="grid gap-4 grid-cols-2 md:grid-cols-4 lg:grid-cols-5">
                {[
                    { label: 'Add Poet', target: 'Create Poet', link: '/admin/poets/create', icon: Feather },
                    { label: 'Add Poetry', target: 'Create Poetry', link: '/admin/poetry/create', icon: BookOpen },
                    { label: 'Add Tag', target: 'Create Tag', link: '/admin/tags/create', icon: Tags },
                    { label: 'Add Topic', target: 'New Topic', link: '/admin/topic-categories', icon: Layers },
                    { label: 'Add Couplet', target: 'New Couplet', link: '/admin/couplet/create', icon: AlignCenter },
                ].map((action, i) => (
                    <Card key={i} className="hover:bg-muted/50 cursor-pointer transition-colors group relative overflow-hidden">
                        <Link to={action.link}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-xs font-medium text-muted-foreground uppercase tracking-wider">{action.label}</CardTitle>
                                <Plus className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-xl font-bold">{action.target}</div>
                                <div className="flex items-center mt-2">
                                    <action.icon className="h-4 w-4 text-muted-foreground mr-2" />
                                    <span className="text-xs text-muted-foreground">Jump to creator</span>
                                </div>
                            </CardContent>
                        </Link>
                    </Card>
                ))}
            </div>

            {/* Charts Grid */}
            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Activity Overview</CardTitle>
                        <CardDescription>System activity over the last 30 days</CardDescription>
                    </CardHeader>
                    <CardContent className="h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={data?.activity_graph || []}>
                                <defs>
                                    <linearGradient id="colorActions" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#8884d8" stopOpacity={0.8} />
                                        <stop offset="95%" stopColor="#8884d8" stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <XAxis dataKey="date" fontSize={12} tickLine={false} axisLine={false} />
                                <YAxis fontSize={12} tickLine={false} axisLine={false} />
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <Tooltip />
                                <Area type="monotone" dataKey="actions" stroke="#8884d8" fillOpacity={1} fill="url(#colorActions)" />
                            </AreaChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Content Growth</CardTitle>
                        <CardDescription>New poets and poetry added</CardDescription>
                    </CardHeader>
                    <CardContent className="h-[300px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={data?.content_growth || []}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} />
                                <XAxis dataKey="date" fontSize={12} tickLine={false} axisLine={false} />
                                <YAxis fontSize={12} tickLine={false} axisLine={false} />
                                <Tooltip cursor={{ fill: 'transparent' }} />
                                <Legend />
                                <Bar dataKey="poets" fill="#10b981" radius={[4, 4, 0, 0]} name="Poets" />
                                <Bar dataKey="poetry" fill="#3b82f6" radius={[4, 4, 0, 0]} name="Poetry" />
                            </BarChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>
            </div>

            {/* Action Cards Grid */}
            <h2 className="text-xl font-bold tracking-tight text-gray-900 mt-2">Pending Actions</h2>
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                {actionCards.map((card) => {
                    const Icon = card.icon;
                    const colors = getColorClasses(card.color);
                    const itemCount = data?.[card.dataKey]?.length || 0;

                    return (
                        <Card
                            key={card.id}
                            className="cursor-pointer transition-all hover:bg-gray-50 duration-300 group"
                            onClick={() => setOpenDialog(card.id)}
                        >
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-500">
                                    {card.title}
                                </CardTitle>
                                <div className={`p-1.5 rounded-md ${colors.bg}`}>
                                    <Icon className={`h-4 w-4 ${colors.text}`} />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{itemCount}</div>
                                <div className="flex items-center justify-between mt-1">
                                    <p className="text-xs text-gray-500">
                                        {card.description}
                                    </p>
                                    <ArrowUpRight className="h-3 w-3 text-gray-300 group-hover:text-gray-900 transition-colors" />
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {/* Widgets Grid */}
            <div className="grid gap-6 md:grid-cols-2">
                {/* Activity Feed */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="flex items-center gap-2">
                            <Activity className="h-5 w-5 text-muted-foreground" /> Recent Activity
                        </CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link to="/admin/system/activity-logs">View All</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {data?.recent_activity?.length > 0 ? data.recent_activity.map((log) => (
                                <div key={log.id} className="flex items-start gap-4 pb-4 border-b last:border-0 last:pb-0">
                                    <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                                        {log.user?.avatar ? (
                                            <img src={log.user.avatar.startsWith('http') ? log.user.avatar : `/${log.user.avatar}`} alt={log.user.name} className="h-full w-full object-cover" />
                                        ) : (
                                            <span className="text-xs font-bold text-gray-500">{log.user?.name?.[0] || 'S'}</span>
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-900">
                                            {log.user?.name || 'System'} <span className="text-gray-500 font-normal">{log.description || log.action}</span>
                                        </p>
                                        <p className="text-xs text-gray-400 mt-0.5">{log.time}</p>
                                    </div>
                                </div>
                            )) : (
                                <p className="text-sm text-gray-500 italic">No recent activity.</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Reports Widget */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-destructive" /> Recent Reports
                        </CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link to="/admin/moderation/reports">View All</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {data?.recent_reports?.length > 0 ? data.recent_reports.map((report) => (
                                <div key={report.id} className="flex items-start justify-between pb-4 border-b last:border-0 last:pb-0">
                                    <div className="space-y-1">
                                        <p className="text-sm font-medium leading-none">{report.target}</p>
                                        <p className="text-xs text-muted-foreground">Reported by <span className="font-semibold">{report.reporter}</span></p>
                                        <Badge variant="destructive" className="mt-1">{report.reason}</Badge>
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Button size="sm" variant="outline" className="h-7 text-xs" asChild>
                                            <Link to={`/admin/moderation/reports?id=${report.id}`}>View</Link>
                                        </Button>
                                    </div>
                                </div>
                            )) : (
                                <p className="text-sm text-gray-500 italic">No active reports.</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Feedback Widget */}
                <Card className="md:col-span-2 lg:col-span-1">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0">
                        <CardTitle className="flex items-center gap-2">
                            <MessageSquare className="h-5 w-5 text-primary" /> Recent Feedback
                        </CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link to="/admin/moderation/feedback">View All</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {data?.recent_feedback?.length > 0 ? data.recent_feedback.map((feedback) => (
                                <div key={feedback.id} className="flex items-start gap-3 pb-4 border-b last:border-0 last:pb-0">
                                    <div className="h-8 w-8 rounded-full bg-secondary text-secondary-foreground flex items-center justify-center shrink-0 font-bold text-xs uppercase">
                                        {feedback.user.name[0]}
                                    </div>
                                    <div className="flex-1 space-y-1">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium">{feedback.user.name}</p>
                                            <span className="text-xs text-gray-400">{feedback.time}</span>
                                        </div>
                                        <p className="text-sm text-gray-600 line-clamp-2">"{feedback.message}"</p>
                                        <div className="flex items-center gap-1">
                                            {Array.from({ length: 5 }).map((_, i) => (
                                                <span key={i} className={`text-xs ${i < feedback.rating ? 'text-yellow-400' : 'text-gray-200'}`}>★</span>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )) : (
                                <p className="text-sm text-gray-500 italic">No recent feedback.</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Dialogs for each action card */}
            {actionCards.map((card) => {
                const items = data?.[card.dataKey] || [];
                return (
                    <Dialog
                        key={card.id}
                        open={openDialog === card.id}
                        onOpenChange={(open) => !open && setOpenDialog(null)}
                    >
                        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>{card.dialogTitle}</DialogTitle>
                                <DialogDescription>{card.dialogDescription}</DialogDescription>
                            </DialogHeader>
                            <div className="mt-4">
                                {isLoading ? (
                                    <div className="space-y-2">
                                        {[1, 2, 3, 4, 5].map(i => <Skeleton key={i} className="h-12 w-full" />)}
                                    </div>
                                ) : items.length > 0 ? (
                                    <div className="space-y-1">
                                        {items.map(item => (
                                            <ListItem
                                                key={item.id}
                                                item={item}
                                                link={
                                                    item.edit_url || (card.id === 'missing_tags_couplets'
                                                        ? (item.poetry_id ? `/admin/poetry/${item.poetry_id}/edit` : '#')
                                                        : `/admin/poetry/${item.id}/edit`)
                                                }
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground py-8 text-center">
                                        No pending items.
                                    </p>
                                )}
                            </div>
                        </DialogContent>
                    </Dialog>
                );
            })}
        </div>
    );
};

export default Dashboard;
