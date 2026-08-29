import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { Bell, Plus, Trash2, Edit, Send, MoreHorizontal } from 'lucide-react';
import { toast } from 'sonner';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useDebounce } from '@/hooks/useDebounce';
import api from '../../api/axios';

const statusVariant = {
    draft: 'secondary',
    scheduled: 'outline',
    published: 'default',
    archived: 'secondary',
};

const PlatformPills = ({ platforms = [] }) => (
    <div className="flex flex-wrap gap-1">
        {platforms.includes('android') && <Badge variant="outline">Android</Badge>}
        {platforms.includes('ios') && <Badge variant="outline">iOS</Badge>}
        {platforms.length === 0 && <span className="text-xs text-muted-foreground">—</span>}
    </div>
);

const RowActions = ({ item, onPublish, onDelete }) => (
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" className="h-8 w-8">
                <MoreHorizontal className="h-4 w-4" />
                <span className="sr-only">Actions</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            {item.status !== 'published' && (
                <DropdownMenuItem onClick={() => onPublish(item.id)}>
                    <Send className="mr-2 h-4 w-4" /> Publish
                </DropdownMenuItem>
            )}
            <DropdownMenuItem asChild>
                <Link to={`/admin/mobile-notifications/${item.id}/edit`}>
                    <Edit className="mr-2 h-4 w-4" /> Edit
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem className="text-destructive" onClick={() => onDelete(item.id)}>
                <Trash2 className="mr-2 h-4 w-4" /> Delete
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
);

const MobileNotificationList = () => {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [type, setType] = useState('all');
    const [status, setStatus] = useState('all');
    const debouncedSearch = useDebounce(search, 400);
    const queryClient = useQueryClient();

    const { data, isLoading, isError } = useQuery({
        queryKey: ['mobile-notifications', page, debouncedSearch, type, status],
        queryFn: async () => {
            const response = await api.get('/api/admin/mobile-notifications', {
                params: {
                    page,
                    search: debouncedSearch,
                    type: type === 'all' ? '' : type,
                    status: status === 'all' ? '' : status,
                },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const list = data?.notifications || { data: [] };
    const types = data?.types || [];

    const invalidate = () => queryClient.invalidateQueries({ queryKey: ['mobile-notifications'] });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/mobile-notifications/${id}`),
        onSuccess: () => {
            invalidate();
            toast.success('Notification deleted');
        },
        onError: (error) => toast.error(error.response?.data?.message || 'Failed to delete notification'),
    });

    const publishMutation = useMutation({
        mutationFn: (id) => api.post(`/api/admin/mobile-notifications/${id}/publish`),
        onSuccess: () => {
            invalidate();
            toast.success('Notification published');
        },
        onError: (error) => toast.error(error.response?.data?.message || 'Failed to publish notification'),
    });

    const handleDelete = (id) => {
        if (confirm('Delete this notification?')) {
            deleteMutation.mutate(id);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="space-y-1 min-w-0">
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight flex items-center gap-2">
                        <Bell className="h-6 w-6 md:h-7 md:w-7 text-primary shrink-0" />
                        App Notifications
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Custom Android and iOS alerts in Sindhi and English.
                    </p>
                </div>
                <Button asChild className="w-full sm:w-auto">
                    <Link to="/admin/mobile-notifications/create">
                        <Plus className="mr-2 h-4 w-4" /> New notification
                    </Link>
                </Button>
            </div>

            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Published</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.published ?? 0}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Scheduled</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.scheduled ?? 0}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">Android devices</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.devices_android ?? 0}</CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">iOS devices</CardTitle>
                    </CardHeader>
                    <CardContent className="text-2xl font-semibold">{data?.stats?.devices_ios ?? 0}</CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader className="space-y-3">
                    <CardTitle className="text-xl flex items-center gap-2">
                        <Bell className="h-5 w-5" /> Campaigns
                    </CardTitle>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <Input
                            placeholder="Search title or body..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                        />
                        <Select value={type} onValueChange={(value) => { setType(value); setPage(1); }}>
                            <SelectTrigger>
                                <SelectValue placeholder="All types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All types</SelectItem>
                                {types.map((item) => (
                                    <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={status} onValueChange={(value) => { setStatus(value); setPage(1); }}>
                            <SelectTrigger>
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All statuses</SelectItem>
                                {(data?.statuses || ['draft', 'scheduled', 'published', 'archived']).map((item) => (
                                    <SelectItem key={item} value={item}>{item}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="space-y-3 md:hidden">
                        {isLoading ? (
                            Array(4).fill(0).map((_, index) => <Skeleton key={index} className="h-28 w-full" />)
                        ) : isError ? (
                            <p className="py-8 text-center text-red-500">Could not load notifications.</p>
                        ) : list.data.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">No notifications yet. Create one for the mobile app.</p>
                        ) : (
                            list.data.map((item) => (
                                <div key={item.id} className="rounded-lg border p-3 space-y-2">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="font-medium font-arabic" lang="sd" dir="rtl">{item.title_sd}</div>
                                            <div className="text-sm text-muted-foreground truncate">{item.title_en || '—'}</div>
                                        </div>
                                        <RowActions item={item} onPublish={publishMutation.mutate} onDelete={handleDelete} />
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="outline">
                                            {types.find((t) => t.value === item.type)?.label || item.type}
                                        </Badge>
                                        <Badge variant={statusVariant[item.status] || 'secondary'}>{item.status}</Badge>
                                        <PlatformPills platforms={item.platforms} />
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    <div className="hidden md:block rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Notification</TableHead>
                                    <TableHead>Type</TableHead>
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
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
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
                                                <div className="space-y-1 min-w-[180px]">
                                                    <div className="font-medium font-arabic" lang="sd" dir="rtl">{item.title_sd}</div>
                                                    <div className="text-sm text-muted-foreground">{item.title_en || '—'}</div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {types.find((t) => t.value === item.type)?.label || item.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <PlatformPills platforms={item.platforms} />
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={statusVariant[item.status] || 'secondary'}>{item.status}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <RowActions item={item} onPublish={publishMutation.mutate} onDelete={handleDelete} />
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {data?.notifications && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-4">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
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
