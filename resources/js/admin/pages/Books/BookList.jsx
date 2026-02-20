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
import { Plus, Trash2, MoreHorizontal, Edit, Book, Eye } from 'lucide-react';
import { Link } from 'react-router-dom';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Progress as ProgressBar } from "@/components/ui/progress";
import BookProgressModal from '../../components/Books/BookProgressModal';

const BookList = () => {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [selectedBookId, setSelectedBookId] = useState(null);
    const [isProgressModalOpen, setIsProgressModalOpen] = useState(false);

    const { data, isLoading, isError } = useQuery({
        queryKey: ['poet-books', page, search],
        queryFn: async () => {
            const response = await api.get('/api/admin/poet-books', {
                params: { page, search },
            });
            return response.data;
        },
        placeholderData: keepPreviousData,
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            return await api.delete(`/api/admin/poet-books/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['poet-books']);
        },
    });

    const handleDelete = async (id) => {
        if (window.confirm('Are you sure you want to delete this book?')) {
            await deleteMutation.mutateAsync(id);
        }
    };

    const calculateProgress = (lastPage, totalPages) => {
        if (!totalPages || totalPages <= 0) return 0;
        return Math.min(100, Math.round(((lastPage || 0) / totalPages) * 100));
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Poet Books</h2>
                <Button asChild className="w-full sm:w-auto">
                    <Link to="/admin/books/create">
                        <Plus className="mr-2 h-4 w-4" /> Add Book
                    </Link>
                </Button>
            </div>

            <Card>
                <CardHeader className="space-y-1">
                    <CardTitle className="text-xl">Manage Books & Digitization Progress</CardTitle>
                    <div className="flex items-center py-2">
                        <Input
                            placeholder="Search books..."
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
                                    <TableHead>Title</TableHead>
                                    <TableHead>Poet</TableHead>
                                    <TableHead>Pages</TableHead>
                                    <TableHead>Progress</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {isLoading ? (
                                    Array(5).fill(0).map((_, index) => (
                                        <TableRow key={index}>
                                            <TableCell><Skeleton className="h-4 w-48" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-16" /></TableCell>
                                            <TableCell><Skeleton className="h-4 w-32" /></TableCell>
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-red-500">
                                            Error loading books.
                                        </TableCell>
                                    </TableRow>
                                ) : data?.data?.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center">
                                            No books found.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data?.data?.map((book) => {
                                        const progressValue = calculateProgress(book.progress?.last_page, book.total_pages);
                                        return (
                                            <TableRow key={book.id}>
                                                <TableCell className="font-medium">
                                                    <div>{book.title}</div>
                                                    {book.title_sd && (
                                                        <div className="text-xs text-muted-foreground font-arabic mt-1" dir="rtl">
                                                            {book.title_sd}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    <span lang="sd">{book.poet?.details?.[0]?.poet_laqab || book.poet?.poet_slug}</span>
                                                </TableCell>
                                                <TableCell>{book.total_pages}</TableCell>
                                                <TableCell className="min-w-[150px]">
                                                    <div className="space-y-1">
                                                        <div className="flex justify-between text-xs text-muted-foreground">
                                                            <span>Pages: {book.progress?.last_page || 0} / {book.total_pages}</span>
                                                            <span>{progressValue}%</span>
                                                        </div>
                                                        <ProgressBar value={progressValue} className="h-2" />
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                            <DropdownMenuItem onClick={() => {
                                                                setSelectedBookId(book.id);
                                                                setIsProgressModalOpen(true);
                                                            }}>
                                                                <Eye className="mr-2 h-4 w-4" /> View Map
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem asChild>
                                                                <a href={`/sd/poet/${book.poet?.poet_slug}`} target="_blank" rel="noreferrer">
                                                                    <Book className="mr-2 h-4 w-4" /> Public View
                                                                </a>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem asChild>
                                                                <Link to={`/admin/books/${book.id}/edit`}>
                                                                    <Edit className="mr-2 h-4 w-4" /> Edit
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                className="text-destructive"
                                                                onClick={() => handleDelete(book.id)}
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" /> Delete
                                                            </DropdownMenuItem>
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

            <BookProgressModal
                bookId={selectedBookId}
                open={isProgressModalOpen}
                onOpenChange={setIsProgressModalOpen}
            />
        </div>
    );
};

export default BookList;
