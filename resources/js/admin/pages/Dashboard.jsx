import React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { useQuery } from '@tanstack/react-query';
import api from '../api/axios';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Users,
    BookOpen,
    Feather,
    Activity,
    ArrowUpRight,
    ArrowDownRight,
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
    const { data, isError } = useQuery({
        queryKey: ['dashboard-stats'],
        queryFn: async () => {
            const response = await api.get('/api/admin/dashboard');
            return response.data;
        }
    });

    const stats = data?.stats ? [
        {
            title: "Total Poets",
            value: data.stats.total_poets.value,
            change: data.stats.total_poets.change,
            trend: data.stats.total_poets.trend,
            icon: Feather,
        },
        {
            title: "Total Poetry",
            value: data.stats.total_poetry.value,
            change: data.stats.total_poetry.change,
            trend: data.stats.total_poetry.trend,
            icon: BookOpen,
        },
        {
            title: "Registered Users",
            value: data.stats.total_users.value,
            change: data.stats.total_users.change,
            trend: data.stats.total_users.trend,
            icon: Users,
        },
        {
            title: "Daily Views",
            value: data.stats.daily_views.value,
            change: data.stats.daily_views.change,
            trend: data.stats.daily_views.trend,
            icon: Activity,
        }
    ] : [
        { title: "Total Poets", value: "...", change: "...", trend: "up", icon: Feather },
        { title: "Total Poetry", value: "...", change: "...", trend: "up", icon: BookOpen },
        { title: "Registered Users", value: "...", change: "...", trend: "up", icon: Users },
        { title: "Daily Views", value: "...", change: "...", trend: "up", icon: Activity },
    ];

    return (
        <div className="flex flex-col gap-6 fade-in-bottom min-w-0 w-full max-w-full">
            {isError ? (
                <Alert variant="destructive">
                    <AlertTitle>Could not load dashboard</AlertTitle>
                    <AlertDescription>
                        The stats API did not respond. Check that you are signed in with admin access and try refreshing the page.
                    </AlertDescription>
                </Alert>
            ) : null}

            <div className="min-w-0">
                <h1 className="text-2xl md:text-4xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                <p className="mt-1 md:mt-2 text-sm md:text-lg text-gray-500">Overview of your platform's performance and activity.</p>
            </div>

            {/* Stats */}
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

            {/* Graphs */}
            <div className="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Activity Overview</CardTitle>
                        <CardDescription>System activity over the last 30 days</CardDescription>
                    </CardHeader>
                    <CardContent className="h-[240px] sm:h-[300px] min-w-0">
                        <ResponsiveContainer width="100%" height="100%" minWidth={0}>
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
                    <CardContent className="h-[240px] sm:h-[300px] min-w-0">
                        <ResponsiveContainer width="100%" height="100%" minWidth={0}>
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
        </div>
    );
};

export default Dashboard;
