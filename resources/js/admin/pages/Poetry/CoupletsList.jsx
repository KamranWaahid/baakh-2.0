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
import { Plus, Trash2, Eye, EyeOff, Star, Edit, Link as LinkIcon, Unlink, Search, RotateCcw, ShieldAlert, Trash } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useDebounce } from '@/hooks/useDebounce';
import { Badge } from '../../../components/ui/badge';
import { format } from 'date-fns';

const CoupletsList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [showTrash, setShowTrash] = useState(false);
    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['couplets', page, debouncedSearch, showTrash],
        queryFn: async () => {
            const response = await api.get('/api/admin/couplets', {
                params: {
                    page,
                    search: debouncedSearch,
                    only_trashed: showTrash
                },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    // Delete acts on the POETRY (work) ID or Couplet ID
    const deleteMutation = useMutation({
        mutationFn: async (item) => {
            const id = item.poetry?.id || item.id;
            const type = item.poetry ? 'poetry' : 'couplets';
            return await api.delete(`/api/admin/${type}/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const toggleVisibilityMutation = useMutation({
        mutationFn: async (item) => {
            const id = item.poetry?.id || item.id;
            const type = item.poetry ? 'poetry' : 'couplets';
            return await api.patch(`/api/admin/${type}/${id}/toggle-visibility`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const toggleFeaturedMutation = useMutation({
        mutationFn: async (item) => {
            const id = item.poetry?.id || item.id;
            const type = item.poetry ? 'poetry' : 'couplets';
            return await api.patch(`/api/admin/${type}/${id}/toggle-featured`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const restoreMutation = useMutation({
        mutationFn: async (item) => {
            const id = item.poetry?.id || item.id;
            const type = item.poetry ? 'poetry' : 'couplets';
            return await api.post(`/api/admin/${type}/${id}/restore`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const permanentDeleteMutation = useMutation({
        mutationFn: async (item) => {
            const id = item.poetry?.id || item.id;
            const type = item.poetry ? 'poetry' : 'couplets';
            return await api.delete(`/api/admin/${type}/${id}/permanent`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['couplets']);
        },
    });

    const handleDelete = async (item) => {
        if (showTrash) {
            if (window.confirm('Are you sure you want to PERMANENTLY delete this content? This cannot be undone.')) {
                await permanentDeleteMutation.mutateAsync(item);
            }
        } else {
            if (window.confirm('Are you sure you want to move this content to trash?')) {
                await deleteMutation.mutateAsync(item);
            }
        }
    };

    const handleRestore = async (item) => {
        if (window.confirm('Are you sure you want to restore this content?')) {
            await restoreMutation.mutateAsync(item);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="space-y-1">
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight text-gray-900">
                        {showTrash ? "Trash Management" : "Couplets"}
                    </h2>
                    <p className="text-sm text-gray-500 hidden sm:block">
                        {showTrash ? "View and restore deleted couplets" : "Manage your library of poetic couplets and their metadata"}
                    </p>
                </div>
                <div className="flex flex-col sm:flex-row gap-2">
                    <Button
                        variant={showTrash ? "destructive" : "outline"}
                        onClick={() => { setShowTrash(!showTrash); setPage(1); }}
                        className="w-full sm:w-auto h-10 shadow-sm gap-2"
                    >
                        {showTrash ? <RotateCcw className="h-4 w-4" /> : <Trash2 className="h-4 w-4" />}
                        <span>{showTrash ? "Back to Active" : "Trash"}</span>
                    </Button>
                    <Button asChild className="w-full sm:w-auto h-10 shadow-sm">
                        <Link to="/admin/couplet/create" className="flex items-center justify-center gap-2">
                            <Plus className="h-4 w-4" />
                            <span>Add Couplet</span>
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardContent>
                    <div className="flex items-center pb-6">
                        <Input
                            placeholder="Search couplet text or poets..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1);
                            }}
                            className="max-w-full sm:max-w-sm h-10 border-gray-200"
                        />
                    </div>

                    {/* Mobile Card View */}
                    <div className="grid grid-cols-1 gap-4 md:hidden">
                        {isLoading ? (
                            Array(3).fill(0).map((_, i) => (
                                <div key={i} className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-3">
                                    <Skeleton className="h-6 w-3/4 ml-auto" />
                                    <Skeleton className="h-6 w-1/2 ml-auto" />
                                    <div className="pt-3 border-t flex justify-between">
                                        <Skeleton className="h-4 w-20" />
                                        <Skeleton className="h-4 w-20" />
                                    </div>
                                </div>
                            ))
                        ) : isError ? (
                            <div className="py-12 text-center text-red-500 font-medium">Error loading couplets.</div>
                        ) : data?.data?.length === 0 ? (
                            <div className="py-12 text-center text-gray-400 italic">No couplets found.</div>
                        ) : (
                            data?.data?.map((c) => (
                                <div key={c.id} className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm space-y-4">
                                    <div className="space-y-2">
                                        {c.couplet_text?.split('\n').slice(0, 2).map((line, i) => (
                                            <div key={i} className="text-xl md:text-2xl leading-relaxed text-right font-arabic text-gray-900" dir="rtl" lang="sd">
                                                {line}
                                            </div>
                                        ))}
                                    </div>

                                    <div className="pt-3 border-t flex items-center justify-between">
                                        <div className="flex flex-col gap-1">
                                            <span className="text-sm font-medium text-gray-700" lang="sd">{c.poet_details?.poet_laqab || 'N/A'}</span>
                                            <div className="flex items-center gap-1.5">
                                                {c.poetry ? (
                                                    <Badge variant={c.poetry.visibility === 1 ? "outline" : "secondary"} className="text-[9px] uppercase h-4 px-1.5">
                                                        {c.poetry.visibility === 1 ? 'Visible' : 'Hidden'}
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="text-[9px] uppercase h-4 px-1.5 border-green-200 text-green-700 bg-green-50">
                                                        Active
                                                    </Badge>
                                                )}
                                                {c.poetry?.is_featured === 1 && (
                                                    <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-1">
                                            {showTrash ? (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-9 w-9 text-green-600 hover:text-green-700"
                                                        onClick={() => handleRestore(c)}
                                                        title="Restore"
                                                    >
                                                        <RotateCcw className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-9 w-9 text-destructive"
                                                        onClick={() => handleDelete(c)}
                                                        title="Delete Permanently"
                                                    >
                                                        <ShieldAlert className="h-4 w-4" />
                                                    </Button>
                                                </>
                                            ) : (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-9 w-9"
                                                        onClick={() => toggleVisibilityMutation.mutate(c)}
                                                    >
                                                        {((c.poetry?.visibility ?? c.visibility) === 1 || (c.poetry?.visibility ?? c.visibility) === true) ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                                                    </Button>
                                                    <Button variant="ghost" size="icon" className="h-9 w-9" asChild>
                                                        <Link to={c.poetry?.category_id ? `/admin/poetry/${c.poetry.poetry_slug}/edit` : `/admin/couplet/${c.poetry?.poetry_slug || c.id}/edit`}>
                                                            <Edit className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-9 w-9 text-destructive"
                                                        onClick={() => handleDelete(c)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>

                    {/* Desktop Table View */}
                    <div className="hidden md:block rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <Table>
                            <TableHeader className="bg-gray-50/50">
                                <TableRow>
                                    <TableHead className="w-[80px] hidden sm:table-cell">ID</TableHead>
                                    <TableHead className="min-w-[300px]">Couplet</TableHead>
                                    <TableHead>Poet</TableHead>
                                    <TableHead className="hidden md:table-cell">Type</TableHead>
                                    <TableHead className="hidden lg:table-cell">Languages</TableHead>
                                    <TableHead className="hidden md:table-cell">Status</TableHead>
                                    <TableHead className="hidden xl:table-cell">Added By</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="hidden sm:table-cell"><Skeleton className="h-4 w-12" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-48" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell className="hidden lg:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="hidden xl:table-cell"><Skeleton className="h-4 w-24" /></TableCell>
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
                                        <TableRow key={c.id} className="hover:bg-gray-50/50 transition-colors">
                                            <TableCell className="font-mono text-xs hidden sm:table-cell text-gray-400">{c.id}</TableCell>
                                            <TableCell className="font-medium">
                                                <div className="space-y-1 py-1">
                                                    {c.couplet_text?.split('\n').slice(0, 2).map((line, i) => (
                                                        <div key={i} className="text-lg leading-relaxed text-right font-arabic text-gray-900" dir="rtl" lang="sd">
                                                            {line}
                                                        </div>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                <span lang="sd" className="font-medium text-gray-700">{c.poet_details?.poet_laqab || 'N/A'}</span>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                {c.poetry?.category?.detail?.cat_name ? (
                                                    <Badge variant="secondary" className="flex items-center gap-1 w-fit font-normal text-[10px] uppercase">
                                                        <LinkIcon className="h-3 w-3" />
                                                        Linked
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="flex items-center gap-1 w-fit font-normal text-[10px] uppercase border-gray-100">
                                                        <Unlink className="h-3 w-3" />
                                                        Independent
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <div className="flex flex-wrap gap-1">
                                                    {['sd', ...new Set(c.poetry?.translations?.map(t => t.lang) || [])].filter(l => l && l !== 'sd').length > 0 ? (
                                                        <>
                                                            <Badge variant="outline" className="text-[10px] uppercase h-5 font-normal">SD</Badge>
                                                            {c.poetry?.translations?.map(t => t.lang).filter(l => l !== 'sd').map(lang => (
                                                                <Badge key={lang} variant="outline" className="text-[10px] uppercase h-5 font-normal">
                                                                    {lang}
                                                                </Badge>
                                                            ))}
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Badge variant="outline" className="text-[10px] uppercase h-5 font-normal">SD</Badge>
                                                            {c.has_roman > 0 && (
                                                                <Badge variant="outline" className="text-[10px] uppercase h-5 font-normal">EN</Badge>
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <div className="flex items-center gap-2">
                                                    {c.poetry ? (
                                                        <>
                                                            {c.poetry.visibility === 1 ? (
                                                                <Badge variant="default" className="text-[10px] uppercase h-5 font-normal">Visible</Badge>
                                                            ) : (
                                                                <Badge variant="secondary" className="text-[10px] uppercase h-5 font-normal">Hidden</Badge>
                                                            )}
                                                            {c.poetry.is_featured === 1 && (
                                                                <Star className="h-3.5 w-3.5 fill-yellow-400 text-yellow-400" />
                                                            )}
                                                        </>
                                                    ) : (
                                                        <>
                                                            {((c.visibility ?? 1) === 1 || (c.visibility ?? 1) === true) ? (
                                                                <Badge variant="outline" className="text-[10px] uppercase h-5 font-normal border-green-200 text-green-700 bg-green-50">Visible</Badge>
                                                            ) : (
                                                                <Badge variant="secondary" className="text-[10px] uppercase h-5 font-normal">Hidden</Badge>
                                                            )}
                                                            {((c.is_featured ?? 0) === 1 || (c.is_featured ?? 0) === true) && (
                                                                <Star className="h-3.5 w-3.5 fill-yellow-400 text-yellow-400" />
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden xl:table-cell">
                                                <div className="text-xs">
                                                    <div className="font-medium text-gray-700">{c.poetry?.user?.name || 'System'}</div>
                                                    <div className="text-gray-400">
                                                        {format(new Date(c.created_at), 'MMM d, yyyy')}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap">
                                                <div className="flex justify-end items-center gap-1">
                                                    {showTrash ? (
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 text-green-600 hover:text-green-700"
                                                                onClick={() => handleRestore(c)}
                                                                title="Restore"
                                                            >
                                                                <RotateCcw className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 text-destructive hover:bg-destructive/10"
                                                                onClick={() => handleDelete(c)}
                                                                title="Delete Permanently"
                                                            >
                                                                <ShieldAlert className="h-4 w-4" />
                                                            </Button>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 opacity-60 hover:opacity-100"
                                                                onClick={() => toggleVisibilityMutation.mutate(c)}
                                                                title={((c.poetry?.visibility ?? c.visibility) === 1 || (c.poetry?.visibility ?? c.visibility) === true) ? "Hide" : "Show"}
                                                            >
                                                                {((c.poetry?.visibility ?? c.visibility) === 1 || (c.poetry?.visibility ?? c.visibility) === true) ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 opacity-60 hover:opacity-100"
                                                                onClick={() => toggleFeaturedMutation.mutate(c)}
                                                                title={((c.poetry?.is_featured ?? c.is_featured) === 1 || (c.poetry?.is_featured ?? c.is_featured) === true) ? "Unfeature" : "Feature"}
                                                            >
                                                                <Star className={`h-4 w-4 ${((c.poetry?.is_featured ?? c.is_featured) === 1 || (c.poetry?.is_featured ?? c.is_featured) === true) ? 'fill-yellow-400 text-yellow-400' : ''}`} />
                                                            </Button>
                                                            <Button variant="ghost" size="icon" className="h-8 w-8 opacity-60 hover:opacity-100" asChild>
                                                                <Link to={c.poetry?.category_id ? `/admin/poetry/${c.poetry.poetry_slug}/edit` : `/admin/couplet/${c.poetry?.poetry_slug || c.id}/edit`}>
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 text-destructive opacity-60 hover:opacity-100 hover:bg-destructive/10"
                                                                onClick={() => handleDelete(c)}
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {data && (
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-4">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
                                Showing {data.from || 0} to {data.to || 0} of {data.total || 0} results
                            </div>
                            <div className="flex items-center space-x-2">
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
