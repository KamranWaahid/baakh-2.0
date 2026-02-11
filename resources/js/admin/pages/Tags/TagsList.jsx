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
import { Plus, Trash2, Edit, Tag } from 'lucide-react';
import { useDebounce } from '@/hooks/useDebounce';
import { Badge } from '@/components/ui/badge';
import { Link } from 'react-router-dom';

const TagsList = () => {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search, 500);
    const queryClient = useQueryClient();

    const { data, isLoading, isError } = useQuery({
        queryKey: ['tags', page, debouncedSearch],
        queryFn: async () => {
            const params = { page, search: debouncedSearch };
            const response = await api.get('/api/admin/tags', { params });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const tagsResponse = data?.tags || { data: [] };

    const typeLabels = React.useMemo(() => {
        if (!data?.available_types) return {};
        if (typeof data.available_types[0] === 'object') {
            return data.available_types.reduce((acc, curr) => {
                acc[curr.value] = curr.label;
                return acc;
            }, {});
        }
        return data.available_types.reduce((acc, type) => {
            acc[type] = type;
            return acc;
        }, {});
    }, [data?.available_types]);

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/tags/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['tags']);
            alert('Tag deleted successfully');
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete tag');
        }
    });

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this tag?')) {
            deleteMutation.mutate(id);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Tags</h2>
                <Button asChild className="w-full sm:w-auto">
                    <Link to="/admin/tags/create">
                        <Plus className="mr-2 h-4 w-4" /> Add Tag
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader className="space-y-1">
                    <CardTitle className="text-xl">Manage Tags</CardTitle>
                    <div className="flex items-center py-2">
                        <Input
                            placeholder="Search tags..."
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
                                    <TableHead className="w-[80px] hidden sm:table-cell">ID</TableHead>
                                    <TableHead className="min-w-[150px]">Tag (SD)</TableHead>
                                    <TableHead className="min-w-[150px]">Tag (EN)</TableHead>
                                    <TableHead className="hidden md:table-cell">Slug</TableHead>
                                    <TableHead className="hidden lg:table-cell">Type</TableHead>
                                    <TableHead className="hidden xl:table-cell">Topic Category</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="hidden sm:table-cell"><Skeleton className="h-4 w-12" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="hidden lg:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="hidden xl:table-cell"><Skeleton className="h-4 w-24" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-24 text-center text-red-500">
                                            Error loading tags.
                                        </TableCell>
                                    </TableRow>
                                ) : tagsResponse.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-24 text-center">
                                            No tags found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    tagsResponse.data.map((tag) => (
                                        <TableRow key={tag.id}>
                                            <TableCell className="font-medium hidden sm:table-cell">{tag.id}</TableCell>
                                            <TableCell className="whitespace-nowrap font-medium">
                                                <div className="flex items-center gap-2">
                                                    <Tag className="h-3 w-3 text-muted-foreground" />
                                                    <span lang="sd">{tag.details?.sd?.name || 'N/A'}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap italic text-muted-foreground">
                                                {tag.details?.en?.name || '-'}
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell whitespace-nowrap text-xs font-mono">{tag.slug || '-'}</TableCell>
                                            <TableCell className="hidden lg:table-cell whitespace-nowrap font-semibold">
                                                <Badge variant="outline">{typeLabels[tag.type] || tag.type || '-'}</Badge>
                                            </TableCell>
                                            <TableCell className="hidden xl:table-cell whitespace-nowrap text-muted-foreground italic">
                                                {tag.topic_category_name || '-'}
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap space-x-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                    <Link to={`/admin/tags/${tag.id}/edit`}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => handleDelete(tag.id)}
                                                    disabled={deleteMutation.isPending}
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
                        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 py-4">
                            <div className="text-sm text-muted-foreground text-center sm:text-left">
                                Showing {tagsResponse.from || 0} to {tagsResponse.to || 0} of {tagsResponse.total || 0} results
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPage(p => Math.max(1, p - 1))}
                                    disabled={!tagsResponse.prev_page_url}
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setPage(p => p + 1)}
                                    disabled={!tagsResponse.next_page_url}
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

export default TagsList;
