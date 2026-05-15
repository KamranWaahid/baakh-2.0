import React, { useState, useEffect, useMemo } from 'react';
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
import { Loader2, RefreshCw, Check, Circle, BookOpen, Save } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

const pageSnapshot = (page) => ({
    id: page.id,
    page_number: page.page_number,
    title: page.title ?? '',
    type: page.type,
    is_completed: Boolean(page.is_completed),
});

const pagesEqual = (a, b) =>
    a.title === b.title && a.type === b.type && a.is_completed === b.is_completed;

const BookProgressModal = ({ bookId, open, onOpenChange }) => {
    const queryClient = useQueryClient();
    const [selectedPages, setSelectedPages] = useState([]);
    const [localPages, setLocalPages] = useState([]);
    const [savedPages, setSavedPages] = useState([]);

    const { data: pageData, isLoading } = useQuery({
        queryKey: ['book-pages', bookId],
        queryFn: async () => {
            const response = await api.get(`/api/admin/poet-books/${bookId}/pages`);
            return response.data;
        },
        enabled: !!bookId && open,
    });

    useEffect(() => {
        if (pageData?.pages) {
            const snapshot = pageData.pages.map(pageSnapshot);
            setLocalPages(snapshot);
            setSavedPages(snapshot);
            setSelectedPages([]);
        }
    }, [pageData]);

    const isDirty = useMemo(() => {
        if (localPages.length !== savedPages.length) return false;
        return localPages.some((page, index) => !pagesEqual(page, savedPages[index]));
    }, [localPages, savedPages]);

    const saveMutation = useMutation({
        mutationFn: async (pages) => {
            return await api.post(`/api/admin/poet-books/${bookId}/pages/bulk-save`, { pages });
        },
        onSuccess: (_, dirtyPages) => {
            toast.success(`Saved ${dirtyPages.length} page${dirtyPages.length === 1 ? '' : 's'}`);
            setSavedPages((prev) =>
                prev.map((page) => {
                    const updated = dirtyPages.find((p) => p.id === page.id);
                    return updated ? { ...updated } : page;
                })
            );
            queryClient.invalidateQueries(['book-pages', bookId]);
            queryClient.invalidateQueries(['poet-books']);
        },
        onError: (error) => {
            const message = error.response?.data?.message || 'Failed to save pages';
            toast.error(message);
        },
    });

    const syncMutation = useMutation({
        mutationFn: async () => {
            return await api.post(`/api/admin/poet-books/${bookId}/pages/sync`);
        },
        onSuccess: () => {
            toast.success('Synced with poetry content');
            queryClient.invalidateQueries(['book-pages', bookId]);
            queryClient.invalidateQueries(['poet-books']);
        },
    });

    const updateLocalPage = (pageId, field, value) => {
        setLocalPages((prev) =>
            prev.map((page) =>
                page.id === pageId ? { ...page, [field]: value } : page
            )
        );
    };

    const togglePageSelection = (id) => {
        setSelectedPages((prev) =>
            prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id]
        );
    };

    const handleBatchStatus = (completed) => {
        if (selectedPages.length === 0) return;
        setLocalPages((prev) =>
            prev.map((page) =>
                selectedPages.includes(page.id)
                    ? { ...page, is_completed: completed }
                    : page
            )
        );
    };

    const handleBatchType = (type) => {
        if (selectedPages.length === 0) return;
        setLocalPages((prev) =>
            prev.map((page) =>
                selectedPages.includes(page.id) ? { ...page, type } : page
            )
        );
    };

    const getDirtyPages = () =>
        localPages.filter((page, index) => !pagesEqual(page, savedPages[index]));

    const handleSave = () => {
        const dirtyPages = getDirtyPages();
        if (dirtyPages.length === 0) {
            toast.info('No changes to save');
            return;
        }
        saveMutation.mutate(dirtyPages);
    };

    const handleClose = () => {
        if (isDirty && !window.confirm('You have unsaved changes. Discard them?')) {
            return;
        }
        onOpenChange(false);
    };

    const handleOpenChange = (newOpen) => {
        if (!newOpen) {
            handleClose();
            return;
        }
        onOpenChange(newOpen);
    };

    if (!bookId) return null;

    const displayPages = localPages.length > 0 ? localPages : [];

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
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
                                onClick={() => {
                                    if (isDirty && !window.confirm('You have unsaved changes. Sync anyway?')) return;
                                    syncMutation.mutate();
                                }}
                                disabled={syncMutation.isPending || saveMutation.isPending}
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
                                                checked={displayPages.length > 0 && selectedPages.length === displayPages.length}
                                                onCheckedChange={(checked) => {
                                                    if (checked) setSelectedPages(displayPages.map((p) => p.id));
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
                                    {displayPages.map((page) => (
                                        <TableRow key={page.id} className={cn(page.is_completed && "bg-green-50/30")}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={selectedPages.includes(page.id)}
                                                    onCheckedChange={() => togglePageSelection(page.id)}
                                                />
                                            </TableCell>
                                            <TableCell className="font-bold">{page.page_number}</TableCell>
                                            <TableCell>
                                                <Input
                                                    value={page.title}
                                                    placeholder="e.g. Introduction"
                                                    dir="rtl"
                                                    className="h-8 border-transparent hover:border-gray-200 focus:border-primary transition-all bg-transparent focus:bg-white font-arabic text-sm"
                                                    onChange={(e) => updateLocalPage(page.id, 'title', e.target.value)}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Select
                                                    value={page.type}
                                                    onValueChange={(val) => updateLocalPage(page.id, 'type', val)}
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
                                                    type="button"
                                                    onClick={() => updateLocalPage(page.id, 'is_completed', !page.is_completed)}
                                                    className="transition-transform active:scale-90"
                                                >
                                                    {page.is_completed ? (
                                                        <div className="flex items-center justify-center">
                                                            <div className="h-6 w-6 rounded-full bg-green-100 flex items-center justify-center text-green-600 border border-green-200 shadow-sm">
                                                                <Check className="h-4 w-4 stroke-[3]" />
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <Circle className="h-5 w-5 text-gray-300 mx-auto" />
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
                            <span>Completed Pages: {displayPages.filter((p) => p.is_completed).length}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <div className="h-2 w-2 rounded-full bg-gray-300"></div>
                            <span>Total Pages: {pageData?.book?.total_pages}</span>
                        </div>
                        {isDirty && (
                            <span className="text-amber-600 font-medium">Unsaved changes</span>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={handleClose} disabled={saveMutation.isPending}>
                            Cancel
                        </Button>
                        <Button
                            variant="default"
                            onClick={handleSave}
                            disabled={!isDirty || saveMutation.isPending}
                        >
                            {saveMutation.isPending ? (
                                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                            ) : (
                                <Save className="h-4 w-4 mr-2" />
                            )}
                            Save
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
};

export default BookProgressModal;
