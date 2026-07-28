import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import api from '../../api/axios';
import { useDebounce } from '@/hooks/useDebounce';
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
import { Plus, Trash2, MoreHorizontal, Edit, Eye, EyeOff, RotateCcw, Star, Mic2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const picUrl = (path) => {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    return `/${path}`;
};

const SingersList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [showTrash, setShowTrash] = useState(false);
    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['singers', page, debouncedSearch, showTrash],
        queryFn: async () => {
            const response = await api.get('/api/admin/singers', {
                params: { page, search: debouncedSearch, only_trashed: showTrash },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const invalidate = () => queryClient.invalidateQueries({ queryKey: ['singers'] });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/singers/${id}`),
        onSuccess: invalidate,
    });

    const restoreMutation = useMutation({
        mutationFn: (id) => api.post(`/api/admin/singers/${id}/restore`),
        onSuccess: invalidate,
    });

    const permanentDeleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/singers/${id}/permanent`),
        onSuccess: invalidate,
    });

    const toggleVisibilityMutation = useMutation({
        mutationFn: (id) => api.patch(`/api/admin/singers/${id}/toggle-visibility`),
        onSuccess: invalidate,
    });

    const toggleFeaturedMutation = useMutation({
        mutationFn: (id) => api.patch(`/api/admin/singers/${id}/toggle-featured`),
        onSuccess: invalidate,
    });

    const handleDelete = async (id) => {
        if (showTrash) {
            if (window.confirm('Permanently delete this singer? This cannot be undone.')) {
                await permanentDeleteMutation.mutateAsync(id);
            }
        } else if (window.confirm('Move this singer to trash?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    const handleRestore = async (id) => {
        if (window.confirm('Restore this singer?')) {
            await restoreMutation.mutateAsync(id);
        }
    };

    const lastPage = data?.last_page || 1;

    return (
        <div className="min-w-0 w-full max-w-full space-y-4 p-0">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="space-y-1 min-w-0">
                    <h2 className="text-2xl font-bold tracking-tight md:text-3xl">
                        {showTrash ? 'Singers Trash' : 'Singers'}
                    </h2>
                    <p className="hidden text-sm text-muted-foreground sm:block">
                        {showTrash
                            ? 'Restore or permanently remove deleted singers'
                            : 'Manage singers linked to Sindhi song lyrics'}
                    </p>
                </div>
                <div className="flex flex-col gap-2 sm:flex-row">
                    <Button
                        variant={showTrash ? 'destructive' : 'outline'}
                        onClick={() => { setShowTrash(!showTrash); setPage(1); }}
                        className="w-full sm:w-auto"
                    >
                        {showTrash ? <RotateCcw className="mr-2 h-4 w-4" /> : <Trash2 className="mr-2 h-4 w-4" />}
                        {showTrash ? 'Back to Active' : 'Trash'}
                    </Button>
                    {!showTrash && (
                        <Button asChild className="w-full sm:w-auto">
                            <Link to="/admin/singers/create">
                                <Plus className="mr-2 h-4 w-4" /> Add Singer
                            </Link>
                        </Button>
                    )}
                </div>
            </div>

            <Card className="min-w-0 overflow-hidden">
                <CardHeader className="space-y-3">
                    <CardTitle className="flex items-center gap-2 text-lg sm:text-xl">
                        <Mic2 className="h-5 w-5" /> Manage Singers
                    </CardTitle>
                    <Input
                        placeholder="Search name, stage name, or slug…"
                        value={search}
                        onChange={(e) => {
                            setSearch(e.target.value);
                            setPage(1);
                        }}
                        className="max-w-full sm:max-w-sm"
                    />
                </CardHeader>
                <CardContent className="min-w-0 space-y-4">
                    <div className="overflow-x-auto rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[180px]">Singer</TableHead>
                                    <TableHead className="hidden md:table-cell">Slug</TableHead>
                                    <TableHead className="hidden lg:table-cell">Lyrics</TableHead>
                                    <TableHead className="hidden sm:table-cell">Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, i) => (
                                        <TableRow key={i}>
                                            <TableCell><Skeleton className="h-10 w-40" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="hidden lg:table-cell"><Skeleton className="h-4 w-10" /></TableCell>
                                            <TableCell className="hidden sm:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="ml-auto h-8 w-8" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-red-500">
                                            Error loading singers.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center">
                                            No singers found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    {item.singer_pic ? (
                                                        <img
                                                            src={picUrl(item.singer_pic)}
                                                            alt=""
                                                            className="h-10 w-10 shrink-0 rounded-full border object-cover"
                                                        />
                                                    ) : (
                                                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border bg-muted">
                                                            <Mic2 className="h-4 w-4 text-muted-foreground" />
                                                        </div>
                                                    )}
                                                    <div className="min-w-0 text-right font-arabic" dir="rtl">
                                                        <div className="truncate font-medium">
                                                            {item.singer_laqab || item.singer_name}
                                                        </div>
                                                        {item.singer_laqab && item.singer_name && (
                                                            <div className="truncate text-xs text-muted-foreground">
                                                                {item.singer_name}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden font-mono text-xs text-muted-foreground md:table-cell">
                                                {item.singer_slug}
                                            </TableCell>
                                            <TableCell className="hidden text-sm text-muted-foreground lg:table-cell">
                                                {item.lyrics_count || 0}
                                            </TableCell>
                                            <TableCell className="hidden sm:table-cell">
                                                <div className="flex items-center gap-2">
                                                    {item.visibility ? (
                                                        <span className="flex items-center gap-1 text-xs text-emerald-600">
                                                            <Eye className="h-3 w-3" /> Public
                                                        </span>
                                                    ) : (
                                                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <EyeOff className="h-3 w-3" /> Hidden
                                                        </span>
                                                    )}
                                                    {item.is_featured ? (
                                                        <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                                                    ) : null}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="h-8 w-8">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                        <DropdownMenuSeparator />
                                                        {showTrash ? (
                                                            <>
                                                                <DropdownMenuItem onClick={() => handleRestore(item.id)}>
                                                                    <RotateCcw className="mr-2 h-4 w-4" /> Restore
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem
                                                                    className="text-destructive"
                                                                    onClick={() => handleDelete(item.id)}
                                                                >
                                                                    <Trash2 className="mr-2 h-4 w-4" /> Delete forever
                                                                </DropdownMenuItem>
                                                            </>
                                                        ) : (
                                                            <>
                                                                <DropdownMenuItem asChild>
                                                                    <Link to={`/admin/singers/${item.singer_slug}/edit`}>
                                                                        <Edit className="mr-2 h-4 w-4" /> Edit
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => toggleVisibilityMutation.mutate(item.id)}>
                                                                    {item.visibility
                                                                        ? <EyeOff className="mr-2 h-4 w-4" />
                                                                        : <Eye className="mr-2 h-4 w-4" />}
                                                                    {item.visibility ? 'Hide' : 'Show'}
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => toggleFeaturedMutation.mutate(item.id)}>
                                                                    <Star className="mr-2 h-4 w-4" />
                                                                    {item.is_featured ? 'Unfeature' : 'Feature'}
                                                                </DropdownMenuItem>
                                                                <DropdownMenuSeparator />
                                                                <DropdownMenuItem
                                                                    className="text-destructive"
                                                                    onClick={() => handleDelete(item.id)}
                                                                >
                                                                    <Trash2 className="mr-2 h-4 w-4" /> Trash
                                                                </DropdownMenuItem>
                                                            </>
                                                        )}
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {data && lastPage > 1 && (
                        <Pagination>
                            <PaginationContent className="flex-wrap">
                                <PaginationItem>
                                    <PaginationPrevious
                                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                                        className={page <= 1 ? 'pointer-events-none opacity-50' : 'cursor-pointer'}
                                    />
                                </PaginationItem>
                                {Array.from({ length: Math.min(lastPage, 5) }, (_, i) => {
                                    const pageNum = lastPage <= 5
                                        ? i + 1
                                        : Math.min(Math.max(page - 2, 1), lastPage - 4) + i;
                                    return (
                                        <PaginationItem key={pageNum}>
                                            <PaginationLink
                                                isActive={page === pageNum}
                                                onClick={() => setPage(pageNum)}
                                                className="cursor-pointer"
                                            >
                                                {pageNum}
                                            </PaginationLink>
                                        </PaginationItem>
                                    );
                                })}
                                <PaginationItem>
                                    <PaginationNext
                                        onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                                        className={page >= lastPage ? 'pointer-events-none opacity-50' : 'cursor-pointer'}
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default SingersList;
