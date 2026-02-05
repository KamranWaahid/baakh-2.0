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
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Trash2, Eye, EyeOff, Star, Edit } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useDebounce } from '@/hooks/useDebounce';

const PoetryList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['poetry', page, debouncedSearch],
        queryFn: async () => {
            const response = await api.get('/api/admin/poetry', {
                params: { page, search: debouncedSearch },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            return await api.delete(`/api/admin/poetry/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poetry']);
        },
    });

    const toggleVisibilityMutation = useMutation({
        mutationFn: async (id) => {
            return await api.patch(`/api/admin/poetry/${id}/toggle-visibility`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poetry']);
        },
    });

    const toggleFeaturedMutation = useMutation({
        mutationFn: async (id) => {
            return await api.patch(`/api/admin/poetry/${id}/toggle-featured`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poetry']);
        },
    });

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to move this poetry to trash?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Poetry</h2>
                <Button asChild>
                    <Link to="/poetry/create">
                        <Plus className="mr-2 h-4 w-4" /> Add Poetry
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Manage Poetry</CardTitle>
                    <div className="flex items-center py-4">
                        <Input
                            placeholder="Search titles or poets..."
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
                                    <TableHead>Title</TableHead>
                                    <TableHead>Poet</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Added By</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-48" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-24 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-red-500">
                                            Error loading poetry.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
                                            No poetry found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((p) => (
                                        <TableRow key={p.id}>
                                            <TableCell className="font-medium">
                                                <span lang="sd">{p.info?.title || 'Untitled'}</span>
                                            </TableCell>
                                            <TableCell>
                                                <span lang="sd">{p.poet_details?.poet_laqab || 'N/A'}</span>
                                            </TableCell>
                                            <TableCell>
                                                {p.category?.detail?.cat_name || 'N/A'}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    {p.visibility === 1 ? (
                                                        <span className="text-green-600 text-xs font-semibold">Visible</span>
                                                    ) : (
                                                        <span className="text-muted-foreground text-xs font-semibold">Hidden</span>
                                                    )}
                                                    {p.is_featured === 1 && (
                                                        <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-sm">
                                                    <div>{p.user?.name || 'System'}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {new Date(p.created_at).toLocaleDateString()}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right space-x-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => toggleVisibilityMutation.mutate(p.id)}
                                                    title={p.visibility === 1 ? "Hide" : "Show"}
                                                >
                                                    {p.visibility === 1 ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => toggleFeaturedMutation.mutate(p.id)}
                                                    title={p.is_featured === 1 ? "Unfeature" : "Feature"}
                                                >
                                                    <Star className={`h-4 w-4 ${p.is_featured === 1 ? 'fill-yellow-400 text-yellow-400' : ''}`} />
                                                </Button>
                                                <Button variant="ghost" size="icon" asChild>
                                                    <Link to={`/admin/new/poetry/${p.id}/edit`}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => handleDelete(p.id)}
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

export default PoetryList;
