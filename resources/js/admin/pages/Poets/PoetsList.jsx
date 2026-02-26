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
import { Plus, Trash2, MoreHorizontal, Edit, Eye, EyeOff, RotateCcw, ShieldAlert } from 'lucide-react';
import { Link } from 'react-router-dom';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const PoetsList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [showTrash, setShowTrash] = useState(false);

    const debouncedSearch = useDebounce(search, 500);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['poets', page, debouncedSearch, showTrash],
        queryFn: async () => {
            const response = await api.get('/api/admin/poets', {
                params: { page, search: debouncedSearch, only_trashed: showTrash },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            return await api.delete(`/api/admin/poets/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poets']);
        },
    });

    const restoreMutation = useMutation({
        mutationFn: async (id) => {
            return await api.post(`/api/admin/poets/${id}/restore`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poets']);
        },
    });

    const permanentDeleteMutation = useMutation({
        mutationFn: async (id) => {
            return await api.delete(`/api/admin/poets/${id}/permanent`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poets']);
        },
    });

    const handleDelete = async (id) => {
        if (showTrash) {
            if (window.confirm('Are you sure you want to PERMANENTLY delete this poet? This cannot be undone.')) {
                await permanentDeleteMutation.mutateAsync(id);
            }
        } else {
            if (window.confirm('Are you sure you want to delete this poet?')) {
                await deleteMutation.mutateAsync(id);
            }
        }
    };

    const handleRestore = async (id) => {
        if (window.confirm('Are you sure you want to restore this poet?')) {
            await restoreMutation.mutateAsync(id);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div className="space-y-1">
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">
                        {showTrash ? "Poet Trash" : "Poets"}
                    </h2>
                    <p className="text-sm text-muted-foreground hidden sm:block">
                        {showTrash ? "View and restore deleted poets" : "Manage your library of poets and their metadata"}
                    </p>
                </div>
                <div className="flex flex-col sm:flex-row gap-2">
                    <Button
                        variant={showTrash ? "destructive" : "outline"}
                        onClick={() => { setShowTrash(!showTrash); setPage(1); }}
                        className="w-full sm:w-auto shadow-sm gap-2"
                    >
                        {showTrash ? <RotateCcw className="h-4 w-4" /> : <Trash2 className="h-4 w-4" />}
                        <span>{showTrash ? "Back to Active" : "Trash"}</span>
                    </Button>
                    <Button asChild className="w-full sm:w-auto">
                        <Link to="/admin/poets/create">
                            <Plus className="mr-2 h-4 w-4" /> Add Poet
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader className="space-y-1">
                    <CardTitle className="text-xl">Manage Poets</CardTitle>
                    <div className="flex items-center py-2">
                        <Input
                            placeholder="Search names or laqab..."
                            value={search}
                            onChange={(e) => {
                                setSearch(e.target.value);
                                setPage(1); // Reset to first page on search
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
                                    <TableHead className="w-[80px]">Image</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Laqab</TableHead>
                                    <TableHead className="hidden md:table-cell">Born</TableHead>
                                    <TableHead className="hidden md:table-cell">Died</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>

                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-10 w-10 rounded-full" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-20" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-red-500">
                                            Error loading poets.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
                                            No poets found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((poet) => {
                                        return (
                                            <TableRow key={poet.id}>
                                                <TableCell>
                                                    <img
                                                        src={'/' + poet.poet_pic}
                                                        alt={poet.poet_name}
                                                        className="h-10 w-10 rounded-full object-cover"
                                                        onError={(e) => e.target.src = 'https://placehold.co/40'}
                                                    />
                                                </TableCell>
                                                <TableCell className="font-medium whitespace-nowrap">
                                                    <span lang="sd">{poet.poet_name}</span>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    <span lang="sd">{poet.poet_laqab}</span>
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell">{poet.date_of_birth || '-'}</TableCell>
                                                <TableCell className="hidden md:table-cell">{poet.date_of_death || '-'}</TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">Open menu</span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                            {showTrash ? (
                                                                <>
                                                                    <DropdownMenuItem onClick={() => handleRestore(poet.id)}>
                                                                        <RotateCcw className="mr-2 h-4 w-4" /> Restore
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        className="text-destructive focus:text-destructive"
                                                                        onClick={() => handleDelete(poet.id)}
                                                                    >
                                                                        <ShieldAlert className="mr-2 h-4 w-4" /> Delete Permanently
                                                                    </DropdownMenuItem>
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <DropdownMenuItem
                                                                        onClick={() => navigator.clipboard.writeText(poet.id)}
                                                                    >
                                                                        Copy ID
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuSeparator />
                                                                    <DropdownMenuItem asChild>
                                                                        <Link to={`/admin/poets/${poet.id}/edit`}>
                                                                            <Edit className="mr-2 h-4 w-4" /> Edit
                                                                        </Link>
                                                                    </DropdownMenuItem>
                                                                    <DropdownMenuItem
                                                                        className="text-destructive focus:text-destructive"
                                                                        onClick={() => handleDelete(poet.id)}
                                                                    >
                                                                        <Trash2 className="mr-2 h-4 w-4" /> Delete
                                                                    </DropdownMenuItem>
                                                                </>
                                                            )}
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {data && (
                        <div className="flex items-center justify-end space-x-2 py-4">
                            <div className="flex-1 text-sm text-muted-foreground">
                                Showing {data.from} to {data.to} of {data.total} results
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

export default PoetsList;
