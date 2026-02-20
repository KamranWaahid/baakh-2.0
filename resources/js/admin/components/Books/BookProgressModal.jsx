import React, { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../api/axios';
import { Loader2, RefreshCw, CheckCircle2, Circle, BookOpen, Save } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

const BookProgressModal = ({ bookId, open, onOpenChange }) => {
    const queryClient = useQueryClient();
    const [selectedPages, setSelectedPages] = useState([]);
    const [editingPages, setEditingPages] = useState({}); // Localy track changes before save

    const { data: pageData, isLoading, refetch } = useQuery({
        queryKey: ['book-pages', bookId],
        queryFn: async () => {
            const response = await api.get(`/api/admin/poet-books/${bookId}/pages`);
            return response.data;
        },
        enabled: !!bookId && open,
    });

    const updatePageMutation = useMutation({
        mutationFn: async ({ pageId, data }) => {
            return await api.put(`/api/admin/poet-books/${bookId}/pages/${pageId}`, data);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['book-pages', bookId]);
            queryClient.invalidateQueries(['poet-books']);
        }
    });

    const batchUpdateMutation = useMutation({
        mutationFn: async (data) => {
            return await api.post(`/api/admin/poet-books/${bookId}/pages/batch-update`, data);
        },
        onSuccess: () => {
            toast.success('Batch update successful');
            setSelectedPages([]);
            queryClient.invalidateQueries(['book-pages', bookId]);
            queryClient.invalidateQueries(['poet-books']);
        }
    });

    const syncMutation = useMutation({
        mutationFn: async () => {
            return await api.post(`/api/admin/poet-books/${bookId}/pages/sync`);
        },
        onSuccess: () => {
            toast.success('Synced with poetry content');
            queryClient.invalidateQueries(['book-pages', bookId]);
            queryClient.invalidateQueries(['poet-books']);
        }
    });

    const handlePageUpdate = (page, field, value) => {
        updatePageMutation.mutate({
            pageId: page.id,
            data: {
                ...page,
                [field]: value
            }
        });
    };

    const togglePageSelection = (id) => {
        setSelectedPages(prev =>
            prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]
        );
    };

    const handleBatchStatus = (completed) => {
        if (selectedPages.length === 0) return;
        batchUpdateMutation.mutate({
            page_ids: selectedPages,
            is_completed: completed
        });
    };

    const handleBatchType = (type) => {
        if (selectedPages.length === 0) return;
        batchUpdateMutation.mutate({
            page_ids: selectedPages,
            type: type
        });
    };

    if (!bookId) return null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col p-0 overflow-hidden bg-white">
                <DialogHeader className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <div className="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                                <BookOpen className="h-6 w-6" />
                            </div>
                            <div>
                                <DialogTitle className="text-xl font-bold">
                                    {pageData?.book?.title || 'Book Details'}
                                </DialogTitle>
                                <DialogDescription>
                                    Manage individual page metadata and digitization status.
                                </DialogDescription>
                            </div>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => syncMutation.mutate()}
                                disabled={syncMutation.isPending}
                            >
                                {syncMutation.isPending ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <RefreshCw className="h-4 w-4 mr-2" />}
                                Sync Poetry
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                <div className="flex-1 overflow-auto p-0">
                    {isLoading ? (
                        <div className="flex flex-col items-center justify-center h-[400px] text-muted-foreground">
                            <Loader2 className="h-8 w-8 animate-spin mb-4" />
                            <p>Loading book mapping...</p>
                        </div>
                    ) : (
                        <div className="relative">
                            {/* Sticky Batch Actions Bar */}
                            {selectedPages.length > 0 && (
                                <div className="sticky top-0 z-10 bg-primary text-primary-foreground px-6 py-2 flex items-center justify-between shadow-md">
                                    <span className="text-sm font-medium">{selectedPages.length} pages selected</span>
                                    <div className="flex items-center gap-2">
                                        <Select onValueChange={handleBatchType}>
                                            <SelectTrigger className="h-8 w-32 bg-white text-black text-xs">
                                                <SelectValue placeholder="Set Type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="poetry">Poetry</SelectItem>
                                                <SelectItem value="information">Information</SelectItem>
                                                <SelectItem value="cover">Cover</SelectItem>
                                                <SelectItem value="preface">Preface</SelectItem>
                                                <SelectItem value="blank">Blank</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Button variant="secondary" size="sm" className="h-8 text-xs font-bold" onClick={() => handleBatchStatus(true)}>
                                            Mark Completed
                                        </Button>
                                        <Button variant="destructive" size="sm" className="h-8 text-xs font-bold" onClick={() => handleBatchStatus(false)}>
                                            Mark Incomplete
                                        </Button>
                                    </div>
                                </div>
                            )}

                            <Table>
                                <TableHeader className="sticky top-0 bg-white z-0 shadow-sm">
                                    <TableRow>
                                        <TableHead className="w-[50px]">
                                            <Checkbox
                                                checked={selectedPages.length === pageData?.pages?.length}
                                                onCheckedChange={(checked) => {
                                                    if (checked) setSelectedPages(pageData.pages.map(p => p.id));
                                                    else setSelectedPages([]);
                                                }}
                                            />
                                        </TableHead>
                                        <TableHead className="w-[80px]">Page #</TableHead>
                                        <TableHead>Title (Optional)</TableHead>
                                        <TableHead className="w-[150px]">Content Type</TableHead>
                                        <TableHead className="w-[100px] text-center">Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pageData?.pages?.map((page) => (
                                        <TableRow key={page.id} className={cn(page.is_completed && "bg-green-50/30")}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={selectedPages.includes(page.id)}
                                                    onCheckedChange={() => togglePageSelection(page.id)}
                                                />
                                            </TableCell>
                                            <TableCell className="font-bold">{page.page_number}</TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2 group">
                                                    <Input
                                                        defaultValue={page.title}
                                                        placeholder="e.g. Introduction"
                                                        className="h-8 border-transparent hover:border-gray-200 focus:border-primary transition-all bg-transparent focus:bg-white"
                                                        onBlur={(e) => {
                                                            if (e.target.value !== page.title) {
                                                                handlePageUpdate(page, 'title', e.target.value);
                                                            }
                                                        }}
                                                    />
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Select
                                                    value={page.type}
                                                    onValueChange={(val) => handlePageUpdate(page, 'type', val)}
                                                >
                                                    <SelectTrigger className="h-8 border-none bg-transparent hover:bg-gray-100 focus:ring-0">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="poetry">Poetry</SelectItem>
                                                        <SelectItem value="information">Information</SelectItem>
                                                        <SelectItem value="cover">Cover</SelectItem>
                                                        <SelectItem value="preface">Preface</SelectItem>
                                                        <SelectItem value="blank">Blank</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <button
                                                    onClick={() => handlePageUpdate(page, 'is_completed', !page.is_completed)}
                                                    className="transition-transform active:scale-90"
                                                >
                                                    {page.is_completed ? (
                                                        <CheckCircle2 className="h-5 w-5 text-green-600" />
                                                    ) : (
                                                        <Circle className="h-5 w-5 text-gray-300" />
                                                    )}
                                                </button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </div>

                <div className="p-4 border-t bg-gray-50 flex justify-between items-center">
                    <div className="text-xs text-muted-foreground flex items-center gap-4">
                        <div className="flex items-center gap-1">
                            <div className="h-2 w-2 rounded-full bg-green-600"></div>
                            <span>Completed Pages: {pageData?.pages?.filter(p => p.is_completed).length}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <div className="h-2 w-2 rounded-full bg-gray-300"></div>
                            <span>Total Pages: {pageData?.book?.total_pages}</span>
                        </div>
                    </div>
                    <Button variant="default" onClick={() => onOpenChange(false)}>
                        Close
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default BookProgressModal;
