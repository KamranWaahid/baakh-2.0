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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogFooter
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Plus, Trash2, Edit, Tag } from 'lucide-react';
import { useDebounce } from '@/hooks/useDebounce';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const TagsList = () => {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingTag, setEditingTag] = useState(null);
    const [formData, setFormData] = useState({
        slug: '',
        type: '',
        details: {
            sd: { name: '' },
            en: { name: '' }
        }
    });

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
        // If types are in value/label format
        if (typeof data.available_types[0] === 'object') {
            return data.available_types.reduce((acc, curr) => {
                acc[curr.value] = curr.label;
                return acc;
            }, {});
        }
        // Fallback for simple array
        return data.available_types.reduce((acc, type) => {
            acc[type] = type;
            return acc;
        }, {});
    }, [data?.available_types]);

    const createMutation = useMutation({
        mutationFn: (newTag) => api.post('/api/admin/tags', newTag),
        onSuccess: () => {
            queryClient.invalidateQueries(['tags']);
            setIsDialogOpen(false);
            resetForm();
        },
    });

    const updateMutation = useMutation({
        mutationFn: ({ id, data }) => api.put(`/api/admin/tags/${id}`, data),
        onSuccess: () => {
            queryClient.invalidateQueries(['tags']);
            setIsDialogOpen(false);
            resetForm();
        },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/api/admin/tags/${id}`),
        onSuccess: () => {
            queryClient.invalidateQueries(['tags']);
        },
    });

    const resetForm = () => {
        setFormData({
            slug: '',
            type: '',
            details: {
                sd: { name: '' },
                en: { name: '' }
            }
        });
        setEditingTag(null);
    };

    const handleEdit = (tag) => {
        setEditingTag(tag);
        setFormData({
            slug: tag.slug || '',
            type: tag.type || '',
            details: {
                sd: { name: tag.details?.sd?.name || '' },
                en: { name: tag.details?.en?.name || '' }
            }
        });
        setIsDialogOpen(true);
    };

    const handleDelete = (id) => {
        if (confirm('Are you sure you want to delete this tag?')) {
            deleteMutation.mutate(id);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (editingTag) {
            updateMutation.mutate({ id: editingTag.id, data: formData });
        } else {
            createMutation.mutate(formData);
        }
    };

    return (
        <div className="space-y-4 p-4 md:p-0">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 className="text-2xl md:text-3xl font-bold tracking-tight">Tags</h2>
                <Dialog open={isDialogOpen} onOpenChange={(open) => {
                    setIsDialogOpen(open);
                    if (!open) resetForm();
                }}>
                    <DialogTrigger asChild>
                        <Button className="w-full sm:w-auto">
                            <Plus className="mr-2 h-4 w-4" /> Add Tag
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>{editingTag ? 'Edit Tag' : 'Create New Tag'}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="tag-slug">Slug (Unique ID)</Label>
                                <Input
                                    id="tag-slug"
                                    value={formData.slug}
                                    onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                                    placeholder="e.g. love-poetry"
                                    required
                                    className="w-full"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tag-type">Type</Label>
                                <Select
                                    value={formData.type}
                                    onValueChange={(value) => setFormData({ ...formData, type: value })}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select tag type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {data?.available_types?.map(type => (
                                            <SelectItem key={type.value || type} value={type.value || type}>
                                                {type.label || type}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tag-name-sd">Name (Sindhi)</Label>
                                <Input
                                    id="tag-name-sd"
                                    dir="rtl"
                                    value={formData.details.sd.name}
                                    onChange={(e) => setFormData({
                                        ...formData,
                                        details: { ...formData.details, sd: { name: e.target.value } }
                                    })}
                                    placeholder="روزمرہ جي زندگي..."
                                    required
                                    className="w-full text-right font-arabic"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tag-name-en">Name (English)</Label>
                                <Input
                                    id="tag-name-en"
                                    value={formData.details.en.name}
                                    onChange={(e) => setFormData({
                                        ...formData,
                                        details: { ...formData.details, en: { name: e.target.value } }
                                    })}
                                    placeholder="Love Poetry"
                                    className="w-full"
                                />
                            </div>
                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)} className="w-full sm:w-auto">Cancel</Button>
                                <Button type="submit" className="w-full sm:w-auto" disabled={createMutation.isPending || updateMutation.isPending}>
                                    {createMutation.isPending || updateMutation.isPending ? 'Saving...' : 'Save'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
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
                                            <TableCell className="text-right"><Skeleton className="h-8 w-16 ml-auto" /></TableCell>
                                        </TableRow>
                                    ))
                                ) : isError ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center text-red-500">
                                            Error loading tags.
                                        </TableCell>
                                    </TableRow>
                                ) : tagsResponse.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-24 text-center">
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
                                            <TableCell className="text-right whitespace-nowrap space-x-1">
                                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(tag)}>
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                    onClick={() => handleDelete(tag.id)}
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
