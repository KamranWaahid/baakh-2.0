import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient, keepPreviousData } from '@tanstack/react-query';
import { Plus, Trash2, Search, RefreshCw, Languages, FileSearch } from 'lucide-react';
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
import RomanizerForm from './RomanizerForm';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Link } from 'react-router-dom';

const RomanizerList = () => {
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingEntry, setEditingEntry] = useState(null);
    const queryClient = useQueryClient();

    const { data, isLoading, isError } = useQuery({
        queryKey: ['romanizer', page, search],
        queryFn: async () => {
            const response = await api.get('/api/admin/romanizer', {
                params: { page, search, per_page: 20 }
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/romanizer/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['romanizer']);
        }
    });

    const refreshMutation = useMutation({
        mutationFn: () => api.post('/api/admin/romanizer/refresh'),
        onSuccess: (data) => {
            alert(data.data.message || 'Romanizer dictionary refreshed successfully!');
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
        <div className="p-4 md:p-8 space-y-6">
            <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div className="flex items-center gap-2">
                    <Languages className="h-6 w-6 md:h-8 md:w-8 text-primary" />
                    <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Romanizer</h2>
                </div>
                <div className="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <Button variant="outline" asChild className="w-full sm:w-auto">
                        <Link to="/romanizer/check">
                            <FileSearch className="mr-2 h-4 w-4" /> Bulk Check
                        </Link>
                    </Button>
                    <Button variant="outline" className="w-full sm:w-auto" onClick={() => refreshMutation.mutate()} disabled={refreshMutation.isPending}>
                        {refreshMutation.isPending ? <RefreshCw className="mr-2 h-4 w-4 animate-spin" /> : <RefreshCw className="mr-2 h-4 w-4" />}
                        Refresh Dictionary
                    </Button>
                    <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                        <DialogTrigger asChild>
                            <Button onClick={handleAdd} className="w-full sm:w-auto">
                                <Plus className="mr-2 h-4 w-4" /> Add Word
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-[425px]">
                            <DialogHeader>
                                <DialogTitle>{editingEntry ? 'Edit Romanized Word' : 'Add New Romanized Word'}</DialogTitle>
                            </DialogHeader>
                            <RomanizerForm
                                entry={editingEntry}
                                onSuccess={() => setIsDialogOpen(false)}
                            />
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Manage Sindhi to Roman Dictionary</CardTitle>
                    <div className="flex items-center py-4">
                        <div className="relative flex-1">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Search words (Sindhi or Roman)..."
                                className="pl-8 w-full max-w-sm"
                                value={search}
                                onChange={(e) => {
                                    setSearch(e.target.value);
                                    setPage(1);
                                }}
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="rounded-md border overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sindhi Word</TableHead>
                                    <TableHead>Roman Word</TableHead>
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
                                            Error loading romanizer dictionary.
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
                                            <TableCell className="font-medium" dir="rtl" lang="sd">{entry.word_sd}</TableCell>
                                            <TableCell>{entry.word_roman}</TableCell>
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

export default RomanizerList;
