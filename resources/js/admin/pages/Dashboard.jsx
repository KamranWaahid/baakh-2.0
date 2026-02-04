import React from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Users,
    BookOpen,
    Feather,
    TrendingUp,
    Activity,
    ArrowUpRight,
    ArrowDownRight,
    Plus,
    Search
} from 'lucide-react';

const Dashboard = () => {
    // Static mock data for the dashboard
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

    const recentActivity = [
        {
            id: 1,
            user: "Sarah Ahmed",
            action: "Added new poem",
            target: "Dreams of Sindh",
            time: "2 mins ago"
        },
        {
            id: 2,
            user: "Ali Raza",
            action: "Updated profile",
            target: "Biography",
            time: "15 mins ago"
        },
        {
            id: 3,
            user: "System",
            action: "Generated sitemap",
            target: "XML Sitemap",
            time: "1 hour ago"
        },
        {
            id: 4,
            user: "Fatima Noor",
            action: "Commented on",
            target: "Shah Jo Risalo",
            time: "3 hours ago"
        }
    ];

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
                    <Button className="gap-2 bg-black hover:bg-gray-800 text-white">
                        <Plus className="h-4 w-4" />
                        New Entry
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

            {/* Main Content Area */}
            <div className="grid gap-6 md:grid-cols-7 lg:grid-cols-7">
                {/* Large Chart/Graph Section Placeholder */}
                <Card className="col-span-4 transition-all">
                    <CardHeader>
                        <CardTitle>Platform Growth</CardTitle>
                        <CardDescription>User registration and content contribution trends.</CardDescription>
                    </CardHeader>
                    <CardContent className="pl-2">
                        <div className="h-[300px] flex items-center justify-center bg-gray-50 rounded-md border border-dashed border-gray-200">
                            <div className="text-center text-gray-400">
                                <TrendingUp className="h-10 w-10 mx-auto mb-2 opacity-50" />
                                <p>Interactive Chart Visualization</p>
                                <p className="text-xs">(To be integrated with Recharts or Chart.js)</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Recent Activity Feed */}
                <Card className="col-span-3 transition-all">
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>Latest actions performed across the system.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-8">
                            {recentActivity.map((activity) => (
                                <div key={activity.id} className="flex items-center">
                                    <div className="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                        {activity.user.charAt(0)}
                                    </div>
                                    <div className="ml-4 space-y-1">
                                        <p className="text-sm font-medium leading-none">{activity.user}</p>
                                        <p className="text-sm text-gray-500">
                                            {activity.action} <span className="font-semibold text-gray-700">{activity.target}</span>
                                        </p>
                                    </div>
                                    <div className="ml-auto font-medium text-xs text-gray-400">
                                        {activity.time}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
};

export default Dashboard;
