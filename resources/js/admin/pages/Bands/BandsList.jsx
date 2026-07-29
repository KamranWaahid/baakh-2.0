import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import api from '../../api/axios';
import { useDebounce } from '@/hooks/useDebounce';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious,
} from '@/components/ui/pagination';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Trash2, MoreHorizontal, Edit, Eye, EyeOff, RotateCcw, Star, Users } from 'lucide-react';
import { Link } from 'react-router-dom';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const picUrl = (path) => {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    return `/${path}`;
};

const BandsList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [showTrash, setShowTrash] = useState(false);
    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['bands', page, debouncedSearch, showTrash],
        queryFn: async () => {
            const response = await api.get('/api/admin/bands', {
                params: { page, search: debouncedSearch, only_trashed: showTrash },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const invalidate = () => queryClient.invalidateQueries({ queryKey: ['bands'] });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/bands/${id}`),
        onSuccess: invalidate,
    });
    const restoreMutation = useMutation({
        mutationFn: (id) => api.post(`/api/admin/bands/${id}/restore`),
        onSuccess: invalidate,
    });
    const permanentDeleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/bands/${id}/permanent`),
        onSuccess: invalidate,
    });
    const toggleVisibilityMutation = useMutation({
        mutationFn: (id) => api.patch(`/api/admin/bands/${id}/toggle-visibility`),
        onSuccess: invalidate,
    });
    const toggleFeaturedMutation = useMutation({
        mutationFn: (id) => api.patch(`/api/admin/bands/${id}/toggle-featured`),
        onSuccess: invalidate,
    });

    const handleDelete = async (id) => {
        if (showTrash) {
            if (window.confirm('Permanently delete this band?')) {
                await permanentDeleteMutation.mutateAsync(id);
            }
        } else if (window.confirm('Move this band to trash?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    const items = data?.data || [];
    const lastPage = data?.last_page || 1;

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
                        <Users className="h-6 w-6" /> Bands
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {showTrash ? 'Restore or permanently remove deleted bands' : 'Bands with member artists and lyrics'}
                    </p>
                </div>
                <div className="flex gap-2">
                    <Button variant={showTrash ? 'default' : 'outline'} onClick={() => { setShowTrash((v) => !v); setPage(1); }}>
                        {showTrash ? 'Active' : 'Trash'}
                    </Button>
                    {!showTrash && (
                        <Button asChild>
                            <Link to="/admin/bands/create"><Plus className="h-4 w-4 mr-1" /> Add band</Link>
                        </Button>
                    )}
                </div>
            </div>

            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-base">
                        <Input
                            placeholder="Search bands..."
                            value={search}
                            onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                            className="max-w-sm"
                        />
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Band</TableHead>
                                <TableHead>Members</TableHead>
                                <TableHead>Lyrics</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[60px]" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading && (
                                <TableRow>
                                    <TableCell colSpan={5}><Skeleton className="h-10 w-full" /></TableCell>
                                </TableRow>
                            )}
                            {isError && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-destructive">Error loading bands.</TableCell>
                                </TableRow>
                            )}
                            {!isLoading && !isError && items.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-muted-foreground">No bands found.</TableCell>
                                </TableRow>
                            )}
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <div className="h-10 w-10 rounded-md bg-muted overflow-hidden flex items-center justify-center text-sm font-arabic">
                                                {item.band_pic
                                                    ? <img src={picUrl(item.band_pic)} alt="" className="h-full w-full object-cover" />
                                                    : (item.band_name || '·').slice(0, 1)}
                                            </div>
                                            <div>
                                                <div className="font-medium font-arabic">{item.band_name}</div>
                                                <div className="text-xs text-muted-foreground">{item.band_slug}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>{item.members_count ?? 0}</TableCell>
                                    <TableCell>{item.lyrics_count ?? 0}</TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {item.visibility ? 'Visible' : 'Hidden'}
                                        {item.is_featured ? ' · Featured' : ''}
                                    </TableCell>
                                    <TableCell>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon"><MoreHorizontal className="h-4 w-4" /></Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                {!showTrash && (
                                                    <>
                                                        <DropdownMenuItem asChild>
                                                            <Link to={`/admin/bands/${item.band_slug}/edit`}>
                                                                <Edit className="h-4 w-4 mr-2" /> Edit
                                                            </Link>
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => toggleVisibilityMutation.mutate(item.id)}>
                                                            {item.visibility ? <EyeOff className="h-4 w-4 mr-2" /> : <Eye className="h-4 w-4 mr-2" />}
                                                            Toggle visibility
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem onClick={() => toggleFeaturedMutation.mutate(item.id)}>
                                                            <Star className="h-4 w-4 mr-2" /> Toggle featured
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                    </>
                                                )}
                                                {showTrash ? (
                                                    <>
                                                        <DropdownMenuItem onClick={() => restoreMutation.mutate(item.id)}>
                                                            <RotateCcw className="h-4 w-4 mr-2" /> Restore
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem className="text-destructive" onClick={() => handleDelete(item.id)}>
                                                            <Trash2 className="h-4 w-4 mr-2" /> Delete forever
                                                        </DropdownMenuItem>
                                                    </>
                                                ) : (
                                                    <DropdownMenuItem className="text-destructive" onClick={() => handleDelete(item.id)}>
                                                        <Trash2 className="h-4 w-4 mr-2" /> Trash
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {lastPage > 1 && (
                        <Pagination className="mt-4">
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious href="#" onClick={(e) => { e.preventDefault(); setPage((p) => Math.max(1, p - 1)); }} />
                                </PaginationItem>
                                <PaginationItem>
                                    <PaginationLink isActive>{page}</PaginationLink>
                                </PaginationItem>
                                <PaginationItem>
                                    <PaginationNext href="#" onClick={(e) => { e.preventDefault(); setPage((p) => Math.min(lastPage, p + 1)); }} />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    )}
                </CardContent>
            </Card>
        </div>
    );
};

export default BandsList;
