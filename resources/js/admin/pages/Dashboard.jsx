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
    Languages
} from 'lucide-react';

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
                                    <span className="ml-2 text-gray-400">from last month</span>
                                </p>
                            </CardContent>
                        </Card>
                    );
                })}
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
                                                    card.id === 'missing_tags_couplets'
                                                        ? (item.poetry_id ? `/admin/poetry/${item.poetry_id}/edit` : '#')
                                                        : `/admin/poetry/${item.id}/edit`
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
