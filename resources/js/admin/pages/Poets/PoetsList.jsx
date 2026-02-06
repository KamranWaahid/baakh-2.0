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
import { Plus, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';

const PoetsList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');

    console.log('PoetsList: Rendering');

    // const debouncedSearch = useDebounce(search, 500);
    const debouncedSearch = search;

    const { data, isLoading, isError } = useQuery({
        queryKey: ['poets', page, debouncedSearch],
        queryFn: async () => {
            const response = await api.get('/api/admin/poets', {
                params: { page, search: debouncedSearch },
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

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to delete this poet?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Poets</h2>
                <Button asChild className="w-full sm:w-auto">
                    <Link to="/poets/create">
                        <Plus className="mr-2 h-4 w-4" /> Add Poet
                    </Link>
                </Button>
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
                                                <TableCell className="text-right space-x-2">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link to={`/poets/${poet.id}/edit`}>Edit</Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        onClick={() => handleDelete(poet.id)}
                                                        disabled={deleteMutation.isPending}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
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
