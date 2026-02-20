import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '../../api/axios';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { format } from "date-fns";
import { Terminal } from 'lucide-react';

const ActivityLogs = () => {
    const [page, setPage] = useState(1);

    const { data, isLoading } = useQuery({
        queryKey: ['activity-logs', page],
        queryFn: async () => {
            const res = await api.get(`/api/admin/activity-logs?page=${page}`);
            return res.data;
        }
    });

    if (isLoading) return <div className="p-8">Loading logs...</div>;

    const logs = data?.data || [];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Activity Logs</h1>
                    <p className="text-muted-foreground">
                        Track system and user actions across the platform.
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Recent Activity</CardTitle>
                    <CardDescription>A detailed record of all administrative actions.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead>Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {logs.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center">
                                            No activity logs found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    logs.map((log) => (
                                        <TableRow key={log.id}>
                                            <TableCell className="font-medium">
                                                {log.user?.name || 'System'}
                                                <div className="text-xs text-muted-foreground font-normal">{log.user?.email}</div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline" className="capitalize">
                                                    {log.action.replace('_', ' ')}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="max-w-md truncate">
                                                {log.description}
                                            </TableCell>
                                            <TableCell className="font-mono text-xs">
                                                {log.ip_address}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground whitespace-nowrap">
                                                {format(new Date(log.created_at), 'MMM d, yyyy HH:mm')}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Simple Pagination */}
                    <div className="flex items-center justify-end space-x-2 py-4">
                        <button
                            className="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
                            onClick={() => setPage((p) => Math.max(1, p - 1))}
                            disabled={page === 1}
                        >
                            Previous
                        </button>
                        <span className="text-sm font-medium">Page {page}</span>
                        <button
                            className="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-9 px-4 py-2"
                            onClick={() => setPage((p) => p + 1)}
                            disabled={!data?.next_page_url}
                        >
                            Next
                        </button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
};

export default ActivityLogs;
