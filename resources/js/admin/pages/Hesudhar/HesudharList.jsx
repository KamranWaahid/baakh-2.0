import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { Plus, Trash2, RefreshCw, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import api from '../../api/axios';
import { Link } from 'react-router-dom';
import HesudharForm from './HesudharForm';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { GlobalSearch } from '../../components/GlobalSearch';

const HesudharList = () => {
    const [page, setPage] = useState(1);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingEntry, setEditingEntry] = useState(null);
    const queryClient = useQueryClient();

    const { data, isLoading, isError } = useQuery({
        queryKey: ['hesudhar', page],
        queryFn: async () => {
            const response = await api.get('/api/admin/hesudhar', {
                params: { page, per_page: 20 }
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/hesudhar/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['hesudhar']);
        }
    });

    const refreshMutation = useMutation({
        mutationFn: () => api.post('/api/admin/hesudhar/refresh'),
        onSuccess: (data) => {
            alert(data.data.message || 'Dictionary refreshed successfully!');
        }
    });

    const handleDelete = (id) => {
        if (window.confirm('Are you sure you want to delete this entry?')) {
            deleteMutation.mutate(id);
        }
    };

    const handleEdit = (entry) => {
        setEditingEntry(entry);
        setIsDialogOpen(true);
    };

    const handleAdd = () => {
        setEditingEntry(null);
        setIsDialogOpen(true);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h2 className="text-3xl font-bold tracking-tight">Hesudhar</h2>
                <div className="flex gap-2">
                    <Button variant="outline" onClick={() => refreshMutation.mutate()} disabled={refreshMutation.isPending}>
                        {refreshMutation.isPending ? <RefreshCw className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
                        Refresh Dictionary
                    </Button>
                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={handleAdd}>
                                <Plus className="mr-2 h-4 w-4" /> Add Word
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{editingEntry ? 'Edit Word pair' : 'Add New Word Pair'}</DialogTitle>
                            </DialogHeader>
                            <HesudharForm
                                entry={editingEntry}
                                onSuccess={() => setIsDialogOpen(false)}
                            />
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Manage Spell Correction Dictionary</CardTitle>
                    <div className="flex items-center py-4">
                        <div className="relative flex-1">
                            <GlobalSearch
                                className="w-full max-w-sm ml-auto"
                                onSelect={handleEdit}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Incorrect Word</TableHead>
                                    <TableHead>Correct Word</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="h-24 text-center text-red-500">
                                            Error loading dictionary.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={3} className="h-24 text-center">
                                            No entries found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((entry) => (
                                        <TableRow key={entry.id}>
                                            <TableCell className="font-medium">
                                                <span lang="sd">{entry.word}</span>
                                            </TableCell>
                                            <TableCell>
                                                <span lang="sd">{entry.correct}</span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleEdit(entry)}
                                                    >
                                                        Edit
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        onClick={() => handleDelete(entry.id)}
                                                        disabled={deleteMutation.isPending}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
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

export default HesudharList;
