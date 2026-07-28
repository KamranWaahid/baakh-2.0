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
import { Plus, Trash2, Eye, EyeOff, Star, Edit, MoreHorizontal, RotateCcw, Music2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useDebounce } from '@/hooks/useDebounce';

const LyricsList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [showTrash, setShowTrash] = useState(false);
    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['lyrics', page, debouncedSearch, showTrash],
        queryFn: async () => {
            const response = await api.get('/api/admin/lyrics', {
                params: { page, search: debouncedSearch, only_trashed: showTrash },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const invalidate = () => queryClient.invalidateQueries({ queryKey: ['lyrics'] });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/lyrics/${id}`),
        onSuccess: invalidate,
    });

    const toggleVisibilityMutation = useMutation({
        mutationFn: (id) => api.patch(`/api/admin/lyrics/${id}/toggle-visibility`),
        onSuccess: invalidate,
    });

    const toggleFeaturedMutation = useMutation({
        mutationFn: (id) => api.patch(`/api/admin/lyrics/${id}/toggle-featured`),
        onSuccess: invalidate,
    });

    const restoreMutation = useMutation({
        mutationFn: (id) => api.post(`/api/admin/lyrics/${id}/restore`),
        onSuccess: invalidate,
    });

    const permanentDeleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/lyrics/${id}/permanent`),
        onSuccess: invalidate,
    });

    const handleDelete = async (id) => {
        if (showTrash) {
            if (window.confirm('Permanently delete this lyrics entry? This cannot be undone.')) {
                await permanentDeleteMutation.mutateAsync(id);
            }
        } else if (window.confirm('Move this lyrics entry to trash?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    const handleRestore = async (id) => {
        if (window.confirm('Restore this lyrics entry?')) {
            await restoreMutation.mutateAsync(id);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="space-y-1">
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                        {showTrash ? 'Lyrics Trash' : 'Lyrics'}
                    </h2>
                    <p className="text-sm text-muted-foreground hidden sm:block">
                        {showTrash
                            ? 'Restore or permanently remove deleted lyrics'
                            : 'Sindhi song lyrics with sung parts, couplets, spoken lines, explanations and music'}
                    </p>
                </div>
                <div className="flex flex-col sm:flex-row gap-2">
                    <Button
                        variant={showTrash ? 'destructive' : 'outline'}
                        onClick={() => { setShowTrash(!showTrash); setPage(1); }}
                        className="w-full sm:w-auto"
                    >
                        {showTrash ? <RotateCcw className="mr-2 h-4 w-4" /> : <Trash2 className="mr-2 h-4 w-4" />}
                        {showTrash ? 'Back to Active' : 'Trash'}
                    </Button>
                    <Button asChild className="w-full sm:w-auto">
                        <Link to="/admin/lyrics/create">
                            <Plus className="mr-2 h-4 w-4" /> Add Lyrics
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader className="space-y-1">
                    <CardTitle className="text-xl flex items-center gap-2">
                        <Music2 className="h-5 w-5" /> Manage Lyrics
                    </CardTitle>
                    <div className="flex items-center py-2">
                        <Input
                            placeholder="Search titles, singers, or text..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            className="max-w-full sm:max-w-sm"
                        />
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[200px]">Title</TableHead>
                                    <TableHead>Singer</TableHead>
                                    <TableHead className="hidden lg:table-cell">Parts</TableHead>
                                    <TableHead className="hidden md:table-cell">Status</TableHead>
                                    <TableHead className="hidden xl:table-cell">Added By</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-48" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="hidden lg:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="hidden xl:table-cell"><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-24 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-red-500">
                                            Error loading lyrics.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
                                            No lyrics found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="font-medium">
                                                <div className="flex items-center gap-3">
                                                    {item.cover_image ? (
                                                        <img
                                                            src={item.cover_image.startsWith('http') || item.cover_image.startsWith('/')
                                                                ? item.cover_image
                                                                : `/${item.cover_image}`}
                                                            alt=""
                                                            className="h-10 w-10 rounded-md object-cover border shrink-0"
                                                        />
                                                    ) : (
                                                        <div className="h-10 w-10 rounded-md border bg-muted flex items-center justify-center shrink-0">
                                                            <Music2 className="h-4 w-4 text-muted-foreground" />
                                                        </div>
                                                    )}
                                                    <span className="font-arabic text-right whitespace-nowrap" dir="rtl">
                                                        {item.info?.title || item.lyrics_slug}
                                                    </span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-arabic" dir="rtl">
                                                {item.singer_name || '—'}
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell text-muted-foreground text-sm">
                                                <div className="flex items-center gap-2">
                                                    <span>{item.parts_count || 0}</span>
                                                    {item.poets_count > 0 && (
                                                        <span className="text-xs">· {item.poets_count} poets</span>
                                                    )}
                                                    {item.has_music && (
                                                        <Music2 className="h-3.5 w-3.5 text-foreground/70" title="Has music" />
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <div className="flex items-center gap-2">
                                                    {item.visibility ? (
                                                        <span className="text-xs text-emerald-600 flex items-center gap-1"><Eye className="h-3 w-3" /> Public</span>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground flex items-center gap-1"><EyeOff className="h-3 w-3" /> Hidden</span>
                                                    )}
                                                    {item.is_featured ? <Star className="h-3.5 w-3.5 fill-amber-400 text-amber-400" /> : null}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden xl:table-cell text-sm text-muted-foreground">
                                                {item.user?.name || '—'}
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
                                                                    <Link to={`/admin/lyrics/${item.lyrics_slug}/edit`}>
                                                                        <Edit className="mr-2 h-4 w-4" /> Edit
                                                                    </Link>
                                                                </DropdownMenuItem>
                                                                <DropdownMenuItem onClick={() => toggleVisibilityMutation.mutate(item.id)}>
                                                                    {item.visibility ? <EyeOff className="mr-2 h-4 w-4" /> : <Eye className="mr-2 h-4 w-4" />}
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

                    {data?.last_page > 1 && (
                        <Pagination className="mt-4">
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        onClick={() => setPage((p) => Math.max(1, p - 1))}
                                        className={page <= 1 ? 'pointer-events-none opacity-50' : 'cursor-pointer'}
                                    />
                                </PaginationItem>
                                {Array.from({ length: data.last_page }, (_, i) => i + 1)
                                    .filter((p) => p === 1 || p === data.last_page || Math.abs(p - page) <= 1)
                                    .map((p, idx, arr) => (
                                        <React.Fragment key={p}>
                                            {idx > 0 && arr[idx - 1] !== p - 1 && (
                                                <PaginationItem><span className="px-2">…</span></PaginationItem>
                                            )}
                                            <PaginationItem>
                                                <PaginationLink
                                                    isActive={p === page}
                                                    onClick={() => setPage(p)}
                                                    className="cursor-pointer"
                                                >
                                                    {p}
                                                </PaginationLink>
                                            </PaginationItem>
                                        </React.Fragment>
                                    ))}
                                <PaginationItem>
                                    <PaginationNext
                                        onClick={() => setPage((p) => Math.min(data.last_page, p + 1))}
                                        className={page >= data.last_page ? 'pointer-events-none opacity-50' : 'cursor-pointer'}
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

export default LyricsList;
