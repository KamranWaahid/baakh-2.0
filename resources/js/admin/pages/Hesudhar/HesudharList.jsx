import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { Plus, Trash2, RefreshCw, Loader2, FileSearch, Wand2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
} from '@/components/ui/dialog';
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
                params: { page, per_page: 20 },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/hesudhar/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['hesudhar']);
        },
    });

    const refreshMutation = useMutation({
        mutationFn: () => api.post('/api/admin/hesudhar/refresh'),
        onSuccess: (data) => {
            alert(data.data.message || 'Dictionary refreshed successfully!');
        },
    });

    const cleanseMutation = useMutation({
        mutationFn: () => api.post('/api/admin/hesudhar/cleanse'),
        onSuccess: (data) => {
            queryClient.invalidateQueries(['hesudhar']);
            alert(data.data.message || 'Phonetic cleanse complete!');
        },
        onError: (err) => {
            alert('Cleanse failed: ' + (err.response?.data?.message || err.message));
        },
    });

    const handleCleanse = () => {
        if (window.confirm(
            'Run Phonetic Cleanse on the entire WordNet database?\n\n' +
            'This will fix incorrect heh characters (ھ → ہ), Kaf, Yeh, and Alef+Madda encoding on all records.\n\n' +
            'This cannot be undone. Continue?'
        )) {
            cleanseMutation.mutate();
        }
    };

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
        <div className="min-w-0 w-full max-w-full space-y-4 p-0 sm:space-y-6">
            <div className="flex min-w-0 flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0 space-y-1">
                    <h2 className="text-2xl font-bold tracking-tight md:text-3xl">Hesudhar</h2>
                    <p className="text-sm text-muted-foreground md:text-base">
                        Manage spell correction dictionary
                    </p>
                </div>

                <div className="flex min-w-0 w-full flex-wrap gap-2 lg:w-auto lg:justify-end">
                    <Button variant="outline" asChild className="flex-1 min-w-[9.5rem] sm:flex-none">
                        <Link to="/admin/hesudhar/check">
                            <FileSearch className="mr-2 h-4 w-4 shrink-0" />
                            <span className="truncate">Bulk Check</span>
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        className="flex-1 min-w-[9.5rem] sm:flex-none"
                        onClick={() => refreshMutation.mutate()}
                        disabled={refreshMutation.isPending}
                    >
                        {refreshMutation.isPending
                            ? <RefreshCw className="mr-2 h-4 w-4 shrink-0 animate-spin" />
                            : <RefreshCw className="mr-2 h-4 w-4 shrink-0" />}
                        <span className="truncate">
                            <span className="sm:hidden">Refresh</span>
                            <span className="hidden sm:inline">Refresh Dictionary</span>
                        </span>
                    </Button>
                    <Button
                        variant="destructive"
                        className="flex-1 min-w-[9.5rem] sm:flex-none"
                        onClick={handleCleanse}
                        disabled={cleanseMutation.isPending}
                        title="Run phonetic cleansing on all WordNet records (fixes ھ→ہ, Kaf, Yeh, Alef+Madda)"
                    >
                        {cleanseMutation.isPending
                            ? <Loader2 className="mr-2 h-4 w-4 shrink-0 animate-spin" />
                            : <Wand2 className="mr-2 h-4 w-4 shrink-0" />}
                        <span className="truncate">
                            <span className="sm:hidden">Cleanse</span>
                            <span className="hidden sm:inline">Cleanse WordNet</span>
                        </span>
                    </Button>
                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={handleAdd} className="flex-1 min-w-[9.5rem] sm:flex-none">
                                <Plus className="mr-2 h-4 w-4 shrink-0" />
                                Add Word
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="w-[calc(100vw-2rem)] max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle>{editingEntry ? 'Edit Word Pair' : 'Add New Word Pair'}</DialogTitle>
                            </DialogHeader>
                            <HesudharForm
                                entry={editingEntry}
                                onSuccess={() => setIsDialogOpen(false)}
                            />
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Card className="min-w-0 overflow-hidden">
                <CardHeader className="space-y-3">
                    <CardTitle className="text-lg sm:text-xl">Manage Spell Correction Dictionary</CardTitle>
                    <div className="w-full min-w-0">
                        <GlobalSearch
                            className="w-full max-w-full sm:max-w-sm"
                            onSelect={handleEdit}
                        />
                    </div>
                </CardHeader>
                <CardContent className="min-w-0 space-y-4">
                    <div className="rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="min-w-[7rem]">Incorrect Word</TableHead>
                                    <TableHead className="min-w-[7rem]">Correct Word</TableHead>
                                    <TableHead className="w-[7.5rem] text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-24 sm:w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-24 sm:w-32" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="ml-auto h-8 w-16" /></TableCell>
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
                                                <span lang="sd" className="font-arabic break-words" dir="rtl">
                                                    {entry.word}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <span lang="sd" className="font-arabic break-words" dir="rtl">
                                                    {entry.correct}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1 sm:gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="px-2 sm:px-3"
                                                        onClick={() => handleEdit(entry)}
                                                    >
                                                        Edit
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="px-2 text-destructive hover:bg-destructive/10 hover:text-destructive sm:px-3"
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
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-muted-foreground">
                                Showing {data.from} to {data.to} of {data.total} results
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 sm:flex-none"
                                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                                    disabled={!data.prev_page_url}
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="flex-1 sm:flex-none"
                                    onClick={() => setPage((p) => p + 1)}
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
