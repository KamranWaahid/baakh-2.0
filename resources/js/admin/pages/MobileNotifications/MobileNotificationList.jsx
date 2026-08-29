import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Bell, Plus, Trash2, Edit, Smartphone, Send } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useDebounce } from '@/hooks/useDebounce';
import api from '../../api/axios';

const statusVariant = {
    draft: 'secondary',
    scheduled: 'outline',
    published: 'default',
    archived: 'secondary',
};

const MobileNotificationList = () => {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [type, setType] = useState('');
    const [status, setStatus] = useState('');
    const debouncedSearch = useDebounce(search, 400);
    const queryClient = useQueryClient();

    const { data, isLoading, isError } = useQuery({
        queryKey: ['mobile-notifications', page, debouncedSearch, type, status],
        queryFn: async () => {
            const response = await api.get('/api/admin/mobile-notifications', {
                params: { page, search: debouncedSearch, type, status },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const list = data?.notifications || { data: [] };
    const types = data?.types || [];

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/mobile-notifications/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mobile-notifications'] });
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete notification');
        },
    });

    const publishMutation = useMutation({
        mutationFn: (id) => api.post(`/api/admin/mobile-notifications/${id}/publish`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['mobile-notifications'] });
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to publish notification');
        },
    });

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Bell className="h-7 w-7 text-primary" />
                        App Notifications
                    </h2>
                    <p className="text-muted-foreground mt-1">
                        Custom Android and iOS alerts: daily verses, new poetry, reminders, and updates.
                    </p>
                </div>
                <Button asChild className="w-full sm:w-auto">
                    <Link to="/admin/mobile-notifications/create">
                        <Plus className="mr-2 h-4 w-4" /> New notification
                    </Link>
                </Button>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Published</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.published ?? 0}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Scheduled</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.scheduled ?? 0}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Android devices</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.devices_android ?? 0}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">iOS devices</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.devices_ios ?? 0}</CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader className="space-y-3">
                    <CardTitle className="text-xl">Campaigns</CardTitle>
                    <div className="flex flex-col md:flex-row gap-3">
                        <Input
                            placeholder="Search title or body..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            className="md:max-w-sm"
                        />
                        <select
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            value={type}
                            onChange={(e) => {
                                setType(e.target.value);
                                setPage(1);
                            }}
                        >
                            <option value="">All types</option>
                            {types.map((item) => (
                                <option key={item.value} value={item.value}>{item.label}</option>
                            ))}
                        </select>
                        <select
                            className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                setPage(1);
                            }}
                        >
                            <option value="">All statuses</option>
                            {(data?.statuses || ['draft', 'scheduled', 'published', 'archived']).map((item) => (
                                <option key={item} value={item}>{item}</option>
                            ))}
                        </select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Notification</TableHead>
                                    <TableHead className="hidden md:table-cell">Type</TableHead>
                                    <TableHead className="hidden lg:table-cell">Platforms</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-48" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="hidden lg:table-cell"><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-red-500">
                                            Could not load notifications.
                                        </TableCell>
                                    </TableRow>
                                ) : list.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-muted-foreground">
                                            No notifications yet. Create one for the mobile app.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    list.data.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <div className="space-y-1">
                                                    <div className="font-medium" lang="sd">{item.title_sd}</div>
                                                    <div className="text-sm text-muted-foreground">{item.title_en || '—'}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <Badge variant="outline">
                                                    {types.find((t) => t.value === item.type)?.label || item.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                    <Smartphone className="h-3 w-3" />
                                                    {(item.platforms || []).join(' · ') || '—'}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={statusVariant[item.status] || 'secondary'}>{item.status}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap space-x-1">
                                                {item.status !== 'published' && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-8 w-8"
                                                        title="Publish"
                                                        onClick={() => publishMutation.mutate(item.id)}
                                                    >
                                                        <Send className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                    <Link to={`/admin/mobile-notifications/${item.id}/edit`}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => {
                                                        if (confirm('Delete this notification?')) {
                                                            deleteMutation.mutate(item.id);
                                                        }
                                                    }}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {data?.notifications && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-4">
                            <div className="text-sm text-muted-foreground">
                                Showing {list.from || 0} to {list.to || 0} of {list.total || 0}
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button variant="outline" size="sm" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={!list.prev_page_url}>
                                    Previous
                                </Button>
                                <Button variant="outline" size="sm" onClick={() => setPage((p) => p + 1)} disabled={!list.next_page_url}>
                                    Next
                                </Button>
                            </div>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default MobileNotificationList;
