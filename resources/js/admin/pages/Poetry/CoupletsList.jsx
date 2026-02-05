import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import api from '../../api/axios';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Trash2, Eye, EyeOff, Star, Edit, Link as LinkIcon, Unlink } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useDebounce } from '@/hooks/useDebounce';
import { Badge } from '../../../components/ui/badge';

const CoupletsList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['couplets', page, debouncedSearch],
        queryFn: async () => {
            const response = await api.get('/api/admin/couplets', {
                params: {
                    page,
                    search: debouncedSearch
                },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    // Delete acts on the POETRY (work) ID for now
    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            return await api.delete(`/api/admin/poetry/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const toggleVisibilityMutation = useMutation({
        mutationFn: async (id) => {
            return await api.patch(`/api/admin/poetry/${id}/toggle-visibility`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const toggleFeaturedMutation = useMutation({
        mutationFn: async (id) => {
            return await api.patch(`/api/admin/poetry/${id}/toggle-featured`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to move this content to trash?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Couplets</h2>
                <Button asChild>
                    <Link to="/couplet/create">
                        <Plus className="mr-2 h-4 w-4" /> Add Couplet
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Manage Couplets</CardTitle>
                    <div className="flex items-center py-4">
                        <Input
                            placeholder="Search couplet text or poets..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            className="max-w-sm"
                        />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[80px]">ID</TableHead>
                                    <TableHead>Couplet</TableHead>
                                    <TableHead>Poet</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Languages</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Added By</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-12" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-48" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-24 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="h-24 text-center text-red-500">
                                            Error loading couplets.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="h-24 text-center">
                                            No couplets found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((c) => (
                                        <TableRow key={c.id}>
                                            <TableCell className="font-mono text-xs">{c.id}</TableCell>
                                            <TableCell className="font-medium">
                                                <div className="space-y-1">
                                                    {c.couplet_text?.split('\n').slice(0, 2).map((line, i) => (
                                                        <div key={i} className="text-base font-arabic leading-relaxed text-right" dir="rtl">
                                                            {line}
                                                        </div>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell>{c.poet_details?.poet_laqab || 'N/A'}</TableCell>
                                            <TableCell>
                                                {c.poetry?.category?.detail?.cat_name ? (
                                                    <Badge variant="secondary" className="flex items-center gap-1 w-fit">
                                                        <LinkIcon className="h-3 w-3" />
                                                        Linked
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="flex items-center gap-1 w-fit">
                                                        <Unlink className="h-3 w-3" />
                                                        Independent
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {['sd', ...new Set(c.poetry?.translations?.map(t => t.lang) || [])].filter(l => l && l !== 'sd').length > 0 ? (
                                                        <>
                                                            <Badge variant="outline" className="text-[10px] uppercase">SD</Badge>
                                                            {c.poetry?.translations?.map(t => t.lang).filter(l => l !== 'sd').map(lang => (
                                                                <Badge key={lang} variant="outline" className="text-[10px] uppercase">
                                                                    {lang}
                                                                </Badge>
                                                            ))}
                                                        </>
                                                    ) : (
                                                        <Badge variant="outline" className="text-[10px] uppercase">SD</Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    {c.poetry?.visibility === 1 ? (
                                                        <span className="text-green-600 text-xs font-semibold">Visible</span>
                                                    ) : (
                                                        <span className="text-muted-foreground text-xs font-semibold">Hidden</span>
                                                    )}
                                                    {c.poetry?.is_featured === 1 && (
                                                        <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    <div>{c.poetry?.user?.name || 'System'}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {new Date(c.created_at).toLocaleDateString()}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right space-x-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => c.poetry && toggleVisibilityMutation.mutate(c.poetry.id)}
                                                    title={c.poetry?.visibility === 1 ? "Hide" : "Show"}
                                                    disabled={!c.poetry}
                                                >
                                                    {c.poetry?.visibility === 1 ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => c.poetry && toggleFeaturedMutation.mutate(c.poetry.id)}
                                                    title={c.poetry?.is_featured === 1 ? "Unfeature" : "Feature"}
                                                    disabled={!c.poetry}
                                                >
                                                    <Star className={`h-4 w-4 ${c.poetry?.is_featured === 1 ? 'fill-yellow-400 text-yellow-400' : ''}`} />
                                                </Button>
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link to={c.poetry?.category_id ? `/poetry/${c.poetry?.id}/edit` : `/couplet/${c.poetry?.id}/edit`}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => c.poetry && handleDelete(c.poetry.id)}
                                                    disabled={!c.poetry}
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

                    {data && (
                        <div className="flex items-center justify-end space-x-2 py-4">
                            <div className="flex-1 text-sm text-muted-foreground">
                                Showing {data.from || 0} to {data.to || 0} of {data.total || 0} results
                            </div>
                            <div className="space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPage(p => Math.max(1, p - 1))}
                                    disabled={!data.prev_page_url}
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPage(p => p + 1)}
                                    disabled={!data.next_page_url}
                                >
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

export default CoupletsList;
