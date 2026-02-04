import React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useQuery } from '@tanstack/react-query';
import api from '../api/axios';
import { Link } from 'react-router-dom';
import { Skeleton } from '@/components/ui/skeleton';
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
    const { data, isLoading, isError } = useQuery({
        queryKey: ['dashboard-stats'],
        queryFn: async () => {
            const response = await api.get('/api/admin/dashboard');
            return response.data;
        }
    });

    const stats = [
        {
            title: "Total Poets",
            value: "1,248",
            change: "+12%",
            trend: "up",
            icon: Feather,
            description: "Active poets in database"
        },
        {
            title: "Total Poetry",
            value: "14,503",
            change: "+5%",
            trend: "up",
            icon: BookOpen,
            description: "Published poems"
        },
        {
            title: "Registered Users",
            value: "8,942",
            change: "+18%",
            trend: "up",
            icon: Users,
            description: "Active community members"
        },
        {
            title: "Daily Views",
            value: "45.2K",
            change: "-2%",
            trend: "down",
            icon: Activity,
            description: "Page views in last 24h"
        }
    ];

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
        <div className="flex flex-col gap-8 p-8 fade-in-bottom">
            {/* Header Section */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-4xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                    <p className="mt-2 text-lg text-gray-500">Overview of your platform's performance and activity.</p>
                </div>
                <div className="flex gap-4">
                    <Button variant="outline" className="gap-2">
                        <Search className="h-4 w-4" />
                        Search
                    </Button>
                    <Button className="gap-2 bg-black hover:bg-gray-800 text-white" asChild>
                        <Link to="/poetry/create">
                            <Plus className="h-4 w-4" />
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

            {/* Content Tasks Section */}
            <div className="grid gap-6 md:grid-cols-1 lg:grid-cols-3">
                {/* 1. Missing EN Poetry */}
                <Card className="col-span-1 shadow-sm border-orange-100 bg-orange-50/30">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Languages className="h-5 w-5 text-orange-500" />
                            <CardTitle className="text-lg">Poetry Missing Translation</CardTitle>
                        </div>
                        <CardDescription>Poems (Linked) that need English content.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {isLoading ? (
                            <div className="space-y-2">
                                {[1, 2, 3].map(i => <Skeleton key={i} className="h-10 w-full" />)}
                            </div>
                        ) : data?.missing_en_poetry?.length > 0 ? (
                            <div className="space-y-1">
                                {data.missing_en_poetry.map(item => (
                                    <ListItem key={item.id} item={item} link={`/poetry/${item.id}/edit`} />
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground py-4">No pending items.</p>
                        )}
                    </CardContent>
                </Card>

                {/* 2. Missing EN Couplets */}
                <Card className="col-span-1 shadow-sm border-blue-100 bg-blue-50/30">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Languages className="h-5 w-5 text-blue-500" />
                            <CardTitle className="text-lg">Couplets Missing Translation</CardTitle>
                        </div>
                        <CardDescription>Independent couplets needing English content.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {isLoading ? (
                            <div className="space-y-2">
                                {[1, 2, 3].map(i => <Skeleton key={i} className="h-10 w-full" />)}
                            </div>
                        ) : data?.missing_en_couplets?.length > 0 ? (
                            <div className="space-y-1">
                                {data.missing_en_couplets.map(item => (
                                    <ListItem key={item.id} item={item} link={`/poetry/${item.id}/edit`} />
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground py-4">No pending items.</p>
                        )}
                    </CardContent>
                </Card>

                {/* 3. Missing Tags */}
                <Card className="col-span-1 shadow-sm border-green-100 bg-green-50/30">
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Tag className="h-5 w-5 text-green-500" />
                            <CardTitle className="text-lg">Couplets Missing Tags</CardTitle>
                        </div>
                        <CardDescription>All couplets that haven't been tagged yet.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {isLoading ? (
                            <div className="space-y-2">
                                {[1, 2, 3].map(i => <Skeleton key={i} className="h-10 w-full" />)}
                            </div>
                        ) : data?.missing_tags_couplets?.length > 0 ? (
                            <div className="space-y-1">
                                {data.missing_tags_couplets.map(item => (
                                    // Note: poetry_id is used for editing usually, check if null
                                    <ListItem key={item.id} item={item} link={item.poetry_id ? `/poetry/${item.poetry_id}/edit` : '#'} />
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground py-4">No pending items.</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default Dashboard;
