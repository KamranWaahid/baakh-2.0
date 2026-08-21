import React, { useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
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
import { Badge } from '@/components/ui/badge';
import { Plus, Trash2, Edit, Scale, ExternalLink } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useDebounce } from '@/hooks/useDebounce';

const ProsodyTermList = () => {
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search, 400);
    const queryClient = useQueryClient();

    const { data: terms, isLoading, isError } = useQuery({
        queryKey: ['prosody-terms', debouncedSearch],
        queryFn: async () => {
            const response = await api.get('/api/admin/prosody-terms', {
                params: { search: debouncedSearch || undefined },
            });
            return response.data;
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/prosody-terms/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['prosody-terms'] });
        },
        onError: (error) => {
            alert(error.response?.data?.message || 'Failed to delete term');
        },
    });

    const handleDelete = (id) => {
        if (confirm('Delete this prosody card? It will disappear from /sd/prosody and /en/prosody.')) {
            deleteMutation.mutate(id);
        }
    };

    const rows = Array.isArray(terms) ? terms : [];

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Prosody cards</h2>
                    <p className="text-sm text-muted-foreground mt-1">
                        Titles, introductions, and technical notes for the public{' '}
                        <a href="/sd/prosody" className="underline underline-offset-2" target="_blank" rel="noreferrer">
                            /sd/prosody
                        </a>{' '}
                        grid.
                    </p>
                </div>
                <div className="flex flex-col sm:flex-row gap-2">
                    <Button variant="outline" asChild>
                        <a href="/sd/prosody" target="_blank" rel="noreferrer">
                            <ExternalLink className="mr-2 h-4 w-4" /> View page
                        </a>
                    </Button>
                    <Button asChild>
                        <Link to="/admin/prosody/create">
                            <Plus className="mr-2 h-4 w-4" /> Add card
                        </Link>
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader className="space-y-1">
                    <CardTitle className="text-xl flex items-center gap-2">
                        <Scale className="h-5 w-5" /> Manage terms
                    </CardTitle>
                    <Input
                        placeholder="Search Sindhi or English…"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-full sm:max-w-sm"
                    />
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[70px]">Order</TableHead>
                                    <TableHead>Title (SD)</TableHead>
                                    <TableHead>Title (EN)</TableHead>
                                    <TableHead className="hidden md:table-cell">Type</TableHead>
                                    <TableHead className="hidden lg:table-cell">Icon</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(6).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-8" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell className="hidden md:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="hidden lg:table-cell"><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-red-500">
                                            Error loading prosody terms.
                                        </TableCell>
                                    </TableRow>
                                ) : rows.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
                                            No terms yet. Add the first card.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    rows.map((term) => (
                                        <TableRow key={term.id}>
                                            <TableCell className="font-mono text-xs">{term.order}</TableCell>
                                            <TableCell className="font-arabic" lang="sd" dir="rtl">
                                                {term.title_sd}
                                            </TableCell>
                                            <TableCell>{term.title_en}</TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <Badge variant="outline">{term.logic_type || '—'}</Badge>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell font-mono text-xs">
                                                {term.icon || 'Info'}
                                            </TableCell>
                                            <TableCell className="text-right whitespace-nowrap space-x-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                    <Link to={`/admin/prosody/${term.id}/edit`}>
                                                        <Edit className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => handleDelete(term.id)}
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
                </CardContent>
            </Card>
        </div>
    );
};

export default ProsodyTermList;
